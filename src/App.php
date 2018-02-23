<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick;

/**
 * Framework main class
 */
class App implements \ArrayAccess
{
    /** Framework details */
    const
        PACKAGE = 'Stick-PHP',
        VERSION = '0.1.0';

    /** Request types */
    const
        REQ_SYNC = 1,
        REQ_AJAX = 2,
        REQ_CLI  = 4;

    /** @var array Initial value */
    protected $init;

    /** @var array Vars hive */
    protected $hive;

    /** @var array */
    protected $services = [];

    /** @var array Service aliases */
    protected $aliases = [];

    /**
     * Class constructor
     *
     * @param int|integer $debug
     */
    public function __construct(int $debug = 0)
    {
        $check    = error_reporting((E_ALL|E_STRICT)&~(E_NOTICE|E_USER_NOTICE));
        $helper   = new Helper();
        $request  = new Request($helper);
        $response = new Response($request, $helper);

        $this->hive = [
            'ALIAS'      => null,
            'ALIASES'    => [],
            'CACHE'      => '',
            'CASELESS'   => false,
            'DEBUG'      => $debug,
            'DNSBL'      => '',
            'ERROR'      => null,
            'EXEMPT'     => null,
            'EVENT'      => [],
            'HALT'       => true,
            'LOG_ERROR'  => true,
            'PACKAGE'    => static::PACKAGE,
            'PARAMS'     => null,
            'PATTERN'    => null,
            'PREMAP'     => '',
            'QUIET'      => false,
            'RAW'        => false,
            'ROUTES'     => [],
            'SEED'       => hash($request['HOST'] . $request['BASE']),
            'SERIALIZER' => '',
            'TEMP'       => './var/',
            'TZ'         => date_default_timezone_get(),
            'VERSION'    => static::VERSION,
            'CORS'       => [
                'headers'     => '',
                'origin'      => false,
                'credentials' => false,
                'expose'      => false,
                'ttl'         => 0,
            ],
            'SERVICE' => [
                'cache' => [
                    'class'  => Cache\Cache::class,
                    'params' => [
                        'dsn'    => '%CACHE%',
                        'prefix' => '%SEED%',
                        'dir'    => '%TEMP%cache/',
                        'helper' => '%helper%',
                    ],
                ],
            ],
        ];

        // Save a copy of hive
        $this->init = $this->hive;

        // Register core service
        $this->services['request'] = $request;
        $this->aliases[Request::class] = 'request';

        $this->services['helper'] = $helper;
        $this->aliases[Helper::class] = 'helper';

        $this->services['response'] = $response;
        $this->aliases[Response::class] = 'response';

        $this->services['app'] = $this;
        $this->aliases[static::class] = 'app';


        // @codeCoverageIgnoreStart
        if ('cli-server' === PHP_SAPI && preg_match('/^' . preg_quote($request['BASE'], '/') . '$/', $request['URI'])) {
            $this->reroute('/');
        }

        // Register shutdown handler
        register_shutdown_function([$this, 'unload'], getcwd());

        if ($check && $error = error_get_last()) {
            // Error detected
            $this->error(500, "Fatal error: {$error[message]}", [$error]);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Load ini file and merge to hive
     *
     * @param  string $file
     *
     * @return App
     */
    public function loadIni(string $file): App
    {
        $content = read($file);

        if ($content) {
            $this->sets(parse_ini_string($content, true));
        }

        return $this;
    }

    /**
     * Match routes against incoming URI
     *
     * @return App
     */
    public function run(): App
    {
        $request = $this->service('request');

        // @codeCoverageIgnoreStart
        if ($this->blacklisted($request['IP'])) {
            // Spammer detected
            $this->error(403);

            return $this;
        }
        // @codeCoverageIgnoreEnd

        if (!$this->hive['ROUTES']) {
            // No routes defined
            throw new \LogicException('No route specified');
        }

        // Sort based on length, pure text will be checked first
        ksort($this->hive['ROUTES']);

        // Convert to BASE-relative URL
        $response  = $this->service('response')->clearOutput();
        $path      = $request['PATH'];
        $method    = $request['METHOD'];
        $headers   = $request['HEADERS'];
        $modifier  = $this->hive['CASELESS'] ? 'i' : '';
        $type      = $request['CLI'] ? self::REQ_CLI : ((int) $request['AJAX']) + 1;
        $preflight = false;
        $cors      = null;
        $allowed   = [];

        if (isset($headers['Origin']) && $this->hive['CORS']['origin']) {
            $cors = $this->hive['CORS'];
            $preflight = isset($headers['Access-Control-Request-Method']);

            $response
                ->setHeader('Access-Control-Allow-Origin', $cors['origin'])
                ->setHeader('Access-Control-Allow-Credentials', var_export($cors['credentials'], true))
            ;
        }

        foreach ($this->hive['ROUTES'] as $pattern => $routes) {
            if (!$this->routeMatch($pattern, $path, $modifier, $args)) {
                continue;
            } elseif (isset($routes[$type][$method])) {
                $route = $routes[$type];
            } elseif (isset($routes[0])) {
                $route = $routes[0];
            } else {
                continue;
            }

            if (isset($route[$method]) && !$preflight) {
                list($handler, $ttl, $kbps, $alias) = $route[$method];

                // Capture values of route pattern tokens
                $this->hive['PARAMS']  = $args;
                // Save matching route
                $this->hive['ALIAS']   = $alias;
                $this->hive['PATTERN'] = $pattern;

                if ($cors && $cors['expose']) {
                    $response->setHeader('Access-Control-Expose-Headers', reqstr($cors['expose']));
                }

                if (is_string($handler)) {
                    // Replace route pattern tokens in handler if any
                    $handler = preg_replace_callback('/\{(\w+)\}/', function($m) use ($args) {
                        return isset($args[$m[1]]) ? $args[$m[1]] : $m[0];
                    }, $handler);

                    if (preg_match('/(.+)\h*(?:->|::)/', $handler, $match) && !class_exists($match[1])) {
                        $this->error(404);

                        return $this;
                    }
                }

                // Process request
                $now = microtime(true);
                if (in_array($method, ['GET','HEAD']) && $ttl) {
                    // Only GET and HEAD requests are cacheable
                    $cache  = $this->service('cache');
                    $hash   = hash($method . ' ' . $request['URI']) . '.url';
                    $cached = $cache->get($hash);
                    if ($cached) {
                        if (isset($headers['If-Modified-Since']) && strtotime($headers['If-Modified-Since'])+$ttl > $now) {
                            $response
                                ->status(304)
                                ->sendHeader()
                            ;
                            $this->halt();

                            return $this;
                        }

                        // Retrieve from cache backend
                        list($headers, $body) = $cached[0];

                        $response
                            ->setHeaders($headers)
                            ->setBody($body)
                        ;

                        $this->expire((int) ($cached[1] + $ttl - $now));
                    } else {
                        // Expire HTTP client-cached page
                        $this->expire($ttl);
                    }
                } else {
                    $this->expire(0);
                }

                if (!$response->getBody()) {
                    if (!$this->hive['RAW'] && !$request['BODY']) {
                        $request['BODY'] = file_get_contents('php://input');
                    }

                    // Call route handler
                    try {
                        $result = $this->call($handler, array_slice($args, 1));
                    } catch (\BadMethodCallException $e) {
                        $this->error(404);

                        return $this;
                    } catch (\BadFunctionCallException $e) {
                        $this->error(404);

                        return $this;
                    } catch (\Throwable $e) {
                        $this->error(500, $e->getMessage(), $e->getTrace());

                        return $this;
                    }

                    if (is_string($result)) {
                        $response->setBody($result);
                    } elseif (is_array($result)) {
                        $response->json($result);
                    } elseif (is_callable($result)) {
                        $response->setOutput($result);
                    }

                    if (isset($cache) && $response->hasBody() && !error_get_last()) {
                        // Save to cache backend
                        $cache->set($hash, [
                            $response->getHeadersWithoutCookie(),
                            $response->getBody()
                        ], $ttl);
                    }
                }

                $response->sendHeader();

                if (!$this->hive['QUIET']) {
                    $response->sendContent($kbps);
                }

                if ('OPTIONS' !== $method) {
                    return $this;
                }
            }

            $allowed = array_merge($allowed, array_keys($route));
        }

        if (!$allowed) {
            // URL doesn't match any route
            $this->error(404);
        } elseif (!$request['CLI']) {
            // Unhandled HTTP method

            $allowed = reqstr(array_unique($allowed));

            $response->setHeader('Allow', $allowed);

            if ($cors) {
                $response->setHeader('Access-Control-Allow-Methods', 'OPTIONS,' . $allowed);
                if ($cors['headers']) {
                    $response->setHeader('Access-Control-Allow-Headers', reqstr($cors['headers']));
                }
                if ($cors['ttl'] > 0) {
                    $response->setHeader('Access-Control-Max-Age', (string) $cors['ttl']);
                }
            }

            if ('OPTIONS' !== $method) {
                $this->error(405);
            }
        }

        return $this;
    }

    /**
     * Mock HTTP Request
     *
     * @param  string      $pattern
     * @param  array|null  $args
     * @param  array|null  $headers
     * @param  string|null $body
     *
     * @return App
     */
    public function mock(string $pattern, array $args = null, array $headers = null, string $body = null): App
    {
        preg_match('/^([\w]+)(?:\h+([^\h]+))(?:\h+(sync|ajax|cli))?$/', $pattern, $match);

        if (empty($match[2])) {
            throw new \LogicException("Invalid mock pattern: {$pattern}");
        }

        $request = $this->service('request');
        $args    = (array) $args;
        $headers = (array) $headers;
        $method  = $match[1];
        $path    = $this->build($match[2]);
        $url     = parse_url($path) + ['query'=>'', 'fragment'=>''];

        parse_str($url['query'], $GLOBALS['_GET']);

        $request['METHOD']   = $method;
        $request['PATH']     = $url['path'];
        $request['URI']      = $request['BASE'] . $url['path'];
        $request['BODY']     = '';
        $request['FRAGMENT'] = $url['fragment'];
        $request['AJAX']     = isset($match[3]) && 'ajax' === $match[3];
        $request['CLI']      = isset($match[3]) && 'cli' === $match[3];

        if (in_array($method, ['GET', 'HEAD'])) {
            $GLOBALS['_GET'] = array_merge($GLOBALS['_GET'], $args);
        } else {
            $request['BODY'] = $body ?: http_build_query($args);
        }

        $GLOBALS['_POST']    = 'POST' === $method ? $args : [];
        $GLOBALS['_REQUEST'] = array_merge($GLOBALS['_GET'], $GLOBALS['_POST']);

        if ($GLOBALS['_GET']) {
            $request['QUERY'] = http_build_query($GLOBALS['_GET']);
            $request['URI'] .= '?' . $request['QUERY'];
        }

        foreach ($headers as $key => $val) {
            $_SERVER['HTTP_' . strtr(strtoupper($key), '-', '_')] = $val;
        }
        $request['HEADERS'] = $headers;

        return $this->run();
    }

    /**
     * Reroute to specified URI, or dispatch REROUTE event if listener exists
     *
     * @param  string|array|null  $url
     * @param  boolean $permanent
     * @param  boolean $die
     *
     * @return void
     */
    public function reroute($url = null, bool $permanent = false, bool $die = true): void
    {
        $request = $this->service('request');

        if (!$url) {
            $url = $request['REALM'];
        } elseif (is_array($url)) {
            $url = call_user_func_array([$this, 'alias'], $url);
        } else {
            $url = $this->build($url);
        }

        if (isset($this->hive['EVENT']['REROUTE'])) {
            $this->dispatch('REROUTE', [$url, $permanent]);

            return;
        }

        if ('/' === $url[0] && (empty($url[1]) || '/' !== $url[1])) {
            $port = $request['PORT'];
            $url  = $request['SCHEME'] . '://' . $request['HOST'] . (in_array($port, [80, 443]) ? '' : (':' . $port)) . $request['BASE'] . $url;
        }

        if ($request['CLI']) {
            $this->mock('GET ' . $url . ' cli');
        } else {
            $this
                ->service('response')
                ->status($permanent ? 301 : 302)
                ->setHeader('Location', $url)
                ->sendHeader()
            ;
            $this->halt($die);
        }
    }

    /**
     * Build URL from expression
     * Example:
     *     route(id=1,name=other)
     *
     * @param  string $expr
     *
     * @return string
     *
     * @throws LogicException
     */
    public function build(string $expr): string
    {
        if (!preg_match('/^(\w+)(#\w+)?(?:\(([^\)]+)\))?$/', $expr, $match)) {
            // no route alias declaration

            return $expr;
        }

        $args = [];
        $match += [2=>'',''];
        foreach (split($match[3]) as $pair) {
            $apair = explode('=', $pair);
            $args[trim($apair[0])] = trim($apair[1] ?? '');
        }

        return $this->alias($match[1], $args) . $match[2];
    }

    /**
     * Build url from named route
     *
     * @param  string $route
     * @param  array|string $args
     *
     * @return string
     *
     * @throws LogicException
     */
    public function alias(string $route, $args = null): string
    {
        if (empty($this->hive['ALIASES'][$route])) {
            throw new \LogicException("Route does not exists: {$route}");
        }

        $args = (array) $args;

        return preg_replace_callback('/\{(\w+)(?:\:\w+)?\}/', function($m) use ($args) {
            return $args[$m[1]] ?? $m[0];
        }, $this->hive['ALIASES'][$route]);
    }

    /**
     * Execute framework/application shutdown sequence
     *
     * @param  string $cwd
     *
     * @return void
     *
     * @codeCoverageIgnore
     */
    public function unload(string $cwd): void
    {
        chdir($cwd);
        $error = error_get_last();
        if (!$error && PHP_SESSION_ACTIVE === session_status()) {
            session_commit();
        }

        if (isset($this->hive['EVENT']['UNLOAD'])) {
            $this->dispatch('UNLOAD', null, true);
        } elseif ($error && in_array($error['type'], [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR])) {
            // Fatal error detected
            $this->error(500, "Fatal error: {$error[message]}", [$error]);
        }
    }

    /**
     * Log error, dispatch ONERROR event if listener exists else
     * display default error page (HTML for synchronous requests, JSON string
     * for AJAX requests)
     *
     * @param  int        $code
     * @param  string     $text
     * @param  array|null $trace
     * @param  integer    $level
     *
     * @return void
     */
    public function error(int $code, string $text = '', array $trace = null, int $level = 0): void
    {
        if ($this->hive['ERROR']) {
            // Prevent recursive call
            return;
        }

        $request  = $this->service('request');
        $response = $this->service('response');
        $header   = $response->status($code)->getStatusText();
        $req      = $request['METHOD'].' '.$request['PATH'];
        $trace    = stringify($trace ?? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));

        if ($request['QUERY']) {
            $req .= '?' . $request['QUERY'];
        }

        if (!$text) {
            $text = 'HTTP ' . $code . ' (' . $req . ')';
        }

        // @codeCoverageIgnoreStart
        if ($this->hive['LOG_ERROR']) {
            error_log($text);
            error_log($trace);
        }
        // @codeCoverageIgnoreEnd

        $this->hive['ERROR'] = [
            'status' => $header,
            'code'   => $code,
            'text'   => $text,
            'trace'  => $trace,
            'level'  => $level
        ];

        $this->expire(-1);

        if (isset($this->hive['EVENT']['ONERROR'])) {
            $this->dispatch('ONERROR', null, true);

            return;
        }

        // @codeCoverageIgnoreStart
        if (!$this->hive['QUIET']) {
            if ($request['AJAX']) {
                $response->json(array_diff_key($this->hive['ERROR'], $this->hive['DEBUG']? [] : ['trace' => 1]));
            } else {
                $trace = $this->hive['DEBUG'] ? '<pre>' . $trace . '</pre>' : '';
                $response->html(<<<ERR
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>{$code} {$header}</title>
</head>
<body>
  <h1>{$header}</h1>
  <p>$text</p>
  $trace
</body>
</html>
ERR
);
            }

            $response->send()->clearOutput();
        }
        // @codeCoverageIgnoreEnd

        $this->halt();
    }

    /**
     * Do halt conditionally
     *
     * @param bool $force
     *
     * @return void
     *
     * @codeCoverageIgnore
     */
    public function halt(bool $force = false): void
    {
        if ($force || $this->hive['HALT']) {
            die(1);
        }
    }

    /**
     * Send cache metadata to HTTP client
     *
     * @param  integer $secs
     *
     * @return App
     */
    public function expire(int $secs = 0): App
    {
        $request = $this->service('request');
        $response = $this->service('response');

        $response
            ->setHeader('X-Powered-By', $this->hive['PACKAGE'])
            ->setHeader('X-Frame-Options', $request['XFRAME'])
            ->setHeader('X-XSS-Protection', '1; mode=block')
            ->setHeader('X-Content-Type-Options', 'nosniff')
        ;

        if ('GET' === $request['METHOD'] && $secs) {
            $time = microtime(true);
            $response
                ->removeHeader('Pragma')
                ->setHeader('Cache-Control', 'max-age=' . $secs)
                ->setHeader('Expires', gmdate('r', (int) ($time + $secs)))
                ->setHeader('Last-Modified', gmdate('r'))
            ;
        } else {
            $response
                ->setHeader('Pragma', 'no-cache')
                ->setHeader('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->setHeader('Expires', gmdate('r', 0))
            ;
        }

        return $this;
    }

    /**
     * Return true if IPv4 address exists in DNSBL
     *
     * @param  string $ip
     *
     * @return bool
     *
     * @codeCoverageIgnore
     */
    public function blacklisted(string $ip): bool
    {
        if ($this->hive['DNSBL'] && !in_array($ip, reqarr($this->hive['EXEMPT']))) {
            // Reverse IPv4 dotted quad
            $rev = implode('.', array_reverse(explode('.', $ip)));
            foreach (reqarr($this->hive['DNSBL']) as $server) {
                // DNSBL lookup
                if (checkdnsrr($rev . '.' . $server, 'A')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Route resource complement
     *
     * @param  array      $pattern
     * @param  string|object      $class
     * @param  int|integer $ttl
     * @param  int|integer $kbps
     * @param  array|null $map
     *
     * @return App
     */
    public function resources(array $patterns, $class, int $ttl = 0, int $kbps = 0, array $map = null): App
    {
        foreach ($patterns as $pattern) {
            $this->resource($pattern, $class, $ttl, $kbps, $map);
        }

        return $this;
    }

    /**
     * Route resource helper (mimic laravel resource-controllers)
     * Example:
     *     route_name # path will be /route-name
     *     route_name /prefix # path will be /prefix/route-name
     *     route_name /prefix ajax # available only for ajax request
     *
     * @param  string      $pattern
     * @param  string|object      $class
     * @param  int|integer $ttl
     * @param  int|integer $kbps
     * @param  array|null $map
     *
     * @return App
     */
    public function resource(string $pattern, $class, int $ttl = 0, int $kbps = 0, array $map = null): App
    {
        $parts = array_filter(explode(' ', $pattern));
        if (empty($parts)) {
            throw new \LogicException("Invalid resource pattern: {$pattern}");
        }

        static $resources = [
            'index'   => ['GET',    '%prefix%/%route%'],
            'create'  => ['GET',    '%prefix%/%route%/create'],
            'store'   => ['POST',   '%prefix%/%route%'],
            'show'    => ['GET',    '%prefix%/%route%/{%id%}'],
            'edit'    => ['GET',    '%prefix%/%route%/{%id%}/edit'],
            'update'  => ['PUT',    '%prefix%/%route%/{%id%}'],
            'destroy' => ['DELETE', '%prefix%/%route%/{%id%}'],
        ];

        list($route, $prefix) = $parts + [1=>''];
        $type = constant(static::class . '::REQ_' . strtoupper($parts[2] ?? ''), 0);
        $str = is_string($class);

        foreach ($map ?? array_keys($resources) as $res => $action) {
            if (is_numeric($res)) {
                $res = $action;
            }
            if (isset($resources[$res])) {
                list($verb, $format) = $resources[$res];
                $path = str_replace([
                    '%prefix%',
                    '%route%',
                    '%id%',
                ], [
                    $prefix,
                    str_replace('_', '-', $route),
                    $route,
                ], $format);

                $this->hive['ROUTES'][$path][$type][$verb] = [
                    $str ? "$class->$action" : [$class, $action],
                    $ttl,
                    $kbps,
                    "{$route}_{$res}"
                ];
            }
        }

        return $this;
    }

    /**
     * Map multiple route to class method
     *
     * @param  array      $pattern Route pattern without method
     * @param  string|object      $class
     * @param  int|integer $ttl
     * @param  int|integer $kbps
     * @param  string|null $map Defaults to GET|HEAD|POST|PUT|PATCH|DELETE|CONNECT|OPTIONS
     *
     * @return App
     */
    public function maps(array $patterns, $class, int $ttl = 0, int $kbps = 0, string $map = null): App
    {
        foreach ($patterns as $pattern) {
            $this->map($pattern, $class, $ttl, $kbps, $map);
        }

        return $this;
    }

    /**
     * Map route to class method
     *
     * @param  string      $pattern Route pattern without method
     * @param  string|object      $class
     * @param  int|integer $ttl
     * @param  int|integer $kbps
     * @param  string|null $map Defaults to GET|HEAD|POST|PUT|PATCH|DELETE|CONNECT|OPTIONS
     *
     * @return App
     */
    public function map($pattern, $class, int $ttl = 0, int $kbps = 0, string $map = null): App
    {
        $str = is_string($class);
        $prefix = $this->hive['PREMAP'];

        foreach (split($map ?? 'get|head|post|put|patch|delete|connect|options') as $verb) {
            $this->route(
                $verb . ' '. $pattern,
                $str ? "$class->$prefix$verb" : [$class, "$prefix$verb"],
                $ttl,
                $kbps
            );
        }

        return $this;
    }

    /**
     * Multiple route redirection
     *
     * @param  array  $patterns
     * @param  string $url
     * @param  bool   $permanent
     *
     * @return App
     */
    public function redirects(array $patterns, string $url, bool $permanent = true): App
    {
        foreach ($patterns as $pattern) {
            $this->redirect($pattern, $url, $permanent);
        }

        return $this;
    }

    /**
     * Route redirection
     *
     * @param  string $pattern @see App::route
     * @param  string $url
     * @param  bool   $permanent
     *
     * @return App
     */
    public function redirect(string $pattern, string $url, bool $permanent = true): App
    {
        return $this->route($pattern, function() use ($url, $permanent) {
            $this->reroute($url, $permanent);
        });
    }

    /**
     * Add multiple route to single handler
     *
     * @param  array       $patterns
     * @param  string|callable      $callback
     * @param  int|integer $ttl
     * @param  int|integer $kbps
     *
     * @return App
     */
    public function routes(array $patterns, $callback, int $ttl = 0, int $kbps = 0): App
    {
        foreach ($patterns as $pattern) {
            $this->route($pattern, $callback, $ttl, $kbps);
        }

        return $this;
    }

    /**
     * Add route, example pattern:
     *     GET /
     *     GET home /home # with route name (after method declaration)
     *     GET|POST|CUSTOM user /user ajax # with ajax mode
     *     GET /profile sync # with sync mode
     *     GET /command cli # with cli mode
     *     GET /product/{keyword} # with placeholder, defaults placeholder is alnum
     *     GET /product/{id:digit} # with placeholder and character class
     *     GET /category/{category:word} # with placeholder and character class
     *     GET /post/{post:custom} # with custom character class (will be fallback to alnum)
     *     GET /regex/(?<regex>[[:alpha:]]) # with regex
     *
     * Callback support Class(::|->)method format,
     * Dinamic class/method can be passed like below:
     *     {class}->{method}
     *
     * @param  string      $pattern
     * @param  string|callable      $callback
     * @param  int|integer $ttl
     * @param  int|integer $kbps
     *
     * @return App
     */
    public function route(string $pattern, $callback, int $ttl = 0, int $kbps = 0): App
    {
        preg_match('/^([\|\w]+)(?:\h+(\w+))?(?:\h+([^\h]+))(?:\h+(sync|ajax|cli))?$/', $pattern, $match);

        if (empty($match[3])) {
            throw new \LogicException("Invalid route pattern: {$pattern}");
        }

        $alias = $match[2] ?? null;
        if ($alias) {
            $this->hive['ALIASES'][$alias] = $match[3];
        }

        $type  = constant(static::class . '::REQ_' . strtoupper($match[4] ?? ''), 0);
        $verbs = split(strtoupper($match[1]));

        foreach ($verbs as $verb) {
            $this->hive['ROUTES'][$match[3]][$type][$verb] = [$callback, $ttl, $kbps, $alias];
        }

        return $this;
    }

    /**
     * Dispatch event
     *
     * @param  string $event
     * @param  array  $args
     * @param  bool  $once Remove handler before execute
     *
     * @return App
     */
    public function dispatch(string $event, array $args = null, bool $once = false): App
    {
        $handlers = (array) $this->get("EVENT.$event");

        if ($once) {
            $this->clear("EVENT.$event");
        }

        foreach ($handlers as $handler) {
            $this->call($handler, $args);
        }

        return $this;
    }

    /**
     * Call callable, support (-> and ::) format
     * Example: Class->method , Class::method
     *
     * @param  callable|string $callback
     * @param  array|string $args
     *
     * @return mixed
     *
     * @throws BadMethodCallException
     * @throws BadFunctionCallException
     * @throws LogicException
     */
    public function call($callback, $args = null)
    {
        if (is_string($callback)) {
            $callback = $this->grab($callback);
        }

        if (!is_callable($callback)) {
            if (is_array($callback)) {
                $info = stringify($callback);
                throw new \BadMethodCallException("Invalid method call: {$info}");
            } elseif (is_string($callback)) {
                throw new \BadFunctionCallException("Invalid function call: {$callback}");
            } else {
                throw new \LogicException("Invalid callback: (Unknown)");
            }
        }

        if (is_array($callback)) {
            $ref = new \ReflectionMethod($callback[0], $callback[1]);
        } else {
            $ref = new \ReflectionFunction($callback);
        }

        $mArgs = $this->methodArgs($ref, [], (array) $args);

        return call_user_func_array($callback, $mArgs);
    }

    /**
     * Get service by id or class name
     *
     * @param  string $id
     * @param  array  $args
     *
     * @return mixed
     */
    public function service(string $id, array $args = [])
    {
        if (isset($this->services[$id])) {
            return $this->services[$id];
        } elseif (isset($this->aliases[$id])) {
            // real id
            $id = $this->aliases[$id];

            if (isset($this->services[$id])) {
                return $this->services[$id];
            }
        }

        $rule = $this->get("SERVICE.$id", []);
        $class = $rule['class'] ?? $id;

        if (method_exists($class, '__construct')) {
            $cArgs = $this->methodArgs(
                new \ReflectionMethod($class, '__construct'),
                $rule['params'] ?? [],
                $args
            );

            $service = new $class(...$cArgs);
        } else {
            $service = new $class;
        }

        if ($rule['keep'] ?? false) {
            $this->services[$id] = $service;
        }

        return $service;
    }

    /**
     * Expose hive
     *
     * @return array
     */
    public function hive(): array
    {
        return $this->hive;
    }

    /**
     * Get hive ref
     *
     * @param  string       $key
     * @param  bool|boolean $add
     *
     * @return mixed
     */
    public function &ref(string $key, bool $add = true)
    {
        $null   = null;
        $parts  = explode('.', $key);

        if ($add) {
            $var =& $this->hive;
        } else {
            $var = $this->hive;
        }

        foreach ($parts as $part) {
            if (is_object($var)) {
                if ($add || property_exists($var, $part)) {
                    $var =& $var->$part;
                } else {
                    $var =& $null;
                    break;
                }
            } else {
                if (!is_array($var)) {
                    $var = [];
                }
                if ($add || array_key_exists($part, $var)) {
                    $var =& $var[$part];
                } else {
                    $var =& $null;
                    break;
                }
            }
        }

        return $var;
    }

    /**
     * Get from hive
     *
     * @param  string $key
     * @param  mixed $default
     *
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $var = $this->ref($key, false);

        return $var ?? $default;
    }

    /**
     * Set to hive
     *
     * @param string $key
     * @param mixed $val
     *
     * @return App
     */
    public function set(string $key, $val): App
    {
        if (preg_match('/^SERVICE\.(.+)$/', $key, $match)) {
            if (is_string($val)) {
                // assume val is a class name
                $val = ['class' => $val];
            }

            // defaults it's a service
            $val += ['keep' => true];

            if (isset($val['class']) && $val['class'] !== $match[1]) {
                $this->aliases[$val['class']] = $match[1];
            }

            // remove existing service
            $this->services[$match[1]] = null;
        }

        $var =& $this->ref($key);
        $var = $val;

        switch ($key) {
            case 'CACHE':
                $this->service('cache')->setDsn($val);
                break;
            case 'SEED':
                $this->service('cache')->setPrefix($val);
                break;
            case 'SERIALIZER':
                $this->service('helper')->setOption('serializer', $val);
                break;
            case 'TZ':
                date_default_timezone_set($val);
                break;
        }

        return $this;
    }

    /**
     * Check in hive
     *
     * @param  string $key
     *
     * @return bool
     */
    public function exists(string $key): bool
    {
        $var = $this->ref($key, false);

        return isset($var);
    }

    /**
     * Remove from hive
     *
     * @param  string $key
     *
     * @return App
     */
    public function clear(string $key): App
    {
        if ('CACHE' === $key) {
            // Clear cache contents
            $this->service('cache')->reset();
        } elseif (preg_match('/^SERVICE\.(.+)$/', $key, $match) && !in_array($match[1], ['app','request','response','helper'])) {
            // Remove instance too
            $this->services[$match[1]] = null;
        }

        $parts = explode('.', $key);

        if (empty($parts[1]) && array_key_exists($parts[0], $this->init)) {
            $this->hive[$parts[0]] = $this->init[$parts[0]];
        } else {
            $var  =& $this->hive;
            $last = count($parts) - 1;
            foreach ($parts as $key => $part) {
                if (is_object($var)) {
                    if ($last == $key) {
                        unset($var->$part);
                    } else {
                        $var =& $var->$part;
                    }
                } else {
                    if (!is_array($var)) {
                        break;
                    }
                    if ($last == $key) {
                        unset($var[$part]);
                    } else {
                        $var =& $var[$part];
                    }
                }
            }
            unset($var);
        }

        return $this;
    }

    /**
     * Multi-variable assignment using associative array
     *
     * @param array $vars
     * @param string $prefix
     *
     * @return App
     */
    public function sets(array $vars, string $prefix = ''): App
    {
        foreach ($vars as $var => $val) {
            $this->set($prefix . $var, $val);
        }

        return $this;
    }

    /**
     * Convenient way get hive item
     *
     * @param  string $offset
     *
     * @return mixed
     */
    public function &offsetget($key)
    {
        $var =& $this->ref($key);

        return $var;
    }

    /**
     * Convenient way to set hive item
     *
     * @param  string $offset
     * @param  scalar|array $value
     *
     * @return void
     *
     * @throws InvalidArgumentException
     */
    public function offsetset($key, $val)
    {
        $this->set($key, $val);
    }

    /**
     * Convenient way to check hive item
     *
     * @param  string $offset
     *
     * @return bool
     */
    public function offsetexists($key)
    {
        return $this->exists($key);
    }

    /**
     * Convenient way to remove hive item
     *
     * @param  string $offset
     *
     * @return void
     */
    public function offsetunset($key)
    {
        $this->clear($key);
    }

    /**
     * offsetget alias
     *
     * @see offsetGet
     */
    public function &__get($key)
    {
        $var =& $this->offsetget($key);

        return $var;
    }

    /**
     * offsetset alias
     *
     * @see offsetGet
     */
    public function __set($key, $val)
    {
        $this->offsetset($key, $val);
    }

    /**
     * offsetexists alias
     *
     * @see offsetGet
     */
    public function __isset($key)
    {
        return $this->offsetexists($key);
    }

    /**
     * offsetunset alias
     *
     * @see offsetGet
     */
    public function __unset($key)
    {
        $this->offsetunset($key);
    }

    /**
     * Grab class name and method, create instance if needed
     *
     * @param  string $callback
     *
     * @return callable
     */
    protected function grab(string $callback)
    {
        if (preg_match('/^(.+)(::|\->)(\w+)$/', $callback, $match)) {
            $callback = [
                '->' === $match[2] ? $this->service($match[1]) : $match[1],
                $match[3]
            ];
        }

        return $callback;
    }

    /**
     * Build method arguments
     *
     * @param  \ReflectionFunctionAbstract $ref
     * @param  array                       $sArgs
     * @param  array                       $lArgs
     *
     * @return array
     */
    protected function methodArgs(\ReflectionFunctionAbstract $ref, array $sArgs = [], array $lArgs = []): array
    {
        $args  = [];
        $pArgs = array_filter($lArgs, 'is_numeric', ARRAY_FILTER_USE_KEY);

        foreach ($ref->getParameters() as $param) {
            $name = $param->name;

            if (isset($sArgs[$name])) {
                $val = $sArgs[$name];

                if (is_string($val)) {
                    // assume it is a class name
                    if (class_exists($val)) {
                        $val = $this->service($val);
                    } elseif (preg_match('/(.+)?%(.+)%(.+)?/', $val, $match)) {
                        // assume it does exist in hive
                        $ref = $this->ref($match[2], false);
                        if (isset($ref)) {
                            $val = ($match[1] ?? '') . $ref . ($match[3] ?? '');
                        } else {
                            // it is a service alias
                            $val = $this->service($match[2]);
                        }
                    }
                }

                $args[] = $val;
            } elseif (isset($lArgs[$name])) {
                $args[] = $lArgs[$name];
            } elseif ($param->isVariadic()) {
                $args = array_merge($args, $pArgs);
            } elseif ($refClass = $param->getClass()) {
                $args[] = $this->service($refClass->name);
            } elseif ($pArgs) {
                $args[] = array_shift($pArgs);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            }
        }

        return $args;
    }

    /**
     * Check pattern against path
     *
     * @param string $pattern
     * @param string $path
     * @param string $modifier
     * @param array  &$match
     *
     * @return bool
     */
    protected function routeMatch(string $pattern, string $path, string $modifier, array &$match = null): bool
    {
        $wild = preg_replace_callback('/\{(\w+)(?:\:(?:(alnum|alpha|digit|lower|upper|word)|(\w+)))?\}/', function($m) {
            // defaults to alnum
            return '(?<' . $m[1] . '>[[:' . (isset($m[2]) && $m[2] ? $m[2] : 'alnum') . ':]]+)';
        }, $pattern);

        $regex = '~^' . $wild. '$~' . $modifier;

        return (bool) preg_match($regex, $path, $match);
    }
}

<?php

declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick;

use Fal\Stick\Sql\Mapper;

/**
 * Main framework class.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class App implements \ArrayAccess
{
    // Framework details
    const PACKAGE = 'Stick-PHP';
    const VERSION = '0.1.0-beta';

    // Request types
    const REQ_SYNC = 1;
    const REQ_AJAX = 2;
    const REQ_CLI = 4;

    // Mapped PHP globals
    const GLOBALS = 'GET|POST|COOKIE|REQUEST|SESSION|FILES|SERVER|ENV';

    // Request methods
    const VERBS = 'GET|HEAD|POST|PUT|PATCH|DELETE|CONNECT|OPTIONS';

    // HTTP status codes (RFC 2616)
    const HTTP_100 = 'Continue';
    const HTTP_101 = 'Switching Protocols';
    const HTTP_103 = 'Early Hints';
    const HTTP_200 = 'OK';
    const HTTP_201 = 'Created';
    const HTTP_202 = 'Accepted';
    const HTTP_203 = 'Non-Authorative Information';
    const HTTP_204 = 'No Content';
    const HTTP_205 = 'Reset Content';
    const HTTP_206 = 'Partial Content';
    const HTTP_300 = 'Multiple Choices';
    const HTTP_301 = 'Moved Permanently';
    const HTTP_302 = 'Found';
    const HTTP_303 = 'See Other';
    const HTTP_304 = 'Not Modified';
    const HTTP_305 = 'Use Proxy';
    const HTTP_307 = 'Temporary Redirect';
    const HTTP_400 = 'Bad Request';
    const HTTP_401 = 'Unauthorized';
    const HTTP_402 = 'Payment Required';
    const HTTP_403 = 'Forbidden';
    const HTTP_404 = 'Not Found';
    const HTTP_405 = 'Method Not Allowed';
    const HTTP_406 = 'Not Acceptable';
    const HTTP_407 = 'Proxy Authentication Required';
    const HTTP_408 = 'Request Timeout';
    const HTTP_409 = 'Conflict';
    const HTTP_410 = 'Gone';
    const HTTP_411 = 'Length Required';
    const HTTP_412 = 'Precondition Failed';
    const HTTP_413 = 'Request Entity Too Large';
    const HTTP_414 = 'Request-URI Too Long';
    const HTTP_415 = 'Unsupported Media Type';
    const HTTP_416 = 'Requested Range Not Satisfiable';
    const HTTP_417 = 'Expectation Failed';
    const HTTP_500 = 'Internal Server Error';
    const HTTP_501 = 'Not Implemented';
    const HTTP_502 = 'Bad Gateway';
    const HTTP_503 = 'Service Unavailable';
    const HTTP_504 = 'Gateway Timeout';
    const HTTP_505 = 'HTTP Version Not Supported';

    // App events
    const EVENT_BOOT = 'app.boot';
    const EVENT_SHUTDOWN = 'app.shutdown';
    const EVENT_PREROUTE = 'app.preroute';
    const EVENT_POSTROUTE = 'app.postroute';
    const EVENT_REROUTE = 'app.reroute';
    const EVENT_ERROR = 'app.error';

    // Default rule
    const RULE_DEFAULT = [
        'class' => null,
        'use' => null,
        'args' => null,
        'service' => true,
        'constructor' => null,
        'boot' => null,
    ];

    // Default group
    const GROUP_DEFAULT = [
        'class' => '',
        'route' => '',
        'prefix' => '',
        'suffix' => '',
        'mode' => '->',
    ];

    // Config map
    const CONFIG_MAP = [
        'configs' => 'config',
        'routes' => 'route',
        'maps' => 'map',
        'redirects' => 'redirect',
        'rules' => 'rule',
        'listeners' => 'on',
        'groups' => 'group',
    ];

    /**
     * Initial value.
     *
     * @var array
     */
    private $init;

    /**
     * Variable hive.
     *
     * @var array
     */
    private $hive;

    /**
     * Class constructor.
     */
    public function __construct()
    {
        ini_set('default_charset', 'UTF-8');
        session_cache_limiter('');

        $headers = [
            'Content-Type' => $_SERVER['CONTENT_TYPE'] ?? null,
            'Content-Length' => $_SERVER['CONTENT_LENGTH'] ?? null,
        ];

        foreach ($_SERVER as $key => $val) {
            if ($header = Helper::cutafter($key, 'HTTP_')) {
                $headers[Helper::toHKey($header)] = $val;
            }
        }

        $cli = PHP_SAPI === 'cli';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $domain = $_SERVER['SERVER_NAME'] ?? gethostname();
        $urireq = $_SERVER['REQUEST_URI'] ?? '/';
        $uridomain = preg_match('~^\w+://~', $urireq) ? '' : '//'.$domain;
        $uri = parse_url($uridomain.$urireq) + ['query' => '', 'fragment' => ''];
        $base = $cli ? '' : rtrim(Helper::fixslashes(dirname($_SERVER['SCRIPT_NAME'])), '/');
        $path = Helper::cutafter($uri['path'], $base, $uri['path']);
        $secure = ($_SERVER['HTTPS'] ?? '') === 'on' || ($headers['X-Forwarded-Proto'] ?? '') === 'https';
        $scheme = $secure ? 'https' : 'http';
        $port = $headers['X-Forwarded-Port'] ?? $_SERVER['SERVER_PORT'] ?? 80;

        $_SERVER['REQUEST_URI'] = $uri['path'].rtrim('?'.$uri['query'], '?').rtrim('#'.$uri['fragment'], '#');
        $_SERVER['DOCUMENT_ROOT'] = realpath($_SERVER['DOCUMENT_ROOT']);
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['SERVER_NAME'] = $domain;
        $_SERVER['SERVER_PROTOCOL'] = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0';

        // Early assignment
        $this->hive = [
            'REQ' => [
                'HEADERS' => $headers,
            ],
        ];
        // Full assignment
        $this->hive = [
            '_BOOTED' => false,
            '_GROUP' => [],
            '_GROUP_DEPTH' => 0,
            '_HANDLE_MAPPER' => false,
            '_LISTENERS' => [],
            '_ROUTES' => [],
            '_ROUTE_ALIASES' => [],
            '_SERVICES' => [],
            '_SERVICE_RULES' => [
                'cache' => [
                    'class' => Cache::class,
                    'args' => [
                        'dsn' => '%CACHE%',
                        'prefix' => '%SEED%',
                        'fallback' => '%TEMP%cache/',
                    ],
                ],
                'logger' => [
                    'class' => Logger::class,
                    'args' => [
                        'dir' => '%TEMP%log/',
                        'logLevelThreshold' => '%LOG.THRESHOLD%',
                    ],
                ],
            ],
            '_SERVICE_ALIASES' => [
                Cache::class => 'cache',
                Logger::class => 'logger',
            ],
            'CACHE' => '',
            'CASELESS' => false,
            'CORS' => [
                'HEADERS' => '',
                'ORIGIN' => false,
                'CREDENTIALS' => false,
                'EXPOSE' => false,
                'TTL' => 0,
            ],
            'DEBUG' => 0,
            'DNSBL' => '',
            'ENCODING' => 'UTF-8',
            'ERROR' => [],
            'EXEMPT' => [],
            'CMAPPER' => false,
            'JAR' => [
                'EXPIRE' => 0,
                'PATH' => $base ?: '/',
                'DOMAIN' => (false === strpos($domain, '.') || filter_var($domain, FILTER_VALIDATE_IP)) ? '' : $domain,
                'SECURE' => $secure,
                'HTTPONLY' => true,
            ],
            'LOG' => [
                'THRESHOLD' => Logger::LEVEL_ERROR,
                'LEVEL' => Logger::LEVEL_DEBUG,
            ],
            'NAMESPACE' => [],
            'PACKAGE' => self::PACKAGE,
            'PREMAP' => '',
            'QUIET' => false,
            'RAW' => false,
            'RES' => [
                'CODE' => 200,
                'STATUS' => self::HTTP_200,
                'CONTENT' => '',
                'HEADERS' => [],
            ],
            'REQ' => [
                'AGENT' => $this->agent(),
                'AJAX' => $this->ajax(),
                'ALIAS' => '',
                'BASE' => $base,
                'BODY' => '',
                'CLI' => $cli,
                'FRAGMENT' => $uri['fragment'],
                'HEADERS' => $headers,
                'HOST' => $_SERVER['SERVER_NAME'],
                'IP' => $this->ip(),
                'LANGUAGE' => $headers['Accept-Language'] ?? '',
                'MATCH' => '',
                'METHOD' => $method,
                'PARAMS' => [],
                'PATH' => urldecode($path),
                'PATTERN' => '',
                'PROTOCOL' => $_SERVER['SERVER_PROTOCOL'],
                'PORT' => $port,
                'QUERY' => $uri['query'],
                'REALM' => $scheme.'://'.$_SERVER['SERVER_NAME'].($port && !in_array($port, [80, 443]) ? (':'.$port) : '').$_SERVER['REQUEST_URI'],
                'ROOT' => $_SERVER['DOCUMENT_ROOT'],
                'SCHEME' => $scheme,
                'URI' => $_SERVER['REQUEST_URI'],
            ],
            'SEED' => Helper::hash($_SERVER['SERVER_NAME'].$base),
            'TEMP' => './var/',
            'TRACE' => is_dir($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : dirname($_SERVER['DOCUMENT_ROOT']),
            'TZ' => date_default_timezone_get(),
            'VERSION' => self::VERSION,
            'XFRAME' => 'SAMEORIGIN',
        ];

        // Sync PHP globals with corresponding hive keys
        array_map([$this, 'sync'], explode('|', self::GLOBALS));

        // Save a copy of hive
        $this->init = $this->hive;
        unset($this->init['GET'], $this->init['POST'], $this->init['COOKIE'], $this->init['REQUEST'], $this->init['SESSION'], $this->init['FILES']);

        if (ini_get('auto_globals_jit')) {
            // Override setting
            $GLOBALS += ['_ENV' => $_ENV, '_REQUEST' => $_REQUEST];
        }

        // Register shutdown handler
        register_shutdown_function([$this, 'unload'], getcwd());
    }

    /**
     * Return current user agent.
     *
     * @return string
     */
    public function agent(): string
    {
        $use = $this->hive['REQ']['HEADERS'];

        return $use['X-Operamini-Phone-Ua'] ?? $use['X-Skyfire-Phone'] ?? $use['User-Agent'] ?? '';
    }

    /**
     * Return ajax status.
     *
     * @return bool
     */
    public function ajax(): bool
    {
        $use = $this->hive['REQ']['HEADERS'];

        return 'xmlhttprequest' === strtolower($use['X-Requested-With'] ?? '');
    }

    /**
     * Return ip address.
     *
     * @return string
     */
    public function ip(): string
    {
        $use = $this->hive['REQ']['HEADERS'];

        return $use['Client-Ip'] ?? (isset($use['X-Forwarded-For']) ? Helper::split($use['X-Forwarded-For'])[0] : $_SERVER['REMOTE_ADDR'] ?? '');
    }

    /**
     * Check if ip is blacklisted.
     *
     * @param string|null $ip
     *
     * @return bool
     */
    public function blacklisted(string $ip = null): bool
    {
        $use = $ip ?? $this->hive['REQ']['IP'];
        $dnsbl = Helper::reqarr($this->hive['DNSBL'] ?? '');
        $exempt = Helper::reqarr($this->hive['EXEMPT'] ?? '');

        if ($dnsbl && !in_array($use, $exempt)) {
            // We skip this part to test
            // @codeCoverageIgnoreStart
            // Reverse IPv4 dotted quad
            $rev = implode('.', array_reverse(explode('.', $use)));
            foreach ($dnsbl as $server) {
                // DNSBL lookup
                if (checkdnsrr($rev.'.'.$server, 'A')) {
                    return true;
                }
            }
            // @codeCoverageIgnoreEnd
        }

        return false;
    }

    /**
     * Mimic composer autoload behaviour, can be used as class autoloader.
     *
     * @param string $class
     *
     * @return mixed
     */
    public function autoload($class)
    {
        $logicalPath = Helper::fixslashes($class).'.php';
        $subPath = $class;

        while (false !== $lastPos = strrpos($subPath, '\\')) {
            $subPath = substr($subPath, 0, $lastPos);
            $paths = $this->hive['NAMESPACE'][$subPath.'\\'] ?? null;

            if ($paths) {
                $pathEnd = substr($logicalPath, $lastPos + 1);

                foreach (Helper::reqarr($paths) as $dir) {
                    if (file_exists($file = $dir.$pathEnd)) {
                        Helper::exinclude($file);

                        return true;
                    }
                }
            }
        }

        // fallback (root)
        foreach (Helper::reqarr($this->hive['NAMESPACE']['\\'] ?? []) as $dir) {
            if (file_exists($file = $dir.$logicalPath)) {
                Helper::exinclude($file);

                return true;
            }
        }
    }

    /**
     * Override request method.
     *
     * @return App
     */
    public function overrideRequestMethod(): App
    {
        $method = $this->hive['REQ']['HEADERS']['X-HTTP-Method-Override'] ?? $_SERVER['REQUEST_METHOD'];

        if ('POST' === $method && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        $this->hive['REQ']['METHOD'] = $_SERVER['REQUEST_METHOD'] = $method;

        return $this;
    }

    /**
     * Emulate CLI request.
     *
     * @return App
     */
    public function emulateCliRequest(): App
    {
        if (!$this->hive['REQ']['CLI']) {
            return $this;
        }

        if (!isset($_SERVER['argv'][1])) {
            ++$_SERVER['argc'];
            $_SERVER['argv'][1] = '/';
        }

        if ($_SERVER['argv'][1][0] === '/') {
            $req = $_SERVER['argv'][1];
        } else {
            $req = '';
            $opts = '';

            for ($i = 1; $i < $_SERVER['argc']; ++$i) {
                $arg = $_SERVER['argv'][$i];

                if ('-' === $arg[0]) {
                    $m = explode('=', $arg);
                    if ('-' === $arg[1]) {
                        $opts .= '&'.urlencode(substr($m[0], 2)).'=';
                    } else {
                        $opts .= '&'.implode('=&', array_map('urlencode', str_split(substr($m[0], 1)))).'=';
                    }
                    $opts = ltrim($opts, '&').($m[1] ?? '');
                } else {
                    $req .= '/'.$arg;
                }
            }

            $req = '/'.ltrim(rtrim($req.'?'.$opts, '?'), '/');
        }

        $uri = parse_url($req) + ['query' => '', 'fragment' => ''];
        $this->hive['REQ']['PATH'] = $uri['path'];
        $this->hive['REQ']['QUERY'] = $uri['query'];
        $this->hive['REQ']['FRAGMENT'] = $uri['fragment'];
        $this->hive['REQ']['URI'] = $_SERVER['REQUEST_URI'] = $req;
        $this->hive['REQ']['METHOD'] = $_SERVER['REQUEST_METHOD'] = 'GET';
        parse_str($uri['query'], $GLOBALS['_GET']);
        $this->sync('GET');

        return $this;
    }

    /**
     * Register route with group.
     *
     * Available options:
     *
     *  * route  : route prefix
     *  * prefix : path prefix
     *  * suffix : path suffix
     *  * class  : class name handler
     *  * mode   : '->' (default) or '::'
     *
     * Example:
     *
     *      $app->group(['prefix'=>'/foo', 'route'=>'foo'], function($app) {
     *          $app->route('GET bar /bar', function() {
     *              # ...
     *          });
     *      });
     *
     * @param array    $options
     * @param callable $callback
     *
     * @return App
     */
    public function group(array $options, callable $callback): App
    {
        ++$this->hive['_GROUP_DEPTH'];

        $use = $options + self::GROUP_DEFAULT;

        if ($this->hive['_GROUP_DEPTH'] > 1) {
            $use = [
                'route' => $this->hive['_GROUP']['route'].$use['route'],
                'prefix' => $this->hive['_GROUP']['prefix'].$use['prefix'],
                'suffix' => $this->hive['_GROUP']['suffix'].$use['suffix'],
                'class' => $use['class'],
                'mode' => $use['mode'],
                '_parent' => $this->hive['_GROUP'],
            ];
        }

        $this->hive['_GROUP'] = $use;

        call_user_func_array($callback, [$this]);

        $this->hive['_GROUP'] = --$this->hive['_GROUP_DEPTH'] > 0 ? $this->hive['_GROUP']['_parent'] ?? self::GROUP_DEFAULT : self::GROUP_DEFAULT;

        return $this;
    }

    /**
     * Register route.
     *
     * Pattern rule: "METHOD [routeName] /path [requestMode]".
     *
     *  * Multiple method can be defined with pipe character as separator.
     *  * Route name should not contains '.' (dot) or space.
     *  * /path can be a valid routeName or pure path, if routeName given the previous path of routeName will be used
     *  * Supported request mode: sync, ajax, cli
     *
     * Pattern example:
     *
     *      * GET /foo
     *      * GET|POST /bar
     *      * GET routeName /baz
     *      * POST routeName (Above path '/baz' will be used)
     *      * GET syncModeOnly /sync sync
     *      * GET ajaxModeOnly /ajax ajax
     *      * GET cliModeOnly /cli ajax
     *      * GET /arguments/{arg}
     *      * GET /arguments/{arg:alpha}/{arg2:digit}/{arg3:word}/{arg4:alnum}
     *      * GET /arguments/{arg:lower}/{arg2:upper}
     *
     * Handler example:
     *
     *      * function() { ... } # no argument
     *      * function($arg) { ... } # named arg in pattern
     *      * function(App $app) { ... } # if instance of App needed
     *      * function(OtherService $instance) { ... } # if instance of other service needed
     *      * [$instanceOfController, 'method'] # if controller instantiate manually
     *      * 'ControllerName->method' # same as above but controller will be instantiated by the Framework
     *      * ['ControllerName', 'method'] # if controller method is static
     *      * 'ControllerName::method' # same as above
     *
     * @param string          $pattern
     * @param string|callable $handler
     * @param int             $ttl
     * @param int             $kbps
     *
     * @return App
     *
     * @throws LogicException If route pattern is not valid
     */
    public function route(string $pattern, $handler, int $ttl = 0, int $kbps = 0): App
    {
        preg_match('/^([\|\w]+)(?:\h+(\w+))?(?:\h+([^\h]+))?(?:\h+(sync|ajax|cli))?$/i', $pattern, $match);

        $alias = $match[2] ?? null;
        $path = $match[3] ?? null;
        $group = $this->hive['_GROUP'] ?: self::GROUP_DEFAULT;

        if (!$path && $alias && isset($this->hive['_ROUTE_ALIASES'][$alias])) {
            $path = $this->hive['_ROUTE_ALIASES'][$alias];
        } else {
            if ($alias) {
                $alias = $group['route'].$alias;
            }

            $path = $group['prefix'].$path.$group['suffix'];
        }

        if (!$path) {
            throw new \LogicException('Route pattern should contain at least request method and path, given "'.$pattern.'"');
        }

        if ($alias) {
            $this->hive['_ROUTE_ALIASES'][$alias] = $path;
        }

        $type = Helper::constant(self::class.'::REQ_'.strtoupper($match[4] ?? ''), 0);
        $use = is_string($handler) ? ($group['class'] ? $group['class'].$group['mode'] : '').$handler : $handler;

        foreach (Helper::split(strtoupper($match[1])) as $verb) {
            $this->hive['_ROUTES'][$path][$type][$verb] = [$use, $ttl, $kbps, $alias];
        }

        return $this;
    }

    /**
     * Register pattern to handle ReST.
     *
     * Pattern is same as App::route but without method.
     *
     * App::VERBS will be registered with verb name as its method.
     * Controller method can be prefixed with PREMAP value.
     *
     * Pattern example:
     *
     *  * /foo
     *  * routeName /bar
     *  * routeName /path sync
     *
     * Class example:
     *
     *      * $instanceOfController
     *      * 'ControllerName'
     *
     * @param string        $pattern
     * @param string|object $class
     * @param int           $ttl
     * @param int           $kbps
     *
     * @return App
     */
    public function map(string $pattern, $class, int $ttl = 0, int $kbps = 0): App
    {
        $str = is_string($class);
        $prefix = $this->hive['PREMAP'];

        foreach (Helper::split(self::VERBS) as $verb) {
            $this->route($verb.' '.$pattern, $str ? $class.'->'.$prefix.$verb : [$class, $prefix.$verb], $ttl, $kbps);
        }

        return $this;
    }

    /**
     * Redirect pattern to specified url.
     *
     * Pattern is same as App::route.
     *
     * Url can be string or array.
     *
     * Url example:
     *
     *      * /target/path
     *      * routeName
     *      * ['routeName', ['arg'=>'foo']]
     *
     * @param string       $pattern
     * @param string|array $url
     * @param bool         $permanent
     *
     * @return App
     *
     * @throws LogicException If url empty
     */
    public function redirect(string $pattern, $url, bool $permanent = true): App
    {
        if (!$url) {
            throw new \LogicException('Url cannot be empty');
        }

        return $this->route($pattern, function () use ($url, $permanent) {
            $this->reroute($url, $permanent);
        });
    }

    /**
     * Set response status code.
     *
     * @param int $code
     *
     * @return App
     *
     * @throws DomainException If given code is not supported
     */
    public function status(int $code): App
    {
        $status = Helper::constant(self::class.'::HTTP_'.$code);

        if (!$status) {
            throw new \DomainException('Unsupported http code: '.$code);
        }

        $this->hive['RES']['CODE'] = $code;
        $this->hive['RES']['STATUS'] = $status;

        return $this;
    }

    /**
     * Set expire headers.
     *
     * @param int $secs
     *
     * @return App
     */
    public function expire(int $secs = 0): App
    {
        $this->hive['RES']['HEADERS']['X-Powered-By'] = $this->hive['PACKAGE'];
        $this->hive['RES']['HEADERS']['X-Frame-Options'] = $this->hive['XFRAME'];
        $this->hive['RES']['HEADERS']['X-XSS-Protection'] = '1; mode=block';
        $this->hive['RES']['HEADERS']['X-Content-Type-Options'] = 'nosniff';

        if ('GET' === $this->hive['REQ']['METHOD'] && $secs) {
            $expires = (int) (microtime(true) + $secs);

            unset($this->hive['RES']['HEADERS']['Pragma']);
            $this->hive['RES']['HEADERS']['Cache-Control'] = 'max-age='.$secs;
            $this->hive['RES']['HEADERS']['Expires'] = gmdate('r', $expires);
            $this->hive['RES']['HEADERS']['Last-Modified'] = gmdate('r');
        } else {
            $this->hive['RES']['HEADERS']['Pragma'] = 'no-cache';
            $this->hive['RES']['HEADERS']['Cache-Control'] = 'no-cache, no-store, must-revalidate';
            $this->hive['RES']['HEADERS']['Expires'] = gmdate('r', 0);
        }

        return $this;
    }

    /**
     * Build url from given alias.
     *
     * @param string     $alias
     * @param array|null $args
     *
     * @return string
     *
     * @throws LogicException If named route (alias) does not exists
     */
    public function alias(string $alias, array $args = null): string
    {
        if (!isset($this->hive['_ROUTE_ALIASES'][$alias])) {
            throw new \LogicException('Route "'.$alias.'" does not exists');
        }

        $lookup = (array) $args;

        return preg_replace_callback('/\{(\w+)(?:\:\w+)?\}/', function ($m) use ($lookup) {
            return array_key_exists($m[1], $lookup) ? $lookup[$m[1]] : $m[0];
        }, $this->hive['_ROUTE_ALIASES'][$alias]);
    }

    /**
     * Build url from string expression, example: @foo#bar(argument=value).
     *
     * @param string $expr
     *
     * @return string
     */
    public function build(string $expr): string
    {
        if (!preg_match('/^(\w+)(#\w+)?(?:\(([^\)]+)\))?$/', $expr, $match)) {
            // no route alias declaration
            return $expr;
        }

        $args = [];
        $route = $match[1];
        $fragment = $match[2] ?? '';
        $defArgs = $match[3] ?? '';

        foreach (Helper::split($defArgs) as $arg) {
            $pair = explode('=', $arg);
            $args[trim($pair[0])] = trim($pair[1] ?? '');
        }

        return $this->alias($route, $args).$fragment;
    }

    /**
     * Reroute to given url or alias or do reload current url.
     *
     * @param string|array|null $url
     * @param bool              $permanent
     */
    public function reroute($url = null, bool $permanent = false): void
    {
        $use = $url ? (is_array($url) ? $this->alias(...$url) : $this->build($url)) : $this->hive['REQ']['REALM'];

        if ($this->trigger(self::EVENT_REROUTE, [$use, $permanent])) {
            return;
        }

        if ('/' === $use[0] && (empty($use[1]) || '/' !== $use[1])) {
            $port = $this->hive['REQ']['PORT'];
            $use = $this->hive['REQ']['SCHEME'].'://'.$this->hive['REQ']['HOST'].(in_array($port, [80, 443]) ? '' : (':'.$port)).$this->hive['REQ']['BASE'].$use;
        }

        if ($this->hive['REQ']['CLI']) {
            $this->mock('GET '.$use.' cli');

            return;
        }

        $this->hive['RES']['HEADERS']['Location'] = $use;
        $this->status($permanent ? 301 : 302)->sendHeaders();
    }

    /**
     * Start route matching.
     */
    public function run(): void
    {
        $level = ob_get_level();

        try {
            $this->doRun();
        } catch (\Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            $this->error($e instanceof ResponseErrorException ? $e->getCode() : 500, $e->getMessage() ?: null, $e->getTrace());
        }
    }

    /**
     * Mock request.
     *
     * @param string      $pattern
     * @param array|null  $args
     * @param array|null  $headers
     * @param string|null $body
     *
     * @throws LogicException If mock pattern is not valid
     */
    public function mock(string $pattern, array $args = null, array $headers = null, string $body = null): void
    {
        preg_match('/^([\w]+)(?:\h+([^\h]+))(?:\h+(sync|ajax|cli))?$/i', $pattern, $match);

        if (empty($match[2])) {
            throw new \LogicException('Mock pattern should contain at least request method and path, given "'.$pattern.'"');
        }

        $args = (array) $args;
        $headers = (array) $headers;
        $method = strtoupper($match[1]);
        $path = $this->build($match[2]);
        $mode = strtolower($match[3] ?? '');
        $uri = parse_url($path) + ['query' => '', 'fragment' => ''];

        $this->clear('REQ');

        $this->hive['REQ']['METHOD'] = $method;
        $this->hive['REQ']['PATH'] = $uri['path'];
        $this->hive['REQ']['URI'] = $this->hive['REQ']['BASE'].$uri['path'];
        $this->hive['REQ']['FRAGMENT'] = $uri['fragment'];
        $this->hive['REQ']['AJAX'] = 'ajax' === $mode;
        $this->hive['REQ']['CLI'] = 'cli' === $mode;
        $this->hive['REQ']['HEADERS'] = $headers;

        parse_str($uri['query'], $GLOBALS['_GET']);

        if (in_array($method, ['GET', 'HEAD'])) {
            $GLOBALS['_GET'] = array_merge($GLOBALS['_GET'], $args);
        } else {
            $this->hive['REQ']['BODY'] = $body ?? http_build_query($args);
        }

        if ($GLOBALS['_GET']) {
            $this->hive['REQ']['QUERY'] = http_build_query($GLOBALS['_GET']);
            $this->hive['REQ']['URI'] .= '?'.$this->hive['REQ']['QUERY'];
        }

        $GLOBALS['_POST'] = 'POST' === $method ? $args : [];
        $GLOBALS['_REQUEST'] = array_merge($GLOBALS['_GET'], $GLOBALS['_POST']);

        foreach ($headers as $key => $val) {
            $_SERVER['HTTP_'.Helper::fromHKey($key)] = $val;
        }

        $this->run();
    }

    /**
     * Send error.
     *
     * @param int    $code
     * @param string $message
     * @param array  $trace
     * @param string $level
     */
    public function error(int $code, string $message = null, array $trace = null, string $level = null): void
    {
        $this->clear('RES')->status($code);

        $status = $this->hive['RES']['STATUS'];
        $req = rtrim($this->hive['REQ']['METHOD'].' '.$this->hive['REQ']['PATH'].'?'.$this->hive['REQ']['QUERY'], '?');
        $text = $message ?? 'HTTP '.$code.' ('.$req.')';
        $sTrace = $this->trace($trace);

        $this->service('logger')->log($level ?? $this->hive['LOG']['LEVEL'], $text.PHP_EOL.$sTrace);

        $prior = $this->hive['ERROR'];
        $this->hive['ERROR'] = [
            'status' => $status,
            'code' => $code,
            'text' => $text,
            'trace' => $sTrace,
        ];

        $this->expire(-1);

        if ($this->trigger(self::EVENT_ERROR, [$this->hive['ERROR'], $prior])) {
            return;
        }

        if ($prior || $this->hive['QUIET']) {
            return;
        }

        if ($this->hive['REQ']['AJAX']) {
            $type = 'application/json';
            $out = json_encode(array_diff_key(
                $this->hive['ERROR'],
                $this->hive['DEBUG'] ? [] : ['trace' => 1]
            ));
        } elseif ($this->hive['REQ']['CLI']) {
            $type = 'text/plain';
            $out = Helper::contexttostring(array_diff_key(
                $this->hive['ERROR'],
                $this->hive['DEBUG'] ? [] : ['trace' => 1]
            ));
        } else {
            $type = 'text/html';
            $out = '<!DOCTYPE html>'.
                '<html>'.
                '<head>'.
                  '<meta charset="'.$this->hive['ENCODING'].'">'.
                  '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">'.
                  '<title>'.$code.' '.$status.'</title>'.
                '</head>'.
                '<body>'.
                  '<h1>'.$status.'</h1>'.
                  '<p>'.$text.'</p>'.
                  ($this->hive['DEBUG'] ? '<pre>'.$sTrace.'</pre>' : '').
                '</body>'.
                '</html>'
            ;
        }

        $this->hive['RES']['HEADERS']['Content-Type'] = $type;
        $this->sendHeaders();
        echo $out;
    }

    /**
     * Framework shutdown sequence.
     *
     * @param string $cwd
     *
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

        if ($this->trigger(self::EVENT_SHUTDOWN, [$cwd])) {
            return;
        }

        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            // Fatal error detected
            $this->error(500, 'Fatal error: '.$error['message'], [$error]);
        }
    }

    /**
     * Set event listener.
     *
     * Listener should return true to give control back to the caller
     *
     * @param string   $event
     * @param callable $listener
     *
     * @return App
     */
    public function on(string $event, callable $listener): App
    {
        $ref = &$this->ref('_LISTENERS.'.$event);

        if ($ref) {
            $ref[] = $listener;
        } else {
            $ref = [$listener];
        }

        return $this;
    }

    /**
     * Trigger event listener.
     *
     * @param string     $event
     * @param array|null $args
     *
     * @return bool
     */
    public function trigger(string $event, array $args = null): bool
    {
        $listeners = $this->ref('_LISTENERS.'.$event, false);

        if (!$listeners) {
            return false;
        }

        foreach ($listeners as $listener) {
            if (true === $this->call($listener, $args)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Grab class->method declaration from a string.
     *
     * @param string $def
     * @param bool   $create
     *
     * @return mixed
     */
    public function grab(string $def, bool $create = true)
    {
        $obj = explode('->', $def);
        $static = explode('::', $def);
        $grabbed = $def;

        if (2 === count($obj)) {
            $grabbed = [$create ? $this->service($obj[0]) : $obj[0], $obj[1]];
        } elseif (2 === count($static)) {
            $grabbed = $static;
        }

        return $grabbed;
    }

    /**
     * Call callback.
     *
     * Framework will trying to find required arguments by looking up in services
     * and given args.
     *
     * Callback example:
     *
     *  * regular callback is supported
     *  * 'ClassName->method', instance of class name will be instantiated by the framework
     *  * 'ClassName->method', static callable will be grabbed (['ClassName', 'method'])
     *
     * @param mixed      $callback
     * @param array|null $args
     *
     * @return mixed
     *
     * @throws BadMethodCallException   When method is not callable
     * @throws BadFunctionCallException When function is not callable
     */
    public function call($callback, array $args = null)
    {
        $func = is_string($callback) ? $this->grab($callback) : $callback;

        if (!is_callable($func)) {
            if (is_array($func)) {
                throw new \BadMethodCallException('Call to undefined method '.get_class($func[0]).'::'.$func[1]);
            } else {
                throw new \BadFunctionCallException('Call to undefined function '.$func);
            }
        }

        if (is_array($func)) {
            $rArgs = $this->resolveArgs(new \ReflectionMethod($func[0], $func[1]), $args);
        } else {
            $rArgs = $this->resolveArgs(new \ReflectionFunction($func), $args);
        }

        return call_user_func_array($func, $rArgs);
    }

    /**
     * Register service rule.
     *
     * Available options:
     *
     *  * class: class name
     *  * use: use this class name instead one defined as class name (in case your class is an interface or abstract class)
     *  * args: an array of required arguments
     *  * service: true or false
     *  * boot: a callable to call after instance creation
     *
     * Usage example:
     *
     *  * $app->rule('YourClassName') # class name will be service id
     *  * $app->rule('serviceId', 'YourClassName') # using class name
     *  * $app->rule('serviceId', ['class'=>'YourClassName']) # same as above
     *  * $app->rule('serviceId', $yourClassInstance) # using class instance
     *  * $app->rule('serviceId', ['class'=>'YourClassName','service'=>false])
     *
     * @param string $id
     * @param mixed  $rule
     *
     * @return array
     */
    public function rule(string $id, $rule = null): App
    {
        $ref = &$this->ref('_SERVICES.'.$id);
        $ref = null;

        if (is_callable($rule)) {
            $use = ['class' => $id, 'constructor' => $rule];
        } elseif (is_object($rule)) {
            $use = ['class' => get_class($rule)];
            $ref = $rule;
        } elseif (is_string($rule)) {
            $use = ['class' => $rule];
        } else {
            $use = $rule;
        }

        unset($ref);

        $ref = &$this->ref('_SERVICE_RULES.'.$id);
        $ref = array_filter(array_replace(self::RULE_DEFAULT, [
            'class' => $id,
        ], $use ?? []), function ($val) {
            return null !== $val;
        });

        $this->hive['_SERVICE_ALIASES'][$ref['class']] = $id;

        return $this;
    }

    /**
     * Create/get instance of a class.
     *
     * @param string     $id
     * @param array|null $args
     *
     * @return mixed
     */
    public function service(string $id, array $args = null)
    {
        if (in_array($id, ['app', self::class])) {
            return $this;
        } elseif (isset($this->hive['_SERVICES'][$id])) {
            return $this->hive['_SERVICES'][$id];
        } elseif (isset($this->hive['_SERVICE_ALIASES'][$id])) {
            $id = $this->hive['_SERVICE_ALIASES'][$id];

            if (isset($this->hive['_SERVICES'][$id])) {
                return $this->hive['_SERVICES'][$id];
            }
        }

        return $this->create($id, $args);
    }

    /**
     * Create instance of a class.
     *
     * @param string     $id
     * @param array|null $args
     *
     * @return mixed
     *
     * @throws LogicException If instance is not instantiable, eg: Interface, Abstract class
     */
    public function create(string $id, array $args = null)
    {
        $rule = $this->ref('_SERVICE_RULES.'.$id, false);
        $use = ($rule ?? []) + [
            'class' => $id,
            'args' => $args,
            'service' => false,
        ] + self::RULE_DEFAULT;
        $ref = new \ReflectionClass($use['use'] ?? $use['class']);

        if (!$ref->isInstantiable()) {
            throw new \LogicException('Unable to create instance for "'.$id.'". Please provide instantiable version of '.$ref->name);
        }

        if (isset($use['constructor']) && is_callable($use['constructor'])) {
            $instance = $this->call($use['constructor']);

            if (!$instance instanceof $ref->name) {
                throw new \LogicException('Constructor of "'.$id.'" should return instance of '.$ref->name);
            }
        } elseif ($ref->hasMethod('__construct')) {
            $instance = $ref->newInstanceArgs($this->resolveArgs($ref->getMethod('__construct'), $use['args']));
        } else {
            $instance = $ref->newInstance();
        }

        unset($ref);

        if ($use['boot'] && is_callable($use['boot'])) {
            call_user_func_array($use['boot'], [$instance, $this]);
        }

        if ($use['service']) {
            $ref = &$this->ref('_SERVICES.'.$id);
            $ref = $instance;
        }

        return $instance;
    }

    /**
     * Return app hive.
     *
     * @return array
     */
    public function hive(): array
    {
        return $this->hive;
    }

    /**
     * Load configuration from a file.
     *
     * Expect file which return multidimensional array.
     *
     * All key except below will be added to App hive.
     *
     *  * configs: to load another configuration file
     *  * routes: to register routes
     *  * maps: to register maps
     *  * redirects: to register redirection
     *  * rules: to register services
     *  * listeners: to register event listener
     *  * groups: to register route groups
     *
     * @param string $file
     *
     * @return App
     */
    public function config(string $file): App
    {
        foreach (file_exists($file) ? Helper::exrequire($file, []) : [] as $key => $val) {
            $lkey = strtolower($key);

            if (isset(self::CONFIG_MAP[$lkey])) {
                $call = self::CONFIG_MAP[$lkey];

                foreach ((array) $val as $arg) {
                    $args = array_values((array) $arg);

                    $this->$call(...$args);
                }
            } else {
                $this->set($key, $val);
            }
        }

        return $this;
    }

    /**
     * Get hive reference.
     *
     * @param string     $key
     * @param bool       $add
     * @param array|null $var
     *
     * @return mixed
     */
    public function &ref(string $key, bool $add = true, array $var = null)
    {
        $parts = explode('.', $key);
        $null = null;

        $this->startSession('SESSION' === $parts[0]);

        if (null === $var) {
            if ($add) {
                $var = &$this->hive;
            } else {
                $var = $this->hive;
            }
        }

        foreach ($parts as $part) {
            if (!is_array($var)) {
                $var = [];
            }

            if ($add || array_key_exists($part, $var)) {
                $var = &$var[$part];
            } else {
                $var = &$null;
                break;
            }
        }

        return $var;
    }

    /**
     * Check hive existance.
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists(string $key): bool
    {
        $ref = $this->ref($key, false);

        return isset($ref);
    }

    /**
     * Get hive value.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function &get(string $key)
    {
        $ref = &$this->ref($key);

        return $ref;
    }

    /**
     * Set hive value.
     *
     * @param string $key
     * @param mixed  $val
     *
     * @return App
     */
    public function set(string $key, $val): App
    {
        $this->beforeSet($key, $val);

        $ref = &$this->ref($key);
        $ref = $val;

        $this->afterSet($key, $val);

        return $this;
    }

    /**
     * Clear hive value (reset if initial value exists).
     *
     * @param string $key
     *
     * @return App
     */
    public function clear(string $key): App
    {
        if ($this->beforeClear($key)) {
            return $this;
        }

        $init = $this->ref($key, false, $this->init);

        if (isset($init)) {
            $this->set($key, $init);
        } else {
            $this->doClear($key);
        }

        $this->afterClear($key);

        return $this;
    }

    /**
     * Massive set.
     *
     * @param array       $values
     * @param string|null $prefix
     *
     * @return App
     */
    public function mset(array $values, string $prefix = null): App
    {
        foreach ($values as $key => $val) {
            $this->set($prefix.$key, $val);
        }

        return $this;
    }

    /**
     * Massive clear.
     *
     * @param array $keys
     *
     * @return App
     */
    public function mclear(array $keys): App
    {
        foreach ($keys as $key) {
            $this->clear($key);
        }

        return $this;
    }

    /**
     * Get and clear hive.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function flash(string $key)
    {
        $res = $this->get($key);
        $this->clear($key);

        return $res;
    }

    /**
     * Do run.
     */
    private function doRun(): void
    {
        if (!$this->hive['_BOOTED']) {
            $this->trigger(self::EVENT_BOOT);
            $this->hive['_BOOTED'] = true;
        }

        // We skip this part to test
        // @codeCoverageIgnoreStart
        if ($this->blacklisted()) {
            // Spammer detected
            throw new ResponseErrorException("Sorry, you're not allowed to visit this site.", 403);
        }
        // @codeCoverageIgnoreEnd

        if (!$this->hive['_ROUTES']) {
            // No routes defined
            throw new ResponseErrorException('No route specified');
        }

        $method = $this->hive['REQ']['METHOD'];
        $headers = $this->hive['REQ']['HEADERS'];
        $type = $this->hive['REQ']['CLI'] ? self::REQ_CLI : ((int) $this->hive['REQ']['AJAX']) + 1;
        $modifier = $this->hive['CASELESS'] ? 'i' : '';
        $preflight = false;
        $cors = null;
        $allowed = [];

        $this->mclear(['RES', 'ERROR']);

        if (isset($headers['Origin']) && $this->hive['CORS']['ORIGIN']) {
            $cors = $this->hive['CORS'];
            $preflight = isset($headers['Access-Control-Request-Method']);

            $this->hive['RES']['HEADERS']['Access-Control-Allow-Origin'] = $cors['ORIGIN'];
            $this->hive['RES']['HEADERS']['Access-Control-Allow-Credentials'] = Helper::reqstr($cors['CREDENTIALS']);
        }

        foreach ($this->hive['_ROUTES'] as $pattern => $routes) {
            if ($this->noMatch($pattern, $modifier, $args)) {
                continue;
            } elseif (isset($routes[$type][$method])) {
                $route = $routes[$type];
            } elseif (isset($routes[0])) {
                $route = $routes[0];
            } else {
                continue;
            }

            if (!isset($route[$method]) || $preflight) {
                $allowed = array_merge($allowed, array_keys($route));

                continue;
            }

            list($handler, $ttl, $kbps, $alias) = $route[$method];

            // Capture values of route pattern tokens
            $this->hive['MATCH'] = array_shift($args);
            $this->hive['PARAMS'] = $args;
            // Save matching route
            $this->hive['ALIAS'] = $alias;
            $this->hive['PATTERN'] = $pattern;

            // Expose if defined
            if ($cors && $cors['EXPOSE']) {
                $this->hive['RES']['HEADERS']['Access-Control-Expose-Headers'] = Helper::reqstr($cors['EXPOSE']);
            }

            if (is_string($handler)) {
                // Replace route pattern tokens in handler if any
                $handler = Helper::interpolate($handler, $args, '{}');
                $check = $this->grab($handler, false);

                if (is_array($check) && !class_exists($check[0])) {
                    throw new ResponseErrorException(null, 404);
                }
            }

            // Process request
            $now = microtime(true);
            $body = '';
            $cached = null;
            $handled = false;

            if ($ttl && in_array($method, ['GET', 'HEAD'])) {
                // Only GET and HEAD requests are cacheable
                $cache = $this->service('cache');
                $hash = Helper::hash($method.' '.$this->hive['REQ']['URI']).'.url';

                if ($cache->exists($hash)) {
                    if (isset($headers['If-Modified-Since']) && strtotime($headers['If-Modified-Since']) + $ttl > $now) {
                        $this->status(304)->sendHeaders();

                        return;
                    }

                    // Retrieve from cache backend
                    $cached = $cache->get($hash);
                    list($headers, $body) = $cached[0];

                    $this->hive['RES']['HEADERS'] += $headers;
                    $this->expire((int) ($cached[1] + $ttl - $now))->sendHeaders();
                } else {
                    // Expire HTTP client-cached page
                    $this->expire($ttl);
                }
            } else {
                $this->expire(0);
            }

            if (!$cached) {
                if (!$this->hive['RAW'] && !$this->hive['REQ']['BODY']) {
                    $this->hive['REQ']['BODY'] = file_get_contents('php://input');
                }

                if (is_string($handler)) {
                    $handler = $this->grab($handler);
                }

                if (!is_callable($handler)) {
                    throw new ResponseErrorException(null, 405);
                }

                if ($this->trigger(self::EVENT_PREROUTE, $args)) {
                    return;
                }

                $this->hive['_HANDLE_MAPPER'] = $this->hive['CMAPPER'];
                $result = $this->call($handler, $args);
                $this->hive['_HANDLE_MAPPER'] = false;

                if (is_array($result)) {
                    $body = json_encode($result);
                } elseif ($result instanceof \Closure) {
                    $handled = true;
                    $result($this);
                } else {
                    $body = (string) $result;
                }

                if (isset($hash) && $body && !error_get_last()) {
                    $headers = $this->hive['RES']['HEADERS'];
                    unset($headers['Set-Cookie']);

                    // Save to cache backend
                    $cache->set($hash, [$headers, $body], $ttl);
                }

                if ($this->trigger(self::EVENT_POSTROUTE, $args)) {
                    return;
                }
            }

            $this->hive['RES']['CONTENT'] = $body;

            if (!$handled) {
                $this->sendHeaders();

                if (!$this->hive['QUIET']) {
                    $this->throttle($body, $kbps);
                }
            }

            if ('OPTIONS' !== $method) {
                return;
            }
        }

        if (!$allowed) {
            // URL doesn't match any route
            throw new ResponseErrorException(null, 404);
        }

        if (!$this->hive['REQ']['CLI']) {
            // Unhandled HTTP method
            $allowed = Helper::reqstr(array_unique($allowed));

            $this->hive['RES']['HEADERS']['Allow'] = $allowed;

            if ($cors) {
                $this->hive['RES']['HEADERS']['Access-Control-Allow-Methods'] = 'OPTIONS,'.$allowed;

                if ($cors['HEADERS']) {
                    $this->hive['RES']['HEADERS']['Access-Control-Allow-Headers'] = Helper::reqstr($cors['HEADERS']);
                }

                if ($cors['TTL'] > 0) {
                    $this->hive['RES']['HEADERS']['Access-Control-Max-Age'] = $cors['TTL'];
                }
            }

            if ('OPTIONS' !== $method) {
                throw new ResponseErrorException(null, 405);
            }
        }

        $this->sendHeaders();
    }

    /**
     * Perform pattern match, return true if no match.
     *
     * @param string     $pattern
     * @param string     $modifier
     * @param array|null &$args
     *
     * @return bool
     */
    private function noMatch(string $pattern, string $modifier, array &$args = null): bool
    {
        $wild = preg_replace_callback(
            '/\{(\w+)(?:\:(?:(alnum|alpha|digit|lower|upper|word)|(\w+)))?\}/',
            function ($m) {
                return '(?<'.$m[1].'>[[:'.(empty($m[2]) ? 'alnum' : $m[2]).':]]+)';
            },
            $pattern
        );
        $regex = '~^'.$wild.'$~'.$modifier;

        $res = preg_match($regex, $this->hive['REQ']['PATH'], $match);

        $args = [];
        $prev = null;

        foreach ($match as $key => $value) {
            if ((is_string($key) || 0 === $key) || (is_numeric($key) && !is_string($prev))) {
                $args[$key] = $value;
            }

            $prev = $key;
        }

        return !$res;
    }

    /**
     * Do throttle output.
     *
     * @param string $content
     * @param int    $kbps
     */
    private function throttle(string $content, int $kbps = 0): void
    {
        if ($kbps <= 0) {
            echo $content;

            return;
        }

        $now = microtime(true);
        $ctr = 0;

        foreach (str_split($content, 1024) as $part) {
            // Throttle output
            ++$ctr;

            if ($ctr / $kbps > ($elapsed = microtime(true) - $now) && !connection_aborted()) {
                usleep((int) (1e6 * ($ctr / $kbps - $elapsed)));
            }

            echo $part;
        }
    }

    /**
     * Send response headers and cookies.
     *
     * @return App
     */
    private function sendHeaders(): App
    {
        if ($this->hive['REQ']['CLI'] || headers_sent()) {
            return $this;
        }

        foreach ($this->hive['COOKIE'] as $name => $value) {
            setcookie($name, ...$value);
        }

        foreach ($this->hive['RES']['HEADERS'] as $name => $value) {
            header($name.': '.$value);
        }

        header($this->hive['REQ']['PROTOCOL'].' '.$this->hive['RES']['CODE'].' '.$this->hive['RES']['STATUS'], true);

        return $this;
    }

    /**
     * Resolve function/method arguments.
     *
     * @param ReflectionFunctionAbstract $ref
     * @param array|null                 $args
     *
     * @return array
     */
    private function resolveArgs(\ReflectionFunctionAbstract $ref, array $args = null): array
    {
        if (0 === $ref->getNumberOfParameters()) {
            return [];
        }

        $resolved = [];
        $use = (array) $args;
        $pArgs = array_filter($use, 'is_numeric', ARRAY_FILTER_USE_KEY);

        foreach ($ref->getParameters() as $param) {
            if ($param->getClass()) {
                $resolved[] = $this->resolveClassArg($param, $use, $pArgs);
            } elseif (array_key_exists($param->name, $use)) {
                $resolved[] = is_string($use[$param->name]) ? $this->resolveArg($use[$param->name]) : $use[$param->name];
            } elseif ($param->isVariadic()) {
                $resolved = array_merge($resolved, $pArgs);
            } elseif ($pArgs) {
                $resolved[] = array_shift($pArgs);
            }
        }

        return $resolved;
    }

    /**
     * Resolve string argument.
     *
     * @param string $val
     *
     * @return mixed
     */
    private function resolveArg(string $val)
    {
        if (class_exists($val)) {
            return $this->service($val);
        } elseif (preg_match('/(.+)?%(.+)%(.+)?/', $val, $match)) {
            // assume it does exists in hive
            $var = $this->ref($match[2], false);

            if (isset($var)) {
                return ($match[1] ?? '').$var.($match[3] ?? '');
            }

            // it is service alias
            return $this->service($match[2]);
        }

        return $val;
    }

    /**
     * Resolve class arguments.
     *
     * @param ReflectionParameter $ref
     * @param array               &$lookup
     * @param array               &$pArgs
     *
     * @return mixed
     */
    private function resolveClassArg(\ReflectionParameter $ref, array &$lookup, array &$pArgs)
    {
        $classname = $ref->getClass()->name;

        if (!isset($lookup[$ref->name])) {
            if ($pArgs && $pArgs[0] instanceof $classname) {
                return array_shift($pArgs);
            }

            return $this->service($classname);
        }

        // special handling for converting REQ.PARAMS to mapper instance
        if ($this->hive['_HANDLE_MAPPER'] && is_subclass_of($classname, Mapper::class)) {
            $mapper = $this->service($classname);
            $keys = $mapper->getKeys();
            $kcount = count($keys);
            $vals = Helper::pickstartsat($lookup, $ref->name, $kcount);
            $vcount = count($vals);

            if ($vcount !== $kcount) {
                throw new ResponseErrorException('Insufficient primary keys value, expect value of "'.implode(', ', $keys).'"');
            }

            $use = array_values($vals);
            $lookup = array_diff_key($lookup, $vals);

            $mapper->find(...$use);

            if ($mapper->dry()) {
                throw new ResponseErrorException('Record is not found ('.$this->hive['REQ']['METHOD'].' '.$this->hive['REQ']['PATH'].')', 404);
            }

            return $mapper;
        }

        return is_string($lookup[$ref->name]) ? $this->resolveArg($lookup[$ref->name]) : $lookup[$ref->name];
    }

    /**
     * Before set procedure.
     *
     * @param string $key
     * @param mixed  &$val
     */
    private function beforeSet(string $key, &$val): void
    {
        if (Helper::startswith($key, 'GET.')) {
            $this->set('REQUEST'.Helper::cutafter($key, 'GET'), $val);
        } elseif (Helper::startswith($key, 'POST.')) {
            $this->set('REQUEST'.Helper::cutafter($key, 'POST'), $val);
        } elseif (Helper::startswith($key, 'COOKIE.')) {
            $val = $this->modifyCookieSet($val);
            $this->set('REQUEST'.Helper::cutafter($key, 'COOKIE'), $val);
        } elseif ('TZ' === $key) {
            date_default_timezone_set($val);
        } elseif ('ENCODING' === $key) {
            ini_set('default_charset', $val);
        }
    }

    /**
     * After set procedure.
     *
     * @param string $key
     * @param mixed  $val
     */
    private function afterSet(string $key, $val): void
    {
        if (Helper::cutafter($key, 'JAR') && !Helper::endswith($key, '.EXPIRE')) {
            $this->hive['JAR']['EXPIRE'] -= microtime(true);
        }
    }

    /**
     * Modify cookie set.
     *
     * @param mixed $val
     *
     * @return array
     */
    private function modifyCookieSet($val): array
    {
        $jar = [];
        $needshift = false;

        if (is_array($val) && isset($val['_jar']) && is_array($val['_jar'])) {
            $jar = array_values($val['_jar']);
            unset($val['_jar']);
            $needshift = 1 === count($val);
        }

        $ujar = array_replace(array_values($this->hive['JAR']), $jar);
        array_unshift($ujar, $needshift ? array_shift($val) : $val);

        return $ujar;
    }

    /**
     * Perform real hive removal.
     *
     * @param string $key
     */
    private function doClear(string $key): void
    {
        $parts = explode('.', $key);
        $last = array_pop($parts);
        $var = &$this->hive;

        foreach ($parts as $part) {
            if (!is_array($var)) {
                return;
            }

            $var = &$var[$part];
        }

        unset($var[$last]);
    }

    /**
     * Before clear procedure.
     *
     * @param string $key
     *
     * @return bool
     */
    private function beforeClear(string $key): bool
    {
        if (Helper::startswith($key, 'GET.')) {
            $this->clear('REQUEST'.Helper::cutafter($key, 'GET'));
        } elseif (Helper::startswith($key, 'POST.')) {
            $this->clear('REQUEST'.Helper::cutafter($key, 'POST'));
        } elseif (Helper::startswith($key, 'COOKIE.')) {
            $this->clear('REQUEST'.Helper::cutafter($key, 'COOKIE'));
            $this->set($key, ['', '_jar' => [strtotime('-1 year')]]);

            return true;
        }

        return false;
    }

    /**
     * After clear procedure.
     *
     * @param string $key
     */
    private function afterClear(string $key): void
    {
        if (Helper::startswith($key, 'SESSION')) {
            $this->clearSession(Helper::cutafter($key, 'SESSION'));
        }
    }

    /**
     * Clear session.
     *
     * @param string $name
     */
    private function clearSession(string $name): void
    {
        $this->startSession();

        if ($name) {
            session_commit();
            session_start();

            return;
        }

        // End session
        session_unset();
        session_destroy();
        $this->clear('COOKIE.'.session_name());
        $this->sync('SESSION');
    }

    /**
     * Start session.
     *
     * @param bool $start
     */
    private function startSession(bool $start = true): void
    {
        if ($start && !headers_sent() && PHP_SESSION_ACTIVE !== session_status()) {
            session_start();
        }

        $this->sync('SESSION');
    }

    /**
     * Sync globals to hive.
     *
     * @param string $key
     *
     * @return array|null
     */
    private function sync(string $key): ?array
    {
        $this->hive[$key] = &$GLOBALS['_'.$key];

        return $this->hive[$key];
    }

    /**
     * Convert and modify trace as string.
     *
     * @param array|null &$trace
     *
     * @return string
     */
    private function trace(array &$trace = null): string
    {
        if (!$trace) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_shift($trace);
        }

        $trace = array_filter($trace, function ($frame) {
            return
                isset($frame['file']) &&
                (
                    $this->hive['DEBUG'] > 1
                    || (__FILE__ !== $frame['file'] || $this->hive['DEBUG'])
                    && (
                        empty($frame['function'])
                        || !preg_match('/^(?:(?:trigger|user)_error|__call|call_user_func)/', $frame['function'])
                    )
                )
            ;
        });

        $out = '';
        $eol = "\n";
        $root = [$this->hive['TRACE'].'/' => ''];

        // Analyze stack trace
        foreach ($trace as $frame) {
            $line = '';

            if (isset($frame['class'])) {
                $line .= $frame['class'].$frame['type'];
            }

            if (isset($frame['function'])) {
                $line .= $frame['function'].'('.
                         ($this->hive['DEBUG'] > 2 && isset($frame['args']) ? Helper::csv($frame['args']) : '').
                         ')';
            }

            $src = Helper::fixslashes(strtr($frame['file'], $root));
            $out .= '['.$src.':'.$frame['line'].'] '.$line.$eol;
        }

        return $out;
    }

    /**
     * Convenience method to check hive value.
     *
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->exists($offset);
    }

    /**
     * Convenience method to get hive value.
     *
     * @param string $offset
     *
     * @return mixed
     */
    public function &offsetGet($offset)
    {
        $ref = &$this->get($offset);

        return $ref;
    }

    /**
     * Convenience method to set hive value.
     *
     * @param string $offset
     * @param mixed  $value
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Convenience method to clear hive value.
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        $this->clear($offset);
    }
}

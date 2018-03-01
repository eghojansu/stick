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
final class App implements \ArrayAccess
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

    /** Mapped PHP globals */
    const GLOBALS = 'GET|POST|COOKIE|REQUEST|SESSION|FILES|SERVER|ENV';

    /** Request methods */
    const VERBS = 'GET|HEAD|POST|PUT|PATCH|DELETE|CONNECT|OPTIONS';

    /** HTTP status codes (RFC 2616) */
    const
        HTTP_100 = 'Continue',
        HTTP_101 = 'Switching Protocols',
        HTTP_103 = 'Early Hints',
        HTTP_200 = 'OK',
        HTTP_201 = 'Created',
        HTTP_202 = 'Accepted',
        HTTP_203 = 'Non-Authorative Information',
        HTTP_204 = 'No Content',
        HTTP_205 = 'Reset Content',
        HTTP_206 = 'Partial Content',
        HTTP_300 = 'Multiple Choices',
        HTTP_301 = 'Moved Permanently',
        HTTP_302 = 'Found',
        HTTP_303 = 'See Other',
        HTTP_304 = 'Not Modified',
        HTTP_305 = 'Use Proxy',
        HTTP_307 = 'Temporary Redirect',
        HTTP_400 = 'Bad Request',
        HTTP_401 = 'Unauthorized',
        HTTP_402 = 'Payment Required',
        HTTP_403 = 'Forbidden',
        HTTP_404 = 'Not Found',
        HTTP_405 = 'Method Not Allowed',
        HTTP_406 = 'Not Acceptable',
        HTTP_407 = 'Proxy Authentication Required',
        HTTP_408 = 'Request Timeout',
        HTTP_409 = 'Conflict',
        HTTP_410 = 'Gone',
        HTTP_411 = 'Length Required',
        HTTP_412 = 'Precondition Failed',
        HTTP_413 = 'Request Entity Too Large',
        HTTP_414 = 'Request-URI Too Long',
        HTTP_415 = 'Unsupported Media Type',
        HTTP_416 = 'Requested Range Not Satisfiable',
        HTTP_417 = 'Expectation Failed',
        HTTP_500 = 'Internal Server Error',
        HTTP_501 = 'Not Implemented',
        HTTP_502 = 'Bad Gateway',
        HTTP_503 = 'Service Unavailable',
        HTTP_504 = 'Gateway Timeout',
        HTTP_505 = 'HTTP Version Not Supported';

    /** @var array Initial value */
    private $init;

    /** @var array Vars hive */
    private $hive;

    /** @var array */
    protected $services = [];

    /** @var array Service aliases */
    protected $aliases = [];

    /** @var string Cache id */
    private $cache;

    /** @var Redis|Memcached|string */
    private $cacheRef;

    /**
     * Class constructor
     *
     * @param int $debug
     */
    public function __construct(int $debug = 0)
    {
        $check = error_reporting((E_ALL|E_STRICT)&~(E_NOTICE|E_USER_NOTICE));
        $cli   = 'cli' === PHP_SAPI;

        // @codeCoverageIgnoreStart
        if (function_exists('apache_setenv')) {
            // Work around Apache pre-2.4 VirtualDocumentRoot bug
            $_SERVER['DOCUMENT_ROOT'] = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['SCRIPT_FILENAME']);
            apache_setenv('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
        }
        // @codeCoverageIgnoreEnd

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $headers[dashcase($key)] = $value;
            } elseif ($header = cutafter('HTTP_', $key)) {
                $headers[dashcase($header)] = $value;
            }
        }

        $_SERVER['SERVER_NAME']     = $_SERVER['SERVER_NAME'] ?? gethostname();
        $_SERVER['SERVER_PROTOCOL'] = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0';
        $_SERVER['REQUEST_METHOD']  = $headers['X-HTTP-Method-Override'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($cli) {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            if (!isset($_SERVER['argv'][1])) {
                $_SERVER['argc']++;
                $_SERVER['argv'][1] = '/';
            }

            if ('/' === $_SERVER['argv'][1][0]) {
                $_SERVER['REQUEST_URI'] = $_SERVER['argv'][1];
            } else {
                $req  = '';
                $opts = '';
                for ($i = 1; $i < $_SERVER['argc']; $i++) {
                    $arg = $_SERVER['argv'][$i];
                    if ('-' === $arg[0]) {
                        $m = explode('=', $arg);
                        if ('-' === $arg[1]) {
                            $opts .= '&' . urlencode(substr($m[0], 2)) . '=';
                        } else {
                            $opts .= '&' . implode('=&', array_map('urlencode', str_split(substr($m[0], 1)))) . '=';
                        }
                        $opts = ltrim($opts, '&') . ($m[1] ?? '');
                    } else {
                        $req .= '/' . $arg;
                    }
                }

                $_SERVER['REQUEST_URI'] = '/' . ltrim(rtrim($req . '?'. $opts, '?'), '/');
                parse_str($opts, $GLOBALS['_GET']);
            }
        }

        if (preg_match('~^\w+://~', $_SERVER['REQUEST_URI'])) {
            // @codeCoverageIgnoreStart
            $uri = parse_url($_SERVER['REQUEST_URI']);
            // @codeCoverageIgnoreEnd
        } else {
            $uri = parse_url('//'. $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI']);
        }

        $uri   += ['query'=>'', 'fragment'=>''];
        $base   = $cli ? '' : rtrim(fixslashes(dirname($_SERVER['SCRIPT_NAME'])), '/');
        $path   = preg_replace('/^' . preg_quote($base, '/') . '/', '', $uri['path']);
        $secure = 'on' === ($_SERVER['HTTPS'] ?? '') || 'https' === ($headers['X-Forwarded-Proto'] ?? '');
        $scheme = $secure ? 'https' : 'http';
        $port   = $headers['X-Forwarded-Port'] ?? $_SERVER['SERVER_PORT'] ?? 80;
        $domain = is_int(strpos($_SERVER['SERVER_NAME'], '.')) && !filter_var($_SERVER['SERVER_NAME'], FILTER_VALIDATE_IP)? $_SERVER['SERVER_NAME'] : '';

        $_SERVER['REQUEST_URI']   = $uri['path'] . rtrim('?' . $uri['query'], '?') . rtrim('#' . $uri['fragment'], '#');
        $_SERVER['DOCUMENT_ROOT'] = realpath($_SERVER['DOCUMENT_ROOT']);

        session_cache_limiter('');

        $this->hive = ['HEADERS' => $headers];
        $this->hive = [
            'AGENT'      => $this->agent(),
            'AJAX'       => $this->ajax(),
            'ALIAS'      => NULL,
            'ALIASES'    => [],
            'BASE'       => $base,
            'BODY'       => '',
            'CACHE'      => NULL,
            'CASELESS'   => FALSE,
            'CORS'       => [
                'headers'     => '',
                'origin'      => FALSE,
                'credentials' => FALSE,
                'expose'      => FALSE,
                'ttl'         => 0,
            ],
            'CLI'        => $cli,
            'DEBUG'      => $debug,
            'DNSBL'      => '',
            'ERROR'      => NULL,
            'EXEMPT'     => NULL,
            'FRAGMENT'   => $uri['fragment'],
            'HALT'       => TRUE,
            'HEADERS'    => $headers,
            'HOST'       => $_SERVER['SERVER_NAME'],
            'IP'         => $this->ip(),
            'JAR'        => [
                'expire'   => 0,
                'path'     => $base ?: '/',
                'domain'   => $domain,
                'secure'   => $secure,
                'httponly' => TRUE
            ],
            'LOG_ERROR'  => TRUE,
            'METHOD'     => $_SERVER['REQUEST_METHOD'],
            'ONERROR'    => NULL,
            'ONREROUTE'  => NULL,
            'ONUNLOAD'   => NULL,
            'PACKAGE'    => self::PACKAGE,
            'PATH'       => urldecode($path),
            'PARAMS'     => NULL,
            'PATTERN'    => NULL,
            'PORT'       => $port,
            'PREMAP'     => '',
            'QUERY'      => $uri['query'],
            'QUIET'      => FALSE,
            'RAW'        => FALSE,
            'REALM'      => $scheme . '://' . $_SERVER['SERVER_NAME'] . ($port && !in_array($port, [80, 443])? (':' . $port):'') . $_SERVER['REQUEST_URI'],
            'RHEADERS'   => [],
            'ROOT'       => $_SERVER['DOCUMENT_ROOT'],
            'ROUTES'     => [],
            'SCHEME'     => $scheme,
            'SEED'       => hash($_SERVER['SERVER_NAME'] . $base),
            'SERIALIZER' => extension_loaded('igbinary') ? 'igbinary' : 'php',
            'SERVICE'    => [],
            'STATUS'     => 200,
            'TEMP'       => './var/',
            'TEXT'       => self::HTTP_200,
            'TZ'         => date_default_timezone_get(),
            'URI'        => $_SERVER['REQUEST_URI'],
            'VERSION'    => self::VERSION,
            'XFRAME'     => 'SAMEORIGIN',
        ];

        // Save a copy of hive
        $this->init = $this->hive;

        if (ini_get('auto_globals_jit')) {
            // Override setting
            $GLOBALS += ['_ENV' => $_ENV, '_REQUEST' => $_REQUEST];
        }

        // Sync PHP globals with corresponding hive keys
        foreach (explode('|', self::GLOBALS) as $global) {
            $sync = $this->sync($global);
            $this->init[$global] = in_array($global, ['ENV', 'SERVER']) ? $sync : [];
        }

        // @codeCoverageIgnoreStart
        if ('cli-server' === PHP_SAPI && $base === $_SERVER['REQUEST_URI']) {
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
     * Get client browser name
     *
     * @return string
     */
    public function agent(): string
    {
        return (
            $this->hive['HEADERS']['X-Operamini-Phone-Ua'] ??
            $this->hive['HEADERS']['X-Skyfire-Phone'] ??
            $this->hive['HEADERS']['User-Agent'] ??
            ''
        );
    }

    /**
     * Get XMLHttpRequest (ajax) status
     *
     * @return bool
     */
    public function ajax(): bool
    {
        return 'xmlhttprequest' === strtolower($this->hive['HEADERS']['X-Requested-With'] ?? '');
    }

    /**
     * Get client ip address
     *
     * @return string
     */
    public function ip(): string
    {
        return
            $this->hive['HEADERS']['Client-Ip'] ?? (
                isset($this->hive['HEADERS']['X-Forwarded-For']) ?
                    explode(',', $this->hive['HEADERS']['X-Forwarded-For'])[0] :
                    ($_SERVER['REMOTE_ADDR'] ?? '')
            );
    }

    /**
     * Configure framework according to .ini-style file settings;
     * If optional 2nd arg is provided, template strings are interpreted
     *
     * @param  string|array  $sources
     *
     * @return App
     */
    public function config($sources): App
    {
        foreach (reqarr($sources) as $file) {
            preg_match_all(
                '/(?<=^|\n)(?:'.
                    '\[(?<section>.+?)\]|'.
                    '(?<lval>[^\h\r\n;].*?)\h*=\h*'.
                    '(?<rval>(?:\\\\\h*\r?\n|.+?)*)'.
                ')(?=\r?\n|$)/',
                read($file),
                $matches,
                PREG_SET_ORDER
            );

            if ($matches) {
                $sec = 'globals';
                $cmd = [];
                foreach ($matches as $match) {
                    if ($match['section']) {
                        $sec = $match['section'];
                        if (preg_match(
                            '/^(?!(?:global|config|route|map|redirect|resource)s\b)'.
                            '((?:\.?\w)+)/i', $sec, $msec) &&
                            !$this->exists($msec[0])) {
                            $this->set($msec[0], NULL);
                        }

                        preg_match('/^(config|route|map|redirect|resource)s\b/i', $sec, $cmd);

                        continue;
                    }

                    if (!empty($cmd)) {
                        call_user_func_array(
                            [$this, $cmd[1]],
                            array_merge([$match['lval']], str_getcsv(cast($match['rval'])))
                        );
                    } else {
                        $rval = preg_replace('/\\\\\h*(\r?\n)/', '\1', $match['rval']);

                        $args = array_map(
                            function($val) {
                                $val = cast($val);
                                return is_string($val)
                                    ? preg_replace('/\\\\"/','"',$val)
                                    : $val;
                            },
                            // Mark quoted strings with 0x00 whitespace
                            str_getcsv(preg_replace(
                                '/(?<!\\\\)(")(.*?)\1/',
                                "\\1\x00\\2\\1",trim($rval)))
                        );

                        preg_match('/^(?<section>[^:]+)/', $sec, $parts);

                        $custom = 'globals' !== strtolower($parts['section']);

                        call_user_func_array(
                            [$this, 'set'],
                            array_merge(
                                [
                                    ($custom?($parts['section'].'.'):'').
                                    $match['lval']
                                ],
                                $args
                            )
                        );
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Set HTTP status header
     *
     * @param  int $code
     *
     * @return App
     */
    public function status(int $code): App
    {
        $this->hive['STATUS'] = $code;
        $this->hive['TEXT']   = constant(self::class . '::HTTP_' . $code, '');

        return $this;
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
        $this
            ->header('X-Powered-By', $this->hive['PACKAGE'])
            ->header('X-Frame-Options', $this->hive['XFRAME'])
            ->header('X-XSS-Protection', '1; mode=block')
            ->header('X-Content-Type-Options', 'nosniff')
        ;

        if ('GET' === $this->hive['METHOD'] && $secs) {
            $time = microtime(TRUE);
            $this
                ->removeHeader('Pragma')
                ->header('Cache-Control', 'max-age=' . $secs)
                ->header('Expires', gmdate('r', (int) ($time + $secs)))
                ->header('Last-Modified', gmdate('r'))
            ;
        } else {
            $this
                ->header('Pragma', 'no-cache')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Expires', gmdate('r', 0))
            ;
        }

        return $this;
    }

    /**
     * Add headers
     *
     * @param  array       $headers
     *
     * @return App
     */
    public function headers(array $headers): App
    {
        foreach ($headers as $name => $contents) {
            foreach ((array) $contents as $content) {
                $this->header($name, (string) $content);
            }
        }

        return $this;
    }

    /**
     * Add header
     *
     * @param  string       $name
     * @param  string       $content
     *
     * @return App
     */
    public function header(string $name, string $content = ''): App
    {
        if ('' !== $content) {
            $this->hive['RHEADERS'][$name][] = $content;
        }

        return $this;
    }

    /**
     * Get header by name
     *
     * @param  string $name
     *
     * @return array
     */
    public function getHeader(string $name): array
    {
        return $this->hive['RHEADERS'][$name] ?? $this->hive['RHEADERS'][ucfirst($name)] ?? $this->hive['RHEADERS'][strtolower($name)] ?? [];
    }

    /**
     * Get all headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->hive['RHEADERS'];
    }

    /**
     * Remove header
     *
     * @param  string|NULL $name
     *
     * @return App
     */
    public function removeHeader(string $name = NULL): App
    {
        if ($name) {
            unset($this->hive['RHEADERS'][$name], $this->hive['RHEADERS'][ucfirst($name)], $this->hive['RHEADERS'][strtolower($name)]);
        } else {
            $this->hive['RHEADERS'] = [];
        }

        return $this;
    }

    /**
     * Send headers
     *
     * @return App
     */
    public function sendHeader(): App
    {
        if (!$this->hive['CLI'] && !headers_sent()) {
            $headers = $this->hive['RHEADERS'];

            if (isset($headers['Set-Cookie'])) {
                foreach ($headers['Set-Cookie'] as $content) {
                    header("Set-Cookie: $content");
                }

                unset($headers['Set-Cookie']);
            }

            foreach ($headers as $header => $contents) {
                foreach ($contents as $content) {
                    header("$header: $content");
                }
            }

            header($_SERVER['SERVER_PROTOCOL'] . ' ' . $this->hive['STATUS'] . ' ' . $this->hive['TEXT'], TRUE);
        }

        return $this;
    }

    /**
     * Send text/plain header and output content
     *
     * @param  string $content
     * @param  int $kbps
     *
     * @return void
     */
    public function text(string $content, int $kbps = 0): void
    {
        $this
            ->header('Content-Type', 'text/plain;charset=' . ini_get('default_charset'))
            ->header('Content-Length', (string) strlen($content))
            ->sendHeader()
            ->out($content, $kbps)
        ;
    }

    /**
     * Send html header and output content
     *
     * @param  string $content
     * @param  int $kbps
     *
     * @return void
     */
    public function html(string $content, int $kbps = 0): void
    {
        $this
            ->header('Content-Type', 'text/html;charset=' . ini_get('default_charset'))
            ->header('Content-Length', (string) strlen($content))
            ->sendHeader()
            ->out($content, $kbps)
        ;
    }

    /**
     * Set JSON header and encode data
     *
     * @param  array  $data
     * @param  int $kbps
     *
     * @return void
     */
    public function json(array $data, int $kbps = 0): void
    {
        $content = json_encode($data);

        $this
            ->header('Content-Type', 'application/json;charset=' . ini_get('default_charset'))
            ->header('Content-Length', (string) strlen($content))
            ->sendHeader()
            ->out($content, $kbps)
        ;
    }

    /**
     * Cache page mechanism
     *
     * @param  Closure    $callback
     * @param  int         $ttl
     * @param  int $kbps
     *
     * @return void
     */
    public function cache(\Closure $callback, int $ttl, int $kbps = 0): void
    {
        $method  = $this->hive['METHOD'];
        $content = '';

        $this->removeHeader();

        if (in_array($method, ['GET', 'HEAD']) && $ttl) {
            // Only GET and HEAD requests are cacheable
            $hash = hash($method . ' ' . $this->hive['URI']) . '.url';

            if ($this->cacheExists($hash)) {
                $now = microtime(TRUE);

                if (isset($this->hive['HEADERS']['If-Modified-Since']) && strtotime($this->hive['HEADERS']['If-Modified-Since'])+$ttl > $now) {
                    $this
                        ->status(304)
                        ->sendHeader()
                        ->halt()
                    ;

                    return;
                }

                // Retrieve from cache backend
                $cached = $this->cacheGet($hash);

                list($headers, $content) = $cached[0];

                $this
                    ->headers($headers)
                    ->expire((int) ($cached[1] + $ttl - $now))
                    ->sendHeader()
                ;

                unset($cached, $headers);
            } else {
                // Expire HTTP client-cached page
                $this->expire($ttl);
            }
        } else {
            $this->expire(0);
        }

        if ('' === $content) {
            ob_start();
            $callback();
            $content = ob_get_clean();

            if (isset($hash) && !error_get_last()) {
                $headers = $this->hive['RHEADERS'];
                unset($headers['Set-Cookie']);

                // Save to cache backend
                $this->cacheSet($hash, [
                    $headers,
                    $content
                ], $ttl);
            }
        }

        $this->out($content, $kbps);
    }

    /**
     * Throttle output
     *
     * @param  string      $content
     * @param  int $kbps
     * @param  bool $quiet
     *
     * @return void
     */
    public function out(string $content, int $kbps = 0, bool $quiet = NULL): void
    {
        $silent = $quiet ?? $this->hive['QUIET'];

        if ($silent) {
            return;
        }

        if ($kbps) {
            $now = microtime(TRUE);
            $ctr = 0;

            foreach (str_split($content, 1024) as $part) {
                // Throttle output
                $ctr++;

                if ($ctr/$kbps > ($elapsed = microtime(TRUE) - $now) && !connection_aborted()) {
                    usleep((int) (1e6 * ($ctr / $kbps - $elapsed)));
                }

                echo $part;
            }
        } else {
            echo $content;
        }
    }

    /**
     * Match routes against incoming URI
     *
     * @return void
     */
    public function run(): void
    {
        // @codeCoverageIgnoreStart
        if ($this->blacklisted($this->hive['IP'])) {
            // Spammer detected
            $this->error(403);

            return;
        }
        // @codeCoverageIgnoreEnd

        if (!$this->hive['ROUTES']) {
            // No routes defined
            throw new \LogicException('No route specified');
        }

        // Convert to BASE-relative URL
        $path      = $this->hive['PATH'];
        $method    = $this->hive['METHOD'];
        $headers   = $this->hive['HEADERS'];
        $modifier  = $this->hive['CASELESS'] ? 'i' : '';
        $type      = $this->hive['CLI'] ? self::REQ_CLI : ((int) $this->hive['AJAX']) + 1;
        $preflight = FALSE;
        $cors      = NULL;
        $allowed   = [];

        $this->removeHeader();

        if (isset($headers['Origin']) && $this->hive['CORS']['origin']) {
            $cors      = $this->hive['CORS'];
            $preflight = isset($headers['Access-Control-Request-Method']);

            $this
                ->header('Access-Control-Allow-Origin', $cors['origin'])
                ->header('Access-Control-Allow-Credentials', var_export($cors['credentials'], TRUE))
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
                list($handler, $alias) = $route[$method];

                // Capture values of route pattern tokens
                $this->hive['PARAMS']  = $args;
                // Save matching route
                $this->hive['ALIAS']   = $alias;
                $this->hive['PATTERN'] = $pattern;

                // Expose if defined
                if ($cors && $cors['expose']) {
                    $this->header('Access-Control-Expose-Headers', reqstr($cors['expose']));
                }

                if (is_string($handler)) {
                    // Replace route pattern tokens in handler if any
                    $keys    = explode(',', '{' . implode('},{', array_keys($args)) . '}');
                    $handler = str_replace($keys, array_values($args), $handler);

                    if (preg_match('/(.+)\h*(?:->|::)/', $handler, $match) && !class_exists($match[1])) {
                        $this->error(404);

                        return;
                    }
                }

                // Process request
                if (!$this->hive['RAW'] && !$this->hive['BODY']) {
                    $this->hive['BODY'] = file_get_contents('php://input');
                }

                // Call route handler
                try {
                    $this->expire();

                    $this->call($handler, array_slice($args, 1));
                } catch (\BadMethodCallException|\BadFunctionCallException $e) {
                    $this->error(404, $e->getMessage(), $e->getTrace());
                } catch (\Throwable $e) {
                    $this->error(500, $e->getMessage(), $e->getTrace());
                }

                if ('OPTIONS' !== $method) {
                    return;
                }
            }

            $allowed = array_merge($allowed, array_keys($route));
        }

        if (!$allowed) {
            // URL doesn't match any route
            $this->error(404);
        } elseif (!$this->hive['CLI']) {
            // Unhandled HTTP method
            $allowed = reqstr(array_unique($allowed));

            $this->header('Allow', $allowed);

            if ($cors) {
                $this->header('Access-Control-Allow-Methods', 'OPTIONS,' . $allowed);

                if ($cors['headers']) {
                    $this->header('Access-Control-Allow-Headers', reqstr($cors['headers']));
                }

                if ($cors['ttl'] > 0) {
                    $this->header('Access-Control-Max-Age', (string) $cors['ttl']);
                }
            }

            if ('OPTIONS' !== $method) {
                $this->error(405);
            }
        }
    }

    /**
     * Mock HTTP Request
     *
     * @param  string      $pattern
     * @param  array|NULL  $args
     * @param  array|NULL  $headers
     * @param  string|NULL $body
     *
     * @return void
     */
    public function mock(string $pattern, array $args = NULL, array $headers = NULL, string $body = NULL): void
    {
        preg_match('/^([\w]+)(?:\h+([^\h]+))(?:\h+(sync|ajax|cli))?$/', $pattern, $match);

        if (empty($match[2])) {
            throw new \LogicException("Invalid mock pattern: {$pattern}");
        }

        $args    = (array) $args;
        $headers = (array) $headers;
        $method  = $match[1];
        $path    = $this->build($match[2]);
        $uri     = parse_url($path) + ['query'=>'', 'fragment'=>''];

        $this->hive['METHOD']   = $method;
        $this->hive['PATH']     = $uri['path'];
        $this->hive['URI']      = $this->hive['BASE'] . $uri['path'];
        $this->hive['FRAGMENT'] = $uri['fragment'];
        $this->hive['AJAX']     = isset($match[3]) && 'ajax' === $match[3];
        $this->hive['CLI']      = isset($match[3]) && 'cli' === $match[3];
        $this->hive['HEADERS']  = $headers;

        // reset
        $this->clears(['BODY', 'RHEADERS', 'ERROR', 'TEXT', 'STATUS']);

        parse_str($uri['query'], $GLOBALS['_GET']);

        if (in_array($method, ['GET', 'HEAD'])) {
            $GLOBALS['_GET'] = array_merge($GLOBALS['_GET'], $args);
        } else {
            $this->hive['BODY'] = $body ?: http_build_query($args);
        }

        if ($GLOBALS['_GET']) {
            $this->hive['QUERY'] = http_build_query($GLOBALS['_GET']);
            $this->hive['URI'] .= '?' . $this->hive['QUERY'];
        }

        $GLOBALS['_POST']    = 'POST' === $method ? $args : [];
        $GLOBALS['_REQUEST'] = array_merge($GLOBALS['_GET'], $GLOBALS['_POST']);

        foreach ($headers as $key => $val) {
            $_SERVER['HTTP_' . strtr(strtoupper($key), '-', '_')] = $val;
        }

        $this->run();
    }

    /**
     * Reroute to specified URI, or trigger ONREROUTE event if exists
     *
     * @param  string|array|NULL  $url
     * @param  boolean $permanent
     * @param  boolean $die
     *
     * @return void
     */
    public function reroute($url = NULL, bool $permanent = FALSE, bool $die = TRUE): void
    {
        if (!$url) {
            $url = $this->hive['REALM'];
        } elseif (is_array($url)) {
            $url = call_user_func_array([$this, 'alias'], $url);
        } else {
            $url = $this->build($url);
        }

        if ($this->trigger('ONREROUTE', [$url, $permanent])) {
            return;
        }

        if ('/' === $url[0] && (empty($url[1]) || '/' !== $url[1])) {
            $port = $this->hive['PORT'];
            $url  = $this->hive['SCHEME'] . '://' . $this->hive['HOST'] . (in_array($port, [80, 443]) ? '' : (':' . $port)) . $this->hive['BASE'] . $url;
        }

        if ($this->hive['CLI']) {
            $this->mock('GET ' . $url . ' cli');
        } else {
            $this
                ->status($permanent ? 301 : 302)
                ->header('Location', $url)
                ->sendHeader()
                ->halt($die)
            ;
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

        $args   = [];
        $match += [2=>'', ''];

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
    public function alias(string $route, $args = NULL): string
    {
        if (empty($this->hive['ALIASES'][$route])) {
            throw new \LogicException("Route was not exists: {$route}");
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

        if ($this->trigger('ONUNLOAD', [$cwd], TRUE)) {
            return;
        }

        if ($error && in_array($error['type'], [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR])) {
            // Fatal error detected
            $this->error(500, "Fatal error: {$error[message]}", [$error]);
        }
    }

    /**
     * Log error, trigger ONERROR event if exists else
     * display default error page (HTML for synchronous requests, JSON string
     * for AJAX requests)
     *
     * @param  int        $code
     * @param  string     $text
     * @param  array|NULL $trace
     * @param  integer    $level
     *
     * @return void
     */
    public function error(int $code, string $text = '', array $trace = NULL, int $level = 0): void
    {
        if ($this->hive['ERROR']) {
            // Prevent recursive call
            return;
        }

        $this
            ->removeHeader()
            ->status($code)
        ;

        $header   = $this->hive['TEXT'];
        $req      = $this->hive['METHOD'].' '.$this->hive['PATH'];
        $trace    = stringify($trace ?? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));

        if ($this->hive['QUERY']) {
            $req .= '?' . $this->hive['QUERY'];
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

        if ($this->trigger('ONERROR', NULL, TRUE)) {
            return;
        }

        if ($this->hive['QUIET']) {
            return;
        }

        if ($this->hive['AJAX']) {
            $this->json(array_diff_key($this->hive['ERROR'], $this->hive['DEBUG']? [] : ['trace' => 1]));
        } else {
            $trace  = $this->hive['DEBUG'] ? '<pre>' . $trace . '</pre>' : '';
            $this->html(<<<ERR
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
    public function halt(bool $force = FALSE): void
    {
        if ($force || $this->hive['HALT']) {
            die(1);
        }
    }

    /**
     * Return TRUE if IPv4 address exists in DNSBL
     *
     * @param  string $ip
     *
     * @return bool
     */
    public function blacklisted(string $ip): bool
    {
        if ($this->hive['DNSBL'] && !in_array($ip, reqarr($this->hive['EXEMPT'] ?? ''))) {
            // Reverse IPv4 dotted quad
            $rev = implode('.', array_reverse(explode('.', $ip)));
            foreach (reqarr($this->hive['DNSBL']) as $server) {
                // DNSBL lookup
                if (checkdnsrr($rev . '.' . $server, 'A')) {
                    return TRUE;
                }
            }
        }

        return FALSE;
    }

    /**
     * Route resource complement
     *
     * @param  array      $pattern
     * @param  string|object      $class
     * @param  int $ttl
     * @param  int $kbps
     * @param  array|NULL $map
     *
     * @return App
     */
    public function resources(array $patterns, $class, array $map = NULL): App
    {
        foreach ($patterns as $pattern) {
            $this->resource($pattern, $class, $map);
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
     * @param  array|NULL $map
     *
     * @return App
     */
    public function resource(string $pattern, $class, array $map = NULL): App
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

        $type = constant(self::class . '::REQ_' . strtoupper($parts[2] ?? ''), 0);
        $str  = is_string($class);

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
     * @param  string|NULL $map Defaults to self::VERBS
     *
     * @return App
     */
    public function maps(array $patterns, $class, string $map = NULL): App
    {
        foreach ($patterns as $pattern) {
            $this->map($pattern, $class, $map);
        }

        return $this;
    }

    /**
     * Map route to class method
     *
     * @param  string      $pattern Route pattern without method
     * @param  string|object      $class
     * @param  string|NULL $map Defaults to self::VERBS
     *
     * @return App
     */
    public function map($pattern, $class, string $map = NULL): App
    {
        $str    = is_string($class);
        $prefix = $this->hive['PREMAP'];

        foreach (split($map ?? self::VERBS) as $verb) {
            $this->route(
                $verb . ' '. $pattern,
                $str ? "$class->$prefix$verb" : [$class, "$prefix$verb"]
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
    public function redirects(array $patterns, string $url, bool $permanent = TRUE): App
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
    public function redirect(string $pattern, string $url, bool $permanent = TRUE): App
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
     *
     * @return App
     */
    public function routes(array $patterns, $callback): App
    {
        foreach ($patterns as $pattern) {
            $this->route($pattern, $callback);
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
     *
     * @return App
     */
    public function route(string $pattern, $callback): App
    {
        preg_match('/^([\|\w]+)(?:\h+(\w+))?(?:\h+([^\h]+))(?:\h+(sync|ajax|cli))?$/', $pattern, $match);

        if (empty($match[3])) {
            throw new \LogicException("Invalid route pattern: {$pattern}");
        }

        $alias = $match[2] ?? NULL;

        if ($alias) {
            $this->hive['ALIASES'][$alias] = $match[3];
        }

        $type  = constant(self::class . '::REQ_' . strtoupper($match[4] ?? ''), 0);
        $verbs = split(strtoupper($match[1]));

        foreach ($verbs as $verb) {
            $this->hive['ROUTES'][$match[3]][$type][$verb] = [$callback, $alias];
        }

        return $this;
    }

    /**
     * Return string representation of PHP value
     *
     * @param mixed $arg
     * @return string
     */
    public function serialize($arg): string
    {
        switch ($this->hive['SERIALIZER']) {
            case 'igbinary':
                return igbinary_serialize($arg);
            default:
                return serialize($arg);
        }
    }

     /**
     * Return PHP value derived from string
     *
     * @param mixed $arg
     * @return mixed
     */
    public function unserialize($arg)
    {
        switch ($this->hive['SERIALIZER']) {
            case 'igbinary':
                return igbinary_unserialize($arg);
            default:
                return unserialize($arg);
        }
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
    public function call($callback, $args = NULL)
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

        $mArgs = $this->methodArgs($ref, (array) $args);

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
        if ('app' === $id || self::class === $id) {
            return $this;
        } elseif (isset($this->services[$id])) {
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
                array_merge($rule['params'] ?? [], $args)
            );

            $service = new $class(...$cArgs);
        } else {
            $service = new $class;
        }

        if ($rule['keep'] ?? FALSE) {
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
    public function &ref(string $key, bool $add = TRUE)
    {
        $NULL   = NULL;
        $parts  = $this->cut($key);

        $this->startSession('SESSION' === $parts[0]);

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
                    $var =& $NULL;
                    break;
                }
            } else {
                if (!is_array($var)) {
                    $var = [];
                }
                if ($add || array_key_exists($part, $var)) {
                    $var =& $var[$part];
                } else {
                    $var =& $NULL;
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
    public function get(string $key, $default = NULL)
    {
        $var = $this->ref($key, FALSE);

        return $var ?? $default;
    }

    /**
     * Set to hive
     *
     * @param string $key
     * @param mixed $val
     * @param int $ttl For cookie set
     *
     * @return App
     */
    public function set(string $key, $val, int $ttl = NULL): App
    {
        static $serverMap = [
            'URI' => 'REQUEST_URI',
            'METHOD' => 'REQUEST_METHOD',
        ];

        preg_match('/^(?:(?:(?:GET|POST)\b(.+))|COOKIE\.(.+)|SERVICE\.(.+)|(JAR\b))$/', $key, $match);

        if (isset($serverMap[$key])) {
            $_SERVER[$serverMap[$key]] = $val;
        } elseif (isset($match[1]) && $match[1]) {
            $this->set('REQUEST' . $match[1], $val);
        } elseif (isset($match[2]) && $match[2]) {
            $this->set('REQUEST.' . $match[2], $val);

            $this->setCookie($match[2], $val, $ttl);
        } elseif (isset($match[3]) && $match[3]) {
            if (is_string($val)) {
                // assume val is a class name
                $val = ['class' => $val];
            }

            // defaults it's a service
            $val += ['keep' => TRUE];

            if (isset($val['class']) && $val['class'] !== $match[3]) {
                $this->aliases[$val['class']] = $match[3];
            }

            // remove existing service
            $this->services[$match[1]] = NULL;
        }

        $var =& $this->ref($key);
        $var = $val;

        if (isset($match[4]) && $match[4]) {
            $this->hive['JAR']['expire'] -= microtime(TRUE);
        } else {
            switch ($key) {
                case 'CACHE':
                    $this->cache = NULL;
                    break;
                case 'TZ':
                    date_default_timezone_set($val);
                    break;
            }
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
        $var = $this->ref($key, FALSE);

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
            $this->cacheReset();

            return $this;
        }

        preg_match('/^(?:(?:(?:GET|POST)\b(.+))|(?:COOKIE\.(.+))|(SESSION(?:\.(.+))?)|(?:SERVICE\.(.+)))$/', $key, $match);

        if (isset($match[1]) && $match[1]) {
            $this->clear('REQUEST' . $match[1]);
        } elseif (isset($match[2]) && $match[2]) {
            $this->clear('REQUEST.' . $match[2]);

            $this->setCookie($match[2], NULL, strtotime('-1 year'));
        } elseif (isset($match[4]) && $match[4]) {
            $this->startSession();
        } elseif (isset($match[3]) && $match[3]) {
            $this->startSession();

            // End session
            session_unset();
            session_destroy();
            $this->clear('COOKIE.' . session_name());

            $this->sync('SESSION');
        } elseif (isset($match[5]) && $match[5]) {
            // Remove instance too
            $this->services[$match[5]] = NULL;
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

            if (isset($match[3]) && $match[3]) {
                session_commit();
                session_start();
            }
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
     * Clear multiple var
     *
     * @param  array  $vars
     *
     * @return App
     */
    public function clears(array $vars): App
    {
        foreach ($vars as $var) {
            $this->clear($var);
        }

        return $this;
    }

    /**
     * Copy contents of hive variable to another
     *
     * @param string $src
     * @param string $dst
     *
     * @return $this
     */
    public function copy(string $src, string $dst): App
    {
        $ref =& $this->ref($dst);
        $ref = $this->ref($src, FALSE);

        return $this;
    }

    /**
     * Concatenate string to hive string variable
     *
     * @param string $key
     * @param string $prefix
     * @param string $suffix
     * @param boolean $keep
     *
     * @return string|this
     */
    public function concat(string $key, string $suffix, string $prefix = '', bool $keep = FALSE)
    {
        $ref =& $this->ref($key);
        $out = $prefix . $ref . $suffix;

        if ($keep) {
            $ref = $out;

            return $this;
        }

        return $out;
    }

    /**
     * Swap keys and values of hive array variable
     *
     * @param string $key
     * @param boolean $keep
     *
     * @return array|$this
     */
    public function flip(string $key, bool $keep = FALSE)
    {
        $ref =& $this->ref($key);
        $out = array_flip($ref);

        if ($keep) {
            $ref = $out;

            return $this;
        }

        return $out;
    }

    /**
     * Add element to the end of hive array variable
     *
     * @param string $key
     * @param mixed $val
     *
     * @return $this
     */
    public function push(string $key, $val): App
    {
        $ref   =& $this->ref($key);
        $ref[] = $val;

        return $this;
    }

    /**
     * Remove last element of hive array variable
     *
     * @param string $key
     *
     * @return mixed
     */
    public function pop(string $key)
    {
        $ref =& $this->ref($key);

        return array_pop($ref);
    }

    /**
     * Add element to the beginning of hive array variable
     *
     * @param string $key
     * @param mixed $val
     *
     * @return $this
     */
    public function unshift(string $key, $val)
    {
        $ref =& $this->ref($key);

        array_unshift($ref, $val);

        return $this;
    }

    /**
     * Remove first element of hive array variable
     *
     * @param string $key
     *
     * @return mixed
     */
    public function shift(string $key)
    {
        $ref =& $this->ref($key);

        return array_shift($ref);
    }

    /**
     * Merge array with hive array variable
     *
     * @param string $key
     * @param string|array $src
     * @param boolean $keep
     *
     * @return array|$this
     */
    public function merge(string $key, $src, bool $keep = FALSE)
    {
        $ref =& $this->ref($key);

        if (!$ref) {
            $ref = [];
        }

        $out = array_merge($ref, is_string($src) ? $this->get($src, []) : $src);

        if ($keep) {
            $ref = $out;

            return $this;
        }

        return $out;
    }

    /**
     * Extend hive array variable with default values from $src
     *
     * @param string $key
     * @param string|array $src
     * @param boolean $keep
     *
     * @return array|$this
     */
    public function extend(string $key, $src, bool $keep = FALSE)
    {
        $ref =& $this->ref($key);

        if (!$ref) {
            $ref = [];
        }

        $out = array_replace_recursive(is_string($src) ? $this->get($src, []) : $src, $ref);

        if ($keep) {
            $ref = $out;

            return $this;
        }

        return $out;
    }

    /**
     * Check cache item by key
     *
     * @param  string $key
     *
     * @return bool
     */
    public function cacheExists(string $key): bool
    {
        $this->cacheLoad();

        $ndx = $this->hive['SEED'] . '.' . $key;

        switch ($this->cache) {
            case 'apc':
                return apc_exists($ndx);
            case 'apcu':
                return apcu_exists($ndx);
            case 'folder':
                return (bool) $this->cacheParse($key, read($this->cacheRef . $ndx));
            case 'memcached':
                return (bool) $this->cacheRef->get($ndx);
            case 'redis':
                return $this->cacheRef->exists($ndx);
            case 'wincache':
                return wincache_ucache_exists($ndx);
            case 'xcache':
                return xcache_exists($ndx);
            default:
                return FALSE;
        }
    }

    /**
     * Get cache item content
     *
     * @param  string $key
     *
     * @return array
     */
    public function cacheGet(string $key): array
    {
        $this->cacheLoad();

        $ndx = $this->hive['SEED'] . '.' . $key;

        switch ($this->cache) {
            case 'apc':
                $raw = apc_fetch($ndx);
                break;
            case 'apcu':
                $raw = apcu_fetch($ndx);
                break;
            case 'folder':
                $raw = read($this->cacheRef . $ndx);
                break;
            case 'memcached':
                $raw = $this->cacheRef->get($ndx);
                break;
            case 'redis':
                $raw = $this->cacheRef->get($ndx);
                break;
            case 'wincache':
                $raw = wincache_ucache_get($ndx);
                break;
            case 'xcache':
                $raw = xcache_get($ndx);
                break;
            default:
                $raw = NULL;
                break;
        }

        return $this->cacheParse($key, (string) $raw);
    }

    /**
     * Set cache item content
     *
     * @param  string $key
     * @param  mixed $val
     * @param  int $ttl
     *
     * @return App
     */
    public function cacheSet(string $key, $val, int $ttl = 0): App
    {
        $this->cacheLoad();

        $ndx = $this->hive['SEED'] . '.' . $key;
        $content = $this->cacheCompact($val, (int) microtime(TRUE), $ttl);

        switch ($this->cache) {
            case 'apc':
                apc_store($ndx, $content, $ttl);
                break;
            case 'apcu':
                apcu_store($ndx, $content, $ttl);
                break;
            case 'folder':
                write($this->cacheRef . str_replace(['/', '\\'], '', $ndx), $content);
                break;
            case 'memcached':
                $this->cacheRef->set($ndx, $content);
                break;
            case 'redis':
                $this->cacheRef->set($ndx, $content, array_filter(['ex'=>$ttl]));
                break;
            case 'wincache':
                wincache_ucache_set($ndx, $content, $ttl);
                break;
            case 'xcache':
                xcache_set($ndx, $content, $ttl);
                break;
        }

        return $this;
    }

    /**
     * Remove cache item
     *
     * @param  string $key
     *
     * @return bool
     */
    public function cacheClear(string $key): bool
    {
        $this->cacheLoad();

        $ndx = $this->hive['SEED'] . '.' . $key;

        switch ($this->cache) {
            case 'apc':
                return apc_delete($ndx);
            case 'apcu':
                return apcu_delete($ndx);
            case 'folder':
                return delete($this->cacheRef . $ndx);
            case 'memcached':
                return $this->cacheRef->delete($ndx);
            case 'redis':
                return (bool) $this->cacheRef->del($ndx);
            case 'wincache':
                return wincache_ucache_delete($ndx);
            case 'xcache':
                return xcache_unset($ndx);
            default:
                return FALSE;
        }
    }

    /**
     * Reset cache
     *
     * @param  string $suffix
     *
     * @return bool
     */
    public function cacheReset(string $suffix = ''): bool
    {
        $this->cacheLoad();

        $regex = '/' . preg_quote($this->hive['SEED'], '/') . '\..+' . preg_quote($suffix, '/') . '/';

        switch ($this->cache) {
            case 'apc':
                $info = apc_cache_info('user');
                if ($info && isset($info['cache_list']) && $info['cache_list']) {
                    $key = array_key_exists('info', $info['cache_list'][0]) ? 'info' : 'key';
                    foreach ($info['cache_list'] as $item) {
                        if (preg_match($regex, $item[$key])) {
                            apc_delete($item[$key]);
                        }
                    }
                }

                return TRUE;
            case 'apcu':
                $info = apcu_cache_info(FALSE);
                if ($info && isset($info['cache_list']) && $info['cache_list']) {
                    $key = array_key_exists('info', $info['cache_list'][0]) ? 'info' : 'key';
                    foreach ($info['cache_list'] as $item) {
                        if (preg_match($regex, $item[$key])) {
                            apcu_delete($item[$key]);
                        }
                    }
                }

                return TRUE;
            case 'folder':
                $files = glob($this->cacheRef . $this->hive['SEED'] . '*' . $suffix) ?: [];
                foreach ($files as $file) {
                    unlink($file);
                }

                return TRUE;
            case 'memcached':
                $keys = preg_grep($regex, $this->cacheRef->getallkeys());
                foreach ($keys as $key) {
                    $this->cacheRef->delete($key);
                }

                return TRUE;
            case 'redis':
                $keys = $this->cacheRef->keys($this->hive['SEED'] . '*' . $suffix);
                foreach($keys as $key) {
                    $this->cacheRef->del($key);
                }

                return TRUE;
            case 'wincache':
                $info = wincache_ucache_info();
                $keys = preg_grep($regex, array_column($info['ucache_entries'], 'key_name'));
                foreach ($keys as $key) {
                    wincache_ucache_delete($key);
                }

                return TRUE;
            case 'xcache':
                xcache_unset_by_prefix($this->hive['SEED'] . '.');

                return TRUE;
            default:

                return TRUE;
        }
    }

    /**
     * Get used cache
     *
     * @return array
     */
    public function cacheDef(): array
    {
        $this->cacheLoad();

        return [$this->cache, $this->cacheRef];
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
        if (2 === count($cut = explode('->', $callback))) {
            $callback = [$this->service($cut[0]), $cut[1]];
        } elseif (2 === count($cut = explode('::', $callback))) {
            $callback = $cut;
        }

        return $callback;
    }

    /**
     * Build method arguments
     *
     * @param  \ReflectionFunctionAbstract $ref
     * @param  array                       $args
     *
     * @return array
     */
    protected function methodArgs(\ReflectionFunctionAbstract $ref, array $args = []): array
    {
        $result  = [];
        $pArgs = array_filter($args, 'is_numeric', ARRAY_FILTER_USE_KEY);

        foreach ($ref->getParameters() as $param) {
            $name = $param->name;

            if (isset($args[$name])) {
                $val = $args[$name];

                if (is_string($val)) {
                    // assume it is a class name
                    if (class_exists($val)) {
                        $val = $this->service($val);
                    } elseif (preg_match('/(.+)?%(.+)%(.+)?/', $val, $match)) {
                        // assume it does exist in hive
                        $ref = $this->ref($match[2], FALSE);
                        if (isset($ref)) {
                            $val = ($match[1] ?? '') . $ref . ($match[3] ?? '');
                        } else {
                            // it is a service alias
                            $val = $this->service($match[2]);
                        }
                    }
                }

                $result[] = $val;
            } elseif ($param->isVariadic()) {
                $result = array_merge($result, $pArgs);
            } elseif ($refClass = $param->getClass()) {
                $result[] = $this->service($refClass->name);
            } elseif ($pArgs) {
                $result[] = array_shift($pArgs);
            } elseif ($param->isDefaultValueAvailable()) {
                $result[] = $param->getDefaultValue();
            }
        }

        return $result;
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
    protected function routeMatch(string $pattern, string $path, string $modifier, array &$match = NULL): bool
    {
        $wild = preg_replace_callback('/\{(\w+)(?:\:(?:(alnum|alpha|digit|lower|upper|word)|(\w+)))?\}/', function($m) {
            // defaults to alnum
            return '(?<' . $m[1] . '>[[:' . (isset($m[2]) && $m[2] ? $m[2] : 'alnum') . ':]]+)';
        }, $pattern);

        $regex = '~^' . $wild. '$~' . $modifier;

        return (bool) preg_match($regex, $path, $match);
    }

    /**
     * Start session if not started yet
     *
     * @param bool $start
     *
     * @return void
     */
    protected function startSession(bool $start = TRUE)
    {
        if ($start && !headers_sent() && session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->sync('SESSION');
    }

    /**
     * Sync PHP global with corresponding hive key
     *
     * @param  string $key
     *
     * @return array|NULL
     */
    protected function sync(string $key): ?array
    {
        $this->hive[$key] =& $GLOBALS["_$key"];

        return $this->hive[$key];
    }

    /**
     * Format to cookie header syntax
     *
     * @param string   $name
     * @param string   $value
     * @param int|NULL $ttl
     */
    protected function setCookie(string $name, string $value = NULL, int $ttl = NULL): void
    {
        $jar    = $this->hive['JAR'];
        $cookie = $this->cookiefy($name) . '=' . urlencode($value ?? '');
        $expire = $ttl ?? $jar['expire'];

        if ($expire) {
            $cookie .= '; Expires=' . gmdate('D, d M Y H:i:s') . ' GMT';
        }

        if ($jar['domain']) {
            $cookie .= '; Domain=' . $jar['domain'];
        }

        if ($jar['path']) {
            $cookie .= '; Path=' . $jar['path'];
        }

        if ($jar['secure']) {
            $cookie = '__Secure-' . $cookie . '; Secure';
        }

        if ($jar['httponly']) {
            $cookie .= '; HttpOnly';
        }

        $this->header('Set-Cookie', $cookie);
    }

    /**
     * Build valid cookie name from dot based name
     *
     * @param  string $name
     *
     * @return string
     */
    protected function cookiefy(string $name): string
    {
        $parts = explode('.', $name);
        $cname = array_shift($parts);

        return $cname . ($parts ? '[' . implode('][', $parts) . ']' : '');
    }

    /**
     * Trigger event if exists
     *
     * @param  string     $event
     * @param  array|NULL $args
     * @param  bool       $once
     *
     * @return bool
     */
    protected function trigger(string $event, array $args = NULL, bool $once = FALSE): bool
    {
        if (isset($this->hive[$event])) {
            $handler = $this->hive[$event];

            if ($once) {
                $this->hive[$event] = NULL;
            }

            $result  = $this->call($handler, $args);

            return FALSE !== $result;
        }

        return FALSE;
    }

    /**
     * Load cache by dsn
     *
     * @return void
     */
    protected function cacheLoad(): void
    {
        $dsn = $this->hive['CACHE'] ?? '';

        if ($this->cache || !$dsn) {
            return;
        }

        $parts = array_map('trim', explode('=', $dsn) + [1 => '']);
        $auto  = '/^(apc|apcu|wincache|xcache)/';

        // Fallback to filesystem cache
        $fallback = 'folder';
        $folder   = $this->hive['TEMP'] . 'cache/';

        if ('redis' === $parts[0] && $parts[1] && extension_loaded('redis')) {
            list($host, $port, $db) = explode(':', $parts[1]) + [1=>0, 2=>NULL];

            $this->cache    = 'redis';
            $this->cacheRef = new \Redis();

            try {
                $this->cacheRef->connect($host, $port ?: 6379, 2);

                if ($db) {
                    $this->cacheRef->select($db);
                }
            } catch(\Throwable $e) {
                $this->cache    = $fallback;
                $this->cacheRef = $folder;
            }
        } elseif ('memcached' === $parts[0] && $parts[1] && extension_loaded('memcached')) {
            $servers = explode(';', $parts[1]);

            $this->cache    = 'memcached';
            $this->cacheRef = new \Memcached();

            foreach ($servers as $server) {
                list($host, $port) = explode(':', $server) + [1=>11211];

                $this->cacheRef->addServer($host, $port);
            }
        } elseif ('folder' === $parts[0] && $parts[1]) {
            $this->cache    = 'folder';
            $this->cacheRef = $parts[1];
        } elseif (preg_match($auto, $dsn, $parts)) {
            $this->cache    = $parts[1];
            $this->cacheRef = NULL;
        } elseif ('auto' === strtolower($dsn) && $grep = preg_grep($auto, array_map('strtolower', get_loaded_extensions()))) {
            $this->cache    = current($grep);
            $this->cacheRef = NULL;
        } else {
            $this->cache    = $fallback;
            $this->cacheRef = $folder;
        }

        if ($fallback === $this->cache) {
            mkdir($this->cacheRef);
        }
    }

    /**
     * Compact cache content and time
     *
     * @param  mixed $content
     * @param  int    $time
     * @param  int    $ttl
     *
     * @return string
     */
    protected function cacheCompact($content, int $time, int $ttl): string
    {
        return $this->serialize([$content, $time, $ttl]);
    }

    /**
     * Parse raw cache data
     *
     * @param  string $key
     * @param  string $raw
     *
     * @return void
     */
    protected function cacheParse(string $key, string $raw): array
    {
        if ($raw) {
            list($val, $time, $ttl) = (array) $this->unserialize($raw);

            if (0 === $ttl || $time+$ttl > microtime(TRUE)) {
                return [$val, $time, $ttl];
            }

            $this->cacheClear($key);
        }

        return [];
    }

    /**
     * Return the parts of specified hive key
     *
     * @param  string $key
     *
     * @return array
     */
    protected function cut($key): array
    {
        return preg_split('/\[\h*[\'"]?(.+?)[\'"]?\h*\]|\./', $key, 0, PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE);
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
}

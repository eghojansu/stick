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
 * Main framework class
 */
final class App implements \ArrayAccess
{
    /** Framework details */
    const
        PACKAGE = 'Stick-PHP',
        VERSION = '0.1.0-beta';

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

    /** Events */
    const
        EVENT_BOOT = 'app.boot',
        EVENT_SHUTDOWN = 'app.shutdown',
        EVENT_PREROUTE = 'app.preroute',
        EVENT_POSTROUTE = 'app.postroute',
        EVENT_REROUTE = 'app.reroute',
        EVENT_ERROR = 'app.error';

    /** @var array */
    const RULE_DEFAULT = [
        'class' => null,
        'use' => null,
        'args' => null,
        'service' => true,
        'boot' => null,
    ];

    /** @var array Initial value */
    private $init;

    /** @var array Variables hive */
    private $hive;

    /**
     * Class constructor
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
            if ($header = Helper::cutafter('HTTP_', $key)) {
                $headers[Helper::toHKey($header)] = $val;
            }
        }

        $cli = PHP_SAPI === 'cli';
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $domain = $_SERVER['SERVER_NAME'] ?? gethostname();
        $urireq = $_SERVER['REQUEST_URI'] ?? '/';
        $uridomain = preg_match('~^\w+://~', $urireq) ? '' : '//' . $domain;
        $uri = parse_url($uridomain . $urireq) + ['query'=>'', 'fragment'=>''];
        $base = $cli ? '' : rtrim(Helper::fixslashes(dirname($_SERVER['SCRIPT_NAME'])), '/');
        $path = Helper::cutafter($base, $uri['path'], $uri['path']);
        $secure = ($_SERVER['HTTPS'] ?? '') === 'on' || ($headers['X-Forwarded-Proto'] ?? '') === 'https';
        $scheme = $secure ? 'https' : 'http';
        $port = $headers['X-Forwarded-Port'] ?? $_SERVER['SERVER_PORT'] ?? 80;

        $_SERVER['REQUEST_URI'] = $uri['path'] . rtrim('?' . $uri['query'], '?') . rtrim('#' . $uri['fragment'], '#');
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
            'JAR' => [
                'EXPIRE' => 0,
                'PATH' => $base ?: '/',
                'DOMAIN' => (strpos($domain, '.') === false || filter_var($domain, FILTER_VALIDATE_IP)) ? '' : $domain,
                'SECURE' => $secure,
                'HTTPONLY' => true
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
                'MATCH' => '',
                'METHOD' => $method,
                'PARAMS' => [],
                'PATH' => urldecode($path),
                'PATTERN' => '',
                'PROTOCOL' => $_SERVER['SERVER_PROTOCOL'],
                'PORT' => $port,
                'QUERY' => $uri['query'],
                'REALM' => $scheme . '://' . $_SERVER['SERVER_NAME'] . ($port && !in_array($port, [80, 443])? (':' . $port):'') . $_SERVER['REQUEST_URI'],
                'ROOT' => $_SERVER['DOCUMENT_ROOT'],
                'SCHEME' => $scheme,
                'URI' => $_SERVER['REQUEST_URI'],
            ],
            'SEED' => Helper::hash($_SERVER['SERVER_NAME'] . $base),
            'SYS' => [
                'BOOTED' => false,
                'ROUTES' => [],
                'ALIASES' => [],
                'SERVICES' => [],
                'SRULES' => [],
                'SALIASES' => [],
                'LISTENERS' => [],
            ],
            'TEMP' => './var/',
            'TRACE' => is_dir($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : dirname($_SERVER['DOCUMENT_ROOT']),
            'TZ' => date_default_timezone_get(),
            'VERSION' => self::VERSION,
            'XFRAME' => 'SAMEORIGIN',
        ];

        // Sync PHP globals with corresponding hive keys
        array_map([$this, 'sync'], explode('|', self::GLOBALS));

        // register core services
        $this->rules([
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
                    'logLevelThreshold' => Logger::LEVEL_DEBUG,
                ],
            ],
        ]);

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
     * Return current user agent
     *
     * @return string
     */
    public function agent(): string
    {
        $use = $this->hive['REQ']['HEADERS'];

        return $use['X-Operamini-Phone-Ua'] ?? $use['X-Skyfire-Phone'] ?? $use['User-Agent'] ?? '';
    }

    /**
     * Return ajax status
     *
     * @return bool
     */
    public function ajax(): bool
    {
        $use = $this->hive['REQ']['HEADERS'];

        return strtolower($use['X-Requested-With'] ?? '') === 'xmlhttprequest';
    }

    /**
     * Return ip address
     *
     * @return string
     */
    public function ip(): string
    {
        $use = $this->hive['REQ']['HEADERS'];

        return $use['Client-Ip'] ?? (isset($use['X-Forwarded-For']) ? Helper::split($use['X-Forwarded-For'])[0] : $_SERVER['REMOTE_ADDR'] ?? '');
    }

    /**
     * Check if ip is blacklisted
     *
     * @param  string|null $ip
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
                if (checkdnsrr($rev . '.' . $server, 'A')) {
                    return true;
                }
            }
            // @codeCoverageIgnoreEnd
        }

        return false;
    }

    /**
     * Mimic composer autoload behaviour, can be used as class autoloader
     *
     * @param  string $class
     *
     * @return mixed
     */
    public function autoload($class)
    {
        $logicalPath = Helper::fixslashes($class) . '.php';
        $subPath = $class;

        while (false !== $lastPos = strrpos($subPath, '\\')) {
            $subPath = substr($subPath, 0, $lastPos);
            $paths = $this->hive['NAMESPACE'][$subPath . '\\'] ?? null;

            if ($paths) {
                $pathEnd = substr($logicalPath, $lastPos + 1);

                foreach (Helper::reqarr($paths) as $dir) {
                    if (file_exists($file = $dir . $pathEnd)) {
                        Helper::exinclude($file);

                        return true;
                    }
                }
            }
        }

        // fallback (root)
        foreach (Helper::reqarr($this->hive['NAMESPACE']['\\'] ?? []) as $dir) {
            if (file_exists($file = $dir . $logicalPath)) {
                Helper::exinclude($file);

                return true;
            }
        }
    }

    /**
     * Override request method
     *
     * @return App
     */
    public function overrideRequestMethod(): App
    {
        $method = $this->hive['REQ']['HEADERS']['X-HTTP-Method-Override'] ?? $_SERVER['REQUEST_METHOD'];

        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        $this->hive['REQ']['METHOD'] = $_SERVER['REQUEST_METHOD'] = $method;

        return $this;
    }

    /**
     * Emulate CLI request
     *
     * @return App
     */
    public function emulateCliRequest(): App
    {
        if (!$this->hive['REQ']['CLI']) {
            return $this;
        }

        if (!isset($_SERVER['argv'][1])) {
            $_SERVER['argc']++;
            $_SERVER['argv'][1] = '/';
        }

        if ($_SERVER['argv'][1][0] === '/') {
            $req = $_SERVER['argv'][1];
        } else {
            $req = '';
            $opts = '';

            for ($i = 1; $i < $_SERVER['argc']; $i++) {
                $arg = $_SERVER['argv'][$i];

                if ($arg[0] === '-') {
                    $m = explode('=', $arg);
                    if ($arg[1] === '-') {
                        $opts .= '&' . urlencode(substr($m[0], 2)) . '=';
                    } else {
                        $opts .= '&' . implode('=&', array_map('urlencode', str_split(substr($m[0], 1)))) . '=';
                    }
                    $opts = ltrim($opts, '&') . ($m[1] ?? '');
                } else {
                    $req .= '/' . $arg;
                }
            }

            $req = '/' . ltrim(rtrim($req . '?'. $opts, '?'), '/');
        }

        $uri = parse_url($req) + ['query'=>'', 'fragment'=>''];
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
     * Set route
     *
     * @param  string           $pattern
     * @param  string|callback  $callback
     * @param  int              $ttl
     * @param  int              $kbps
     *
     * @return App
     */
    public function route(string $pattern, $callback, int $ttl = 0, int $kbps = 0): App
    {
        preg_match('/^([\|\w]+)(?:\h+(\w+))?(?:\h+([^\h]+))(?:\h+(sync|ajax|cli))?$/i', $pattern, $match);

        $alias = $match[2] ?? null;
        $path = $match[3] ?? '';

        if (!$alias && isset($path) && isset($this->hive['SYS']['ALIASES'][$path])) {
            $alias = $path;
            $path = $this->hive['SYS']['ALIASES'][$alias];
        }

        if (!$path) {
            throw new \LogicException('Route pattern should contain at least request method and path, given "' . $pattern . '"');
        }

        if ($alias) {
            $this->hive['SYS']['ALIASES'][$alias] = $path;
        }

        $type = Helper::constant(self::class . '::REQ_' . strtoupper($match[4] ?? ''), 0);

        foreach (Helper::split(strtoupper($match[1])) as $verb) {
            $this->hive['SYS']['ROUTES'][$path][$type][$verb] = [$callback, $ttl, $kbps, $alias];
        }

        return $this;
    }

    /**
     * Register pattern to handle ReST
     *
     * @param  string           $pattern
     * @param  string|object    $class
     * @param  int              $ttl
     * @param  int              $kbps
     *
     * @return App
     */
    public function map(string $pattern, $class, int $ttl = 0, int $kbps = 0): App
    {
        $str = is_string($class);
        $prefix = $this->hive['PREMAP'];

        foreach (Helper::split(self::VERBS) as $verb) {
            $this->route($verb . ' ' . $pattern, $str ? $class . '->' . $prefix . $verb : [$class, $prefix . $verb], $ttl, $kbps);
        }

        return $this;
    }

    /**
     * Redirect pattern to specified url
     *
     * @param  string               $pattern
     * @param  string|array|null    $url
     * @param  bool                 $permanent
     *
     * @return App
     */
    public function redirect(string $pattern, $url, bool $permanent = true): App
    {
        return $this->route($pattern, function() use ($url, $permanent) {
            $this->reroute($url, $permanent);
        });
    }

    /**
     * Set response status code
     *
     * @param  int    $code
     *
     * @return App
     */
    public function status(int $code): App
    {
        $status = Helper::constant(self::class . '::HTTP_' . $code);

        if (!$status) {
            throw new \DomainException('Unsupported http code: ' . $code);
        }

        $this->hive['RES']['CODE'] = $code;
        $this->hive['RES']['STATUS'] = $status;

        return $this;
    }

    /**
     * Set expire headers
     *
     * @param  int $secs
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
            $this->hive['RES']['HEADERS']['Cache-Control'] = 'max-age=' . $secs;
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
     * Build url from given alias
     *
     * @param  string     $alias
     * @param  array|null $args
     *
     * @return string
     */
    public function alias(string $alias, array $args = null): string
    {
        if (!isset($this->hive['SYS']['ALIASES'][$alias])) {
            throw new \LogicException('Route "' . $alias . '" does not exists');
        }

        $lookup = (array) $args;

        return preg_replace_callback('/\{(\w+)(?:\:\w+)?\}/', function($m) use ($lookup) {
            return array_key_exists($m[1], $lookup) ? $lookup[$m[1]] : $m[0];
        }, $this->hive['SYS']['ALIASES'][$alias]);
    }

    /**
     * Build url from string expression, example: @foo#bar(argument=value)
     *
     * @param  string $expr
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

        return $this->alias($route, $args) . $fragment;
    }

    /**
     * Reroute to given url or alias or do reload current url
     *
     * @param  string|array|null    $url
     * @param  bool                 $permanent
     *
     * @return void
     */
    public function reroute($url = null, bool $permanent = false): void
    {
        $use = $url ? (is_array($url) ? $this->alias(...$url) : $this->build($url)) : $this->hive['REQ']['REALM'];

        if ($this->trigger(self::EVENT_REROUTE, [$use, $permanent])) {
            return;
        }

        if ($use[0] === '/' && (empty($use[1]) || $use[1] !== '/')) {
            $port = $this->hive['REQ']['PORT'];
            $use = $this->hive['REQ']['SCHEME'] . '://' . $this->hive['REQ']['HOST'] . (in_array($port, [80, 443]) ? '' : (':' . $port)) . $this->hive['REQ']['BASE'] . $use;
        }

        if ($this->hive['REQ']['CLI']) {
            $this->mock('GET ' . $use . ' cli');

            return;
        }

        $this->hive['RES']['HEADERS']['Location'] = $use;
        $this->status($permanent ? 301 : 302)->sendHeaders();
    }

    /**
     * Start route matching
     *
     * @return void
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

            $this->error(500, $e->getMessage(), $e->getTrace());
        }
    }

    /**
     * Mock request
     *
     * @param  string      $pattern
     * @param  array|null  $args
     * @param  array|null  $headers
     * @param  string|null $body
     *
     * @return void
     */
    public function mock(string $pattern, array $args = null, array $headers = null, string $body = null): void
    {
        preg_match('/^([\w]+)(?:\h+([^\h]+))(?:\h+(sync|ajax|cli))?$/i', $pattern, $match);

        if (empty($match[2])) {
            throw new \LogicException('Mock pattern should contain at least request method and path, given "' . $pattern . '"');
        }

        $args = (array) $args;
        $headers = (array) $headers;
        $method = strtoupper($match[1]);
        $path = $this->build($match[2]);
        $mode = strtolower($match[3] ?? '');
        $uri = parse_url($path) + ['query'=>'', 'fragment'=>''];

        $this->clear('REQ');

        $this->hive['REQ']['METHOD'] = $method;
        $this->hive['REQ']['PATH'] = $uri['path'];
        $this->hive['REQ']['URI'] = $this->hive['REQ']['BASE'] . $uri['path'];
        $this->hive['REQ']['FRAGMENT'] = $uri['fragment'];
        $this->hive['REQ']['AJAX'] = $mode === 'ajax';
        $this->hive['REQ']['CLI'] = $mode === 'cli';
        $this->hive['REQ']['HEADERS'] = $headers;

        parse_str($uri['query'], $GLOBALS['_GET']);

        if (in_array($method, ['GET', 'HEAD'])) {
            $GLOBALS['_GET'] = array_merge($GLOBALS['_GET'], $args);
        } else {
            $this->hive['REQ']['BODY'] = $body ?? http_build_query($args);
        }

        if ($GLOBALS['_GET']) {
            $this->hive['REQ']['QUERY'] = http_build_query($GLOBALS['_GET']);
            $this->hive['REQ']['URI'] .= '?' . $this->hive['REQ']['QUERY'];
        }

        $GLOBALS['_POST'] = 'POST' === $method ? $args : [];
        $GLOBALS['_REQUEST'] = array_merge($GLOBALS['_GET'], $GLOBALS['_POST']);

        foreach ($headers as $key => $val) {
            $_SERVER['HTTP_' . Helper::fromHKey($key)] = $val;
        }

        $this->run();
    }

    /**
     * Send error
     *
     * @param int       $code
     * @param string    $message
     * @param array     $trace
     * @param string    $level
     *
     * @return void
     */
    public function error(int $code, string $message = null, array $trace = null, string $level = Logger::LEVEL_DEBUG): void
    {
        $this->clear('RES')->status($code);

        $status = $this->hive['RES']['STATUS'];
        $req = rtrim($this->hive['REQ']['METHOD'] . ' ' . $this->hive['REQ']['PATH'] . '?' . $this->hive['REQ']['QUERY'], '?');
        $text = $message ?? 'HTTP ' . $code . ' (' . $req . ')';
        $sTrace = $this->trace($trace);

        $this->service('logger')->log($level, $text . PHP_EOL . $sTrace);

        $prev = $this->hive['ERROR'];
        $this->hive['ERROR'] = [
            'status' => $status,
            'code' => $code,
            'text' => $text,
            'trace' => $sTrace,
        ];

        $this->expire(-1);

        if ((!$prev && $this->trigger(self::EVENT_ERROR, [$this->hive['ERROR']])) || $this->hive['QUIET']) {
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
            $out = '<!DOCTYPE html>' .
                '<html>' .
                '<head>' .
                  '<meta charset="' . $this->hive['ENCODING'] . '">' .
                  '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">' .
                  '<title>' . $code . ' ' . $status . '</title>' .
                '</head>' .
                '<body>' .
                  '<h1>' . $status . '</h1>' .
                  '<p>' . $text . '</p>' .
                  ($this->hive['DEBUG'] ? '<pre>' . $sTrace . '</pre>' : '') .
                '</body>' .
                '</html>'
            ;
        }

        $this->hive['RES']['HEADERS']['Content-Type'] = $type;
        $this->sendHeaders();
        echo $out;
    }

    /**
     * Framework shutdown sequence
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

        if ($this->trigger(self::EVENT_SHUTDOWN, [$cwd])) {
            return;
        }

        if ($error && in_array($error['type'], [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR])) {
            // Fatal error detected
            $this->error(500, 'Fatal error: ' . $error['message'], [$error]);
        }
    }

    /**
     * Set event listener
     *
     * Listener should return true to give control back to the caller
     *
     * @param  string   $event
     * @param  callable $listener
     *
     * @return App
     */
    public function on(string $event, callable $listener): App
    {
        $ref =& $this->ref('SYS.LISTENERS.' . $event);

        if ($ref) {
            $ref[] = $listener;
        } else {
            $ref = [$listener];
        }

        return $this;
    }

    /**
     * Trigger event listener
     *
     * @param  string     $event
     * @param  array|null $args
     *
     * @return bool
     */
    public function trigger(string $event, array $args = null): bool
    {
        $listeners = $this->ref('SYS.LISTENERS.' . $event, false);

        if (!$listeners) {
            return false;
        }

        foreach ($listeners as $listener) {
            if ($this->call($listener, $args) === true) {
                return false;
            }
        }

        return true;
    }

    /**
     * Grab class->method declaration from a string
     *
     * @param  string   $def
     * @param  bool     $create
     *
     * @return mixed
     */
    public function grab(string $def, bool $create = true)
    {
        $obj = explode('->', $def);
        $static = explode('::', $def);
        $grabbed = $def;

        if (count($obj) === 2) {
            $grabbed = [$create ? $this->service($obj[0]) : $obj[0], $obj[1]];
        } elseif (count($static) === 2) {
            $grabbed = $static;
        }

        return $grabbed;
    }

    /**
     * Call method
     *
     * @param  mixed      $def
     * @param  array|null $args
     *
     * @return mixed
     */
    public function call($def, array $args = null)
    {
        $func = is_string($def) ? $this->grab($def) : $def;

        if (!is_callable($func)) {
            if (is_array($func)) {
                throw new \BadMethodCallException('Call to undefined method ' . get_class($func[0]) . '::' . $func[1]);
            } else {
                throw new \BadFunctionCallException('Call to undefined function ' . $func);
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
     * Register service rules
     *
     * @param  array  $rules
     *
     * @return App
     */
    public function rules(array $rules): App
    {
        foreach ($rules as $id => $rule) {
            $this->rule($id, $rule);
        }

        return $this;
    }

    /**
     * Set service rule
     *
     * @param  string $id
     * @param  mixed  $rule
     *
     * @return array
     */
    public function rule(string $id, $rule = null): App
    {
        $ref =& $this->ref('SYS.SERVICES.' . $id);
        $ref = null;

        if (is_object($rule)) {
            $use = ['class' => get_class($rule)];
            $ref = $rule;
        } elseif (is_string($rule)) {
            $use = ['class' => $rule];
        } else {
            $use = $rule;
        }

        unset($ref);

        $ref =& $this->ref('SYS.SRULES.' . $id);
        $ref = array_filter(array_replace(self::RULE_DEFAULT, [
            'class' => $id,
        ], $use ?? []), function($val) {
            return $val !== null;
        });

        $this->hive['SYS']['SALIASES'][$ref['class']] = $id;

        return $this;
    }

    /**
     * Create/get instance of a class
     *
     * @param  string     $id
     * @param  array|null $args
     *
     * @return mixed
     */
    public function service(string $id, array $args = null)
    {
        if (in_array($id, ['app', self::class])) {
            return $this;
        } elseif (isset($this->hive['SYS']['SERVICES'][$id])) {
            return $this->hive['SYS']['SERVICES'][$id];
        } elseif (isset($this->hive['SYS']['SALIASES'][$id])) {
            $id = $this->hive['SYS']['SALIASES'][$id];

            if (isset($this->hive['SYS']['SERVICES'][$id])) {
                return $this->hive['SYS']['SERVICES'][$id];
            }
        }

        return $this->create($id, $args);
    }

    /**
     * Create instance of a class
     *
     * @param  string     $id
     * @param  array|null $args
     *
     * @return mixed
     */
    public function create(string $id, array $args = null)
    {
        $rule = $this->ref('SYS.SRULES.' . $id, false);
        $use = ($rule ?? []) + [
            'class' => $id,
            'args' => $args,
            'service' => false,
        ] + self::RULE_DEFAULT;
        $ref = new \ReflectionClass($use['use'] ?? $use['class']);

        if (!$ref->isInstantiable()) {
            throw new \LogicException('Unable to create instance. Please provide instantiable version of ' . $ref->name);
        }

        if ($ref->hasMethod('__construct')) {
            $instance = $ref->newInstanceArgs($this->resolveArgs($ref->getMethod('__construct'), $use['args']));
        } else {
            $instance = $ref->newInstance();
        }

        unset($ref);

        if ($use['boot'] && is_callable($use['boot'])) {
            call_user_func_array($use['boot'], [$instance, $this]);
        }

        if ($use['service']) {
            $ref =& $this->ref('SYS.SERVICES.' . $id);
            $ref = $instance;
        }

        return $instance;
    }

    /**
     * Return app hive
     *
     * @return array
     */
    public function hive(): array
    {
        return $this->hive;
    }

    /**
     * Load configuration from a file
     *
     * @param  string $file
     *
     * @return App
     */
    public function config(string $file): App
    {
        foreach (file_exists($file) ? Helper::exrequire($file, []) : [] as $key => $val) {
            $lkey = strtolower($key);

            if (in_array($lkey, ['configs', 'routes', 'maps', 'redirects', 'rules', 'listeners'])) {
                $call = $lkey === 'listeners' ? 'on' : substr($lkey, 0, -1);

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
     * Get hive reference
     *
     * @param  string       $key
     * @param  bool         $add
     * @param  array|null   $var
     *
     * @return mixed
     */
    public function &ref(string $key, bool $add = true, array $var = null)
    {
        $parts = explode('.', $key);
        $null = null;

        $this->startSession($parts[0] === 'SESSION');

        if ($var === null) {
            if ($add) {
                $var =& $this->hive;
            } else {
                $var = $this->hive;
            }
        }

        foreach ($parts as $part) {
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

        return $var;
    }

    /**
     * Check hive existance
     *
     * @param  string $key
     *
     * @return bool
     */
    public function exists(string $key): bool
    {
        $ref = $this->ref($key, false);

        return isset($ref);
    }

    /**
     * Get hive value
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function &get(string $key)
    {
        $ref =& $this->ref($key);

        return $ref;
    }

    /**
     * Set hive value
     *
     * @param string $key
     * @param mixed  $val
     *
     * @return App
     */
    public function set(string $key, $val): App
    {
        $this->beforeSet($key, $val);

        $ref =& $this->ref($key);
        $ref = $val;

        $this->afterSet($key, $val);

        return $this;
    }

    /**
     * Clear hive value (reset if initial value exists)
     *
     * @param  string $key
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
     * Massive set
     *
     * @param  array       $values
     * @param  string|null $prefix
     *
     * @return App
     */
    public function mset(array $values, string $prefix = null): App
    {
        foreach ($values as $key => $val) {
            $this->set($prefix . $key, $val);
        }

        return $this;
    }

    /**
     * Massive clear
     *
     * @param  array  $keys
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
     * Get and clear hive
     *
     * @param  string $key
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
     * Do run
     *
     * @return void
     */
    private function doRun(): void
    {
        if (!$this->hive['SYS']['BOOTED']) {
            $this->trigger(self::EVENT_BOOT);
            $this->hive['SYS']['BOOTED'] = true;
        }

        // We skip this part to test
        // @codeCoverageIgnoreStart
        if ($this->blacklisted()) {
            // Spammer detected
            $this->error(403);

            return;
        }
        // @codeCoverageIgnoreEnd

        if (!$this->hive['SYS']['ROUTES']) {
            // No routes defined
            $this->error(500, 'No route specified');

            return;
        }

        $method = $this->hive['REQ']['METHOD'];
        $headers = $this->hive['REQ']['HEADERS'];
        $type = $this->hive['REQ']['CLI'] ? self::REQ_CLI : ((int) $this->hive['REQ']['AJAX']) + 1;
        $modifier = $this->hive['CASELESS'] ? 'i' : '';
        $preflight = false;
        $cors = null;
        $allowed = [];

        $this->mclear(['RES','ERROR']);

        if (isset($headers['Origin']) && $this->hive['CORS']['ORIGIN']) {
            $cors = $this->hive['CORS'];
            $preflight = isset($headers['Access-Control-Request-Method']);

            $this->hive['RES']['HEADERS']['Access-Control-Allow-Origin'] = $cors['ORIGIN'];
            $this->hive['RES']['HEADERS']['Access-Control-Allow-Credentials'] = Helper::reqstr($cors['CREDENTIALS']);
        }

        foreach ($this->hive['SYS']['ROUTES'] as $pattern => $routes) {
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
                    $this->error(404);

                    return;
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
                $hash = Helper::hash($method . ' ' . $this->hive['REQ']['URI']) . '.url';

                if ($cache->exists($hash)) {
                    if (isset($headers['If-Modified-Since']) && strtotime($headers['If-Modified-Since'])+$ttl > $now) {
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
                    $this->error(405);

                    return;
                }

                if ($this->trigger(self::EVENT_PREROUTE, $args)) {
                    return;
                }

                $result = $this->call($handler, $args);

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

            if ($method !== 'OPTIONS') {
                return;
            }
        }

        if (!$allowed) {
            // URL doesn't match any route
            $this->error(404);

            return;
        }

        if (!$this->hive['REQ']['CLI']) {
            // Unhandled HTTP method
            $allowed = Helper::reqstr(array_unique($allowed));

            $this->hive['RES']['HEADERS']['Allow'] = $allowed;

            if ($cors) {
                $this->hive['RES']['HEADERS']['Access-Control-Allow-Methods'] = 'OPTIONS,' . $allowed;

                if ($cors['HEADERS']) {
                    $this->hive['RES']['HEADERS']['Access-Control-Allow-Headers'] = Helper::reqstr($cors['HEADERS']);
                }

                if ($cors['TTL'] > 0) {
                    $this->hive['RES']['HEADERS']['Access-Control-Max-Age'] = $cors['TTL'];
                }
            }

            if ('OPTIONS' !== $method) {
                $this->error(405);

                return;
            }
        }

        $this->sendHeaders();
    }

    /**
     * Perform pattern match, return true if no match
     *
     * @param  string     $pattern
     * @param  string     $modifier
     * @param  array|null &$args
     *
     * @return bool
     */
    private function noMatch(string $pattern, string $modifier, array &$args = null): bool
    {
        $wild = preg_replace_callback(
            '/\{(\w+)(?:\:(?:(alnum|alpha|digit|lower|upper|word)|(\w+)))?\}/',
            function($m) {
                return '(?<' . $m[1] . '>[[:' . (empty($m[2]) ? 'alnum' : $m[2]) . ':]]+)';
            },
            $pattern
        );
        $regex = '~^' . $wild. '$~' . $modifier;

        $res = preg_match($regex, $this->hive['REQ']['PATH'], $match);

        $args = [];
        $prev = null;

        foreach ($match as $key => $value) {
            if ((is_string($key) || $key === 0) || (is_numeric($key) && !is_string($prev))) {
                $args[$key] = $value;
            }

            $prev = $key;
        }

        return !$res;
    }

    /**
     * Do throttle output
     *
     * @param  string   $content
     * @param  int      $kbps
     *
     * @return void
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
            $ctr++;

            if ($ctr/$kbps > ($elapsed = microtime(true) - $now) && !connection_aborted()) {
                usleep((int) (1e6 * ($ctr / $kbps - $elapsed)));
            }

            echo $part;
        }
    }

    /**
     * Send response headers and cookies
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
            header($name . ': ' . $value);
        }

        header($this->hive['REQ']['PROTOCOL'] . ' ' . $this->hive['RES']['CODE'] . ' ' . $this->hive['RES']['STATUS'], true);

        return $this;
    }

    /**
     * Resolve function/method arguments
     *
     * @param  ReflectionFunctionAbstract $ref
     * @param  array|null                 $args
     *
     * @return array
     */
    private function resolveArgs(\ReflectionFunctionAbstract $ref, array $args = null): array
    {
        if ($ref->getNumberOfParameters() === 0) {
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
     * Resolve string argument
     *
     * @param  string $val
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
                return ($match[1] ?? '') . $var . ($match[3] ?? '');
            }

            // it is service alias
            return $this->service($match[2]);
        }

        return $val;
    }

    /**
     * Resolve class arguments
     *
     * @param  ReflectionParameter $ref
     * @param  array               &$lookup
     * @param  array               &$pArgs
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

        // TODO: logic for converting to Mapper
        return is_string($lookup[$ref->name]) ? $this->resolveArg($lookup[$ref->name]) : $lookup[$ref->name];
    }

    /**
     * Before set procedure
     *
     * @param  string $key
     * @param  mixed  &$val
     *
     * @return void
     */
    private function beforeSet(string $key, &$val): void
    {
        if (Helper::startswith('GET.', $key)) {
            $this->set('REQUEST' . Helper::cutafter('GET', $key), $val);
        } elseif (Helper::startswith('POST.', $key)) {
            $this->set('REQUEST' . Helper::cutafter('POST', $key), $val);
        } elseif (Helper::startswith('COOKIE.', $key)) {
            $val = $this->modifyCookieSet($val);
            $this->set('REQUEST' . Helper::cutafter('COOKIE', $key), $val);
        } elseif ($key === 'TZ') {
            date_default_timezone_set($val);
        } elseif ($key === 'ENCODING') {
            ini_set('default_charset', $val);
        }
    }

    /**
     * After set procedure
     *
     * @param  string $key
     * @param  mixed  $val
     *
     * @return void
     */
    private function afterSet(string $key, $val): void
    {
        if (Helper::cutafter('JAR', $key) && !Helper::endswith('.EXPIRE', $key)) {
            $this->hive['JAR']['EXPIRE'] -= microtime(true);
        }
    }

    /**
     * Modify cookie set
     *
     * @param  mixed $val
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
            $needshift = count($val) === 1;
        }

        $ujar = array_replace(array_values($this->hive['JAR']), $jar);
        array_unshift($ujar, $needshift ? array_shift($val) : $val);

        return $ujar;
    }

    /**
     * Perform real hive removal
     *
     * @param  string $key
     *
     * @return void
     */
    private function doClear(string $key): void
    {
        $parts = explode('.', $key);
        $last = array_pop($parts);
        $var =& $this->hive;

        foreach ($parts as $part) {
            if (!is_array($var)) {
                return;
            }

            $var =& $var[$part];
        }

        unset($var[$last]);
    }

    /**
     * Before clear procedure
     *
     * @param  string $key
     *
     * @return bool
     */
    private function beforeClear(string $key): bool
    {
        if (Helper::startswith('GET.', $key)) {
            $this->clear('REQUEST' . Helper::cutafter('GET', $key));
        } elseif (Helper::startswith('POST.', $key)) {
            $this->clear('REQUEST' . Helper::cutafter('POST', $key));
        } elseif (Helper::startswith('COOKIE.', $key)) {
            $this->clear('REQUEST' . Helper::cutafter('COOKIE', $key));
            $this->set($key, ['', '_jar' => [strtotime('-1 year')]]);

            return true;
        }

        return false;
    }

    /**
     * After clear procedure
     *
     * @param  string $key
     *
     * @return void
     */
    private function afterClear(string $key): void
    {
        if (Helper::startswith('SESSION', $key)) {
            $this->clearSession(Helper::cutafter('SESSION', $key));
        }
    }

    /**
     * Clear session
     *
     * @param  string $name
     *
     * @return void
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
        $this->clear('COOKIE.' . session_name());
        $this->sync('SESSION');
    }

    /**
     * Start session
     *
     * @param  bool $start
     *
     * @return void
     */
    private function startSession(bool $start = true): void
    {
        if ($start && !headers_sent() && session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->sync('SESSION');
    }

    /**
     * Sync globals to hive
     *
     * @param  string $key
     *
     * @return array|null
     */
    private function sync(string $key): ?array
    {
        $this->hive[$key] =& $GLOBALS['_' . $key];

        return $this->hive[$key];
    }

    /**
     * Convert and modify trace as string
     *
     * @param  array|null &$trace
     *
     * @return string
     */
    private function trace(array &$trace = null): string
    {
        if (!$trace) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_shift($trace);
        }

        $trace = array_filter($trace, function($frame) {
            return (
                isset($frame['file']) &&
                (
                    $this->hive['DEBUG'] > 1
                    || ($frame['file'] !== __FILE__ || $this->hive['DEBUG'])
                    && (empty($frame['function'])
                        || !preg_match('/^(?:(?:trigger|user)_error|__call|call_user_func)/', $frame['function'])
                    )
                )
            );
        });

        $out = '';
        $eol = "\n";
        $root = [$this->hive['TRACE'] . '/' => ''];

        // Analyze stack trace
        foreach ($trace as $frame) {
            $line = '';

            if (isset($frame['class'])) {
                $line .= $frame['class'] . $frame['type'];
            }

            if (isset($frame['function'])) {
                $line .= $frame['function'] . '(' .
                         ($this->hive['DEBUG'] > 2 && isset($frame['args']) ? Helper::csv($frame['args']): '') .
                         ')';
            }

            $src = Helper::fixslashes(strtr($frame['file'], $root));
            $out .= '[' . $src . ':' . $frame['line'] . '] ' . $line . $eol;
        }

        return $out;
    }

    /**
     * Convenience method to check hive value
     *
     * @param  string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->exists($offset);
    }

    /**
     * Convenience method to get hive value
     *
     * @param  string $offset
     *
     * @return mixed
     */
    public function &offsetGet($offset)
    {
        $ref =& $this->get($offset);

        return $ref;
    }

    /**
     * Convenience method to set hive value
     *
     * @param  string $offset
     * @param  mixed  $value
     *
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Convenience method to clear hive value
     *
     * @param  string $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        $this->clear($offset);
    }
}

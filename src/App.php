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

    /** @var array Response headers key map */
    protected $rheaders = [];

    /**
     * Class constructor
     *
     * @param int $debug
     */
    public function __construct()
    {
        $cli = PHP_SAPI === 'cli';
        $check = error_reporting((E_ALL|E_STRICT)&~(E_NOTICE|E_USER_NOTICE));

        // @codeCoverageIgnoreStart
        if (function_exists('apache_setenv')) {
            // Work around Apache pre-2.4 VirtualDocumentRoot bug
            $_SERVER['DOCUMENT_ROOT'] = str_replace(
                $_SERVER['SCRIPT_NAME'],
                '',
                $_SERVER['SCRIPT_FILENAME']
            );
            apache_setenv('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
        }
        // @codeCoverageIgnoreEnd

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            $header = cutafter('HTTP_', $key);
            if ($header) {
                $headers[dashcase($header)] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $headers[dashcase($key)] = $value;
            }
        }

        $domain = $_SERVER['SERVER_NAME'] ?? gethostname();
        $method = strtoupper($headers['X-HTTP-Method-Override'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET');

        // @codeCoverageIgnoreStart
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }
        // @codeCoverageIgnoreEnd

        if ($cli) {
            $method = 'GET';
            if (!isset($_SERVER['argv'][1])) {
                $_SERVER['argc']++;
                $_SERVER['argv'][1] = '/';
            }

            if ($_SERVER['argv'][1][0] === '/') {
                $_SERVER['REQUEST_URI'] = $_SERVER['argv'][1];
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
                            $opts .= '&' . implode(
                                '=&',
                                array_map('urlencode', str_split(substr($m[0], 1)))
                            ) . '=';
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

        $uridomain = preg_match('~^\w+://~', $_SERVER['REQUEST_URI']) ? '' : '//' . $domain;
        $uri = parse_url($uridomain . $_SERVER['REQUEST_URI']) + ['query'=>'', 'fragment'=>''];
        $base = $cli ? '' : rtrim(fixslashes(dirname($_SERVER['SCRIPT_NAME'])), '/');
        $path = cutafter($base, $uri['path'], $uri['path']);
        $secure = ($_SERVER['HTTPS'] ?? '') === 'on' ||
                  ($headers['X-Forwarded-Proto'] ?? '') === 'https';
        $scheme = $secure ? 'https' : 'http';
        $port = $headers['X-Forwarded-Port'] ?? $_SERVER['SERVER_PORT'] ?? 80;

        $_SERVER['REQUEST_URI'] = $uri['path'] .
                                  rtrim('?' . $uri['query'], '?') .
                                  rtrim('#' . $uri['fragment'], '#');
        $_SERVER['DOCUMENT_ROOT'] = realpath($_SERVER['DOCUMENT_ROOT']);
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['SERVER_NAME'] = $domain;
        $_SERVER['SERVER_PROTOCOL'] = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0';

        session_cache_limiter('');

        $this->hive = [
            'AGENT' => $this->agent($headers),
            'AJAX' => $this->ajax($headers),
            'ALIAS' => null,
            'ALIASES' => [],
            'BASE' => $base,
            'BODY' => '',
            'CACHE' => '',
            'CASELESS' => false,
            'CONFIG_DIR' => './',
            'CORS' => [
                'headers' => '',
                'origin' => false,
                'credentials' => false,
                'expose' => false,
                'ttl' => 0,
            ],
            'CLI' => $cli,
            'DEBUG' => 0,
            'DNSBL' => '',
            'ERROR' => null,
            'EXCEPTION' => null,
            'EXEMPT' => null,
            'FRAGMENT' => $uri['fragment'],
            'HANDLER' => null,
            'HEADERS' => $headers,
            'HOST' => $_SERVER['SERVER_NAME'],
            'IP' => $this->ip($headers),
            'JAR' => [
                'expire' => 0,
                'path' => $base ?: '/',
                'domain' => (strpos($domain, '.') === false
                            || filter_var($domain, FILTER_VALIDATE_IP)) ?
                            '' : $domain,
                'secure' => $secure,
                'httponly' => true
            ],
            'LOG_ERROR' => [
                'enabled' => false,
                'type' => 0,
                'destination' => null,
                'headers' => null,
            ],
            'METHOD' => $method,
            'MODE' => 0,
            'ONAFTERROUTE' => null,
            'ONBEFOREROUTE' => null,
            'ONERROR' => null,
            'ONREROUTE' => null,
            'ONUNLOAD' => null,
            'PACKAGE' => self::PACKAGE,
            'PATH' => urldecode($path),
            'PARAMS' => null,
            'PATTERN' => null,
            'PORT' => $port,
            'PREMAP' => '',
            'QUERY' => $uri['query'],
            'QUIET' => false,
            'RAW' => false,
            'REALM' => $scheme . '://' . $_SERVER['SERVER_NAME'] .
                       ($port && !in_array($port, [80, 443])? (':' . $port):'') .
                       $_SERVER['REQUEST_URI'],
            'RESPONSE' => null,
            'RHEADERS' => [],
            'ROOT' => $_SERVER['DOCUMENT_ROOT'],
            'ROUTES' => [],
            'SCHEME' => $scheme,
            'SEED' => hash($_SERVER['SERVER_NAME'] . $base),
            'SERIALIZER' => extension_loaded('igbinary') ? 'igbinary' : 'php',
            'SERVICE' => [],
            'STATUS' => 200,
            'TEMP' => './var/',
            'TEXT' => self::HTTP_200,
            'TRACE_ROOT' => is_dir($_SERVER['DOCUMENT_ROOT']) ?
                                $_SERVER['DOCUMENT_ROOT'] :
                                dirname($_SERVER['DOCUMENT_ROOT']),
            'TZ' => date_default_timezone_get(),
            'URI' => $_SERVER['REQUEST_URI'],
            'VERSION' => self::VERSION,
            'XFRAME' => 'SAMEORIGIN',
        ];
        // Register core service
        $this->set('SERVICE.cache', [
            'class' => Cache::class,
            'params' => [
                'dsn' => '%CACHE%',
                'prefix' => '%SEED%',
                'temp' => '%TEMP%cache/',
            ]
        ]);
        $this->set('SERVICE.template', [
            'class' => Template::class,
            'params' => [
                'tmp' => '%TEMP%template/',
                'dirs' => './ui/',
            ]
        ]);
        $this->set('SERVICE.audit', Audit::class);

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

        // set serializer
        serialize(null, $this->hive['SERIALIZER']);
        unserialize(null, $this->hive['SERIALIZER']);

        // @codeCoverageIgnoreStart
        if (PHP_SAPI === 'cli-server' && $base === $_SERVER['REQUEST_URI']) {
            $this->reroute('/');
        }

        // Register shutdown handler
        register_shutdown_function([$this, 'unload'], getcwd());

        $error = error_get_last();
        if ($check && $error) {
            // Error detected
            $this->error(500, 'Fatal error: ' . $error['message'], [$error]);
        }
        // @codeCoverageIgnoreEnd
    }

    /**
     * Get hive
     *
     * @return array
     */
    public function getHive(): array
    {
        return $this->hive;
    }

    /**
     * Register error handler
     *
     * @return App
     *
     * @codeCoverageIgnore
     */
    public function registerErrorHandler(): App
    {
        set_exception_handler(function($e) {
            $this->hive['EXCEPTION'] = $e;
            $file = cutafter(
                $this->hive['TRACE_ROOT'],
                $e->getFile(),
                $e->getFile()
            );
            $this->error(
                500,
                $e->getmessage() . ' ' . '[' . $file . ':' . $e->getLine() . ']',
                $e->gettrace()
            );
        });

        set_error_handler(function($level, $text, $file, $line) {
            if ($level & error_reporting()) {
                $message = preg_replace('~\b' . $this->hive['TRACE_ROOT'] . '~', '', $text);
                $this->error(500, $message, NULL, $level);
            }
        });

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
        if ($content !== '') {
            $this->rheaders[strtolower($name)] = $name;
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
        $key = $this->rheaders[strtolower($name)] ?? $name;

        return $this->hive['RHEADERS'][$key] ?? [];
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
     * @param  string|null $name
     *
     * @return App
     */
    public function removeHeader(string $name = null): App
    {
        if ($name) {
            $key = $this->rheaders[strtolower($name)] ?? $name;

            unset($this->hive['RHEADERS'][$key]);
        } else {
            $this->hive['RHEADERS'] = [];
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
     */
    public function call($callback, $args = null)
    {
        if (is_string($callback)) {
            $callback = $this->grab($callback);
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
     * @param array  $args
     *
     * @return mixed
     */
    public function service(string $id, array $args = [])
    {
        if (in_array($id, ['app', self::class])) {
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

        if (isset($rule['constructor'])) {
            $service = $this->call($rule['constructor']);
            $class = $rule['class'] ?? get_class($service);

            if (!isset($this->aliases[$class]) && $class !== $id) {
                $this->aliases[$class] = $id;
            }
        } elseif (method_exists($class, '__construct')) {
            $cArgs = $this->methodArgs(
                new \ReflectionMethod($class, '__construct'),
                array_merge($rule['params'] ?? [], $args)
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
     * Get hive ref
     *
     * @param  string       $key
     * @param  bool $add
     *
     * @return mixed
     */
    public function &ref(string $key, bool $add = true)
    {
        $null = null;
        $parts = $this->cut($key);

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
        return $this->ref($key, false) ?? $default;
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
    public function set(string $key, $val, int $ttl = null): App
    {
        static $serverMap = [
            'URI' => 'REQUEST_URI',
            'METHOD' => 'REQUEST_METHOD',
        ];

        preg_match(
            '/^(?:(?:(?:GET|POST)\b(.+))|COOKIE\.(.+)|SERVICE\.(.+)|(JAR\b))$/',
            $key,
            $match
        );

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
            } elseif (is_callable($val)) {
                $val = ['constructor' => $val];
            } elseif (is_object($val)) {
                $obj = $val;
                $val = ['class' => get_class($obj)];
            }

            // defaults it's a service
            $val += ['keep' => true];

            if (isset($val['class']) && $val['class'] !== $match[3]) {
                $this->aliases[$val['class']] = $match[3];
            }

            // update/remove existing service
            $this->services[$match[3]] = $obj ?? null;
        }

        $var =& $this->ref($key);
        $var = $val;

        if (isset($match[4]) && $match[4]) {
            $this->hive['JAR']['expire'] -= microtime(true);
        } else {
            switch ($key) {
                case 'CACHE':
                    $this->service('cache')->setDsn($val);
                    break;
                case 'SEED':
                    $this->service('cache')->setPrefix($val);
                    break;
                case 'TZ':
                    date_default_timezone_set($val);
                    break;
                case 'SERIALIZER':
                    serialize(null, $val);
                    unserialize(null, $val);
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
        if ($key === 'CACHE') {
            $this->service('cache')->reset();

            return $this;
        }

        preg_match(
            '/^(?:(?:(?:GET|POST)\b(.+))|(?:COOKIE\.(.+))|(SESSION(?:\.(.+))?)|(?:SERVICE\.(.+)))$/',
            $key,
            $match
        );

        if (isset($match[1]) && $match[1]) {
            $this->clear('REQUEST' . $match[1]);
        } elseif (isset($match[2]) && $match[2]) {
            $this->clear('REQUEST.' . $match[2]);

            $this->setCookie($match[2], null, strtotime('-1 year'));
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
            $this->services[$match[5]] = null;
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
     * Get and clear
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function flash(string $key)
    {
        $val = $this->get($key);
        $this->clear($key);

        return $val;
    }

    /**
     * Copy contents of hive variable to another
     *
     * @param string $src
     * @param string $dst
     *
     * @return App
     */
    public function copy(string $src, string $dst): App
    {
        $ref =& $this->ref($dst);
        $ref = $this->ref($src, false);

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
     * @return string|App
     */
    public function concat(string $key, string $suffix, string $prefix = '', bool $keep = false)
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
    public function flip(string $key, bool $keep = false)
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
     * @return App
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
    public function merge(string $key, $src, bool $keep = false)
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
    public function extend(string $key, $src, bool $keep = false)
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
     * Grab class name and method, create instance if needed
     *
     * @param  string $callback
     * @param  bool $create
     *
     * @return callable
     */
    public function grab(string $callback, bool $create = true)
    {
        $obj = explode('->', $callback);
        $static = explode('::', $callback);

        if (count($obj) === 2) {
            $callback = [$create ? $this->service($obj[0]) : $obj[0], $obj[1]];
        } elseif (count($static) === 2) {
            $callback = $static;
        }

        return $callback;
    }

    /**
     * Get client browser name
     *
     * @param array $headers
     *
     * @return string
     */
    public function agent(array $headers = null): string
    {
        $use = $headers ?? $this->hive['HEADERS'];

        return (
            $use['X-Operamini-Phone-Ua'] ??
            $use['X-Skyfire-Phone'] ??
            $use['User-Agent'] ??
            ''
        );
    }

    /**
     * Get XMLHttpRequest (ajax) status
     *
     * @param array $headers
     *
     * @return bool
     */
    public function ajax(array $headers = null): bool
    {
        $use = $headers ?? $this->hive['HEADERS'];

        return strtolower($use['X-Requested-With'] ?? '') === 'xmlhttprequest';
    }

    /**
     * Get client ip address
     *
     * @param array $headers
     *
     * @return string
     */
    public function ip(array $headers = null): string
    {
        $use = $headers ?? $this->hive['HEADERS'];

        return $use['Client-Ip'] ?? (
            isset($use['X-Forwarded-For']) ?
                explode(',', $use['X-Forwarded-For'])[0] :
                    $_SERVER['REMOTE_ADDR'] ?? ''
        );
    }

    /**
     * Configure framework according to .ini-style file settings
     *
     * @param  string|array  $source
     * @param  bool          $absolute
     *
     * @return App
     */
    public function config($source, bool $absolute = true): App
    {
        $sources = reqarr($source);
        $pattern = '/(?<=^|\n)(?:' .
                        '\[(?<section>.+?)\]|' .
                        '(?<lval>[^\h\r\n;].*?)\h*=\h*' .
                        '(?<rval>(?:\\\\\h*\r?\n|.+?)*)' .
                   ')(?=\r?\n|$)/';
        $sec_pattern = '/^(?!(?:global|config|route|map|redirect|resource)s\b)' .
                       '((?:\.?\w)+)/i';
        $dir = $absolute ? '' : $this->hive['CONFIG_DIR'];

        foreach ($sources as $file) {
            if (!preg_match_all($pattern, read($dir . $file), $matches, PREG_SET_ORDER)) {
                continue;
            }

            $sec = 'globals';
            $cmd = [];
            foreach ($matches as $match) {
                if ($match['section']) {
                    $sec = $match['section'];
                    if (
                        preg_match($sec_pattern, $sec, $msec)
                        && !$this->exists($msec[0])
                    ) {
                        $this->set($msec[0], null);
                    }

                    preg_match('/^(config|route|map|redirect|resource)s\b/i', $sec, $cmd);

                    continue;
                }

                if ($cmd) {
                    $call = $cmd[1];
                    $args = casts(str_getcsv($match['rval']));
                    array_unshift($args, $match['lval']);

                    $this->$call(...$args);
                } else {
                    $rval = preg_replace('/\\\\\h*(\r?\n)/', '\1', $match['rval']);
                    $args = array_map(
                        function ($val) {
                            $val = cast($val);

                            if (is_string($val)) {
                                return preg_replace('/\\\\"/','"', $val);
                            }

                            return $val;
                        },
                        // Mark quoted strings with 0x00 whitespace
                        str_getcsv(
                            preg_replace(
                                '/(?<!\\\\)(")(.*?)\1/',
                                "\\1\x00\\2\\1",
                                trim($rval)
                            )
                        )
                    );

                    $var = $match['lval'];
                    if (!istartswith('globals', $sec)) {
                        $var = $sec . '.' . $var;
                    }

                    if (count($args) > 1) {
                        $args = [$args];
                    }

                    $this->set($var, ...$args);
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
        $this->hive['TEXT'] = constant(self::class . '::HTTP_' . $code, '');

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
            $expires = (int) (microtime(true) + $secs);
            $this
                ->removeHeader('Pragma')
                ->header('Cache-Control', 'max-age=' . $secs)
                ->header('Expires', gmdate('r', $expires))
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

            $protocol = $_SERVER['SERVER_PROTOCOL'] . ' ' .
                        $this->hive['STATUS'] . ' ' . $this->hive['TEXT'];
            header($protocol, true);
        }

        return $this;
    }

    /**
     * Send text/plain header and output content
     *
     * @param  string $content
     *
     * @return void
     */
    public function text(string $content): void
    {
        $this
            ->header('Content-Type', 'text/plain;charset=' . ini_get('default_charset'))
            ->header('Content-Length', (string) strlen($content))
        ;

        echo $content;
    }

    /**
     * Send html header and output content
     *
     * @param  string $content
     *
     * @return void
     */
    public function html(string $content): void
    {
        $this
            ->header('Content-Type', 'text/html;charset=' . ini_get('default_charset'))
            ->header('Content-Length', (string) strlen($content))
        ;

        echo $content;
    }

    /**
     * Set JSON header and encode data
     *
     * @param  array  $data
     *
     * @return void
     */
    public function json(array $data): void
    {
        $content = json_encode($data);

        $this
            ->header('Content-Type', 'application/json;charset=' . ini_get('default_charset'))
            ->header('Content-Length', (string) strlen($content))
        ;

        echo $content;
    }

    /**
     * Match routes against incoming URI
     *
     * @return void
     */
    public function run(): void
    {
        if ($this->blacklisted($this->hive['IP'])) {
            // Spammer detected
            $this->error(403);

            return;
        }

        if (!$this->hive['ROUTES']) {
            // No routes defined
            throw new \LogicException('No route specified');
        }

        // Convert to BASE-relative URL
        $path = $this->hive['PATH'];
        $method = $this->hive['METHOD'];
        $headers = $this->hive['HEADERS'];
        $modifier = $this->hive['CASELESS'] ? 'i' : '';
        $type = $this->hive['CLI'] ? self::REQ_CLI : ((int) $this->hive['AJAX']) + 1;
        $preflight = false;
        $cors = null;
        $allowed = [];

        $this->removeHeader();

        if (isset($headers['Origin']) && $this->hive['CORS']['origin']) {
            $cors = $this->hive['CORS'];
            $preflight = isset($headers['Access-Control-Request-Method']);

            $this
                ->header('Access-Control-Allow-Origin', $cors['origin'])
                ->header('Access-Control-Allow-Credentials', var_export($cors['credentials'], true))
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
                $this->hive['PARAMS'] = $args;
                // Save matching route
                $this->hive['ALIAS'] = $alias;
                $this->hive['PATTERN'] = $pattern;
                $this->hive['HANDLER'] = $handler;

                // Expose if defined
                if ($cors && $cors['expose']) {
                    $this->header('Access-Control-Expose-Headers', reqstr($cors['expose']));
                }

                if (is_string($handler)) {
                    // Replace route pattern tokens in handler if any
                    $keys = explode(',', '{' . implode('},{', array_keys($args)) . '}');
                    $handler = str_replace($keys, array_values($args), $handler);
                    $check = $this->grab($handler, false);

                    if (is_array($check) && !class_exists($check[0])) {
                        $this->error(404);

                        return;
                    }
                }

                // Process request
                $now = microtime(true);
                $body = '';

                if ($ttl && in_array($method, ['GET', 'HEAD'])) {
                    // Only GET and HEAD requests are cacheable
                    $hash = hash($method . ' ' . $this->hive['URI']) . '.url';
                    $cache = $this->service('cache');

                    if ($cache->exists($hash)) {
                        if (
                            isset($headers['If-Modified-Since'])
                            && strtotime($headers['If-Modified-Since'])+$ttl > $now
                        ) {
                            $this
                                ->status(304)
                                ->sendHeader()
                            ;

                            return;
                        }

                        // Retrieve from cache backend
                        $cached = $cache->get($hash);
                        list($headers, $body) = $cached[0];

                        $this->headers($headers);
                        $this
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

                if ($body === '') {
                    if (!$this->hive['RAW'] && !$this->hive['BODY']) {
                        $this->hive['BODY'] = file_get_contents('php://input');
                    }

                    if (is_string($handler)) {
                        $handler = $this->grab($handler);
                    }

                    if (!is_callable($handler)) {
                        if (is_array($handler)) {
                            $this->error(404);
                        } else {
                            $info = stringify($handler);
                            $this->error(500, 'Invalid method: ' . $info);
                        }

                        return;
                    }

                    $routeArgs = array_slice($args, 1);

                    if ($this->trigger('ONBEFOREROUTE', $routeArgs) === false) {
                        return;
                    }

                    ob_start();
                    $this->call($handler, $routeArgs);
                    $body = ob_get_clean();

                    if (isset($hash) && $body && !error_get_last()) {
                        $headers = $this->hive['RHEADERS'];
                        unset($headers['Set-Cookie']);

                        // Save to cache backend
                        $cache->set($hash, [$headers, $body], $ttl);
                    }

                    if ($this->trigger('ONAFTERROUTE', $routeArgs) === false) {
                        return;
                    }
                }

                $this->hive['RESPONSE'] = $body;

                $this->sendHeader();
                $this->throttle($body, $kbps);

                if ($method !== 'OPTIONS') {
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
     * @param  array|null  $args
     * @param  array|null  $headers
     * @param  string|null $body
     *
     * @return void
     *
     * @throws LogicException
     */
    public function mock(
        string $pattern,
        array $args = null,
        array $headers = null,
        string $body = null
    ): void {
        preg_match('/^([\w]+)(?:\h+([^\h]+))(?:\h+(sync|ajax|cli))?$/i', $pattern, $match);

        if (empty($match[2])) {
            throw new \LogicException(
                'Mock pattern should contain at least request method and path, given "' .
                $pattern . '"'
            );
        }

        $args = (array) $args;
        $headers = (array) $headers;
        $method = strtoupper($match[1]);
        $path = $this->build($match[2]);
        $mode = strtolower($match[3] ?? '');
        $uri = parse_url($path) + ['query'=>'', 'fragment'=>''];

        $this->hive['METHOD'] = $method;
        $this->hive['PATH'] = $uri['path'];
        $this->hive['URI'] = $this->hive['BASE'] . $uri['path'];
        $this->hive['FRAGMENT'] = $uri['fragment'];
        $this->hive['AJAX'] = $mode === 'ajax';
        $this->hive['CLI'] = $mode === 'cli';
        $this->hive['HEADERS'] = $headers;

        // reset
        $this->clears([
            'ALIAS',
            'BODY',
            'ERROR',
            'RHEADERS',
            'RESPONSE',
            'STATUS',
            'TEXT',
        ]);

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
     * @param  string|array|null  $url
     * @param  boolean $permanent
     *
     * @return bool
     */
    public function reroute($url = null, bool $permanent = false): bool
    {
        if (!$url) {
            $url = $this->hive['REALM'];
        } elseif (is_array($url)) {
            $url = $this->alias(...$url);
        } else {
            $url = $this->build($url);
        }

        if ($this->trigger('ONREROUTE', [$url, $permanent]) === true) {
            return false;
        }

        if ($url[0] === '/' && (empty($url[1]) || $url[1] !== '/')) {
            $port = $this->hive['PORT'];
            $url = $this->hive['SCHEME'] . '://' . $this->hive['HOST'] .
                (in_array($port, [80, 443]) ? '' : (':' . $port)) .
                $this->hive['BASE'] . $url;
        }

        if ($this->hive['CLI']) {
            $this->mock('GET ' . $url . ' cli');
        } else {
            $this->header('Location', $url);
            $this
                ->status($permanent ? 301 : 302)
                ->sendHeader()
            ;
        }

        return false;
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
     * @param  string $alias
     * @param  array|string $args
     *
     * @return string
     *
     * @throws OutOfBoundsException
     */
    public function alias(string $alias, $args = null): string
    {
        if (empty($this->hive['ALIASES'][$alias])) {
            throw new \OutOfBoundsException('Alias "' . $alias . '" does not exists');
        }

        $args = (array) $args;
        $url = preg_replace_callback(
            '/\{(\w+)(?:\:\w+)?\}/',
            function($m) use ($args) {
                return $args[$m[1]] ?? $m[0];
            },
            $this->hive['ALIASES'][$alias]
        );

        return $url;
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

        if ($this->trigger('ONUNLOAD', [$cwd], true) === true) {
            return;
        }

        if ($error && in_array($error['type'], [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR])) {
            // Fatal error detected
            $this->error(500, 'Fatal error: ' . $error['message'], [$error]);
        }
    }

    /**
     * Return filtered stack trace as a formatted string
     *
     * @param  array|null &$trace
     * @param  bool       $format
     *
     * @return string
     */
    public function trace(array &$trace = null, bool $format = true): string
    {
        if (!$trace) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

            if (isset($trace[0]['file']) && $trace[0]['file'] === __FILE__) {
                array_shift($trace);
            }
        }

        $debug = $this->hive['DEBUG'];
        $trace = array_filter(
            $trace,
            function($frame) use ($debug) {
                return (
                    isset($frame['file'])
                    &&
                    (
                        $debug > 1
                        || ($frame['file'] !== __FILE__ || $debug)
                        && (empty($frame['function'])
                            || !preg_match(
                                '/^(?:(?:trigger|user)_error|__call|call_user_func)/',
                                $frame['function']
                                )
                        )
                    )
                );
            }
        );

        if (!$format) {
            return '';
        }

        $out = '';
        $eol = "\n";
        $root = $this->hive['TRACE_ROOT'] . '/';

        // Analyze stack trace
        foreach ($trace as $frame) {
            $line = '';

            if (isset($frame['class'])) {
                $line .= $frame['class'] . $frame['type'];
            }

            if (isset($frame['function'])) {
                $line .= $frame['function'] . '(' .
                         ($debug > 2 && isset($frame['args']) ? csv($frame['args']): '') .
                         ')';
            }

            $src = fixslashes(str_replace($root, '', $frame['file']));
            $out .= '[' . $src . ':' . $frame['line'] . '] ' . $line . $eol;
        }

        return $out;
    }

    /**
     * Log error, trigger ONERROR event if exists else
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

        $this->removeHeader();
        $this->status($code);

        $header = $this->hive['TEXT'];
        $req = $this->hive['METHOD'].' '.$this->hive['PATH'];
        $traceStr = $this->trace($trace);

        if ($this->hive['QUERY']) {
            $req .= '?' . $this->hive['QUERY'];
        }

        if (!$text) {
            $text = 'HTTP ' . $code . ' (' . $req . ')';
        }

        if ($this->hive['LOG_ERROR']['enabled']) {
            $logs = picktoargs($this->hive['LOG_ERROR'], ['destination','headers','type']);
            $eol = $logs[0] === 3 ? "\n" : '';

            error_log($text . $eol, ...$logs);
            error_log($traceStr, ...$logs);
        }

        $this->hive['ERROR'] = [
            'status' => $header,
            'code' => $code,
            'text' => $text,
            'trace' => $traceStr,
            'level' => $level
        ];

        $this->expire(-1);

        if ($this->trigger('ONERROR', null, true) === true || $this->hive['QUIET']) {
            return;
        }

        if ($this->hive['AJAX']) {
            $this->json(array_diff_key(
                $this->hive['ERROR'],
                $this->hive['DEBUG']? [] : ['trace' => 1]
            ));
        } else {
            $tracePre = $this->hive['DEBUG'] ? '<pre>' . $traceStr . '</pre>' : '';

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
  $tracePre
</body>
</html>
ERR
);
        }
    }

    /**
     * Return true if IPv4 address exists in DNSBL
     *
     * @param  string $ip
     *
     * @return bool
     */
    public function blacklisted(string $ip): bool
    {
        $dnsbl = reqarr($this->hive['DNSBL'] ?? '');
        $exempt = reqarr($this->hive['EXEMPT'] ?? '');

        if ($dnsbl && !in_array($ip, $exempt)) {
            // Reverse IPv4 dotted quad
            $rev = implode('.', array_reverse(explode('.', $ip)));
            foreach ($dnsbl as $server) {
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
     * @param  int $ttl
     * @param  int $kbps
     * @param  array|null $map
     *
     * @return App
     */
    public function resources(
        array $patterns,
        $class,
        int $ttl = 0,
        int $kbps = 0,
        array $map = null
    ): App {
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
     * @param  int $ttl
     * @param  int $kbps
     * @param  array|null $map
     *
     * @return App
     */
    public function resource(
        string $pattern,
        $class,
        int $ttl = 0,
        int $kbps = 0,
        array $map = null
    ): App {
        $parts = array_filter(explode(' ', $pattern));

        if (empty($parts)) {
            throw new \LogicException(
                'Resource pattern should contain at least route name, given "' .
                $pattern . '"'
            );
        }

        list($route, $prefix) = $parts + [1=>''];

        $proute = $prefix . '/' . str_replace('_', '-', $route);
        $prouter = $proute . '/{' . $route . '}';
        $defMap = [
            'index'   => ['GET',    $proute],
            'create'  => ['GET',    $proute . '/create'],
            'store'   => ['POST',   $proute],
            'show'    => ['GET',    $prouter],
            'edit'    => ['GET',    $prouter . '/edit'],
            'update'  => ['PUT',    $prouter],
            'destroy' => ['DELETE', $prouter],
        ];

        $type = constant(
            self::class . '::REQ_' . strtoupper($parts[2] ?? ''),
            $this->hive['MODE']
        );
        $str = is_string($class);
        $resources = $map ?? array_keys($defMap);

        foreach ($resources as $res => $action) {
            if (is_numeric($res)) {
                $res = $action;
            }

            if (isset($defMap[$res])) {
                list($verb, $path) = $defMap[$res];
                $alias = $route . '_' . $res;

                $this->hive['ALIASES'][$alias] = $path;
                $this->hive['ROUTES'][$path][$type][$verb] = [
                    $str ? $class . '->' . $action : [$class, $action],
                    $ttl,
                    $kbps,
                    $alias,
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
     * @param  int $ttl
     * @param  int $kbps
     * @param  string|null $map Defaults to self::VERBS
     *
     * @return App
     */
    public function maps(
        array $patterns,
        $class,
        int $ttl = 0,
        int $kbps = 0,
        string $map = null
    ): App {
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
     * @param  int $ttl
     * @param  int $kbps
     * @param  string|null $map Defaults to self::VERBS
     *
     * @return App
     */
    public function map($pattern, $class, int $ttl = 0, int $kbps = 0, string $map = null): App
    {
        $str = is_string($class);
        $prefix = $this->hive['PREMAP'];
        $verbs = split($map ?? self::VERBS);

        foreach ($verbs as $verb) {
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
     * @param  int $ttl
     * @param  int $kbps
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
     * @param  int $ttl
     * @param  int $kbps
     *
     * @return App
     */
    public function route(string $pattern, $callback, int $ttl = 0, int $kbps = 0): App
    {
        preg_match(
            '/^([\|\w]+)(?:\h+(\w+))?(?:\h+([^\h]+))(?:\h+(sync|ajax|cli))?$/i',
            $pattern,
            $match
        );

        $alias = $match[2] ?? null;

        if (!$alias && isset($match[3]) && isset($this->hive['ALIASES'][$match[3]])) {
            $alias = $match[3];
            $match[3] = $this->hive['ALIASES'][$alias];
        }

        if (empty($match[3])) {
            throw new \LogicException(
                'Route pattern should contain at least request method and path, given "' .
                $pattern . '"'
            );
        }

        if ($alias) {
            $this->hive['ALIASES'][$alias] = $match[3];
        }

        $type  = constant(
            self::class . '::REQ_' . strtoupper($match[4] ?? ''),
            $this->hive['MODE']
        );
        $verbs = split(strtoupper($match[1]));

        foreach ($verbs as $verb) {
            $this->hive['ROUTES'][$match[3]][$type][$verb] = [$callback, $ttl, $kbps, $alias];
        }

        return $this;
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
    protected function routeMatch(
        string $pattern,
        string $path,
        string $modifier,
        array &$match = null
    ): bool {
        $wild = preg_replace_callback(
            '/\{(\w+)(?:\:(?:(alnum|alpha|digit|lower|upper|word)|(\w+)))?\}/',
            function($m) {
                // defaults to alnum
                return '(?<' . $m[1] . '>[[:' . (empty($m[2]) ? 'alnum' : $m[2]) . ':]]+)';
            },
            $pattern
        );
        $regex = '~^' . $wild. '$~' . $modifier;

        return (bool) preg_match($regex, $path, $match);
    }

    /**
     * Trigger event if exists
     *
     * @param  string     $event
     * @param  array|null $args
     * @param  bool       $once
     *
     * @return bool|null
     */
    protected function trigger(string $event, array $args = null, bool $once = false): ?bool
    {
        if (isset($this->hive[$event])) {
            $handler = $this->hive[$event];

            if ($once) {
                $this->hive[$event] = null;
            }

            $result  = $this->call($handler, $args);

            return $result !== false;
        }

        return null;
    }

    /**
     * Throttle output
     *
     * @param  string      $content
     * @param  int $kbps
     *
     * @return void
     */
    protected function throttle(string $content, int $kbps = 0): void
    {
        if ($this->hive['QUIET']) {
            return;
        }

        if ($kbps) {
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

            return;
        }

        echo $content;
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
        if ($ref->getNumberOfParameters() === 0) {
            return [];
        }

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
                        // assume it does exists in hive
                        $ref = $this->ref($match[2], false);
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
     * Start session if not started yet
     *
     * @param bool $start
     *
     * @return void
     */
    protected function startSession(bool $start = true)
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
     * @return array|null
     */
    protected function sync(string $key): ?array
    {
        $this->hive[$key] =& $GLOBALS["_$key"];

        return $this->hive[$key];
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
        return preg_split(
            '/\[\h*[\'"]?(.+?)[\'"]?\h*\]|\./',
            $key,
            0,
            PREG_SPLIT_NO_EMPTY|PREG_SPLIT_DELIM_CAPTURE
        );
    }

    /**
     * Format to cookie header syntax
     *
     * @param string   $name
     * @param string   $value
     * @param int|null $ttl
     */
    protected function setCookie(string $name, string $value = null, int $ttl = null): void
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

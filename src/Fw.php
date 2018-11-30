<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick;

/**
 * Framework main class.
 *
 * It contains event dispatcher and listener, route handling, route path generation, dependency injection and some other helpers.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class Fw implements \ArrayAccess
{
    const PACKAGE = 'Stick-Framework';
    const VERSION = 'v0.1.0';

    const REQ_ALL = 0;
    const REQ_AJAX = 1;
    const REQ_CLI = 2;
    const REQ_SYNC = 3;

    const EVENT_START = 'fw.start';
    const EVENT_SHUTDOWN = 'fw.shutdown';
    const EVENT_PREROUTE = 'fw.preroute';
    const EVENT_POSTROUTE = 'fw.postroute';
    const EVENT_CONTROLLER_ARGS = 'fw.controller_args';
    const EVENT_REROUTE = 'fw.reroute';
    const EVENT_ERROR = 'fw.error';

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

    const LEVEL_EMERGENCY = 'emergency';
    const LEVEL_ALERT = 'alert';
    const LEVEL_CRITICAL = 'critical';
    const LEVEL_ERROR = 'error';
    const LEVEL_WARNING = 'warning';
    const LEVEL_NOTICE = 'notice';
    const LEVEL_INFO = 'info';
    const LEVEL_DEBUG = 'debug';

    const LOG_LEVELS = array(
        self::LEVEL_EMERGENCY => 0,
        self::LEVEL_ALERT => 1,
        self::LEVEL_CRITICAL => 2,
        self::LEVEL_ERROR => 3,
        self::LEVEL_WARNING => 4,
        self::LEVEL_NOTICE => 5,
        self::LEVEL_INFO => 6,
        self::LEVEL_DEBUG => 7,
    );

    /**
     * @var array
     */
    private $hive;

    /**
     * @var array
     */
    private $init;

    /**
     * Class constructor.
     *
     * @param array|null $post
     * @param array|null $get
     * @param array|null $cookie
     * @param array|null $server
     */
    public function __construct(array $post = null, array $get = null, array $cookie = null, array $server = null)
    {
        $time = microtime(true);
        $cli = 'cli' === PHP_SAPI;
        $entry = $this->fixslashes($server['SCRIPT_NAME'] ?? $_SERVER['SCRIPT_NAME']);
        $uri = $server['REQUEST_URI'] ?? '/';
        $host = $server['SERVER_NAME'] ?? gethostname();
        $base = $cli ? '' : dirname($entry);
        $front = $cli ? '' : '/'.basename($entry);
        $headers = null;

        foreach ((array) $server as $key => $val) {
            if (in_array($key, array('CONTENT_LENGTH', 'CONTENT_TYPE'))) {
                $key = 'HTTP_'.$key;
            }

            if ('HTTP' === strstr($key, '_', true) && $key = strstr($key, '_')) {
                $key = ucwords(str_replace('_', '-', strtolower(substr($key, 1))), '-');
                $headers[$key] = $val;
            }
        }

        $url = parse_url((preg_match('/^\w+:\/\//', $uri) ? '' : '//'.$host).$uri);
        $secure = 'on' === ($server['HTTPS'] ?? null) || 'https' === ($headers['X-Forwarded-Proto'] ?? null);
        $scheme = $secure ? 'https' : 'http';
        $port = (int) ($headers['X-Forwarded-Port'] ?? $server['SERVER_PORT'] ?? 80);
        $domain = $scheme.'://'.$host.(in_array($port, array(80, 443)) ? null : ':'.$port);
        $cookieJar = array(
            'expire' => 0,
            'path' => $base,
            'domain' => (false === strpos($host, '.') || filter_var($host, FILTER_VALIDATE_IP)) ? '' : $host,
            'secure' => $secure,
            'httponly' => true,
        );

        $this->hive = array(
            'AGENT' => $headers['X-Operamini-Phone-Ua'] ?? $headers['X-Skyfire-Phone'] ?? $headers['User-Agent'] ?? '',
            'AJAX' => 'XMLHttpRequest' === ($headers['X-Requested-With'] ?? null),
            'ALIAS' => null,
            'ASSET' => null,
            'AUTOLOAD' => null,
            'AUTOLOAD_FALLBACK' => null,
            'BASE' => $base,
            'BASEURL' => $domain.$base,
            'BODY' => null,
            'CACHE' => null,
            'CACHE_DRY' => true,
            'CASELESS' => false,
            'CLI' => $cli,
            'CODE' => 200,
            'COOKIE' => null,
            'DEBUG' => false,
            'DICT' => null,
            'DNSBL' => null,
            'ERROR' => null,
            'EVENTS' => null,
            'EXEMPT' => null,
            'FALLBACK' => 'en',
            'FRONT' => $front,
            'GET' => $get,
            'HOST' => $host,
            'IP' => $headers['X-Client-Ip'] ?? strstr(($headers['X-Forwarded-For'] ?? $server['REMOTE_ADDR'] ?? '').',', ',', true),
            'JAR' => $cookieJar,
            'LANGUAGE' => null,
            'LOCALES' => null,
            'LOG' => null,
            'MARKS' => null,
            'MIME' => null,
            'OUTPUT' => null,
            'PACKAGE' => self::PACKAGE,
            'PARAMS' => null,
            'PATH' => preg_replace('/^'.preg_quote($front, '/').'/', '', preg_replace('/^'.preg_quote($base, '/').'/', '', urldecode($url['path']))) ?: '/',
            'PATTERN' => null,
            'PORT' => $port,
            'POST' => $post,
            'PROTOCOL' => $server['SERVER_PROTOCOL'] ?? 'HTTP/1.0',
            'QUIET' => false,
            'RAW' => false,
            'REQUEST' => $headers,
            'RESPONSE' => null,
            'ROUTE_ALIASES' => null,
            'ROUTE_CTR' => -1,
            'ROUTE_HANDLERS' => null,
            'ROUTES' => null,
            'SCHEME' => $scheme,
            'SEED' => $this->hash($host.$base),
            'SENT' => false,
            'SERVER' => $server,
            'SERVICE_ALIASES' => null,
            'SERVICE_RULES' => null,
            'SERVICES' => null,
            'SESSION' => null,
            'STATUS' => self::HTTP_200,
            'TEMP' => './var/',
            'THRESHOLD' => self::LEVEL_ERROR,
            'TIME' => $time,
            'URI' => $uri,
            'URL' => $domain.$uri,
            'VERB' => $server['REQUEST_METHOD'] ?? 'GET',
            'VERSION' => self::VERSION,
            'XFRAME' => 'SAMEORIGIN',
        );
        $this->init = array('GET' => null, 'POST' => null) + $this->hive;
    }

    /**
     * Create instance.
     *
     * @param array|null $post
     * @param array|null $get
     * @param array|null $cookie
     * @param array|null $server
     *
     * @return Fw
     */
    public static function create(array $post = null, array $get = null, array $cookie = null, array $server = null): Fw
    {
        return new self($post, $get, $cookie, $server);
    }

    /**
     * Create instance from globals.
     *
     * @return Fw
     */
    public static function createFromGlobals(): Fw
    {
        return self::create($_POST, $_GET, $_COOKIE, $_SERVER);
    }

    /**
     * Returns the return value of required file.
     *
     * It does ensure loaded file have no access to caller scope.
     *
     * @param string $file
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function requireFile(string $file, $default = null)
    {
        $content = is_file($file) ? (require $file) : null;

        return $content ?: $default;
    }

    /**
     * Returns string with backlashes convert to slash.
     *
     * @param string $str
     *
     * @return string
     */
    public function fixslashes(string $str): string
    {
        return str_replace('\\', '/', $str);
    }

    /**
     * Returns PHP-value of val.
     *
     * @param mixed $val
     *
     * @return mixed
     */
    public function cast($val)
    {
        if (is_numeric($val)) {
            return $val + 0;
        }

        if (is_scalar($val)) {
            $val = trim($val);

            if (preg_match('/^\w+$/i', $val) && defined($val)) {
                return constant($val);
            }
        }

        return $val;
    }

    /**
     * Returns 64bit/base36 hash.
     *
     * @param string $str
     *
     * @return string
     */
    public function hash(string $str): string
    {
        return str_pad(base_convert(substr(sha1($str), -16), 16, 36), 11, '0', STR_PAD_LEFT);
    }

    /**
     * Returns true if dir exists or successfully created.
     *
     * @param string $path
     * @param int    $mode
     * @param bool   $recursive
     *
     * @return bool
     */
    public function mkdir(string $path, int $mode = 0755, bool $recursive = true): bool
    {
        return file_exists($path) ? true : mkdir($path, $mode, $recursive);
    }

    /**
     * Returns file content with option to apply Unix LF as standard line ending.
     *
     * @param string $file
     * @param bool   $lf
     *
     * @return string
     */
    public function read(string $file, bool $lf = false): string
    {
        $out = is_file($file) ? file_get_contents($file) : '';

        return $lf ? preg_replace('/\r\n|\r/', "\n", $out) : $out;
    }

    /**
     * Exclusive file write.
     *
     * @param string $file
     * @param string $data
     * @param bool   $append
     *
     * @return int|false
     */
    public function write(string $file, string $data, bool $append = false)
    {
        return file_put_contents($file, $data, LOCK_EX | ((int) $append * FILE_APPEND));
    }

    /**
     * Delete file if exists.
     *
     * @param string $file
     *
     * @return bool
     */
    public function delete(string $file): bool
    {
        return is_file($file) ? unlink($file) : false;
    }

    /**
     * Returns camelCase string from snake_case.
     *
     * @param string $str
     *
     * @return string
     */
    public function camelCase(string $str): string
    {
        return str_replace('_', '', lcfirst(ucwords($str, '_')));
    }

    /**
     * Returns snake_case string from camelCase.
     *
     * @param string $str
     *
     * @return string
     */
    public function snakeCase(string $str): string
    {
        return strtolower(preg_replace('/(?!^)\p{Lu}/u', '_\0', $str));
    }

    /**
     * Returns class name.
     *
     * @param string|object $class
     *
     * @return string
     */
    public function className($class): string
    {
        $ns = '\\'.ltrim(is_object($class) ? get_class($class) : $class, '\\');

        return substr(strrchr($ns, '\\'), 1);
    }

    /**
     * Split comma-, semi-colon, pipe-separated string or custom pattern.
     *
     * @param array|string|null $str
     * @param string|null       $delimiter
     *
     * @return array
     */
    public function split($val, string $delimiter = null): array
    {
        if (!$val) {
            return array();
        } elseif (is_array($val)) {
            return $val;
        }

        $pattern = '/['.preg_quote($delimiter ?? ',;|', '/').']/';

        return array_map('trim', preg_split($pattern, $val, 0, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * Pick member of array.
     *
     * @param mixed      $key
     * @param array|null $collections
     * @param mixed      $default
     * @param bool       $twoTier
     *
     * @return mixed
     */
    public function pick($key, array $collections = null, $default = null, bool $twoTier = false)
    {
        foreach ($twoTier ? (array) $collections : array((array) $collections) as $collection) {
            if ($collection && is_array($collection) && array_key_exists($key, $collection)) {
                return $collection[$key];
            }
        }

        return $default;
    }

    /**
     * Load configuration from PHP-file.
     *
     * @param string $file
     *
     * @return Fw
     */
    public function config(string $file): Fw
    {
        // Config map
        $maps = array(
            'configs' => 'config',
            'routes' => 'route',
            'redirects' => 'redirect',
            'rests' => 'rest',
            'controllers' => 'controller',
            'rules' => 'setRule',
            'events' => 'on',
            'subscribers' => 'subscribe',
        );
        $config = (array) self::requireFile($file);

        foreach ($config as $key => $val) {
            $call = $maps[strtolower((string) $key)] ?? null;

            if ($call) {
                foreach ((array) $val as $args) {
                    $args = (array) $args;
                    $this->$call(...$args);
                }
            } else {
                if (is_array($val)) {
                    foreach ($val as $key2 => $val2) {
                        $this[$key][$key2] = $val2;
                    }
                } else {
                    $this[$key] = $val;
                }
            }
        }

        return $this;
    }

    /**
     * Add microtime mark.
     *
     * @param mixed $mark
     *
     * @return Fw
     */
    public function mark($mark = null): Fw
    {
        $time = microtime(true);

        if ($mark) {
            $this->hive['MARKS'][$mark] = $time;
        } else {
            $this->hive['MARKS'][] = $time;
        }

        return $this;
    }

    /**
     * Returns ellapsed time since application prepared or specified mark.
     *
     * @param mixed $mark
     * @param bool  $remove
     *
     * @return float
     */
    public function ellapsed($mark = null, bool $remove = true): float
    {
        if (true === $mark || empty($this->hive['MARKS'])) {
            $time = $this->hive['TIME'];
        } else {
            if (isset($this->hive['MARKS'][$mark])) {
                $time = $this->hive['MARKS'][$mark];
                $ndx = $mark;
            } else {
                $time = end($this->hive['MARKS']);
                $ndx = key($this->hive['MARKS']);
            }

            if ($remove) {
                unset($this->hive['MARKS'][$mark]);
            }
        }

        return microtime(true) - $time;
    }

    /**
     * Override request method with Custom http method override or request post method hack.
     *
     * @return Fw
     */
    public function overrideRequestMethod(): Fw
    {
        $verb = $this->hive['REQUEST']['X-Http-Method-Override'] ?? $this->hive['VERB'];

        if ('POST' === $verb && isset($this->hive['POST']['_method'])) {
            $verb = strtoupper($this->hive['POST']['_method']);
        }

        $this->hive['VERB'] = $verb;

        return $this;
    }

    /**
     * Convert console arguments to path and queries.
     *
     * @return Fw
     */
    public function emulateCliRequest(): Fw
    {
        if ($this->hive['CLI'] && isset($this->hive['SERVER']['argv'])) {
            $argv = $this->hive['SERVER']['argv'] + array(1 => '/');

            if ('/' === $argv[1][0]) {
                $req = $argv[1];
            } else {
                $req = '';
                $opts = '';

                for ($i = count($argv) - 1; $i > 0; --$i) {
                    $arg = $argv[$i];

                    if ('-' === $arg[0]) {
                        $m = explode('=', $arg) + array(1 => '');

                        if ('-' === $arg[1]) {
                            $opts .= '&'.urlencode(substr($m[0], 2)).'=';
                        } else {
                            $opts .= '&'.implode('=&', array_map('urlencode', str_split(substr($m[0], 1)))).'=';
                        }

                        $opts = ltrim($opts, '&').$m[1];
                    } else {
                        $req = '/'.$arg.$req;
                    }
                }

                $req = '/'.ltrim(rtrim($req.'?'.$opts, '?'), '/');
            }

            $uri = parse_url($req) + array('query' => '', 'fragment' => '');

            $this->hive['VERB'] = 'GET';
            $this->hive['PATH'] = $uri['path'];
            $this->hive['URI'] = $req;
            $this->hive['URL'] = $this->hive['BASEURL'].$req;
            parse_str($uri['query'], $this->hive['GET']);
        }

        return $this;
    }

    /**
     * Register shutdown function.
     *
     * @return Fw
     *
     * @codeCoverageIgnore
     */
    public function registerShutdownHandler(): Fw
    {
        register_shutdown_function(array($this, 'unload'), getcwd());

        return $this;
    }

    /**
     * Shutdown sequence.
     *
     * @param string $cwd
     *
     * @codeCoverageIgnore
     */
    public function unload(string $cwd): void
    {
        chdir($cwd);
        $error = error_get_last();
        $fatal = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR);

        if (!$error && PHP_SESSION_ACTIVE === session_status()) {
            session_commit();
        }

        $handled = $this->trigger(self::EVENT_SHUTDOWN, array($error));

        if (!$handled && $error && in_array($error['type'], $fatal)) {
            $this->error(500, $error['message'], array($error));
        }
    }

    /**
     * Register autoloader.
     *
     * @return Fw
     *
     * @codeCoverageIgnore
     */
    public function registerAutoload(): Fw
    {
        spl_autoload_register(array($this, 'loadClass'));

        return $this;
    }

    /**
     * Unregister autoloader.
     *
     * @return Fw
     *
     * @codeCoverageIgnore
     */
    public function unregisterAutoload(): Fw
    {
        spl_autoload_unregister(array($this, 'loadClass'));

        return $this;
    }

    /**
     * Load class file.
     *
     * @param string $class
     *
     * @return mixed
     *
     * @codeCoverageIgnore
     */
    public function loadClass(string $class)
    {
        if ($file = $this->findClass($class)) {
            self::requireFile($file);

            return true;
        }
    }

    /**
     * Find class file.
     *
     * @param string $class
     *
     * @return string|null
     */
    public function findClass(string $class): ?string
    {
        // Search for Hack files too if we are running on HHVM
        return $this->findFileWithExtension($class, '.php') ?? (defined('HHVM_VERSION') ? $this->findFileWithExtension($class, '.hh') : null);
    }

    /**
     * Returns true if given ip is blacklisted.
     *
     * @param string $ip
     *
     * @return bool
     *
     * @codeCoverageIgnore
     */
    public function blacklisted(string $ip): bool
    {
        if ($this->hive['DNSBL'] && !in_array($ip, $this->split($this->hive['EXEMPT']))) {
            // Reverse IPv4 dotted quad
            $rev = implode('.', array_reverse(explode('.', $ip)));

            foreach ($this->split($this->hive['DNSBL']) as $server) {
                // DNSBL lookup
                if (checkdnsrr($rev.'.'.$server, 'A')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns callable of string expression.
     *
     * @param string $expr
     * @param bool   $obj
     *
     * @return mixed
     */
    public function grab(string $expr, bool $obj = true)
    {
        if (2 === count($parts = explode('->', $expr))) {
            return array($obj ? $this->service($parts[0]) : $parts[0], $parts[1]);
        }

        if (2 === count($parts = explode('::', $expr))) {
            return $parts;
        }

        return $expr;
    }

    /**
     * Execute callable immediately.
     *
     * @param mixed $cb
     *
     * @return Fw
     */
    public function execute($cb): Fw
    {
        $this->call($cb);

        return $this;
    }

    /**
     * Returns result of callable.
     *
     * @param callable   $cb
     * @param array|null $args
     *
     * @return mixed
     */
    public function call(callable $cb, array $args = null)
    {
        $ref = is_array($cb) ? new \ReflectionMethod(reset($cb), next($cb)) : new \ReflectionFunction($cb);

        return $cb(...$this->resolveArgs($ref, (array) $args));
    }

    /**
     * Returns service rule.
     *
     * @param string      $id
     * @param array|null  $override
     * @param string|null &$realId
     *
     * @return array
     */
    public function getRule(string $id, array $override = null, string &$realId = null): array
    {
        $realId = $id;
        $rule = array();
        $default = array(
            'args' => null,
            'boot' => null,
            'class' => $id,
            'constructor' => null,
            'service' => false,
            'use' => null,
        );

        if (isset($this->hive['SERVICE_RULES'][$id])) {
            $rule = $this->hive['SERVICE_RULES'][$id];
        } elseif ($this->hive['SERVICE_ALIASES'] && $sid = array_search($id, $this->hive['SERVICE_ALIASES'])) {
            $rule = $this->hive['SERVICE_RULES'][$sid];
            $realId = $sid;
        }

        return array_replace_recursive($default, $rule, (array) $override);
    }

    /**
     * Sets class construction rule.
     *
     * @param string $id
     * @param mixed  $rule
     *
     * @return Fw
     */
    public function setRule(string $id, $rule = null): Fw
    {
        unset($this->hive['SERVICES'][$id]);

        if (is_callable($rule)) {
            $this->hive['SERVICE_RULES'][$id] = array('constructor' => $rule);
        } elseif (is_object($rule)) {
            $this->hive['SERVICE_RULES'][$id] = array('class' => get_class($rule));
            $this->hive['SERVICES'][$id] = $rule;
        } elseif (is_string($rule)) {
            $this->hive['SERVICE_RULES'][$id] = array('class' => $rule);
        } else {
            $this->hive['SERVICE_RULES'][$id] = (array) $rule;
        }

        $this->hive['SERVICE_RULES'][$id] += array('class' => $id, 'service' => true);

        if ($this->hive['SERVICE_RULES'][$id]['class'] !== $id) {
            $this->hive['SERVICE_ALIASES'][$id] = $this->hive['SERVICE_RULES'][$id]['class'];
        }

        return $this;
    }

    /**
     * Returns service instance.
     *
     * @param string $id
     *
     * @return mixed
     */
    public function service(string $id)
    {
        if (in_array($id, array('fw', self::class))) {
            return $this;
        }

        $rule = $this->getRule($id, null, $sid);

        if (isset($this->hive['SERVICES'][$sid])) {
            return $this->hive['SERVICES'][$sid];
        }

        $instance = $this->instance($id, $rule);

        if ($rule['service']) {
            $this->hive['SERVICES'][$sid] = $instance;
        }

        return $instance;
    }

    /**
     * Returns new class instance.
     *
     * @param string     $id
     * @param array|null $rule
     *
     * @return mixed
     */
    public function instance(string $id, array $rule = null)
    {
        $fix = $this->getRule($id, $rule);
        $ref = new \ReflectionClass($fix['use'] ?? $fix['class']);

        if (!$ref->isInstantiable()) {
            throw new \LogicException(sprintf('Unable to create instance for "%s". Please provide instantiable version of %s.', $id, $ref->name));
        }

        if (is_callable($fix['constructor'])) {
            $instance = $this->call($fix['constructor']);

            if (!$instance instanceof $ref->name) {
                throw new \LogicException(sprintf('Constructor of "%s" should return instance of %s.', $id, $ref->name));
            }
        } elseif ($ref->hasMethod('__construct')) {
            $instance = $ref->newInstanceArgs($this->resolveArgs($ref->getMethod('__construct'), (array) $fix['args']));
        } else {
            $instance = $ref->newInstance();
        }

        if (is_callable($fix['boot'])) {
            $this->call($fix['boot'], array($instance, $this));
        }

        return $instance;
    }

    /**
     * Add events subscriber.
     *
     * @param string|object $subscriber
     *
     * @return Fw
     */
    public function subscribe($subscriber): Fw
    {
        if (!is_subclass_of($subscriber, 'Fal\\Stick\\EventSubscriberInterface')) {
            throw new \LogicException(sprintf('Subscriber "%s" should implements Fal\\Stick\\EventSubscriberInterface.', $subscriber));
        }

        $events = array($subscriber, 'getEvents');

        foreach ($events() as $event => $handler) {
            $this->on($event, $handler);
        }

        return $this;
    }

    /**
     * Register event handler that will be called once.
     *
     * @param string $event
     * @param mixed  $handler
     *
     * @return Fw
     */
    public function one(string $event, $handler): Fw
    {
        return $this->on($event, $handler, true);
    }

    /**
     * Register event handler.
     *
     * @param string $event
     * @param mixed  $handler
     * @param bool   $once
     *
     * @return Fw
     */
    public function on(string $event, $handler, bool $once = false): Fw
    {
        $this->hive['EVENTS'][$event] = array($handler, $once);

        return $this;
    }

    /**
     * Unregister event handler.
     *
     * @param string $event
     *
     * @return Fw
     */
    public function off(string $event): Fw
    {
        unset($this->hive['EVENTS'][$event]);

        return $this;
    }

    /**
     * Trigger event.
     *
     * @param string     $event
     * @param array|null $event
     * @param bool       $off
     *
     * @return mixed
     */
    public function trigger(string $event, array $args = null, bool $off = false)
    {
        if (empty($this->hive['EVENTS'][$event])) {
            return null;
        }

        list($handler, $once) = $this->hive['EVENTS'][$event];

        if ($once || $off) {
            $this->off($event);
        }

        if (is_string($handler)) {
            $handler = $this->grab($handler);
        }

        return $this->call($handler, $args);
    }

    /**
     * Returns CacheInterface instance or proxy method call to it.
     *
     * @param string|null $call
     * @param mixed       ...$args
     *
     * @return mixed
     */
    public function cache(string $call = null, ...$args)
    {
        $engine = $this->hive['CACHE'];

        if (!$engine) {
            $engine = 'Fal\\Stick\\NoCache';

            if ($this->hive['CACHE_DRY']) {
                $this->setRule($engine);
            }
        } elseif ('fallback' === $engine) {
            $engine = 'Fal\\Stick\\FilesystemCache';

            if ($this->hive['CACHE_DRY']) {
                $this->setRule($engine, array(
                    'args' => array(
                        'directory' => $this->hive['TEMP'].'cache/',
                    ),
                ));
            }
        }

        $cache = $this->service($engine);

        if ($this->hive['CACHE_DRY']) {
            if (!$cache instanceof CacheInterface) {
                throw new \LogicException(sprintf('Cache should be instance of Fal\\Stick\\CacheInterface, %s given.', $this->hive['CACHE']));
            }

            $this->hive['CACHE_DRY'] = false;
            $cache->seed($this->hive['SEED']);
        }

        if ($call) {
            if (!method_exists($cache, $call)) {
                throw new \LogicException(sprintf('Call to undefined cache method "%s".', $call));
            }

            return $cache->$call(...$args);
        }

        return $cache;
    }

    /**
     * Returns translated message.
     *
     * @param string      $key
     * @param array|null  $args
     * @param string|null $fallback
     *
     * @return string
     */
    public function trans(string $key, array $args = null, string $fallback = null): string
    {
        $message = $this->langRef($key) ?? $fallback ?? $key;

        return strtr($message, (array) $args);
    }

    /**
     * Returns translated plural message.
     *
     * Key/content example:
     *
     *  * There is no apple|There is one apple|There is # apples
     *
     * @param string      $key
     * @param numeric     $count
     * @param array|null  $args
     * @param string|null $fallback
     *
     * @return string
     */
    public function choice(string $key, int $count, array $args = null, string $fallback = null): string
    {
        $args['#'] = $count;
        $message = $this->langRef($key) ?? $fallback ?? $key;

        foreach (explode('|', $message) as $key => $choice) {
            if ($count <= $key) {
                return strtr($choice, $args);
            }
        }

        return strtr($choice, $args);
    }

    /**
     * Translate with alternative messages.
     *
     * @param string      $key
     * @param array|null  $args
     * @param string|null $fallback
     * @param string      ...$alts
     *
     * @return string
     */
    public function alt(string $key, array $args = null, string $fallback = null, string ...$alts): string
    {
        $message = $this->langRef($key);

        foreach ($message ? array() : $alts as $alt) {
            if ($ref = $this->langRef($alt)) {
                $message = $ref;
                break;
            }
        }

        return strtr($message ?? $fallback ?? $key, (array) $args);
    }

    /**
     * Send an error message to log file.
     *
     * @param string $level
     * @param string $message
     *
     * @return Fw
     */
    public function log(string $level, string $message): Fw
    {
        $write = $this->hive['LOG'] && (self::LOG_LEVELS[$level] ?? 100) <= (self::LOG_LEVELS[$this->hive['THRESHOLD']] ?? 99);

        if ($write) {
            $ext = '.log';
            $prefix = $this->hive['LOG'].'log_';
            $files = glob($prefix.date('Y-m').'*'.$ext);

            $file = $files[0] ?? $prefix.date('Y-m-d').$ext;
            $content = date('Y-m-d G:i:s.u').' '.$level.' '.$message.PHP_EOL;

            $this->mkdir(dirname($file));
            $this->write($file, $content, true);
        }

        return $this;
    }

    /**
     * Log an error with error code.
     *
     * @param int    $code
     * @param string $message
     *
     * @return Fw
     */
    public function logByCode(int $code, string $message): Fw
    {
        $map = array(
            E_ERROR => self::LEVEL_EMERGENCY,
            E_PARSE => self::LEVEL_EMERGENCY,
            E_CORE_ERROR => self::LEVEL_EMERGENCY,
            E_COMPILE_ERROR => self::LEVEL_EMERGENCY,
            E_WARNING => self::LEVEL_ALERT,
            E_CORE_WARNING => self::LEVEL_ALERT,
            E_STRICT => self::LEVEL_CRITICAL,
            E_USER_ERROR => self::LEVEL_ERROR,
            E_USER_WARNING => self::LEVEL_WARNING,
            E_NOTICE => self::LEVEL_NOTICE,
            E_COMPILE_WARNING => self::LEVEL_NOTICE,
            E_USER_NOTICE => self::LEVEL_NOTICE,
            E_RECOVERABLE_ERROR => self::LEVEL_INFO,
            E_DEPRECATED => self::LEVEL_INFO,
            E_USER_DEPRECATED => self::LEVEL_INFO,
        );

        return $this->log($map[$code] ?? self::LEVEL_DEBUG, $message);
    }

    /**
     * Returns log files.
     *
     * @param string|null $from
     * @param string|null $to
     *
     * @return array
     */
    public function logFiles(string $from = null, string $to = null): array
    {
        if (!$this->hive['LOG']) {
            return array();
        }

        $pattern = $this->hive['LOG'].'log_*.log';
        $files = glob($pattern);

        if (!$from) {
            return $files;
        }

        $fromTime = strtotime($from);
        $toTime = $to ? strtotime($to) : $fromTime;

        if (!$fromTime || !$toTime) {
            return array();
        }

        $filteredFiles = array();
        $start = 4;
        $end = 10;

        foreach ($files as $key => $file) {
            $fileTime = strtotime(substr(basename($file), $start, $end));

            if ($fileTime && ($fileTime >= $fromTime && $fileTime <= $toTime)) {
                $filteredFiles[] = $file;
            }
        }

        return $filteredFiles;
    }

    /**
     * Remove log files.
     *
     * @param string|null $from
     * @param string|null $to
     *
     * @return Fw
     */
    public function logClear(string $from = null, string $to = null): Fw
    {
        foreach ($this->logFiles($from, $to) as $file) {
            unlink($file);
        }

        return $this;
    }

    /**
     * Returns path from alias name.
     *
     * @param string $alias
     * @param mixed  $args
     * @param mixed  $query
     *
     * @return string
     */
    public function alias(string $alias, $args = null, $query = null): string
    {
        $queryPart = rtrim('?'.(is_array($query) ? http_build_query($query) : $query), '?');

        if (isset($this->hive['ROUTE_ALIASES'][$alias])) {
            $pattern = $this->hive['ROUTE_ALIASES'][$alias];

            if ($args) {
                $ctr = 0;
                $mArgs = $args;
                $wild = '/(?:@(\w+))|((?s)\((?:[^()]+|(?R))*+\))|(?:(\*)$)/';

                if (is_string($args)) {
                    parse_str($args, $mArgs);
                }

                $pattern = preg_replace_callback($wild, function ($m) use ($mArgs, &$ctr) {
                    if (isset($m[1]) && array_key_exists($m[1], $mArgs)) {
                        ++$ctr;

                        return $mArgs[$m[1]];
                    } elseif (isset($m[2]) && $m[2]) {
                        $pick = array_slice($mArgs, $ctr++, 1);
                        $val = reset($pick);

                        return false === $val ? $m[0] : $val;
                    }

                    return implode('/', array_slice($mArgs, $ctr));
                }, $pattern);
            }

            return $pattern.$queryPart;
        }

        return '/'.ltrim($alias, '/').$queryPart;
    }

    /**
     * Returns path from alias name, with BASE and FRONT as prefix.
     *
     * @param string $alias
     * @param mixed  $args
     * @param mixed  $query
     *
     * @return string
     */
    public function path(string $alias, $args = null, $query = null): string
    {
        return $this->hive['BASE'].$this->hive['FRONT'].$this->alias($alias, $args, $query);
    }

    /**
     * Returns asset path.
     *
     * @param string $path
     *
     * @return string
     */
    public function asset(string $path): string
    {
        return $this->hive['BASE'].'/'.$path.rtrim('?'.$this->hive['ASSET'], '?');
    }

    /**
     * Reroute to specified URI.
     *
     * @param mixed $target
     * @param bool  $permanent
     *
     * @return Fw
     */
    public function reroute($target = null, bool $permanent = false): Fw
    {
        if (!$target) {
            $path = $this->hive['PATH'];
            $url = $this->hive['URL'];
        } elseif (is_array($target)) {
            $path = $this->alias(...$target);
        } elseif (isset($this->hive['ROUTE_ALIASES'][$target])) {
            $path = $this->hive['ROUTE_ALIASES'][$target];
        } elseif (preg_match('/^(\w+)(?:\(([^(]+)\))?((?:\?).+)?$/', $target, $match)) {
            parse_str(strtr($match[2] ?? '', ',', '&'), $args);
            $path = $this->alias($match[1], $args, $match[3] ?? null);
        } else {
            $path = $target;
        }

        if (empty($url)) {
            $url = $path;

            if ('/' === $path[0] && (empty($path[1]) || '/' !== $path[1])) {
                $url = $this->hive['BASEURL'].$this->hive['FRONT'].$path;
            }
        }

        if ($this->trigger(self::EVENT_REROUTE, array($url, $permanent))) {
            return $this;
        }

        if ($this->hive['CLI']) {
            $this->mock('GET '.$path.' cli');

            return $this;
        }

        $code = 302 - (int) $permanent;

        $this->status($code);
        $this->hive['RESPONSE']['Location'] = $url;
        $this->hive['OUTPUT'] = null;

        return $this->send();
    }

    /**
     * Register rest controller.
     *
     * @param string      $path
     * @param string      $class
     * @param string|null $alias
     * @param string|null $mode
     * @param int         $ttl
     * @param int         $kbps
     *
     * @return Fw
     */
    public function rest(string $path, string $class, string $alias = null, string $mode = null, int $ttl = 0, int $kbps = 0): Fw
    {
        $item_path = $path.'/@item';
        $item_alias = $alias ? $alias.'_item' : null;

        return $this
            ->route('GET '.$alias.' '.$path.' '.$mode, $class.'->all', $ttl, $kbps)
            ->route('POST '.$alias.' '.$path.' '.$mode, $class.'->create', $ttl, $kbps)
            ->route('GET '.$item_alias.' '.$item_path.' '.$mode, $class.'->get', $ttl, $kbps)
            ->route('PUT '.$item_alias.' '.$item_path.' '.$mode, $class.'->put', $ttl, $kbps)
            ->route('DELETE '.$item_alias.' '.$item_path.' '.$mode, $class.'->delete', $ttl, $kbps);
    }

    /**
     * Register route for a class.
     *
     * @param string $class
     * @param array  $routes
     *
     * @return Fw
     */
    public function controller(string $class, array $routes): Fw
    {
        foreach ($routes as $route => $def) {
            list($method, $ttl, $kbps) = ((array) $def) + array(1 => 0, 0);

            $this->route($route, $class.'->'.$method, $ttl, $kbps);
        }

        return $this;
    }

    /**
     * Redirect a route to another URL.
     *
     * @param string $expr
     * @param string $target
     * @param bool   $permanent
     *
     * @return Fw
     */
    public function redirect(string $expr, string $target, bool $permanent = true): Fw
    {
        return $this->route($expr, function () use ($target, $permanent) {
            return $this->reroute($target, $permanent);
        });
    }

    /**
     * Bind handler to route pattern.
     *
     * @param string $route
     * @param mixed  $handler
     * @param int    $ttl
     * @param int    $kbps
     *
     * @return Fw
     */
    public function route(string $route, $handler, int $ttl = 0, int $kbps = 0): Fw
    {
        $pattern = '/^([\w+|]+)(?:\h+(\w+))?(?:\h+(\/[^\h]*))?(?:\h+(all|ajax|cli|sync))?$/i';

        preg_match($pattern, trim($route), $match);

        if (count($match) < 3) {
            throw new \LogicException(sprintf('Route should contains at least a verb and path, given "%s".', $route));
        }

        list($verbs, $alias, $path, $mode) = array_slice($match, 1) + array(1 => '', '', 'all');

        if (!$path) {
            if (empty($this->hive['ROUTE_ALIASES'][$alias])) {
                throw new \LogicException(sprintf('Route "%s" does not exists.', $alias));
            }

            $path = $this->hive['ROUTE_ALIASES'][$alias];
        }

        $ptr = ++$this->hive['ROUTE_CTR'];
        $code = constant('self::REQ_'.strtoupper($mode));

        foreach (array_filter(explode('|', strtoupper($verbs))) as $verb) {
            $this->hive['ROUTES'][$path][$code][$verb] = $ptr;
        }

        if ($alias) {
            $this->hive['ROUTE_ALIASES'][$alias] = $path;
        }

        $this->hive['ROUTE_HANDLERS'][$ptr] = array($handler, $alias, $ttl, $kbps);

        return $this;
    }

    /**
     * Mock request.
     *
     * @param string      $route
     * @param array|null  $args
     * @param array|null  $server
     * @param string|null $body
     */
    public function mock(string $route, array $args = null, array $server = null, string $body = null): void
    {
        $tmp = array_map('trim', explode(' ', $route));

        if (1 === count($tmp)) {
            throw new \LogicException(sprintf('Mock should contains at least a verb and path, given "%s".', $route));
        }

        $verb = strtoupper($tmp[0]);
        $targetExpr = urldecode($tmp[1]);
        $mode = strtolower($tmp[2] ?? 'none');
        $target = strstr($targetExpr.'?', '?', true);
        $query = trim(strstr($targetExpr.'?', '?'), '?');
        $path = $target;

        if (isset($this->hive['ROUTE_ALIASES'][$target])) {
            $path = $this->hive['ROUTE_ALIASES'][$target];
        } elseif (preg_match('/^(\w+)\(([^(]+)\)$/', $target, $match)) {
            parse_str(strtr($match[2], ',', '&'), $args);
            $path = $this->alias($match[1], $args);
        }

        unset($this['SENT'], $this['RESPONSE'], $this['OUTPUT'], $this['BODY']);

        $this->hive['VERB'] = $verb;
        $this->hive['PATH'] = $path;
        $this->hive['URI'] = $this->hive['BASE'].$path.$query;
        $this->hive['AJAX'] = 'ajax' === $mode;
        $this->hive['CLI'] = 'cli' === $mode;
        $this->hive['POST'] = 'POST' === $verb ? $args : array();
        $this->hive['URL'] = $this->hive['BASEURL'].$this->hive['URI'];

        parse_str(ltrim($query, '?'), $this->hive['GET']);

        if (in_array($verb, array('GET', 'HEAD'))) {
            $this->hive['GET'] = array_merge($this->hive['GET'], (array) $args);
        } else {
            $this->hive['BODY'] = $body ?: http_build_query((array) $args);
        }

        $this->hive['SERVER'] = (array) $server + (array) $this->hive['SERVER'];

        $this->run();
    }

    /**
     * Run kernel logic.
     */
    public function run(): void
    {
        $this->trigger(self::EVENT_START, null, true);

        try {
            $this->doRun();
        } catch (\Throwable $e) {
            $this->handleException($e);
        }
    }

    /**
     * Send response headers and content.
     *
     * @param int|null    $code
     * @param array|null  $headers
     * @param string|null $content
     * @param string|null $mime
     * @param int         $kbps
     *
     * @return Fw
     */
    public function send(int $code = null, array $headers = null, string $content = null, string $mime = null, int $kbps = 0): Fw
    {
        if ($this->hive['SENT']) {
            return $this;
        }

        $this->hive['SENT'] = true;
        $this->hive['RESPONSE'] = $headers ?? $this->hive['RESPONSE'];
        $this->hive['OUTPUT'] = $content ?? $this->hive['OUTPUT'];
        $this->hive['MIME'] = $mime ?? $this->hive['MIME'];

        if ($code) {
            $this->status($code);
        }

        if (!$this->hive['CLI'] && !headers_sent()) {
            $this->sendHeaders();
        }

        if (!$this->hive['QUIET'] && $this->hive['OUTPUT']) {
            $this->sendContent($kbps);
        }

        return $this;
    }

    /**
     * Sets cache metadata headers.
     *
     * @param int $secs
     *
     * @return Fw
     */
    public function expire(int $secs = 0): Fw
    {
        $headers = &$this->hive['RESPONSE'];

        $headers['X-Powered-By'] = $this->hive['PACKAGE'];
        $headers['X-Frame-Options'] = $this->hive['XFRAME'];
        $headers['X-XSS-Protection'] = '1; mode=block';
        $headers['X-Content-Type-Options'] = 'nosniff';

        if ('GET' === $this->hive['VERB'] && $secs) {
            $time = time();
            unset($headers['Pragma']);

            $headers['Cache-Control'] = 'max-age='.$secs;
            $headers['Expires'] = gmdate('r', $time + $secs);
            $headers['Last-Modified'] = gmdate('r');
        } else {
            $headers['Pragma'] = 'no-cache';
            $headers['Cache-Control'] = 'no-cache, no-store, must-revalidate';
            $headers['Expires'] = gmdate('r', 0);
        }

        unset($headers);

        return $this;
    }

    /**
     * Sets response status code.
     *
     * @param int $code
     *
     * @return Fw
     */
    public function status(int $code): Fw
    {
        $name = 'self::HTTP_'.$code;

        if (!defined($name)) {
            throw new \DomainException(sprintf('Unsupported HTTP code: %d.', $code));
        }

        $this->hive['CODE'] = $code;
        $this->hive['STATUS'] = constant($name);

        return $this;
    }

    /**
     * Send error response.
     *
     * @param int         $code
     * @param string|null $message
     * @param array|null  $trace
     * @param array|null  $headers
     * @param int|null    $level
     *
     * @return Fw
     */
    public function error(int $code, string $message = null, array $trace = null, array $headers = null, int $level = null): Fw
    {
        $this->status($code);

        if (!$trace) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_shift($trace);
        }

        $status = $this->hive['STATUS'];
        $text = $message ?: 'HTTP '.$code.' ('.$this->hive['VERB'].' '.$this->hive['PATH'].')';
        $mTrace = $this->hive['DEBUG'] ? $this->trace($trace) : '';

        $prior = $this->hive['ERROR'];
        $this->hive['ERROR'] = array(
            'code' => $code,
            'status' => $status,
            'text' => $text,
            'trace' => $mTrace,
        );

        if ($prior) {
            return $this;
        }

        $this->hive['RESPONSE'] = (array) $headers;
        $this->expire(-1)->logByCode($level ?? E_USER_ERROR, $text.PHP_EOL.$mTrace);

        try {
            $response = $this->trigger(self::EVENT_ERROR, array($message, $mTrace), true);
        } catch (\Throwable $e) {
            $response = true;
            $this->hive['ERROR'] = null;
            $this->handleException($e);
        }

        if ($response) {
            return $this->sendResponse($response);
        }

        if ($this->hive['AJAX']) {
            $this->hive['MIME'] = 'application/json';
            $this->hive['OUTPUT'] = json_encode(array_filter($this->hive['ERROR']));
        } elseif ($this->hive['CLI']) {
            $this->hive['OUTPUT'] = 'Status : '.$status.PHP_EOL.'Text : '.$text.PHP_EOL.$mTrace.PHP_EOL;
        } else {
            $this->hive['MIME'] = 'text/html';
            $this->hive['OUTPUT'] = '<!DOCTYPE html>'.
                '<html>'.
                '<head>'.
                  '<meta charset="UTF-8">'.
                  '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">'.
                  '<title>'.$code.' '.$status.'</title>'.
                '</head>'.
                '<body>'.
                  '<h1>'.$status.'</h1>'.
                  '<p>'.$text.'</p>'.
                  ($mTrace ? '<pre>'.$mTrace.'</pre>' : '').
                '</body>'.
                '</html>';
        }

        return $this->send();
    }

    /**
     * Returns language reference.
     *
     * @param string $key
     *
     * @return string|null
     */
    private function langRef(string $key): ?string
    {
        $parts = explode('.', $key);
        $ref = $this->hive['DICT'];

        foreach ($parts as $part) {
            if (!$ref || !array_key_exists($part, $ref)) {
                return null;
            }

            $ref = $ref[$part];
        }

        if (null !== $ref && !is_string($ref)) {
            throw new \UnexpectedValueException('Message reference is not a string.');
        }

        return $ref;
    }

    /**
     * Returns languages.
     *
     * @return array
     */
    private function langLanguages(): array
    {
        $languages = preg_replace('/\h+|;q=[0-9.]+/', '', $this->hive['LANGUAGE']).','.$this->hive['FALLBACK'];
        $final = array();

        foreach ($this->split($languages) as $lang) {
            if (preg_match('/^(\w{2})(?:-(\w{2}))?\b/i', $lang, $parts)) {
                // Generic language
                $final[] = $parts[1];

                if (isset($parts[2])) {
                    // Specific language
                    $final[] = $parts[1].'-'.strtoupper($parts[2]);
                }
            }
        }

        return array_unique($final);
    }

    /**
     * Returns dictionary.
     *
     * @return array
     */
    private function langLoad(): array
    {
        $dict = array();

        foreach ($this->langLanguages() as $lang) {
            foreach ($this->split($this->hive['LOCALES']) as $locale) {
                $file = $locale.$lang.'.php';
                $dict = array_replace_recursive($dict, (array) self::requireFile($file));
            }
        }

        return $dict;
    }

    /**
     * Returns resolved function parameters.
     *
     * @param ReflectionFunctionAbstract $ref
     * @param array|null                 $args
     *
     * @return array
     */
    private function resolveArgs(\ReflectionFunctionAbstract $ref, array $args = null): array
    {
        $resolved = array();
        $rest = 0;

        if (($max = $ref->getNumberOfParameters()) > 0) {
            $params = $ref->getParameters();
            $names = array_keys((array) $args);

            for ($i = 0; $i < $max; ++$i) {
                if ($params[$i]->isVariadic()) {
                    break;
                }

                $param = $params[$i];
                $name = $names[$rest] ?? null;
                $val = $args[$name] ?? null;

                if ($class = $param->getClass()) {
                    if ($val instanceof $class->name) {
                        $resolved[] = $val;
                        ++$rest;
                    } elseif (is_string($val) && is_object($obj = $this->resolveArg($val, true))) {
                        $resolved[] = $obj;
                        ++$rest;
                    } else {
                        $resolved[] = $this->service($class->name);
                    }
                } elseif ((null !== $name) || ($name === $param->name)) {
                    $resolved[] = is_string($val) ? $this->resolveArg($val) : $val;
                    ++$rest;
                }
            }
        }

        return array_merge($resolved, array_values(array_slice((array) $args, $rest)));
    }

    /**
     * Returns resolved named function parameter.
     *
     * @param string $val
     * @param bool   $resolveClass
     *
     * @return mixed
     */
    private function resolveArg(string $val, bool $resolveClass = false)
    {
        if ($resolveClass && class_exists($val)) {
            return $this->service($val);
        }

        if (preg_match('/^(.+)?%([.\w]+)%(.+)?$/', $val, $match)) {
            if (array_key_exists($match[2], $this->hive)) {
                // it does exists in hive
                return ($match[1] ?? null).$this->hive[$match[2]].($match[3] ?? null);
            }

            // it is a service alias
            return $this->service($match[2]);
        }

        return $val;
    }

    /**
     * Returns trace as string.
     *
     * @param array $trace
     *
     * @return string
     */
    private function trace(array $trace): string
    {
        $out = '';
        $fix = array(
            'function' => null,
            'line' => null,
            'file' => '',
            'class' => null,
            'type' => null,
        );

        foreach ($trace as $key => $frame) {
            $frame += $fix;

            $out .= '['.$frame['file'].':'.$frame['line'].'] '.$frame['class'].$frame['type'].$frame['function'];

            if (0 === $key) {
                $out .= ' [['.count($trace).']]';
            }

            $out .= "\n";
        }

        return $out;
    }

    /**
     * Handle thrown exception.
     *
     * @param Throwable $e
     */
    private function handleException(\Throwable $e)
    {
        $httpCode = 500;
        $errorCode = $e->getCode();
        $message = $e->getMessage();
        $trace = $e->getTrace();
        $headers = null;

        array_unshift($trace, array(
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'function' => '***emulated***',
        ));

        if ($e instanceof HttpException) {
            $httpCode = $errorCode;
            $errorCode = E_USER_ERROR;
            $headers = $e->getHeaders();
        }

        $this->error($httpCode, $message, $trace, $headers, $errorCode);
    }

    /**
     * Kernel logic.
     */
    private function doRun()
    {
        if ($response = $this->trigger(self::EVENT_PREROUTE)) {
            return $this->sendResponse($response);
        }

        if (!$this->hive['ROUTES']) {
            throw new HttpException('No route defined.');
        }

        if (!$route = $this->findRoute()) {
            throw new HttpException(null, 404);
        }

        list($handler, $alias, $ttl, $kbps, $pattern, $params) = $route;
        $hash = $this->hash($this->hive['VERB'].' '.$this->hive['PATH']).'.url';
        $checkCache = $ttl && in_array($this->hive['VERB'], array('GET', 'HEAD'));

        if ($checkCache) {
            if ($cache = $this->getRequestCache($hash, $ttl, $kbps)) {
                list($modified, $response) = $cache;

                if ($modified) {
                    $this->expire($modified);
                }

                return $this->send(...$response);
            }

            $this->expire($ttl);
        } else {
            $this->expire(0);
        }

        if (!$this->hive['RAW'] && !$this->hive['BODY']) {
            $this->hive['BODY'] = file_get_contents('php://input');
        }

        $this->hive['PARAMS'] = $params;
        $this->hive['PATTERN'] = $pattern;
        $this->hive['ALIAS'] = $alias;

        $controller = is_string($handler) ? $this->grabController($handler) : $handler;

        if (!is_callable($controller)) {
            throw new HttpException(null, 405);
        }

        $args = (array) ($this->trigger(self::EVENT_CONTROLLER_ARGS, array($controller, $params)) ?? $params);
        $result = $this->call($controller, $args);

        if ($response = $this->trigger(self::EVENT_POSTROUTE, array($result))) {
            $this->sendResponse($response);
        } else {
            if (is_string($result)) {
                $this->hive['OUTPUT'] = $result;
            } elseif (is_callable($result)) {
                $result($this);
            } elseif (is_array($result)) {
                $this->hive['OUTPUT'] = json_encode($result);
                $this->hive['MIME'] = 'application/json';
            }

            $this->send();
        }

        if ($checkCache) {
            $this->setRequestCache($hash, $ttl);
        }
    }

    /**
     * Returns found route.
     *
     * @return array|null
     */
    private function findRoute(): ?array
    {
        $modifier = $this->hive['CASELESS'] ? 'i' : '';
        $patterns = array(
            '/@(\w+)/',
            '/\*$/',
        );
        $replaces = array(
            '(?<$1>[^\\/]+)',
            '(?<_p>.+)',
        );

        foreach ($this->hive['ROUTES'] as $pattern => $routes) {
            $wild = '~^'.preg_replace($patterns, $replaces, $pattern).'$~'.$modifier;

            if (preg_match($wild, $this->hive['PATH'], $match)) {
                if ($handler = $this->findHandler($routes)) {
                    return $handler + array(4 => $pattern, $this->collectParams($match));
                }

                break;
            }
        }

        return null;
    }

    /**
     * Returns array of filtered route match result.
     *
     * @param array $match
     *
     * @return array
     */
    private function collectParams(array $match): array
    {
        $params = array();
        $skipNext = false;

        foreach ($match as $key => $value) {
            if (0 === $key || $skipNext) {
                $skipNext = false;
                continue;
            }

            if (is_string($key)) {
                if ('_p' === $key) {
                    array_push($params, ...explode('/', $value));
                } else {
                    $params[$key] = $value;
                }

                $skipNext = true;
            } else {
                $params[] = $value;
            }
        }

        return $params;
    }

    /**
     * Returns route handler and definition.
     *
     * @param array $routes
     *
     * @return array|null
     */
    private function findHandler(array $routes): ?array
    {
        $mode = $this->hive['AJAX'] ? self::REQ_AJAX : ($this->hive['CLI'] ? self::REQ_CLI : self::REQ_SYNC);
        $route = $routes[$mode] ?? $routes[self::REQ_ALL] ?? null;
        $handlerId = $route[$this->hive['VERB']] ?? -1;

        return $this->hive['ROUTE_HANDLERS'][$handlerId] ?? null;
    }

    /**
     * Grab controller from handler expression.
     *
     * @param string $handler
     *
     * @return mixed
     */
    private function grabController(string $handler)
    {
        $check = $this->grab($handler, false);

        if (is_array($check)) {
            if (!class_exists(reset($check))) {
                throw new HttpException(null, 404);
            }

            return $this->grab($handler);
        }

        return $handler;
    }

    /**
     * Check is current request cached.
     *
     * @param string $key
     * @param int    $ttl
     * @param int    $kbps
     *
     * @return array|null
     */
    private function getRequestCache(string $key, int $ttl, int $kbps): ?array
    {
        $cache = $this->cache('get', $key);

        if (!$cache || $cache->isExpired()) {
            return null;
        }

        $time = time();
        $expDate = $this->hive['REQUEST']['If-Modified-Since'] ?? 0;
        $notModified = $expDate && strtotime($expDate) + $ttl > $time;

        if ($notModified) {
            return array(null, array(304));
        }

        list($code, $headers, $response, $mime) = $cache->getValue();

        return array(
            // new expire date
            $cache->getTtl() + $ttl - $time,
            // response
            array(
                $code,
                (array) $this->hive['RESPONSE'] + (array) $headers,
                $response,
                $mime,
                $kbps,
            ),
        );
    }

    /**
     * Cache output.
     *
     * @param string $key
     * @param int    $ttl
     */
    private function setRequestCache(string $key, int $ttl): void
    {
        if ($this->hive['OUTPUT'] && is_string($this->hive['OUTPUT'])) {
            $cache = array(
                $this->hive['CODE'],
                $this->hive['RESPONSE'],
                $this->hive['OUTPUT'],
                $this->hive['MIME'],
            );

            $this->cache('set', $key, new CacheItem($cache, $ttl));
        }
    }

    /**
     * Handle response from trigger result.
     *
     * @param string|array $response
     *
     * @return Fw
     */
    private function sendResponse($response): Fw
    {
        $mResponse = $response;

        if (is_string($response)) {
            $mResponse = array(null, null, $response);
        }

        if (is_array($mResponse)) {
            $this->send(...$mResponse);
        }

        return $this;
    }

    /**
     * Send response headers.
     */
    private function sendHeaders(): void
    {
        $protocol = $this->hive['PROTOCOL'];
        $code = $this->hive['CODE'];
        $status = $this->hive['STATUS'];
        $mime = $this->hive['MIME'];
        $headers = $this->hive['RESPONSE'];
        $cookies = $this->collectCookies();

        foreach ($cookies as $cookie) {
            setcookie(...$cookie);
        }

        foreach (array_filter((array) $headers, 'is_scalar') as $name => $value) {
            header($name.': '.$value);
        }

        if ($mime && (!$headers || !preg_grep('/^content-type$/i', array_keys($headers)))) {
            header('Content-Type: '.$mime);
        }

        header($protocol.' '.$code.' '.$status, true);
    }

    /**
     * Send response content.
     *
     * @param int $kbps
     */
    private function sendContent(int $kbps = 0): void
    {
        if ($kbps <= 0) {
            echo $this->hive['OUTPUT'];

            return;
        }

        $now = microtime(true);
        $ctr = 0;

        foreach (str_split($this->hive['OUTPUT'], 1024) as $part) {
            // Throttle output
            ++$ctr;

            if ($ctr / $kbps > ($elapsed = microtime(true) - $now) && !connection_aborted()) {
                usleep((int) (1e6 * ($ctr / $kbps - $elapsed)));
            }

            echo $part;
        }
    }

    /**
     * Returns prepared cookies to send.
     *
     * @param array      $jar
     * @param array|null $current
     * @param array|null $init
     *
     * @return array
     */
    private function collectCookies(): array
    {
        $jar = array_combine(range(2, count($this->hive['JAR']) + 1), array_values($this->hive['JAR']));
        $init = (array) $this->init['COOKIE'];
        $current = (array) $this->hive['COOKIE'];
        $cookies = array();

        foreach ($current as $name => $value) {
            if (!isset($init[$name]) || $init[$name] !== $value) {
                $cookie = is_array($value) ? $value : array($value);
                array_unshift($cookie, $name);

                $cookies[$name] = $cookie + $jar;
            }
        }

        foreach ($init as $name => $value) {
            if (!isset($current[$name])) {
                $cookies[$name] = array($name, '', strtotime('-1 year')) + $jar;
            }
        }

        return $cookies;
    }

    /**
     * Find class file with extension.
     *
     * @param string $class
     * @param string $ext
     *
     * @return string|null
     */
    private function findFileWithExtension(string $class, string $ext): ?string
    {
        // PSR-4 lookup
        $logicalPathPsr4 = strtr($class, '\\', DIRECTORY_SEPARATOR).$ext;
        $autoload = (array) $this->hive['AUTOLOAD'] + array('Fal\\Stick\\' => __DIR__.'/');
        $subPath = $class;

        while (false !== $lastPos = strrpos($subPath, '\\')) {
            $subPath = substr($subPath, 0, $lastPos);
            $search = $subPath.'\\';

            if (isset($autoload[$search])) {
                $pathEnd = DIRECTORY_SEPARATOR.substr($logicalPathPsr4, $lastPos + 1);

                foreach ($this->split($autoload[$search]) as $dir) {
                    if (is_file($file = rtrim($dir, '/\\').$pathEnd)) {
                        return $file;
                    }
                }
            }
        }

        // PSR-4 fallback dirs
        foreach ($this->split($this->hive['AUTOLOAD_FALLBACK']) as $dir) {
            if (file_exists($file = rtrim($dir, '/\\').DIRECTORY_SEPARATOR.$logicalPathPsr4)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Returns true if hive member exists.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->hive);
    }

    /**
     * Returns hive member value.
     *
     * @param mixed $offset
     *
     * @return mixed
     */
    public function &offsetGet($offset)
    {
        if (!array_key_exists($offset, $this->hive)) {
            $this->hive[$offset] = null;
        }

        if ('SESSION' === $offset) {
            if (!headers_sent() && PHP_SESSION_ACTIVE !== session_status()) {
                session_start();
            }

            $this->hive[$offset] = &$GLOBALS['_SESSION'];
        }

        return $this->hive[$offset];
    }

    /**
     * Sets hive member value.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->hive[$offset] = $value;

        switch ((string) $offset) {
            case 'CACHE':
                $this->hive['CACHE_DRY'] = true;

                if ($value) {
                    $this->getRule($value, null, $id);
                    unset($this->hive['SERVICES'][$value], $this->hive['SERVICES'][$id]);
                }
                break;
            case 'FALLBACK':
            case 'LOCALES':
            case 'LANGUAGE':
                $this->hive['DICT'] = $this->langLoad();
                break;
        }
    }

    /**
     * Remove hive member.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        if ('SESSION' === $offset && PHP_SESSION_ACTIVE === session_status()) {
            session_unset();
            session_destroy();
        }

        if (array_key_exists($offset, $this->init)) {
            $this->hive[$offset] = $this->init[$offset];
        } else {
            unset($this->hive[$offset]);
        }
    }
}

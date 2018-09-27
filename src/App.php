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
 * It contains the logic of kernel, event dispatcher and listener, route handling,
 * route path generation, services and some other helpers.
 *
 * Request and response also live in this class.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class App implements \ArrayAccess
{
    const PACKAGE = 'Stick-Framework';
    const VERSION = 'v0.1.0';

    const REQ_ALL = 0;
    const REQ_AJAX = 1;
    const REQ_CLI = 2;
    const REQ_SYNC = 4;

    const EVENT_BOOT = 'app_boot';
    const EVENT_ROUTE = 'app_route';
    const EVENT_AFTER_ROUTE = 'app_after_route';
    const EVENT_CONTROLLER_ARGS = 'app_controller_args';
    const EVENT_REROUTE = 'app_reroute';
    const EVENT_ERROR = 'app_error';

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

    const LOG_LEVEL_EMERGENCY = 'emergency';
    const LOG_LEVEL_ALERT = 'alert';
    const LOG_LEVEL_CRITICAL = 'critical';
    const LOG_LEVEL_ERROR = 'error';
    const LOG_LEVEL_WARNING = 'warning';
    const LOG_LEVEL_NOTICE = 'notice';
    const LOG_LEVEL_INFO = 'info';
    const LOG_LEVEL_DEBUG = 'debug';

    const LOG_LEVELS = array(
        self::LOG_LEVEL_EMERGENCY => 0,
        self::LOG_LEVEL_ALERT => 1,
        self::LOG_LEVEL_CRITICAL => 2,
        self::LOG_LEVEL_ERROR => 3,
        self::LOG_LEVEL_WARNING => 4,
        self::LOG_LEVEL_NOTICE => 5,
        self::LOG_LEVEL_INFO => 6,
        self::LOG_LEVEL_DEBUG => 7,
    );

    /**
     * Application variables hive.
     *
     * System variables always in *UPPERCASED* name.
     *
     * @var array
     */
    private $_hive;

    /**
     * A copy of variables hive.
     *
     * @var array
     */
    private $_init;

    /**
     * Class constructor.
     *
     * @param array|null $server  Equivalent to $_SERVER
     * @param array|null $request Equivalent to $_POST
     * @param array|null $query   Equivalent to $_GET
     * @param array|null $cookie  Equivalent to $_COOKIE
     */
    public function __construct(array $server = null, array $request = null, array $query = null, array $cookie = null)
    {
        $now = microtime(true);
        $charset = 'UTF-8';

        ini_set('default_charset', $charset);

        $scriptName = $server['SCRIPT_NAME'] ?? $_SERVER['SCRIPT_NAME'];
        $scriptFilename = $server['SCRIPT_FILENAME'] ?? $_SERVER['SCRIPT_FILENAME'];
        $cli = 'cli' === PHP_SAPI;
        $verb = $server['REQUEST_METHOD'] ?? 'GET';
        $host = $server['SERVER_NAME'] ?? gethostname();
        $uri = $server['REQUEST_URI'] ?? '/';
        $uriHost = preg_match('/^\w+:\/\//', $uri) ? '' : '//'.$host;
        $url = parse_url($uriHost.$uri);
        $port = (int) ($server['HTTP_X_FORWARDED_PORT'] ?? $server['SERVER_PORT'] ?? 80);
        $secure = 'on' === ($server['HTTPS'] ?? '') || 'https' === ($server['HTTP_X_FORWARDED_PROTO'] ?? '');
        $base = rtrim($this->fixslashes(dirname($scriptName)), '/');
        $entry = '/'.basename($scriptName);

        if ($cli) {
            $base = '';
            $entry = '';
        }

        $schar = chr(115 * ((int) !$secure));
        $scheme = rtrim('https', $schar);
        $baseUrl = $scheme.'://'.$host.(in_array($port, array(80, 443)) ? '' : ':'.$port);
        $cookieJar = array(
            'expire' => 0,
            'path' => $base ?: '/',
            'domain' => (false === strpos($host, '.') || filter_var($host, FILTER_VALIDATE_IP)) ? '' : $host,
            'secure' => $secure,
            'httponly' => true,
        );

        $this->_init = $this->_hive = array(
            'AGENT' => $server['HTTP_X_OPERAMINI_PHONE_UA'] ?? $server['HTTP_X_SKYFIRE_PHONE'] ?? $server['HTTP_USER_AGENT'] ?? '',
            'AJAX' => 'XMLHttpRequest' === ($server['HTTP_X_REQUESTED_WITH'] ?? null),
            'ALIAS' => null,
            'AUTOLOAD' => array('Fal\\Stick\\' => __DIR__.'/'),
            'BASE' => $base,
            'BASEURL' => $baseUrl.$base,
            'BODY' => null,
            'CACHE' => null,
            'CACHE_ENGINE' => null,
            'CACHE_REF' => null,
            'CASELESS' => false,
            'CLI' => $cli,
            'CODE' => 200,
            'COOKIE' => $cookie,
            'DEBUG' => 0,
            'DICT' => null,
            'DNSBL' => null,
            'DRY' => true,
            'ENCODING' => $charset,
            'ENTRY' => $entry,
            'ERROR' => false,
            'EVENTS' => null,
            'EVENTS_ONCE' => null,
            'EXEMPT' => null,
            'FALLBACK' => 'en',
            'FRAGMENT' => $url['fragment'] ?? null,
            'HEADERS' => null,
            'HOST' => $host,
            'IP' => $server['HTTP_X_CLIENT_IP'] ?? $this->cutbefore($server['HTTP_X_FORWARDED_FOR'] ?? '', ',', $server['REMOTE_ADDR'] ?? ''),
            'JAR' => $cookieJar,
            'KBPS' => 0,
            'LANGUAGE' => null,
            'LOCALES' => './dict/',
            'LOG' => null,
            'PACKAGE' => self::PACKAGE,
            'PARAMS' => null,
            'PATH' => $this->cutprefix($this->cutprefix(urldecode($url['path']), $base), $entry, '/'),
            'PATTERN' => null,
            'PORT' => $port,
            'PROTOCOL' => $server['SERVER_PROTOCOL'] ?? 'HTTP/1.0',
            'QUERY' => $query,
            'QUIET' => false,
            'RAW' => false,
            'REALM' => $baseUrl.$uri,
            'REQUEST' => $request,
            'RESPONSE' => null,
            'ROUTE_ALIASES' => array(),
            'ROUTE_HANDLER_CTR' => -1,
            'ROUTE_HANDLERS' => array(),
            'ROUTES' => array(),
            'SCHEME' => $scheme,
            'SEED' => $this->hash($host.$base),
            'SENT' => false,
            'SERVER' => (array) $server,
            'SERVICE_ALIASES' => array(),
            'SERVICE_RULES' => array(),
            'SERVICES' => array(),
            'SESSION' => null,
            'STATUS' => self::HTTP_200,
            'TEMP' => './var/',
            'THRESHOLD' => self::LOG_LEVEL_ERROR,
            'TIME' => $now,
            'TRACE' => $this->fixslashes(realpath(dirname($scriptFilename).'/..').'/'),
            'URI' => $uri,
            'VERB' => $verb,
            'VERSION' => self::VERSION,
            'XFRAME' => 'SAMEORIGIN',
        );
        $this->_init['QUERY'] = $this->_init['REQUEST'] = null;
    }

    /**
     * Create App instance with ease.
     *
     * @param array|null $server  Equivalent to $_SERVER
     * @param array|null $request Equivalent to $_POST
     * @param array|null $query   Equivalent to $_GET
     * @param array|null $cookie  Equivalent to $_COOKIE
     *
     * @return App
     */
    public static function create(array $server = null, array $request = null, array $query = null, array $cookie = null): App
    {
        return new static($server, $request, $query, $cookie);
    }

    /**
     * Create App instance from globals environment.
     *
     * @return App
     */
    public static function createFromGlobals(): App
    {
        return new static($_SERVER, $_POST, $_GET, $_COOKIE);
    }

    /**
     * Returns true if val is null or false.
     *
     * @param mixed $val
     *
     * @return bool
     */
    public function notNullFalse($val): bool
    {
        return !(null === $val || false === $val);
    }

    /**
     * Returns trimmed member of array after split given string
     * by comma-, semi-colon, or pipe-separated string.
     *
     * @param string $str
     *
     * @return array
     */
    public function split(string $str): array
    {
        return array_map('trim', preg_split('/[,;\|]/', $str, 0, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * Returns normalized slashes.
     *
     * @param string $str
     *
     * @return string
     */
    public function fixslashes(string $str): string
    {
        return strtr($str, '\\', '/');
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
     * Returns substring before needle.
     *
     * If needle not found it returns defaults.
     *
     * @param string      $str
     * @param string      $needle
     * @param string|null $default
     * @param bool        $with_needle
     *
     * @return string
     */
    public function cutbefore(string $str, string $needle, string $default = null, bool $with_needle = false): string
    {
        if ($str && $needle && false !== ($pos = strpos($str, $needle))) {
            return substr($str, 0, $pos + ((int) $with_needle));
        }

        return $default ?? $str;
    }

    /**
     * Returns substring after needle.
     *
     * If needle not found it returns defaults.
     *
     * @param string      $str
     * @param string      $needle
     * @param string|null $default
     * @param bool        $with_needle
     *
     * @return string
     */
    public function cutafter(string $str, string $needle, string $default = null, bool $with_needle = false): string
    {
        if ($str && $needle && false !== ($pos = strrpos($str, $needle))) {
            return substr($str, $pos + ((int) !$with_needle));
        }

        return $default ?? $str;
    }

    /**
     * Returns substring after prefix removed.
     *
     * @param string      $str
     * @param string      $prefix
     * @param string|null $default
     *
     * @return string
     */
    public function cutprefix(string $str, string $prefix, string $default = null): string
    {
        if ($str && $prefix && substr($str, 0, $cut = strlen($prefix)) === $prefix) {
            return substr($str, $cut) ?: (string) $default;
        }

        return $default ?? $str;
    }

    /**
     * Returns substring after suffix removed.
     *
     * @param string      $str
     * @param string      $suffix
     * @param string|null $default
     *
     * @return string
     */
    public function cutsuffix(string $str, string $suffix, string $default = null): string
    {
        if ($str && $suffix && substr($str, $cut = -strlen($suffix)) === $suffix) {
            return substr($str, 0, $cut) ?: (string) $default;
        }

        return $default ?? $str;
    }

    /**
     * Returns true if string starts with prefix.
     *
     * @param string $str
     * @param string $prefix
     *
     * @return bool
     */
    public function startswith(string $str, string $prefix): bool
    {
        return substr($str, 0, strlen($prefix)) === $prefix;
    }

    /**
     * Returns true if string ends with suffix.
     *
     * @param string $str
     * @param string $suffix
     *
     * @return bool
     */
    public function endswith(string $str, string $suffix): bool
    {
        return substr($str, -1 * strlen($suffix)) === $suffix;
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
        return lcfirst(str_replace(' ', '', ucwords(strtr($str, '_', ' '))));
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
     * Returns "Title Case" string from snake_case.
     *
     * @param string $str
     *
     * @return string
     */
    public function titleCase(string $str): string
    {
        return ucwords(strtr($str, '_', ' '));
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
     * Returns the return value of required file.
     *
     * It does ensure loaded file have no access to caller scope.
     *
     * @param string $file
     * @param mixed  $default
     *
     * @return mixed
     */
    public function requireFile(string $file, $default = null)
    {
        $content = require $file;

        return $this->notNullFalse($content) ? $content : $default;
    }

    /**
     * Returns class name.
     *
     * @param string|object $class
     *
     * @return string
     */
    public function classname($class): string
    {
        $ns = is_object($class) ? get_class($class) : $class;
        $lastPos = strrpos($ns, '\\');

        return false === $lastPos ? $ns : substr($ns, $lastPos + 1);
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
     * Returns parsed string expression.
     *
     * Example:
     *
     *     foo:arg,arg2|bar:arg|baz:["array arg"]|qux:{"arg":"foo"}
     *
     * @param string $expr
     *
     * @return array
     */
    public function parseExpr(string $expr): array
    {
        $len = strlen($expr);
        $res = array();
        $tmp = '';
        $process = false;
        $args = array();
        $quote = null;
        $astate = 0;
        $jstate = 0;

        for ($ptr = 0; $ptr < $len; ++$ptr) {
            $char = $expr[$ptr];
            $prev = $expr[$ptr - 1] ?? null;

            if (('"' === $char || "'" === $char) && '\\' !== $prev) {
                if ($quote) {
                    $quote = $quote === $char ? null : $quote;
                } else {
                    $quote = $char;
                }
                $tmp .= $char;
            } elseif (!$quote) {
                if (':' === $char && 0 === $jstate) {
                    // next chars is arg
                    $args[] = $this->cast($tmp);
                    $tmp = '';
                } elseif (',' === $char && 0 === $astate && 0 === $jstate) {
                    if ($tmp) {
                        $args[] = $this->cast($tmp);
                        $tmp = '';
                    }
                } elseif ('|' === $char) {
                    $process = true;
                    if ($tmp) {
                        $args[] = $this->cast($tmp);
                        $tmp = '';
                    }
                } elseif ('[' === $char) {
                    $astate = 1;
                    $tmp .= $char;
                } elseif (']' === $char && 1 === $astate && 0 === $jstate) {
                    $astate = 0;
                    $args[] = json_decode($tmp.$char, true);
                    $tmp = '';
                } elseif ('{' === $char) {
                    $jstate = 1;
                    $tmp .= $char;
                } elseif ('}' === $char && 1 === $jstate && 0 === $astate) {
                    $jstate = 0;
                    $args[] = json_decode($tmp.$char, true);
                    $tmp = '';
                } else {
                    $tmp .= $char;
                    $astate += '[' === $char ? 1 : (']' === $char ? -1 : 0);
                    $jstate += '{' === $char ? 1 : ('}' === $char ? -1 : 0);
                }
            } else {
                $tmp .= $char;
            }

            if (!$process && $ptr === $len - 1) {
                $process = true;
                if ('' !== $tmp) {
                    $args[] = $this->cast($tmp);
                    $tmp = '';
                }
            }

            if ($process) {
                if ($args) {
                    $res[array_shift($args)] = $args;
                    $args = array();
                }
                $process = false;
            }
        }

        return $res;
    }

    /**
     * Returns array of val.
     *
     * It does check given val, if it is not an array it splitted.
     *
     * @param string|array $val
     *
     * @return array
     *
     * @see App::split For detailed how string is splitted.
     */
    public function arr($val): array
    {
        return is_array($val) ? $val : $this->split((string) $val);
    }

    /**
     * Advanced array_column.
     *
     * @param array  $input
     * @param string $column_key
     *
     * @return array
     */
    public function column(array $input, string $column_key): array
    {
        return array_combine(array_keys($input), array_column($input, $column_key));
    }

    /**
     * Apply callable to each member of an array.
     *
     * @param array    $args
     * @param callable $callable
     * @param bool     $one
     *
     * @return array
     */
    public function walk(array $args, callable $callable, bool $one = true): array
    {
        $result = array();

        foreach ($args as $key => $arg) {
            $result[$key] = call_user_func_array($callable, $one ? array($arg) : (array) $arg);
        }

        return $result;
    }

    /**
     * Throws exception if it should be thrown.
     *
     * @param bool   $throw
     * @param string $message
     * @param string $exception
     *
     * @return App
     */
    public function throws(bool $throw, string $message, string $exception = 'LogicException'): App
    {
        if ($throw) {
            throw new $exception($message);
        }

        return $this;
    }

    /**
     * Returns ellapsed time since application prepared.
     *
     * @return string
     */
    public function ellapsedTime(): string
    {
        return number_format(microtime(true) - $this->_hive['TIME'], 5).' seconds';
    }

    /**
     * Override request method with Custom http method override or request post method hack.
     *
     * @return App
     */
    public function overrideRequestMethod(): App
    {
        $verb = $this->_hive['SERVER']['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? $this->_hive['VERB'];

        if ('POST' === $verb && isset($this->_hive['REQUEST']['_method'])) {
            $verb = strtoupper($this->_hive['REQUEST']['_method']);
        }

        $this->_hive['VERB'] = $verb;

        return $this;
    }

    /**
     * Convert console arguments to path and queries.
     *
     * @return App
     */
    public function emulateCliRequest(): App
    {
        if ($this->_hive['CLI'] && isset($this->_hive['SERVER']['argv'])) {
            $argv = $this->_hive['SERVER']['argv'] + array(1 => '/');

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

            $this->_hive['VERB'] = 'GET';
            $this->_hive['PATH'] = $uri['path'];
            $this->_hive['FRAGMENT'] = $uri['fragment'];
            $this->_hive['URI'] = $req;
            $this->_hive['REALM'] = $this->_hive['BASEURL'].$req;
            parse_str($uri['query'], $this->_hive['QUERY']);
        }

        return $this;
    }

    /**
     * Handle thrown exception.
     *
     * @param Throwable $e
     *
     * @return App
     */
    public function handleException(\Throwable $e): App
    {
        $message = $e->getMessage();
        $httpCode = 500;
        $errorCode = $e->getCode();
        $trace = $e->getTrace();

        array_unshift($trace, array(
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'function' => '***emulated***',
            'args' => array(),
        ));

        if ($e instanceof ResponseException) {
            $httpCode = $errorCode;
            $errorCode = E_USER_ERROR;
        }

        $this->error($httpCode, $message, $trace, $errorCode);

        return $this;
    }

    /**
     * Register class autoloader.
     *
     * Do not forgot set your class namespace in AUTOLOAD variables.
     *
     * @return App
     */
    public function registerAutoloader(): App
    {
        spl_autoload_register(array($this, 'loadClass'));

        return $this;
    }

    /**
     * Unregister class autoloader.
     *
     * @return App
     */
    public function unregisterAutoloader(): App
    {
        spl_autoload_unregister(array($this, 'loadClass'));

        return $this;
    }

    /**
     * Autoload class logic.
     *
     * It is use composer PSR-4 find class file logic.
     *
     * @param string $class
     *
     * @return bool|void
     */
    public function loadClass($class)
    {
        $subPath = $class;
        $logicalPath = $this->fixslashes($class).'.php';

        while (false !== $lastPos = strrpos($subPath, '\\')) {
            $subPath = substr($subPath, 0, $lastPos);
            $search = $subPath.'\\';

            if (isset($this->_hive['AUTOLOAD'][$search])) {
                $pathEnd = substr($logicalPath, $lastPos + 1);
                $dirs = $this->arr($this->_hive['AUTOLOAD'][$search]);

                foreach ($dirs as $dir) {
                    if (file_exists($file = $dir.$pathEnd)) {
                        $this->requireFile($file);

                        return true;
                    }
                }
            }
        }
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
        if ($this->_hive['DNSBL'] && !in_array($ip, $this->arr($this->_hive['EXEMPT']))) {
            // Reverse IPv4 dotted quad
            $rev = implode('.', array_reverse(explode('.', $ip)));

            foreach ($this->arr($this->_hive['DNSBL']) as $server) {
                // DNSBL lookup
                if (checkdnsrr($rev.'.'.$server, 'A')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns variables hive.
     *
     * @return array
     */
    public function hive(): array
    {
        return $this->_hive;
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
    public function transAlt(string $key, array $args = null, string $fallback = null, string ...$alts): string
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
     * Returns variables reference.
     *
     * It allows you to use dot notation to access member of an array.
     *
     * @param string $key
     * @param bool   $add
     * @param array  &$var
     *
     * @return mixed
     */
    public function &ref(string $key, bool $add = true, array &$var = null)
    {
        $null = null;
        $parts = explode('.', $key);

        $this->sessionStart('SESSION' === $parts[0]);

        if (null === $var) {
            if ($add) {
                $var = &$this->_hive;
            } else {
                $var = $this->_hive;
            }
        }

        foreach ($parts as $part) {
            if (!is_array($var)) {
                $var = array();
            }

            if ($add || array_key_exists($part, $var)) {
                $var = &$var[$part];
            } else {
                $var = $null;
                break;
            }
        }

        return $var;
    }

    /**
     * Remove member of variables.
     *
     * It allows you to use dot notation to remove member of an array.
     *
     * @param string $key
     * @param array  &$var
     *
     * @return App
     */
    public function unref(string $key, array &$var = null): App
    {
        $parts = explode('.', $key);
        $last = array_pop($parts);
        $first = $parts[0] ?? $last;
        $end = count($parts) - 1;

        $this->sessionStart('SESSION' === $first);

        if (null === $var) {
            $var = &$this->_hive;
        }

        foreach ($parts as $part) {
            if ($var && array_key_exists($part, $var)) {
                $var = &$var[$part];
            } else {
                break;
            }
        }

        unset($var[$last]);

        return $this;
    }

    /**
     * Returns true if key exists in hive.
     *
     * *Dot notation access allowed*
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists(string $key): bool
    {
        $ref = $this->ref($key, false);

        return null !== $ref;
    }

    /**
     * Sets value of hive.
     *
     * *Dot notation access allowed*
     *
     * @param string $key
     * @param mixed  $val
     *
     * @return App
     */
    public function set(string $key, $val): App
    {
        $ref = &$this->ref($key);
        $ref = $val;

        switch ($key) {
            case 'CACHE':
                $this->_hive['CACHE_ENGINE'] = null;
                $this->_hive['CACHE_REF'] = null;
                break;
            case 'ENCODING':
                ini_set('charset', $val);
                break;
            case 'FALLBACK':
            case 'LANGUAGE':
            case 'LOCALES':
                $this->_hive['DICT'] = $this->langLoad();
                break;
        }

        return $this;
    }

    /**
     * Returns value of hive member.
     *
     * *Dot notation access allowed*
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function &get(string $key, $default = null)
    {
        $ref = &$this->ref($key);

        if (null === $ref) {
            $ref = $default;
        }

        return $ref;
    }

    /**
     * Remove member of hive.
     *
     * *Dot notation access allowed*
     *
     * @param string $key
     *
     * @return App
     */
    public function clear(string $key): App
    {
        $this->unref($key);

        $parts = explode('.', $key);

        if ('SESSION' === $parts[0]) {
            if (empty($parts[1])) {
                session_unset();
                session_destroy();
            }
        } elseif ('COOKIE' === $parts[0]) {
            if (empty($parts[1])) {
                $this->_hive['COOKIE'] = array();
            }
        } elseif (array_key_exists($parts[0], $this->_init)) {
            $ref = &$this->ref($key);
            $ref = $this->ref($key, false, $this->_init);
        }

        return $this;
    }

    /**
     * Massive hive member set.
     *
     * @param array       $values
     * @param string|null $prefix
     *
     * @return App
     */
    public function mset(array $values, string $prefix = null): App
    {
        foreach ($values as $key => $value) {
            $this->set($prefix.$key, $value);
        }

        return $this;
    }

    /**
     * Massive hive member remove.
     *
     * @param array|string $keys
     *
     * @return App
     *
     * @see App::arr For detail how to pass string keys
     */
    public function mclear($keys): App
    {
        foreach ($this->arr($keys) as $key) {
            $this->clear($key);
        }

        return $this;
    }

    /**
     * Prepend string.
     *
     * @param string $key
     * @param string $str
     *
     * @return App
     */
    public function prepend(string $key, string $str): App
    {
        return $this->set($key, $str.$this->get($key));
    }

    /**
     * Append string.
     *
     * @param string $key
     * @param string $str
     *
     * @return App
     */
    public function append(string $key, string $str): App
    {
        return $this->set($key, $this->get($key).$str);
    }

    /**
     * Copy source to target.
     *
     * @param string $source
     * @param string $target
     *
     * @return App
     */
    public function copy(string $source, string $target): App
    {
        return $this->set($target, $this->get($source));
    }

    /**
     * Copy source to target then remove the source.
     *
     * @param string $source
     * @param string $target
     *
     * @return App
     */
    public function cut(string $source, string $target): App
    {
        return $this->copy($source, $target)->clear($source);
    }

    /**
     * Get source value then remove from hive.
     *
     * @param string $source
     *
     * @return mixed
     */
    public function flash(string $source)
    {
        $val = $this->get($source);
        $this->clear($source);

        return $val;
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
     *  * redirects: to register redirection
     *  * rules: to register services
     *  * listeners: to register event listener (on)
     *  * listeners_once: to register event listener (one)
     *
     * @param string $file
     *
     * @return App
     */
    public function config(string $file): App
    {
        // Config map
        $maps = array(
            'configs' => 'config',
            'routes' => 'route',
            'redirects' => 'redirect',
            'maps' => 'map',
            'rules' => 'rule',
            'listeners' => 'on',
            'listeners_once' => 'one',
        );
        $content = file_exists($file) ? $this->requireFile($file, array()) : array();

        foreach ($content as $key => $val) {
            $lkey = strtolower($key);

            if (isset($maps[$lkey])) {
                $call = $maps[$lkey];

                foreach ((array) $val as $arg) {
                    $args = array_values((array) $arg);

                    call_user_func_array(array($this, $call), $args);
                }
            } else {
                $this->set($key, $val);
            }
        }

        return $this;
    }

    /**
     * Returns true if key is cached.
     *
     * Pass second parameter to get cached value.
     *
     * @param string     $key
     * @param array|null &$cache
     *
     * @return bool
     */
    public function isCached(string $key, array &$cache = null): bool
    {
        $exists = $this->cacheExists($key);

        if ($exists) {
            $cache = $this->cacheGet($key);
            $exists = (bool) $cache;
        }

        return $exists;
    }

    /**
     * Returns true if cache exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function cacheExists(string $key): bool
    {
        $this->cacheLoad();

        $ndx = $this->_hive['SEED'].'.'.$key;

        switch ($this->_hive['CACHE_ENGINE']) {
            case 'apc':
                return apc_exists($ndx);
            case 'apcu':
                return apcu_exists($ndx);
            case 'folder':
                return (bool) $this->cacheParse($key, $this->read($this->_hive['CACHE_REF'].$ndx));
            case 'memcached':
                return (bool) $this->_hive['CACHE_REF']->get($ndx);
            case 'redis':
                return (bool) $this->_hive['CACHE_REF']->exists($ndx);
        }

        return false;
    }

    /**
     * Returns cached key.
     *
     * @param string $key
     *
     * @return array
     */
    public function cacheGet(string $key): array
    {
        $this->cacheLoad();

        $ndx = $this->_hive['SEED'].'.'.$key;
        $raw = null;

        switch ($this->_hive['CACHE_ENGINE']) {
            case 'apc':
                $raw = apc_fetch($ndx);
                break;
            case 'apcu':
                $raw = apcu_fetch($ndx);
                break;
            case 'folder':
                $raw = $this->read($this->_hive['CACHE_REF'].$ndx);
                break;
            case 'memcached':
                $raw = $this->_hive['CACHE_REF']->get($ndx);
                break;
            case 'redis':
                $raw = $this->_hive['CACHE_REF']->get($ndx);
                break;
        }

        return $this->cacheParse($key, (string) $raw);
    }

    /**
     * Sets cache value.
     *
     * @param string $key
     * @param mixed  $val
     * @param int    $ttl
     *
     * @return bool
     */
    public function cacheSet(string $key, $val, int $ttl = 0): bool
    {
        $this->cacheLoad();

        $ndx = $this->_hive['SEED'].'.'.$key;
        $content = $this->cacheCompact($val, (int) microtime(true), $ttl);

        switch ($this->_hive['CACHE_ENGINE']) {
            case 'apc':
                return apc_store($ndx, $content, $ttl);
            case 'apcu':
                return apcu_store($ndx, $content, $ttl);
            case 'folder':
                return false !== $this->write($this->_hive['CACHE_REF'].str_replace(array('/', '\\'), '', $ndx), $content);
            case 'memcached':
                return $this->_hive['CACHE_REF']->set($ndx, $content, $ttl);
            case 'redis':
                return $this->_hive['CACHE_REF']->set($ndx, $content, array_filter(array('ex' => $ttl)));
        }

        return true;
    }

    /**
     * Clear cache.
     *
     * @param string $key
     *
     * @return bool
     */
    public function cacheClear(string $key): bool
    {
        $this->cacheLoad();

        $ndx = $this->_hive['SEED'].'.'.$key;

        switch ($this->_hive['CACHE_ENGINE']) {
            case 'apc':
                return apc_delete($ndx);
            case 'apcu':
                return apcu_delete($ndx);
            case 'folder':
                return $this->delete($this->_hive['CACHE_REF'].$ndx);
            case 'memcached':
                return $this->_hive['CACHE_REF']->delete($ndx);
            case 'redis':
                return (bool) $this->_hive['CACHE_REF']->del($ndx);
        }

        return true;
    }

    /**
     * Reset cache.
     *
     * @param string $suffix
     *
     * @return App
     */
    public function cacheReset(string $suffix = ''): App
    {
        $this->cacheLoad();

        $prefix = $this->_hive['SEED'];
        $regex = '/'.preg_quote($prefix, '/').'\..+'.preg_quote($suffix, '/').'/';

        switch ($this->_hive['CACHE_ENGINE']) {
            case 'apc':
                $info = apc_cache_info('user');
                if ($info && isset($info['cache_list']) && $info['cache_list']) {
                    $key = array_key_exists('info', $info['cache_list'][0]) ? 'info' : 'key';
                    $items = preg_grep($regex, array_column($info['cache_list'], $key));

                    $this->walk($items, 'apc_delete');
                }
                break;
            case 'apcu':
                $info = apcu_cache_info(false);
                if ($info && isset($info['cache_list']) && $info['cache_list']) {
                    $key = array_key_exists('info', $info['cache_list'][0]) ? 'info' : 'key';
                    $items = preg_grep($regex, array_column($info['cache_list'], $key));

                    $this->walk($items, 'apcu_delete');
                }
                break;
            case 'folder':
                $files = glob($this->_hive['CACHE_REF'].$prefix.'*'.$suffix);

                $this->walk((array) $files, 'unlink');
                break;
            case 'memcached':
                $keys = preg_grep($regex, (array) $this->_hive['CACHE_REF']->getAllKeys());

                $this->walk($keys, array($this->_hive['CACHE_REF'], 'delete'));
                break;
            case 'redis':
                $keys = $this->_hive['CACHE_REF']->keys($prefix.'*'.$suffix);

                $this->walk($keys, array($this->_hive['CACHE_REF'], 'del'));
                break;
        }

        return $this;
    }

    /**
     * Returns callable of string expression.
     *
     * @param string $expr
     * @param bool   $create
     *
     * @return mixed
     */
    public function grab(string $expr, bool $create = true)
    {
        $obj = explode('->', $expr);
        if (2 === count($obj)) {
            return array($create ? $this->service($obj[0]) : $obj[0], $obj[1]);
        }

        $static = explode('::', $expr);
        if (2 === count($static)) {
            return $static;
        }

        return $expr;
    }

    /**
     * Call callable in app cage so it does not break chain method call.
     *
     * @param mixed $callable
     *
     * @return App
     */
    public function wrap($callable): App
    {
        $this->call($callable);

        return $this;
    }

    /**
     * Returns result of callable.
     *
     * Callable can be expression like this "FooClass->fooMethod".
     * The instance of FooClass will be automatically resolved with service method.
     *
     * @param mixed $callback
     * @param mixed $args
     *
     * @return mixed
     */
    public function call($callback, $args = null)
    {
        $func = is_string($callback) ? $this->grab($callback) : $callback;

        if (is_callable($func)) {
            if (is_array($func)) {
                $resolvedArgs = $this->resolveArgs(new \ReflectionMethod($func[0], $func[1]), (array) $args);
            } else {
                $resolvedArgs = $this->resolveArgs(new \ReflectionFunction($func), (array) $args);
            }

            return call_user_func_array($func, $resolvedArgs);
        }

        if (is_array($func)) {
            $message = 'Call to undefined method '.get_class($func[0]).'::'.$func[1].'.';

            throw new \BadMethodCallException($message);
        }

        $message = 'Call to undefined function '.$func.'.';

        throw new \BadFunctionCallException($message);
    }

    /**
     * Sets class construction rule.
     *
     * @param string $id
     * @param mixed  $rule
     *
     * @return App
     */
    public function rule(string $id, $rule = null): App
    {
        unset($this->_hive['SERVICES'][$id]);

        if (is_callable($rule)) {
            $serviceRule = array('constructor' => $rule);
        } elseif (is_object($rule)) {
            $serviceRule = array('class' => get_class($rule));
            $this->_hive['SERVICES'][$id] = $rule;
        } elseif (is_string($rule)) {
            $serviceRule = array('class' => $rule);
        } else {
            $serviceRule = (array) $rule;
        }

        $serviceRule += array('class' => $id, 'service' => true);

        $this->_hive['SERVICE_RULES'][$id] = $serviceRule;

        if ($id !== $serviceRule['class']) {
            $this->_hive['SERVICE_ALIASES'][$id] = $serviceRule['class'];
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
        if (in_array($id, array('app', self::class))) {
            return $this;
        }

        if (isset($this->_hive['SERVICES'][$id])) {
            return $this->_hive['SERVICES'][$id];
        }

        if ($sid = array_search($id, $this->_hive['SERVICE_ALIASES'])) {
            $id = $sid;

            if (isset($this->_hive['SERVICES'][$id])) {
                return $this->_hive['SERVICES'][$id];
            }
        }

        return $this->instance($id);
    }

    /**
     * Returns new class instance.
     *
     * @param string     $id
     * @param array|null $args
     *
     * @return mixed
     */
    public function instance(string $id, array $args = null)
    {
        $sid = $id;
        $rule = array(
            'class' => $id,
            'args' => null,
            'service' => false,
            'use' => null,
            'constructor' => null,
            'boot' => null,
        );

        if (isset($this->_hive['SERVICE_RULES'][$id])) {
            $rule = $this->_hive['SERVICE_RULES'][$id] + $rule;
        } elseif ($sid = array_search($id, $this->_hive['SERVICE_ALIASES'])) {
            $rule = $this->_hive['SERVICE_RULES'][$sid] + $rule;
        }

        $ref = new \ReflectionClass($rule['use'] ?? $rule['class']);

        $throw = !$ref->isInstantiable();
        $message = 'Unable to create instance for "'.$id.'". Please provide instantiable version of '.$ref->name.'.';
        $this->throws($throw, $message);

        if ($rule['constructor'] && is_callable($rule['constructor'])) {
            $instance = $this->call($rule['constructor']);

            $throw = !$instance || !$instance instanceof $ref->name;
            $message = 'Constructor of "'.$id.'" should return instance of '.$ref->name.'.';
            $this->throws($throw, $message);
        } elseif ($ref->hasMethod('__construct')) {
            $pArgs = array_replace_recursive((array) $rule['args'], (array) $args);
            $resolvedArgs = $this->resolveArgs($ref->getMethod('__construct'), $pArgs);
            $instance = $ref->newInstanceArgs($resolvedArgs);
        } else {
            $instance = $ref->newInstance();
        }

        if ($rule['boot'] && is_callable($rule['boot'])) {
            $this->call($rule['boot'], array($instance));
        }

        if ($rule['service']) {
            $this->_hive['SERVICES'][$sid] = $instance;
        }

        return $instance;
    }

    /**
     * Register event handler that will be called one time only.
     *
     * @param string $eventName
     * @param mixed  $handler
     *
     * @return App
     */
    public function one(string $eventName, $handler): App
    {
        $this->_hive['EVENTS_ONCE'][$eventName] = true;

        return $this->on($eventName, $handler);
    }

    /**
     * Register event handler.
     *
     * @param string $eventName
     * @param mixed  $handler
     *
     * @return App
     */
    public function on(string $eventName, $handler): App
    {
        $this->_hive['EVENTS'][$eventName] = $handler;

        return $this;
    }

    /**
     * Unregister event handler.
     *
     * @param string $eventName
     *
     * @return App
     */
    public function off(string $eventName): App
    {
        unset($this->_hive['EVENTS'][$eventName], $this->_hive['EVENTS_ONCE'][$eventName]);

        return $this;
    }

    /**
     * Trigger event.
     *
     * @param string $eventName
     * @param Event  $event
     *
     * @return App
     */
    public function trigger(string $eventName, Event $event): App
    {
        if (isset($this->_hive['EVENTS'][$eventName])) {
            $handler = $this->_hive['EVENTS'][$eventName];

            if (isset($this->_hive['EVENTS_ONCE'][$eventName])) {
                unset($this->_hive['EVENTS'][$eventName], $this->_hive['EVENTS_ONCE'][$eventName]);
            }

            $this->call($handler, array($event));
        }

        return $this;
    }

    /**
     * Returns path from alias name.
     *
     * @param string $alias
     * @param mixed  $args
     *
     * @return string
     */
    public function alias(string $alias, $args = null): string
    {
        if (isset($this->_hive['ROUTE_ALIASES'][$alias])) {
            $pattern = $this->_hive['ROUTE_ALIASES'][$alias];

            if ($args) {
                $keywordCount = substr_count($pattern, '@');
                $use = $args;

                if (is_string($args)) {
                    parse_str($args, $use);
                }

                $replace = array_slice($use, 0, $keywordCount);
                $search = $replace ? explode(',', '@'.implode(',@', array_keys($replace))) : array();

                $search[] = '*';
                $replace[] = implode('/', array_slice($use, $keywordCount));

                return str_replace($search, $replace, $pattern);
            }

            return $pattern;
        }

        return '/'.ltrim($alias, '/');
    }

    /**
     * Returns path from alias name, with BASE and ENTRY as prefix.
     *
     * @param string $alias
     * @param mixed  $args
     * @param mixed  $query
     *
     * @return string
     */
    public function path(string $alias, $args = null, $query = null): string
    {
        $q = is_array($query) ? http_build_query($query) : $query;

        return rtrim($this->_hive['BASE'].$this->_hive['ENTRY'].$this->alias($alias, $args).'?'.$q, '?');
    }

    /**
     * Returns path with BASEURL as prefix.
     *
     * @param string $path
     *
     * @return string
     */
    public function baseUrl(string $path): string
    {
        return $this->_hive['BASEURL'].'/'.ltrim($path, '/');
    }

    /**
     * Reroute to specified URI.
     *
     * @param mixed $target
     * @param bool  $permanent
     *
     * @return App
     */
    public function reroute($target = null, bool $permanent = false): App
    {
        if (!$target) {
            $path = $this->_hive['PATH'];
            $url = $this->_hive['REALM'];
        } elseif (is_array($target)) {
            $path = call_user_func_array(array($this, 'alias'), $target);
        } elseif (isset($this->_hive['ROUTE_ALIASES'][$target])) {
            $path = $this->_hive['ROUTE_ALIASES'][$target];
        } elseif (preg_match('/^(\w+)\(([^(]+)\)$/', $target, $match)) {
            parse_str(strtr($match[2], ',', '&'), $args);
            $path = $this->alias($match[1], $args);
        } else {
            $path = $target;
        }

        if (empty($url)) {
            $url = $path;

            if ('/' === $path[0] && (empty($path[1]) || '/' !== $path[1])) {
                $url = $this->_hive['BASEURL'].$this->_hive['ENTRY'].$path;
            }
        }

        $event = new ReroutingEvent($url, $permanent);
        $this->trigger(self::EVENT_REROUTE, $event);

        if ($event->isPropagationStopped()) {
            return $this;
        }

        if ($this->_hive['CLI']) {
            return $this->mock('GET '.$path.' cli');
        }

        $this->status(302 - (int) $permanent);
        $this->_hive['HEADERS']['Location'] = $url;
        $this->_hive['RESPONSE'] = null;

        return $this->send();
    }

    /**
     * Register route for a class.
     *
     * @param string|obj $class
     * @param array      $routes
     *
     * @return App
     */
    public function map($class, array $routes): App
    {
        $obj = is_object($class);

        foreach ($routes as $route => $def) {
            list($method, $ttl, $kbps) = ((array) $def) + array(1 => 0, 0);
            $handler = $obj ? array($class, $method) : $class.'->'.$method;

            $this->route($route, $handler, $ttl, $kbps);
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
     * @return App
     */
    public function redirect(string $expr, string $target, bool $permanent = true): App
    {
        return $this->route($expr, function () use ($target, $permanent) {
            return $this->reroute($target, $permanent);
        });
    }

    /**
     * Bind handler to route pattern.
     *
     * @param string $expr
     * @param mixed  $handler
     * @param int    $ttl
     * @param int    $kbps
     *
     * @return App
     */
    public function route(string $expr, $handler, int $ttl = 0, int $kbps = 0): App
    {
        $pattern = '/^([\w+|]+)(?:\h+(\w+))?(?:\h+(\/[^\h]*))?(?:\h+(all|ajax|cli|sync))?$/i';

        preg_match($pattern, $expr, $match);

        $throw = 3 > count($match);
        $message = 'Route should contains at least a verb and path, given "'.$expr.'".';
        $this->throws($throw, $message);

        list($verbs, $alias, $path, $mode) = array_slice($match, 1) + array(1 => '', '', 'all');

        if (!$path) {
            $throw = empty($this->_hive['ROUTE_ALIASES'][$alias]);
            $this->throws($throw, 'Route "'.$alias.'" not exists.');

            $path = $this->_hive['ROUTE_ALIASES'][$alias];
        }

        $ptr = ++$this->_hive['ROUTE_HANDLER_CTR'];
        $bitwiseMode = constant('self::REQ_'.strtoupper($mode));

        foreach (array_filter(explode('|', strtoupper($verbs))) as $verb) {
            $this->_hive['ROUTES'][$path][$bitwiseMode][$verb] = $ptr;
        }

        $this->_hive['ROUTE_HANDLERS'][$ptr] = array(
            $handler,
            $alias,
            $ttl,
            $kbps,
        );

        if ($alias) {
            $this->_hive['ROUTE_ALIASES'][$alias] = $path;
        }

        return $this;
    }

    /**
     * Mock request.
     *
     * @param string      $expr
     * @param array|null  $args
     * @param array|null  $server
     * @param string|null $body
     *
     * @return App
     */
    public function mock(string $expr, array $args = null, array $server = null, string $body = null): App
    {
        $tmp = array_map('trim', explode(' ', $expr));

        $throw = 1 === count($tmp);
        $message = 'Mock should contains at least a verb and path, given "'.$expr.'".';
        $this->throws($throw, $message);

        $verb = strtoupper($tmp[0]);
        $targetExpr = urldecode($tmp[1]);
        $mode = strtolower($tmp[2] ?? 'none');
        $target = $this->cutbefore($targetExpr, '?');
        $query = $this->cutbefore($this->cutafter($targetExpr, '?', '', true), '#');
        $fragment = $this->cutafter($targetExpr, '#', '', true);
        $path = $target;

        if (isset($this->_hive['ROUTE_ALIASES'][$target])) {
            $path = $this->_hive['ROUTE_ALIASES'][$target];
        } elseif (preg_match('/^(\w+)\(([^(]+)\)$/', $target, $match)) {
            parse_str(strtr($match[2], ',', '&'), $args);
            $path = $this->alias($match[1], $args);
        }

        $this->mclear('SENT,RESPONSE,HEADERS,BODY');

        $this->_hive['VERB'] = $verb;
        $this->_hive['PATH'] = $path;
        $this->_hive['URI'] = $this->_hive['BASE'].$path.$query.$fragment;
        $this->_hive['AJAX'] = 'ajax' === $mode;
        $this->_hive['CLI'] = 'cli' === $mode;
        $this->_hive['REQUEST'] = 'POST' === $verb ? $args : array();

        parse_str(ltrim($query, '?'), $this->_hive['QUERY']);

        if (in_array($verb, array('GET', 'HEAD'))) {
            $this->_hive['QUERY'] = array_merge($this->_hive['QUERY'], (array) $args);
        } else {
            $this->_hive['BODY'] = $body ?: http_build_query((array) $args);
        }

        $this->_hive['SERVER'] = ((array) $server) + $this->_hive['SERVER'];

        return $this->run();
    }

    /**
     * Run kernel logic.
     *
     * @return App
     */
    public function run(): App
    {
        try {
            return $this->doRun();
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Send response headers and content.
     *
     * @return App
     */
    public function send(): App
    {
        if ($this->_hive['SENT']) {
            return $this;
        }

        $this->_hive['SENT'] = true;

        return $this
            ->sendHeaders()
            ->sendContent()
        ;
    }

    /**
     * Send response headers.
     *
     * @return App
     */
    public function sendHeaders(): App
    {
        if ($this->_hive['CLI'] || headers_sent()) {
            return $this;
        }

        foreach ($this->cookies() as $cookies) {
            call_user_func_array('setcookie', $cookies);
        }

        foreach (array_filter($this->_hive['HEADERS'], 'is_scalar') as $name => $value) {
            header($name.': '.$value);
        }

        header($this->_hive['PROTOCOL'].' '.$this->_hive['CODE'].' '.$this->_hive['STATUS'], true);

        return $this;
    }

    /**
     * Send response content.
     *
     * @return App
     */
    public function sendContent(): App
    {
        if ($this->_hive['QUIET'] || empty($this->_hive['RESPONSE'])) {
            return $this;
        }

        if (0 >= $this->_hive['KBPS']) {
            echo $this->_hive['RESPONSE'];

            return $this;
        }

        $now = microtime(true);
        $ctr = 0;
        $kbps = $this->_hive['KBPS'];

        foreach (str_split($this->_hive['RESPONSE'], 1024) as $part) {
            // Throttle output
            ++$ctr;

            if ($ctr / $kbps > ($elapsed = microtime(true) - $now) && !connection_aborted()) {
                usleep((int) (1e6 * ($ctr / $kbps - $elapsed)));
            }

            echo $part;
        }

        return $this;
    }

    /**
     * Sets cache metadata headers.
     *
     * @param int $secs
     *
     * @return App
     */
    public function expire(int $secs = 0): App
    {
        $headers = &$this->_hive['HEADERS'];

        $headers['X-Powered-By'] = $this->_hive['PACKAGE'];
        $headers['X-Frame-Options'] = $this->_hive['XFRAME'];
        $headers['X-XSS-Protection'] = '1; mode=block';
        $headers['X-Content-Type-Options'] = 'nosniff';

        if ('GET' === $this->_hive['VERB'] && $secs) {
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

        return $this;
    }

    /**
     * Sets response status code.
     *
     * @param int $code
     *
     * @return App
     */
    public function status(int $code): App
    {
        $name = 'self::HTTP_'.$code;
        $throw = !defined($name);
        $message = 'Unsupported HTTP code: '.$code.'.';

        $this->throws($throw, $message, 'DomainException');

        $this->_hive['CODE'] = $code;
        $this->_hive['STATUS'] = constant($name);

        return $this;
    }

    /**
     * Send error response.
     *
     * @param int         $httpCode
     * @param string|null $message
     * @param array|null  $trace
     * @param int|null    $level
     *
     * @return App
     */
    public function error(int $httpCode, string $message = null, array $trace = null, int $level = null): App
    {
        $this->status($httpCode);

        $debug = $this->_hive['DEBUG'];
        $status = $this->_hive['STATUS'];
        $text = $message ?: 'HTTP '.$httpCode.' ('.$this->_hive['VERB'].' '.$this->_hive['PATH'].')';
        $traceStr = $this->trace($trace);

        $prior = $this->_hive['ERROR'];
        $this->_hive['ERROR'] = true;

        if ($prior) {
            return $this;
        }

        $event = new GetResponseForErrorEvent($httpCode, $status, $message, $traceStr);
        $this
            ->expire(-1)
            ->logByCode($level ?? E_USER_ERROR, $text.PHP_EOL.$traceStr)
            ->trigger(self::EVENT_ERROR, $event)
            ->off(self::EVENT_ERROR)
            ->mclear('HEADERS,RESPONSE,KBPS');

        if ($event->isPropagationStopped()) {
            $this->_hive['HEADERS'] = $event->getHeaders();
            $this->_hive['RESPONSE'] = $event->getResponse();
        } elseif ($this->_hive['AJAX']) {
            $traceInfo = $debug ? array('trace' => $traceStr) : array();
            $this->_hive['HEADERS']['Content-Type'] = 'application/json';
            $this->_hive['RESPONSE'] = json_encode(array(
                'status' => $status,
                'text' => $text,
            ) + $traceInfo);
        } elseif ($this->_hive['CLI']) {
            $traceInfo = $debug ? $traceStr.PHP_EOL : PHP_EOL;
            $this->_hive['RESPONSE'] = 'Status : '.$status.PHP_EOL.
                                      'Text   : '.$text.PHP_EOL.
                                      $traceInfo;
        } else {
            $traceInfo = $debug ? '<pre>'.$traceStr.'</pre>' : '';
            $this->_hive['HEADERS']['Content-Type'] = 'text/html';
            $this->_hive['RESPONSE'] = '<!DOCTYPE html>'.
                '<html>'.
                '<head>'.
                  '<meta charset="'.$this->_hive['ENCODING'].'">'.
                  '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">'.
                  '<title>'.$httpCode.' '.$status.'</title>'.
                '</head>'.
                '<body>'.
                  '<h1>'.$status.'</h1>'.
                  '<p>'.$text.'</p>'.
                  $traceInfo.
                '</body>'.
                '</html>';
        }

        return $this->send();
    }

    /**
     * Send an error message to log file.
     *
     * @param string $level
     * @param string $message
     *
     * @return App
     */
    public function log(string $level, string $message): App
    {
        $shouldWrite = $this->_hive['LOG'] && (self::LOG_LEVELS[$level] ?? 100) <= (self::LOG_LEVELS[$this->_hive['THRESHOLD']] ?? 101);

        if ($shouldWrite) {
            $this->logWrite($message, $level);
        }

        return $this;
    }

    /**
     * Log an error with error code.
     *
     * @param int    $code
     * @param string $message
     *
     * @return App
     */
    public function logByCode(int $code, string $message): App
    {
        $map = array(
            // E_ERROR => self::LOG_LEVEL_EMERGENCY,
            // E_PARSE => self::LOG_LEVEL_EMERGENCY,
            // E_CORE_ERROR => self::LOG_LEVEL_EMERGENCY,
            // E_COMPILE_ERROR => self::LOG_LEVEL_EMERGENCY,
            E_WARNING => self::LOG_LEVEL_ALERT,
            // E_CORE_WARNING => self::LOG_LEVEL_ALERT,
            // E_STRICT => self::LOG_LEVEL_CRITICAL,
            E_USER_ERROR => self::LOG_LEVEL_ERROR,
            E_USER_WARNING => self::LOG_LEVEL_WARNING,
            E_NOTICE => self::LOG_LEVEL_NOTICE,
            // E_COMPILE_WARNING => self::LOG_LEVEL_NOTICE,
            E_USER_NOTICE => self::LOG_LEVEL_NOTICE,
            E_RECOVERABLE_ERROR => self::LOG_LEVEL_INFO,
            E_DEPRECATED => self::LOG_LEVEL_INFO,
            E_USER_DEPRECATED => self::LOG_LEVEL_INFO,
        );
        $level = $map[$code] ?? self::LOG_LEVEL_DEBUG;

        return $this->log($level, $message);
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
        if (!$this->_hive['LOG']) {
            return array();
        }

        $pattern = $this->logDir().'log_*.log';
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
     * @return App
     */
    public function logClear(string $from = null, string $to = null): App
    {
        foreach ($this->logFiles($from, $to) as $file) {
            unlink($file);
        }

        return $this;
    }

    /**
     * Kernel logic.
     *
     * @return App
     */
    private function doRun(): App
    {
        if ($this->_hive['DRY']) {
            $this->trigger(self::EVENT_BOOT, new Event());
            $this->_hive['DRY'] = false;
        }

        if (empty($this->_hive['ROUTES'])) {
            return $this->error(500, 'No route specified.');
        }

        // @codeCoverageIgnoreStart
        if ($this->blacklisted($this->_hive['IP'])) {
            return $this->error(403);
        }
        // @codeCoverageIgnoreEnd

        $event = new GetResponseEvent();
        $this->trigger(self::EVENT_ROUTE, $event);

        if ($event->isPropagationStopped()) {
            if ($this->_hive['SENT']) {
                return $this;
            }

            $code = $event->getCode();
            $this->_hive['HEADERS'] = $event->getHeaders();
            $this->_hive['RESPONSE'] = $event->getResponse();
            $this->_hive['KBPS'] = $event->getKbps();

            return $this->status($code)->send();
        }

        if ($foundRoute = $this->findRoute()) {
            list($pattern, $routes, $args) = $foundRoute;

            if ($foundController = $this->findController($routes, $args)) {
                list($controller, $alias, $ttl, $kbps) = $foundController;

                $now = time();
                $verb = $this->_hive['VERB'];
                $hash = $this->hash($verb.' '.$this->_hive['URI']).'.url';

                if ($ttl && in_array($verb, array('GET', 'HEAD'))) {
                    if ($this->isCached($hash, $cache)) {
                        $expireDate = $this->_hive['SERVER']['HTTP_IF_MODIFIED_SINCE'] ?? 0;
                        $notModified = $expireDate && strtotime($expireDate) + $ttl > $now;

                        if ($notModified) {
                            return $this->status(304);
                        }

                        list($content, $lastModified) = $cache;
                        list($headers, $response) = $content;

                        $newExpireDate = $lastModified + $ttl - $now;
                        $this->_hive['HEADERS'] = $headers;
                        $this->_hive['RESPONSE'] = $response;

                        return $this->expire($newExpireDate);
                    }

                    $this->expire($ttl);
                } else {
                    $this->expire(0);
                }

                $event = new GetControllerArgsEvent($controller, $args);
                $this->trigger(self::EVENT_CONTROLLER_ARGS, $event);

                $this->_hive['PATTERN'] = $pattern;
                $this->_hive['ALIAS'] = $alias;
                $this->_hive['PARAMS'] = $args;
                $this->_hive['KBPS'] = $kbps;

                $controller = $event->getController();

                if (is_callable($controller)) {
                    if (!$this->_hive['RAW'] && !$this->_hive['BODY']) {
                        $this->_hive['BODY'] = file_get_contents('php://input');
                    }

                    $args = $event->getArgs();
                    $result = $this->call($controller, $args);

                    $event = new GetResponseForControllerEvent($result, $this->_hive['HEADERS']);
                    $this->trigger(self::EVENT_AFTER_ROUTE, $event);

                    $result = $event->getResult();

                    if ($event->isPropagationStopped()) {
                        $this->_hive['HEADERS'] = $event->getHeaders();
                        $this->_hive['RESPONSE'] = $event->getResponse();
                    } elseif (is_scalar($result)) {
                        $this->_hive['RESPONSE'] = (string) $result;
                    } elseif (is_array($result)) {
                        $this->_hive['HEADERS']['Content-Type'] = 'application/json';
                        $this->_hive['RESPONSE'] = json_encode($result);
                    } elseif (is_callable($result)) {
                        call_user_func_array($result, array($this));
                    }

                    $shouldBeCached = $ttl && $this->_hive['RESPONSE'] && is_string($this->_hive['RESPONSE']);

                    if ($shouldBeCached) {
                        $this->cacheSet($hash, array(
                            $this->_hive['HEADERS'],
                            $this->_hive['RESPONSE'],
                        ));
                    }

                    return $this->send();
                }

                return $this->error(404);
            }

            return $this->error(405);
        }

        return $this->error(404);
    }

    /**
     * Returns found route.
     *
     * @return array|null
     */
    private function findRoute(): ?array
    {
        $modifier = $this->_hive['CASELESS'] ? 'i' : '';

        foreach ($this->_hive['ROUTES'] as $pattern => $routes) {
            if (preg_match($this->regexify($pattern).$modifier, $this->_hive['PATH'], $match)) {
                return array($pattern, $routes, $this->collectParams($match));
            }
        }

        return null;
    }

    /**
     * Returns route regex expression.
     *
     * @param string $pattern
     *
     * @return string
     */
    private function regexify(string $pattern): string
    {
        $patterns = array(
            '/(?:@([\w]+))/',
            '/(\*)$/',
        );
        $replaces = array(
            '(?<$1>[^\\/]+)',
            '(?<_p>.+)',
        );

        return '~^'.preg_replace($patterns, $replaces, $pattern).'$~';
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
                    $params = array_merge($params, explode('/', $value));
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
     * @param array $args
     *
     * @return array|null
     */
    private function findController(array $routes, array $args): ?array
    {
        $mode = $this->requestMode();
        $route = $routes[$mode] ?? $routes[self::REQ_ALL] ?? array();
        $controller = null;

        if (isset($route[$this->_hive['VERB']])) {
            $handlerId = $route[$this->_hive['VERB']];
            $controller = $this->_hive['ROUTE_HANDLERS'][$handlerId];
            $handler = &$controller[0];

            if (is_string($handler)) {
                // Replace route pattern tokens in handler if any
                $replace = array_filter($args, 'is_scalar');
                $search = explode(',', '@'.implode(',@', array_keys($replace)));
                $handler = str_replace($search, $replace, $handler);
                $check = $this->grab($handler, false);

                if (is_array($check) && !class_exists($check[0])) {
                    $controller = null;
                } else {
                    $handler = $this->grab($handler);
                }
            }
        }

        return $controller;
    }

    /**
     * Returns current request mode bitwise.
     *
     * @return int
     */
    private function requestMode(): int
    {
        if ($this->_hive['AJAX']) {
            return self::REQ_AJAX;
        }

        if ($this->_hive['CLI']) {
            return self::REQ_CLI;
        }

        return self::REQ_SYNC;
    }

    /**
     * Returns prepared cookies to send.
     *
     * @return array
     */
    private function cookies(): array
    {
        $jar = array_combine(range(2, count($this->_hive['JAR']) + 1), array_values($this->_hive['JAR']));
        $init = (array) $this->_init['COOKIE'];
        $current = (array) $this->_hive['COOKIE'];
        $cookies = array();

        foreach ($current as $name => $value) {
            if (!isset($init[$name]) || $init[$name] !== $value) {
                $cookies[$name] = array($name, $value) + $jar;
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
     * Start session.
     *
     * @param bool $startNow
     */
    private function sessionStart(bool $startNow = true): void
    {
        if ($startNow) {
            if (!headers_sent() && PHP_SESSION_ACTIVE !== session_status()) {
                session_start();
            }

            $this->_hive['SESSION'] = &$GLOBALS['_SESSION'];
        }
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

        if ($max = $ref->getNumberOfParameters()) {
            $params = $ref->getParameters();
            $names = array_keys((array) $args);

            for ($i = 0; $i < $max; ++$i) {
                if ($params[$i]->isVariadic()) {
                    break;
                }

                $param = $params[$i];
                $name = $names[$rest] ?? null;
                $val = $args[$name] ?? null;
                $move = 1;

                if ($class = $param->getClass()) {
                    if (is_a($val, $class->name)) {
                        $resolved[] = $val;
                    } elseif (is_string($val)) {
                        $resolved[] = $this->resolveArg($val, true);
                    } else {
                        $resolved[] = $this->service($class->name);
                        --$move;
                    }
                } elseif (null !== $name && array_key_exists($name, $args)) {
                    $resolved[] = is_string($val) ? $this->resolveArg($val) : $val;
                }

                $rest += $move;
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
            // assume it does exists in hive
            $var = $this->ref($match[2], false);

            if (isset($var)) {
                return ($match[1] ?? null).$var.($match[3] ?? null);
            }

            // it is a service alias
            return $this->service($match[2]);
        }

        return $val;
    }

    /**
     * Load cache by defined CACHE dsn.
     */
    private function cacheLoad(): void
    {
        $dsn = $this->_hive['CACHE'];
        $engine = &$this->_hive['CACHE_ENGINE'];
        $ref = &$this->_hive['CACHE_REF'];

        if ($engine || !$dsn) {
            return;
        }

        $parts = array_map('trim', explode('=', $dsn) + array(1 => ''));
        $auto = '/^(apcu|apc)/';
        $grep = preg_grep($auto, array_map('strtolower', get_loaded_extensions()));

        // Fallback to filesystem cache
        $fallback = 'folder';
        $fallbackDir = $this->_hive['TEMP'].'cache/';

        if ('redis' === $parts[0] && $parts[1] && extension_loaded('redis')) {
            list($host, $port, $db) = explode(':', $parts[1]) + array(1 => null, 2 => null);

            $engine = 'redis';
            $ref = new \Redis();

            try {
                $ref->connect($host, (int) ($port ?? 6379), 2);

                if ($db) {
                    $ref->select((int) $db);
                }
            } catch (\Exception $e) {
                $engine = $fallback;
                $ref = $fallbackDir;
            }
        } elseif ('memcached' === $parts[0] && $parts[1] && extension_loaded('memcached')) {
            $servers = explode(';', $parts[1]);

            $engine = 'memcached';
            $ref = new \Memcached();

            foreach ($servers as $server) {
                list($host, $port) = explode(':', $server) + array(1 => 11211);

                $ref->addServer($host, (int) $port);
            }
        } elseif ('folder' === $parts[0] && $parts[1]) {
            $engine = 'folder';
            $ref = $parts[1];
        } elseif (preg_match($auto, $dsn, $parts)) {
            $engine = $parts[1];
            $ref = null;
        } elseif ('auto' === strtolower($dsn) && $grep) {
            $engine = current($grep);
            $ref = null;
        } else {
            $engine = $fallback;
            $ref = $fallbackDir;
        }

        if ($fallback === $engine) {
            $this->mkdir($ref);
        }
    }

    /**
     * Returns serialized cache, timestamp and ttl.
     *
     * @param mixed $content
     * @param int   $time
     * @param int   $ttl
     *
     * @return string
     */
    private function cacheCompact($content, int $time, int $ttl): string
    {
        return serialize(array($content, $time, $ttl));
    }

    /**
     * Returns unserialized serialized cache.
     *
     * @param string $key
     * @param string $raw
     *
     * @return array
     */
    private function cacheParse(string $key, string $raw): array
    {
        if ($raw) {
            list($val, $time, $ttl) = (array) unserialize($raw);

            if (0 === $ttl || $time + $ttl > microtime(true)) {
                return array($val, $time, $ttl);
            }

            $this->cacheClear($key);
        }

        return array();
    }

    /**
     * Returns log directory.
     *
     * @return string
     */
    private function logDir(): string
    {
        $dir = $this->_hive['LOG'];

        return $dir && is_dir($dir) ? $dir : $this->_hive['TEMP'].$dir;
    }

    /**
     * Write log message.
     *
     * @param string $message
     * @param string $level
     */
    private function logWrite(string $message, string $level): void
    {
        $prefix = $this->logDir().'log_';
        $ext = '.log';
        $files = glob($prefix.date('Y-m').'*'.$ext);

        $file = $files[0] ?? $prefix.date('Y-m-d').$ext;
        $content = date('Y-m-d G:i:s.u').' '.$level.' '.$message.PHP_EOL;

        $this->mkdir(dirname($file));
        $this->write($file, $content, true);
    }

    /**
     * Returns trace as string.
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

        $filteredTrace = array();
        $debug = $this->_hive['DEBUG'];

        foreach ($trace as $key => $frame) {
            if (isset($frame['file']) &&
                ($debug > 1 ||
                    (__FILE__ !== $frame['file'] || $debug) &&
                    (
                        empty($frame['function']) ||
                        !preg_match('/^(?:(?:trigger|user)_error|__call|call_user_func)/', $frame['function'])
                    )
                )) {
                $filteredTrace[] = $frame;
            }
        }
        $trace = $filteredTrace;

        $out = '';
        $eol = "\n";
        $cut = $this->_hive['TRACE'];

        // Analyze stack trace
        foreach ($trace as $frame) {
            $line = '';

            if (isset($frame['class'])) {
                $line .= $frame['class'].$frame['type'];
            }

            $line .= $frame['function'] ?? null;

            $src = $this->fixslashes($frame['file']);
            $out .= '['.($cut ? str_replace($cut, '', $src) : $src).':'.$frame['line'].'] '.$line.$eol;
        }

        return $out;
    }

    /**
     * Returns message reference.
     *
     * @param string $key
     *
     * @return string|null
     *
     * @throws UnexpectedValueException If message reference is not a string
     */
    private function langRef(string $key): ?string
    {
        $ref = $this->get('DICT.'.$key);
        $throw = null !== $ref && !is_string($ref);
        $message = 'Message reference is not a string.';

        $this->throws($throw, $message, 'UnexpectedValueException');

        return $ref;
    }

    /**
     * Get languages.
     *
     * @return array
     */
    private function langLanguages(): array
    {
        $langCode = ltrim(preg_replace('/\h+|;q=[0-9.]+/', '', $this->_hive['LANGUAGE']).','.$this->_hive['FALLBACK'], ',');
        $languages = array();

        foreach ($this->split($langCode) as $lang) {
            if (preg_match('/^(\w{2})(?:-(\w{2}))?\b/i', $lang, $parts)) {
                // Generic language
                $languages[] = $parts[1];

                if (isset($parts[2])) {
                    // Specific language
                    $languages[] = $parts[1].'-'.strtoupper($parts[2]);
                }
            }
        }

        return array_unique($languages);
    }

    /**
     * Load languages.
     *
     * @return array
     */
    private function langLoad(): array
    {
        $dict = array();

        foreach ($this->langLanguages() as $lang) {
            foreach ($this->arr($this->_hive['LOCALES']) as $dir) {
                if (is_file($file = $dir.$lang.'.php')) {
                    $dict = array_replace_recursive($dict, $this->requireFile($file, array()));
                }
            }
        }

        return $dict;
    }

    /**
     * Provide checking member as array.
     */
    public function offsetExists($key)
    {
        return $this->exists($key);
    }

    /**
     * Provide retrieving member as array.
     */
    public function &offsetGet($key)
    {
        $ref = &$this->get($key);

        return $ref;
    }

    /**
     * Provide assigning member as array.
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Provide removing member as array.
     */
    public function offsetUnset($key)
    {
        $this->clear($key);
    }

    /**
     * Provide checking member as property.
     */
    public function __isset($key)
    {
        return $this->exists($key);
    }

    /**
     * Provide retrieving member as property.
     */
    public function &__get($key)
    {
        $ref = &$this->get($key);

        return $ref;
    }

    /**
     * Provide assigning member as property.
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Provide removing member as property.
     */
    public function __unset($key)
    {
        $this->clear($key);
    }
}

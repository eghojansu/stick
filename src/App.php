<?php

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
    const EVENT_PREROUTE = 'app_preroute';
    const EVENT_POSTROUTE = 'app_postroute';
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

    /**
     * Log levels.
     *
     * @var array
     */
    private static $logLevels = array(
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
    private $hive;

    /**
     * A copy of variables hive.
     *
     * @var array
     */
    private $init;

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

        $default = array(
            'SERVER_NAME' => gethostname(),
            'SERVER_PORT' => 80,
            'SERVER_PROTOCOL' => 'HTTP/1.0',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_URI' => '/',
            'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'],
            'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'],
            'HTTPS' => 'off',
            'HTTP_USER_AGENT' => '',
            'REMOTE_ADDR' => '',
            'argv' => null,
            'HTTP_IF_MODIFIED_SINCE' => null,
            'HTTP_X_FORWARDED_PORT' => null,
            'HTTP_X_FORWARDED_PROTO' => null,
            'HTTP_X_OPERAMINI_PHONE_UA' => null,
            'HTTP_X_SKYFIRE_PHONE' => null,
            'HTTP_X_REQUESTED_WITH' => null,
            'HTTP_X_CLIENT_IP' => null,
            'HTTP_X_FORWARDED_FOR' => null,
            'HTTP_X_HTTP_METHOD_OVERRIDE' => null,
        );
        $fix = ((array) $server) + $default;

        $cli = 'cli' === PHP_SAPI;
        $verb = $fix['REQUEST_METHOD'];
        $host = $fix['SERVER_NAME'];
        $uri = $fix['REQUEST_URI'];
        $uriHost = preg_match('/^\w+:\/\//', $uri) ? '' : '//'.$host;
        $url = parse_url($uriHost.$uri);
        $port = (int) self::pickFirst($fix, 'HTTP_X_FORWARDED_PORT,SERVER_PORT');
        $secure = 'on' === $fix['HTTPS'] || 'https' === $fix['HTTP_X_FORWARDED_PROTO'];
        $base = rtrim(self::fixslashes(dirname($fix['SCRIPT_NAME'])), '/');
        $entry = '/'.basename($fix['SCRIPT_NAME']);

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

        $this->init = $this->hive = array(
            'AGENT' => self::pickFirst($fix, 'HTTP_X_OPERAMINI_PHONE_UA,HTTP_X_SKYFIRE_PHONE,HTTP_USER_AGENT'),
            'AJAX' => 'XMLHttpRequest' === $fix['HTTP_X_REQUESTED_WITH'],
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
            'FRAGMENT' => self::pick($url, 'fragment'),
            'HEADERS' => null,
            'HOST' => $host,
            'IP' => $fix['HTTP_X_CLIENT_IP'] ?: self::cutbefore($fix['HTTP_X_FORWARDED_FOR'], ',', $fix['REMOTE_ADDR']),
            'JAR' => $cookieJar,
            'KBPS' => 0,
            'LANGUAGE' => null,
            'LOCALES' => './dict/',
            'LOG' => null,
            'PACKAGE' => self::PACKAGE,
            'PARAMS' => null,
            'PATH' => self::cutprefix(self::cutprefix(urldecode($url['path']), $base), $entry, '/'),
            'PATTERN' => null,
            'PORT' => $port,
            'PROTOCOL' => $fix['SERVER_PROTOCOL'],
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
            'SEED' => self::hash($fix['SERVER_NAME'].$base),
            'SENT' => false,
            'SERVER' => $fix,
            'SERVICE_ALIASES' => array(),
            'SERVICE_RULES' => array(),
            'SERVICES' => array(),
            'SESSION' => null,
            'STATUS' => self::HTTP_200,
            'TEMP' => './var/',
            'THRESHOLD' => self::LOG_LEVEL_ERROR,
            'TIME' => $now,
            'TRACE' => self::fixslashes(realpath(dirname($fix['SCRIPT_FILENAME']).'/..').'/'),
            'URI' => $uri,
            'VERB' => $verb,
            'VERSION' => self::VERSION,
            'XFRAME' => 'SAMEORIGIN',
        );
        $this->init['QUERY'] = $this->init['REQUEST'] = null;
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
    public static function create(array $server = null, array $request = null, array $query = null, array $cookie = null)
    {
        return new static($server, $request, $query, $cookie);
    }

    /**
     * Create App instance from globals environment.
     *
     * @return App
     */
    public static function createFromGlobals()
    {
        return new static($_SERVER, $_POST, $_GET, $_COOKIE);
    }

    /**
     * Returns value of an array member if any, otherwise returns defaults.
     *
     * @param array $input
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed
     */
    public static function pick(array $input, $key, $default = null)
    {
        return isset($input[$key]) ? $input[$key] : $default;
    }

    /**
     * Returns value of first array member if any, otherwise returns defaults.
     *
     * @param array        $input
     * @param array|string $keys
     * @param mixed        $default
     *
     * @return mixed
     *
     * @see    App::arr For detailed how pass string as keys.
     */
    public static function pickFirst(array $input, $keys, $default = null)
    {
        foreach (self::arr($keys) as $key) {
            if (isset($input[$key])) {
                return $input[$key];
            }
        }

        return $default;
    }

    /**
     * Returns true if val is null or false.
     *
     * @param mixed $val
     *
     * @return bool
     */
    public static function filterNullFalse($val)
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
    public static function split($str)
    {
        return array_map('trim', preg_split('/[,;\|]/', (string) $str, null, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * Returns normalized slashes.
     *
     * @param string $str
     *
     * @return string
     */
    public static function fixslashes($str)
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
    public static function hash($str)
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
    public static function cutbefore($str, $needle, $default = null, $with_needle = false)
    {
        if ($str && $needle && false !== ($pos = strpos($str, $needle))) {
            return substr($str, 0, $pos + ((int) $with_needle));
        }

        return null === $default ? $str : $default;
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
    public static function cutafter($str, $needle, $default = null, $with_needle = false)
    {
        if ($str && $needle && false !== ($pos = strrpos($str, $needle))) {
            return substr($str, $pos + ((int) !$with_needle));
        }

        return null === $default ? $str : $default;
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
    public static function cutprefix($str, $prefix, $default = null)
    {
        if ($str && $prefix && substr($str, 0, $cut = strlen($prefix)) === $prefix) {
            return substr($str, $cut) ?: $default;
        }

        return null === $default ? $str : $default;
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
    public static function cutsuffix($str, $suffix, $default = null)
    {
        if ($str && $suffix && substr($str, $cut = -strlen($suffix)) === $suffix) {
            return substr($str, 0, $cut) ?: $default;
        }

        return null === $default ? $str : $default;
    }

    /**
     * Returns true if string starts with prefix.
     *
     * @param string $str
     * @param string $prefix
     *
     * @return bool
     */
    public static function startswith($str, $prefix)
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
    public static function endswith($str, $suffix)
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
    public static function camelCase($str)
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
    public static function snakeCase($str)
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
    public static function titleCase($str)
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
    public static function mkdir($path, $mode = 0755, $recursive = true)
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
    public static function read($file, $lf = false)
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
    public static function write($file, $data, $append = false)
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
    public static function delete($file)
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
    public static function requireFile($file, $default = null)
    {
        $content = require $file;

        return (false === $content || null === $content) ? $default : $content;
    }

    /**
     * Returns class name.
     *
     * @param string|object $class
     *
     * @return string
     */
    public static function classname($class)
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
    public static function cast($val)
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
    public static function parseExpr($expr)
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
            $prev = isset($expr[$ptr - 1]) ? $expr[$ptr - 1] : null;

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
                    $args[] = self::cast($tmp);
                    $tmp = '';
                } elseif (',' === $char && 0 === $astate && 0 === $jstate) {
                    if ($tmp) {
                        $args[] = self::cast($tmp);
                        $tmp = '';
                    }
                } elseif ('|' === $char) {
                    $process = true;
                    if ($tmp) {
                        $args[] = self::cast($tmp);
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
                    $args[] = self::cast($tmp);
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
    public static function arr($val)
    {
        return is_array($val) ? $val : self::split($val);
    }

    /**
     * Advanced array_column.
     *
     * @param array  $input
     * @param string $column_key
     *
     * @return array
     */
    public static function column(array $input, $column_key)
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
    public static function walk(array $args, $callable, $one = true)
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
     */
    public static function throws($throw, $message, $exception = 'LogicException')
    {
        if ($throw) {
            throw new $exception($message);
        }
    }

    /**
     * Returns ellapsed time since application prepared.
     *
     * @return string
     */
    public function ellapsedTime()
    {
        return number_format(microtime(true) - $this->hive['TIME'], 5).' seconds';
    }

    /**
     * Override request method with Custom http method override or request post method hack.
     *
     * @return App
     */
    public function overrideRequestMethod()
    {
        $verb = $this->hive['SERVER']['HTTP_X_HTTP_METHOD_OVERRIDE'] ?: $this->hive['VERB'];

        if ('POST' === $verb && isset($this->hive['REQUEST']['_method'])) {
            $verb = strtoupper($this->hive['REQUEST']['_method']);
        }

        $this->hive['VERB'] = $verb;

        return $this;
    }

    /**
     * Convert console arguments to path and queries.
     *
     * @return App
     */
    public function emulateCliRequest()
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
            $this->hive['FRAGMENT'] = $uri['fragment'];
            $this->hive['URI'] = $req;
            $this->hive['REALM'] = $this->hive['BASEURL'].$req;
            parse_str($uri['query'], $this->hive['QUERY']);
        }

        return $this;
    }

    /**
     * Handle thrown exception.
     *
     * @param mixed $e
     */
    public function handleException($e)
    {
        $message = $e->getMessage().' '.'['.$e->getFile().':'.$e->getLine().']';
        $httpCode = 500;
        $errorCode = $e->getCode();

        if ($e instanceof ResponseException) {
            $message = $e->getMessage() ? $message : null;
            $httpCode = $errorCode;
            $errorCode = E_USER_ERROR;
        }

        $this->error($httpCode, $message, $e->gettrace(), $errorCode);
    }

    /**
     * Handle raised error.
     *
     * @param int    $level
     * @param string $text
     * @param string $file
     * @param int    $line
     */
    public function handleError($level, $text, $file, $line)
    {
        if ($level & error_reporting()) {
            $this->error(500, $text, null, $level);
        }
    }

    /**
     * Register error and exception handler.
     *
     * @return App
     */
    public function registerErrorExceptionHandler()
    {
        set_exception_handler(array($this, 'handleException'));
        set_error_handler(array($this, 'handleError'));

        return $this;
    }

    /**
     * Register class autoloader.
     *
     * Do not forgot set your class namespace in AUTOLOAD variables.
     *
     * @return App
     */
    public function registerAutoloader()
    {
        spl_autoload_register(array($this, 'loadClass'));

        return $this;
    }

    /**
     * Unregister class autoloader.
     *
     * @return App
     */
    public function unregisterAutoloader()
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
        $logicalPath = self::fixslashes($class).'.php';

        while (false !== $lastPos = strrpos($subPath, '\\')) {
            $subPath = substr($subPath, 0, $lastPos);
            $search = $subPath.'\\';

            if (isset($this->hive['AUTOLOAD'][$search])) {
                $pathEnd = substr($logicalPath, $lastPos + 1);
                $dirs = self::arr($this->hive['AUTOLOAD'][$search]);

                foreach ($dirs as $dir) {
                    if (file_exists($file = $dir.$pathEnd)) {
                        self::requireFile($file);

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
    public function blacklisted($ip)
    {
        if ($this->hive['DNSBL'] && !in_array($ip, self::arr($this->hive['EXEMPT']))) {
            // Reverse IPv4 dotted quad
            $rev = implode('.', array_reverse(explode('.', $ip)));

            foreach (self::arr($this->hive['DNSBL']) as $server) {
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
    public function hive()
    {
        return $this->hive;
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
    public function trans($key, array $args = null, $fallback = null)
    {
        $message = $this->langRef($key, $fallback);

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
    public function choice($key, $count, array $args = null, $fallback = null)
    {
        $args['#'] = $count;
        $message = $this->langRef($key, $fallback);

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
     * @param array       $alts
     *
     * @return string
     */
    public function transAlt($key, array $args = null, $fallback = null, array $alts = null)
    {
        $message = $this->langRef($key, $fallback);
        $notFound = $message === $key;

        foreach ($notFound ? (array) $alts : array() as $alt) {
            if ($ref = $this->langRef($alt, '')) {
                $message = $ref;

                break;
            }
        }

        return strtr($message, (array) $args);
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
     * @return string
     */
    public function &ref($key, $add = true, array &$var = null)
    {
        $null = null;
        $parts = explode('.', $key);

        $this->sessionStart('SESSION' === $parts[0]);

        if (null === $var) {
            if ($add) {
                $var = &$this->hive;
            } else {
                $var = $this->hive;
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
    public function unref($key, &$var = null)
    {
        $parts = explode('.', $key);
        $last = array_pop($parts);
        $end = count($parts) - 1;

        $this->sessionStart('SESSION' === ($parts ? $parts[0] : $last));

        if (null === $var) {
            $var = &$this->hive;
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
    public function exists($key)
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
    public function set($key, $val)
    {
        $ref = &$this->ref($key);
        $ref = $val;

        switch ($key) {
            case 'CACHE':
                $this->hive['CACHE_ENGINE'] = null;
                $this->hive['CACHE_REF'] = null;
                break;
            case 'ENCODING':
                ini_set('charset', $val);
                break;
            case 'FALLBACK':
            case 'LANGUAGE':
            case 'LOCALES':
                $this->hive['DICT'] = $this->langLoad();
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
    public function &get($key, $default = null)
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
    public function clear($key)
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
                $this->hive['COOKIE'] = array();
            }
        } elseif (array_key_exists($parts[0], $this->init)) {
            $ref = &$this->ref($key);
            $ref = $this->ref($key, false, $this->init);
        }

        return $this;
    }

    /**
     * Massive hive member set.
     *
     * @param array  $values
     * @param string $prefix
     *
     * @return App
     */
    public function mset(array $values, $prefix = null)
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
    public function mclear($keys)
    {
        foreach (self::arr($keys) as $key) {
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
     * @return string
     */
    public function prepend($key, $str)
    {
        return $this->set($key, $str.$this->ref($key, false));
    }

    /**
     * Append string.
     *
     * @param string $key
     * @param string $str
     *
     * @return App
     */
    public function append($key, $str)
    {
        return $this->set($key, $this->ref($key, false).$str);
    }

    /**
     * Copy source to target.
     *
     * @param string $source
     * @param string $target
     *
     * @return App
     */
    public function copy($source, $target)
    {
        return $this->set($target, $this->ref($source, false));
    }

    /**
     * Copy source to target then remove the source.
     *
     * @param string $source
     * @param string $target
     *
     * @return App
     */
    public function cut($source, $target)
    {
        return $this->copy($source, $target)->clear($source);
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
    public function config($file)
    {
        // Config map
        $maps = array(
            'configs' => 'config',
            'routes' => 'route',
            'redirects' => 'redirect',
            'rules' => 'rule',
            'listeners' => 'on',
            'listeners_once' => 'one',
        );
        $content = file_exists($file) ? self::requireFile($file, array()) : array();

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
    public function isCached($key = null, array &$cache = null)
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
    public function cacheExists($key)
    {
        $this->cacheLoad();

        $ndx = $this->hive['SEED'].'.'.$key;

        switch ($this->hive['CACHE_ENGINE']) {
            case 'apc':
                return apc_exists($ndx);
            case 'apcu':
                return apcu_exists($ndx);
            case 'folder':
                return (bool) $this->cacheParse($key, self::read($this->hive['CACHE_REF'].$ndx));
            case 'memcached':
                return (bool) $this->hive['CACHE_REF']->get($ndx);
            case 'redis':
                return (bool) $this->hive['CACHE_REF']->exists($ndx);
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
    public function cacheGet($key)
    {
        $this->cacheLoad();

        $ndx = $this->hive['SEED'].'.'.$key;
        $raw = null;

        switch ($this->hive['CACHE_ENGINE']) {
            case 'apc':
                $raw = apc_fetch($ndx);
                break;
            case 'apcu':
                $raw = apcu_fetch($ndx);
                break;
            case 'folder':
                $raw = self::read($this->hive['CACHE_REF'].$ndx);
                break;
            case 'memcached':
                $raw = $this->hive['CACHE_REF']->get($ndx);
                break;
            case 'redis':
                $raw = $this->hive['CACHE_REF']->get($ndx);
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
    public function cacheSet($key, $val, $ttl = 0)
    {
        $this->cacheLoad();

        $ndx = $this->hive['SEED'].'.'.$key;
        $content = $this->cacheCompact($val, (int) microtime(true), $ttl);

        switch ($this->hive['CACHE_ENGINE']) {
            case 'apc':
                return apc_store($ndx, $content, $ttl);
            case 'apcu':
                return apcu_store($ndx, $content, $ttl);
            case 'folder':
                return false !== self::write($this->hive['CACHE_REF'].str_replace(array('/', '\\'), '', $ndx), $content);
            case 'memcached':
                return $this->hive['CACHE_REF']->set($ndx, $content, $ttl);
            case 'redis':
                return $this->hive['CACHE_REF']->set($ndx, $content, array_filter(array('ex' => $ttl)));
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
    public function cacheClear($key)
    {
        $this->cacheLoad();

        $ndx = $this->hive['SEED'].'.'.$key;

        switch ($this->hive['CACHE_ENGINE']) {
            case 'apc':
                return apc_delete($ndx);
            case 'apcu':
                return apcu_delete($ndx);
            case 'folder':
                return self::delete($this->hive['CACHE_REF'].$ndx);
            case 'memcached':
                return $this->hive['CACHE_REF']->delete($ndx);
            case 'redis':
                return (bool) $this->hive['CACHE_REF']->del($ndx);
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
    public function cacheReset($suffix = '')
    {
        $this->cacheLoad();

        $prefix = $this->hive['SEED'];
        $regex = '/'.preg_quote($prefix, '/').'\..+'.preg_quote($suffix, '/').'/';

        switch ($this->hive['CACHE_ENGINE']) {
            case 'apc':
                $info = apc_cache_info('user');
                if ($info && isset($info['cache_list']) && $info['cache_list']) {
                    $key = array_key_exists('info', $info['cache_list'][0]) ? 'info' : 'key';
                    $items = preg_grep($regex, array_column($info['cache_list'], $key));

                    self::walk($items, 'apc_delete');
                }
                break;
            case 'apcu':
                $info = apcu_cache_info(false);
                if ($info && isset($info['cache_list']) && $info['cache_list']) {
                    $key = array_key_exists('info', $info['cache_list'][0]) ? 'info' : 'key';
                    $items = preg_grep($regex, array_column($info['cache_list'], $key));

                    self::walk($items, 'apcu_delete');
                }
                break;
            case 'folder':
                $files = glob($this->hive['CACHE_REF'].$prefix.'*'.$suffix) ?: array();

                self::walk($files, 'unlink');
                break;
            case 'memcached':
                $keys = preg_grep($regex, $this->hive['CACHE_REF']->getAllKeys() ?: array());

                self::walk($keys, array($this->hive['CACHE_REF'], 'delete'));
                break;
            case 'redis':
                $keys = $this->hive['CACHE_REF']->keys($prefix.'*'.$suffix);

                self::walk($keys, array($this->hive['CACHE_REF'], 'del'));
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
    public function grab($expr, $create = true)
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
    public function wrap($callable)
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
     * @param mixed $args     Will be type cast as array
     *
     * @return mixed
     */
    public function call($callback, $args = null)
    {
        $func = is_string($callback) ? $this->grab($callback) : $callback;

        if (is_callable($func)) {
            $passedArgs = (array) $args;

            if (is_array($func)) {
                $resolvedArgs = $this->resolveArgs(new \ReflectionMethod($func[0], $func[1]), $passedArgs);
            } else {
                $resolvedArgs = $this->resolveArgs(new \ReflectionFunction($func), $passedArgs);
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
    public function rule($id, $rule = null)
    {
        unset($this->hive['SERVICES'][$id]);

        if (is_callable($rule)) {
            $serviceRule = array('constructor' => $rule);
        } elseif (is_object($rule)) {
            $serviceRule = array('class' => get_class($rule));
            $this->hive['SERVICES'][$id] = $rule;
        } elseif (is_string($rule)) {
            $serviceRule = array('class' => $rule);
        } else {
            $serviceRule = (array) $rule;
        }

        $serviceRule += array('class' => $id, 'service' => true);

        $this->hive['SERVICE_RULES'][$id] = $serviceRule;

        if ($id !== $serviceRule['class']) {
            $this->hive['SERVICE_ALIASES'][$id] = $serviceRule['class'];
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
    public function service($id)
    {
        if (in_array($id, array('app', self::class))) {
            return $this;
        }

        if (isset($this->hive['SERVICES'][$id])) {
            return $this->hive['SERVICES'][$id];
        }

        if ($sid = array_search($id, $this->hive['SERVICE_ALIASES'])) {
            $id = $sid;

            if (isset($this->hive['SERVICES'][$id])) {
                return $this->hive['SERVICES'][$id];
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
    public function instance($id, array $args = null)
    {
        $sid = $id;
        $rule = array(
            'class' => $id,
            'args' => $args,
            'service' => false,
            'use' => null,
            'constructor' => null,
            'boot' => null,
        );

        if (isset($this->hive['SERVICE_RULES'][$id])) {
            $rule = $this->hive['SERVICE_RULES'][$id] + $rule;
        } elseif ($sid = array_search($id, $this->hive['SERVICE_ALIASES'])) {
            $rule = $this->hive['SERVICE_RULES'][$sid] + $rule;
        }

        $ref = new \ReflectionClass($rule['use'] ?: $rule['class']);

        $throw = !$ref->isInstantiable();
        $message = 'Unable to create instance for "'.$id.'". Please provide instantiable version of '.$ref->name.'.';
        self::throws($throw, $message);

        if ($rule['constructor'] && is_callable($rule['constructor'])) {
            $instance = $this->call($rule['constructor']);

            $throw = !$instance || !$instance instanceof $ref->name;
            $message = 'Constructor of "'.$id.'" should return instance of '.$ref->name.'.';
            self::throws($throw, $message);
        } elseif ($ref->hasMethod('__construct')) {
            $resolvedArgs = $this->resolveArgs($ref->getMethod('__construct'), $rule['args']);
            $instance = $ref->newInstanceArgs($resolvedArgs);
        } else {
            $instance = $ref->newInstance();
        }

        if ($rule['boot'] && is_callable($rule['boot'])) {
            $this->call($rule['boot'], array($instance));
        }

        if ($rule['service']) {
            $this->hive['SERVICES'][$sid] = $instance;
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
    public function one($eventName, $handler)
    {
        $this->hive['EVENTS_ONCE'][$eventName] = true;

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
    public function on($eventName, $handler)
    {
        $this->hive['EVENTS'][$eventName] = $handler;

        return $this;
    }

    /**
     * Unregister event handler.
     *
     * @param string $eventName
     *
     * @return App
     */
    public function off($eventName)
    {
        unset($this->hive['EVENTS'][$eventName], $this->hive['EVENTS_ONCE'][$eventName]);

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
    public function trigger($eventName, Event $event)
    {
        if (isset($this->hive['EVENTS'][$eventName])) {
            $handler = $this->hive['EVENTS'][$eventName];

            if (isset($this->hive['EVENTS_ONCE'][$eventName])) {
                unset($this->hive['EVENTS'][$eventName], $this->hive['EVENTS_ONCE'][$eventName]);
            }

            $this->call($handler, array($event));
        }

        return $this;
    }

    /**
     * Returns path from alias name.
     *
     * @param string     $alias
     * @param array|null $args
     *
     * @return string
     */
    public function alias($alias, array $args = null)
    {
        if (isset($this->hive['ROUTE_ALIASES'][$alias])) {
            $pattern = $this->hive['ROUTE_ALIASES'][$alias];

            if ($args) {
                $keywordCount = substr_count($pattern, '@');
                $replace = array_slice($args, 0, $keywordCount);
                $search = $replace ? explode(',', '@'.implode(',@', array_keys($replace))) : array();

                $search[] = '*';
                $replace[] = implode('/', array_slice($args, $keywordCount));

                return str_replace($search, $replace, $pattern);
            }

            return $pattern;
        }

        return '/'.ltrim($alias, '/');
    }

    /**
     * Returns path from alias name, with BASE and ENTRY as prefix.
     *
     * @param string     $alias
     * @param array|null $args
     *
     * @return string
     */
    public function path($alias, array $args = null)
    {
        return $this->hive['BASE'].$this->hive['ENTRY'].$this->alias($alias, $args);
    }

    /**
     * Returns path with BASEURL as prefix.
     *
     * @param string $path
     *
     * @return string
     */
    public function baseUrl($path)
    {
        return $this->hive['BASEURL'].'/'.ltrim($path, '/');
    }

    /**
     * Reroute to specified URI.
     *
     * @param string $target
     * @param bool   $permanent
     *
     * @return App
     */
    public function reroute($target = null, $permanent = false)
    {
        if (!$target) {
            $path = $this->hive['PATH'];
            $url = $this->hive['REALM'];
        } elseif (is_array($target)) {
            $path = call_user_func_array(array($this, 'alias'), $target);
        } elseif (isset($this->hive['ROUTE_ALIASES'][$target])) {
            $path = $this->hive['ROUTE_ALIASES'][$target];
        } elseif (preg_match('/^(\w+)\(([^(]+)\)$/', $target, $match)) {
            parse_str(strtr($match[2], ',', '&'), $args);
            $path = $this->alias($match[1], $args);
        } else {
            $path = $target;
        }

        if (!isset($url) && '/' === $path[0] && (empty($path[1]) || '/' !== $path[1])) {
            $url = $this->hive['BASEURL'].$this->hive['ENTRY'].$path;
        } else {
            $url = $path;
        }

        $event = new ReroutingEvent($url, $permanent);
        $this->trigger(self::EVENT_REROUTE, $event);

        if ($event->isPropagationStopped()) {
            return $this;
        }

        if ($this->hive['CLI']) {
            return $this->mock('GET '.$path.' cli');
        }

        $this->status($permanent ? 301 : 302);
        $this->hive['HEADERS']['Location'] = $url;

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
    public function redirect($expr, $target, $permanent = true)
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
    public function route($expr, $handler, $ttl = 0, $kbps = 0)
    {
        $pattern = '/^([\w+|]+)(?:\h+(\w+))?(?:\h+(\/[^\h]*))?(?:\h+(all|ajax|cli|sync))?$/i';

        preg_match($pattern, $expr, $match);

        $throw = 3 > count($match);
        $message = 'Route should contains at least a verb and path, given "'.$expr.'".';
        self::throws($throw, $message);

        list($verbs, $alias, $path, $mode) = array_slice($match, 1) + array(1 => '', '', 'all');

        if (!$path) {
            $throw = empty($this->hive['ROUTE_ALIASES'][$alias]);
            self::throws($throw, 'Route "'.$alias.'" not exists.');

            $path = $this->hive['ROUTE_ALIASES'][$alias];
        }

        $ptr = ++$this->hive['ROUTE_HANDLER_CTR'];
        $bitwiseMode = constant('self::REQ_'.strtoupper($mode));

        foreach (array_filter(explode('|', strtoupper($verbs))) as $verb) {
            $this->hive['ROUTES'][$path][$bitwiseMode][$verb] = $ptr;
        }

        $this->hive['ROUTE_HANDLERS'][$ptr] = array(
            $handler,
            $alias,
            $ttl,
            $kbps,
        );

        if ($alias) {
            $this->hive['ROUTE_ALIASES'][$alias] = $path;
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
    public function mock($expr, array $args = null, array $server = null, $body = null)
    {
        $tmp = array_map('trim', explode(' ', $expr));

        $throw = 1 === count($tmp);
        $message = 'Mock should contains at least a verb and path, given "'.$expr.'".';
        self::throws($throw, $message);

        $verb = strtoupper($tmp[0]);
        $targetExpr = urldecode($tmp[1]);
        $mode = isset($tmp[2]) ? strtolower($tmp[2]) : 'none';
        $target = self::cutbefore($targetExpr, '?');
        $query = self::cutbefore(self::cutafter($targetExpr, '?', '', true), '#');
        $fragment = self::cutafter($targetExpr, '#', '', true);
        $path = $target;

        if (isset($this->hive['ROUTE_ALIASES'][$target])) {
            $path = $this->hive['ROUTE_ALIASES'][$target];
        } elseif (preg_match('/^(\w+)\(([^(]+)\)$/', $target, $match)) {
            parse_str(strtr($match[2], ',', '&'), $args);
            $path = $this->alias($match[1], $args);
        }

        $this->mclear('SENT,RESPONSE,HEADERS,BODY');

        $this->hive['VERB'] = $verb;
        $this->hive['PATH'] = $path;
        $this->hive['URI'] = $this->hive['BASE'].$path.$query.$fragment;
        $this->hive['AJAX'] = 'ajax' === $mode;
        $this->hive['CLI'] = 'cli' === $mode;
        $this->hive['REQUEST'] = 'POST' === $verb ? $args : array();

        parse_str(ltrim($query, '?'), $this->hive['QUERY']);

        if (in_array($verb, array('GET', 'HEAD'))) {
            $this->hive['QUERY'] = array_merge($this->hive['QUERY'], $args ?: array());
        } else {
            $this->hive['BODY'] = $body ?: http_build_query($args ?: array());
        }

        if ($server) {
            $this->hive['SERVER'] = $server + $this->hive['SERVER'];
        }

        return $this->run();
    }

    /**
     * Run kernel logic.
     *
     * @return App
     */
    public function run()
    {
        try {
            $this->doRun();
        } catch (\Exception $e) {
            $this->handleException($e);
        }

        return $this;
    }

    /**
     * Run kernel logic for PHP7.
     *
     * @return App
     *
     * @codeCoverageIgnore
     */
    public function run7()
    {
        try {
            $this->doRun();
        } catch (\Throwable $e) {
            $this->handleException($e);
        }

        return $this;
    }

    /**
     * Send response headers and content.
     *
     * @return App
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();

        return $this;
    }

    /**
     * Send response headers.
     *
     * @return App
     */
    public function sendHeaders()
    {
        if ($this->hive['CLI'] || headers_sent()) {
            return $this;
        }

        foreach ($this->cookies() as $cookies) {
            call_user_func_array('setcookie', $cookies);
        }

        foreach (array_filter($this->hive['HEADERS'], 'is_scalar') as $name => $value) {
            header($name.': '.$value);
        }

        header($this->hive['PROTOCOL'].' '.$this->hive['CODE'].' '.$this->hive['STATUS'], true);

        return $this;
    }

    /**
     * Send response content.
     *
     * @return App
     */
    public function sendContent()
    {
        if ($this->hive['QUIET'] || $this->hive['SENT']) {
            return $this;
        }

        $this->hive['SENT'] = true;

        if (0 >= $this->hive['KBPS']) {
            echo $this->hive['RESPONSE'];

            return $this;
        }

        $now = microtime(true);
        $ctr = 0;
        $kbps = $this->hive['KBPS'];

        foreach (str_split($this->hive['RESPONSE'], 1024) as $part) {
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
    public function expire($secs = 0)
    {
        $expire = (int) $secs;
        $headers = &$this->hive['HEADERS'];

        $headers['X-Powered-By'] = $this->hive['PACKAGE'];
        $headers['X-Frame-Options'] = $this->hive['XFRAME'];
        $headers['X-XSS-Protection'] = '1; mode=block';
        $headers['X-Content-Type-Options'] = 'nosniff';

        if ('GET' === $this->hive['VERB'] && $expire) {
            $time = microtime(true);
            unset($headers['Pragma']);

            $headers['Cache-Control'] = 'max-age='.$expire;
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
    public function status($code)
    {
        $name = 'self::HTTP_'.$code;
        $throw = !defined($name);
        $message = 'Unsupported HTTP code: '.$code.'.';

        self::throws($throw, $message, 'DomainException');

        $this->hive['CODE'] = $code;
        $this->hive['STATUS'] = constant($name);

        return $this;
    }

    /**
     * Send error response.
     *
     * @param int         $httpCode
     * @param string|null $message
     * @param array|null  $trace
     * @param int         $level
     *
     * @return App
     */
    public function error($httpCode, $message = null, array $trace = null, $level = 0)
    {
        $this->status($httpCode);

        $debug = $this->hive['DEBUG'];
        $status = $this->hive['STATUS'];
        $text = $message ?: 'HTTP '.$httpCode.' ('.$this->hive['VERB'].' '.$this->hive['PATH'].')';
        $traceStr = $this->trace($trace);

        $prior = $this->hive['ERROR'];
        $this->hive['ERROR'] = true;

        $this->expire(-1);
        $this->logByCode($level ?: E_USER_ERROR, $text.PHP_EOL.$traceStr);

        $event = new GetResponseForErrorEvent($httpCode, $status, $message, $traceStr);
        $this->trigger(self::EVENT_ERROR, $event)->off(self::EVENT_ERROR);

        if ($prior) {
            return $this;
        }

        $this->mclear('HEADERS,RESPONSE,KBPS');

        if ($event->isPropagationStopped()) {
            $this->hive['HEADERS'] = $event->getHeaders();
            $this->hive['RESPONSE'] = $event->getResponse();
        } elseif ($this->hive['AJAX']) {
            $traceInfo = $debug ? array('trace' => $traceStr) : array();
            $this->hive['HEADERS']['Content-Type'] = 'application/json';
            $this->hive['RESPONSE'] = json_encode(array(
                'status' => $status,
                'text' => $text,
            ) + $traceInfo);
        } elseif ($this->hive['CLI']) {
            $traceInfo = $debug ? $traceStr.PHP_EOL : PHP_EOL;
            $this->hive['RESPONSE'] = 'Status : '.$status.PHP_EOL.
                                      'Text   : '.$text.PHP_EOL.
                                      $traceInfo;
        } else {
            $traceInfo = $debug ? '<pre>'.$traceStr.'</pre>' : '';
            $this->hive['HEADERS']['Content-Type'] = 'text/html';
            $this->hive['RESPONSE'] = '<!DOCTYPE html>'.
                '<html>'.
                '<head>'.
                  '<meta charset="'.$this->hive['ENCODING'].'">'.
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
    public function log($level, $message)
    {
        if ($this->hive['LOG'] && isset(self::$logLevels[$level]) && self::$logLevels[$level] <= self::$logLevels[$this->hive['THRESHOLD']]) {
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
    public function logByCode($code, $message)
    {
        $map = array(
            // Emergency
            E_ERROR => self::LOG_LEVEL_EMERGENCY,
            E_PARSE => self::LOG_LEVEL_EMERGENCY,
            E_CORE_ERROR => self::LOG_LEVEL_EMERGENCY,
            E_COMPILE_ERROR => self::LOG_LEVEL_EMERGENCY,
            // Alerts
            E_WARNING => self::LOG_LEVEL_ALERT,
            E_CORE_WARNING => self::LOG_LEVEL_ALERT,
            // Critical
            E_STRICT => self::LOG_LEVEL_CRITICAL,
            // Error
            E_USER_ERROR => self::LOG_LEVEL_ERROR,
            // Warning
            E_USER_WARNING => self::LOG_LEVEL_WARNING,
            // Notice
            E_NOTICE => self::LOG_LEVEL_NOTICE,
            E_COMPILE_WARNING => self::LOG_LEVEL_NOTICE,
            E_USER_NOTICE => self::LOG_LEVEL_NOTICE,
            // Info
            E_RECOVERABLE_ERROR => self::LOG_LEVEL_INFO,
            E_DEPRECATED => self::LOG_LEVEL_INFO,
            E_USER_DEPRECATED => self::LOG_LEVEL_INFO,
        );
        $level = self::pick($map, (int) $code, self::LOG_LEVEL_DEBUG);

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
    public function logFiles($from = null, $to = null)
    {
        if (!$this->hive['LOG']) {
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
    public function logClear($from = null, $to = null)
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
    private function doRun()
    {
        if ($this->hive['DRY']) {
            $this->trigger(self::EVENT_BOOT, new Event());
            $this->hive['DRY'] = false;
        }

        if (empty($this->hive['ROUTES'])) {
            return $this->error(500, 'No route specified.');
        }

        // @codeCoverageIgnoreStart
        if ($this->blacklisted($this->hive['IP'])) {
            return $this->error(403);
        }
        // @codeCoverageIgnoreEnd

        $event = new GetResponseEvent();
        $this->trigger(self::EVENT_PREROUTE, $event);

        if ($event->isPropagationStopped()) {
            $code = $event->getCode();
            $this->hive['HEADERS'] = $event->getHeaders();
            $this->hive['RESPONSE'] = $event->getResponse();
            $this->hive['KBPS'] = $event->getKbps();

            return $this->status($code)->send();
        }

        if ($foundRoute = $this->findRoute()) {
            list($pattern, $routes, $args) = $foundRoute;

            if ($foundController = $this->findController($routes, $args)) {
                list($controller, $alias, $ttl, $kbps) = $foundController;

                $now = microtime(true);
                $verb = $this->hive['VERB'];
                $hash = self::hash($verb.' '.$this->hive['URI']).'.url';

                if ($ttl && in_array($verb, array('GET', 'HEAD'))) {
                    if ($this->isCached($hash, $cache)) {
                        $expireDate = $this->hive['SERVER']['HTTP_IF_MODIFIED_SINCE'];
                        $notModified = $expireDate && strtotime($expireDate) + $ttl > $now;

                        if ($notModified) {
                            return $this->status(304);
                        }

                        list($content, $lastModified) = $cache;
                        list($headers, $response) = $content;

                        $newExpireDate = $lastModified + $ttl - $now;
                        $this->hive['HEADERS'] = $headers;
                        $this->hive['RESPONSE'] = $response;

                        return $this->expire($newExpireDate);
                    }

                    $this->expire($ttl);
                } else {
                    $this->expire(0);
                }

                $event = new GetControllerArgsEvent($controller, $args);
                $this->trigger(self::EVENT_CONTROLLER_ARGS, $event);

                $controller = $event->getController();
                $args = $event->getArgs();

                $this->hive['PATTERN'] = $pattern;
                $this->hive['ALIAS'] = $alias;
                $this->hive['PARAMS'] = $args;
                $this->hive['KBPS'] = $kbps;

                if (is_callable($controller)) {
                    if (!$this->hive['RAW'] && !$this->hive['BODY']) {
                        $this->hive['BODY'] = file_get_contents('php://input');
                    }

                    $result = $this->call($controller, $args);

                    $event = new GetResponseForControllerEvent($result, $this->hive['HEADERS']);
                    $this->trigger(self::EVENT_POSTROUTE, $event);

                    $result = $event->getResult();

                    if ($event->isPropagationStopped()) {
                        $this->hive['HEADERS'] = $event->getHeaders();
                        $this->hive['RESPONSE'] = $event->getResponse();
                    } elseif (is_scalar($result)) {
                        $this->hive['RESPONSE'] = (string) $result;
                    } elseif (is_array($result)) {
                        $this->hive['HEADERS']['Content-Type'] = 'application/json';
                        $this->hive['RESPONSE'] = json_encode($result);
                    } elseif (is_callable($result)) {
                        call_user_func_array($result, array($this));
                    }

                    if ($ttl && $this->hive['RESPONSE'] && is_string($this->hive['RESPONSE'])) {
                        $this->cacheSet($hash, array(
                            $this->hive['HEADERS'],
                            $this->hive['RESPONSE'],
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
    private function findRoute()
    {
        $modifier = $this->hive['CASELESS'] ? 'i' : '';

        foreach ($this->hive['ROUTES'] as $pattern => $routes) {
            if (preg_match($this->regexify($pattern, $modifier), $this->hive['PATH'], $match)) {
                return array($pattern, $routes, $this->collectParams($match));
            }
        }

        return null;
    }

    /**
     * Returns route regex expression.
     *
     * @param string $pattern
     * @param string $modifier
     *
     * @return string
     */
    private function regexify($pattern, $modifier = '')
    {
        $patterns = array(
            '/(?:@([\w]+))/',
            '/(\*)$/',
        );
        $replaces = array(
            '(?<$1>[^\\/]+)',
            '(?<_p>.+)',
        );

        return '~^'.preg_replace($patterns, $replaces, $pattern).'$~'.$modifier;
    }

    /**
     * Returns array of filtered route match result.
     *
     * @param array $match
     *
     * @return array
     */
    private function collectParams(array $match)
    {
        $params = array();
        $skipNext = false;
        $ctr = 1;

        foreach ($match as $key => $value) {
            if (0 === $key || $skipNext) {
                $skipNext = false;
                continue;
            }

            if (is_string($key)) {
                $params[$key] = '_p' === $key ? explode('/', $value) : $value;
                $skipNext = true;
            } else {
                $params['_p'.$ctr] = $value;
                ++$ctr;
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
    private function findController(array $routes, array $args)
    {
        $mode = $this->requestMode();
        $route = null;
        $controller = null;

        if (isset($routes[$mode])) {
            $route = $routes[$mode];
        } elseif (isset($routes[self::REQ_ALL])) {
            $route = $routes[self::REQ_ALL];
        }

        if ($route && isset($route[$this->hive['VERB']])) {
            $handlerId = $route[$this->hive['VERB']];
            $controller = $this->hive['ROUTE_HANDLERS'][$handlerId];
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
                    $controller = $this->grab($handler);
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
    private function requestMode()
    {
        if ($this->hive['AJAX']) {
            return self::REQ_AJAX;
        }

        if ($this->hive['CLI']) {
            return self::REQ_CLI;
        }

        return self::REQ_SYNC;
    }

    /**
     * Returns prepared cookies to send.
     *
     * @return array
     */
    private function cookies()
    {
        $jar = array_combine(range(2, count($this->hive['JAR']) + 1), array_values($this->hive['JAR']));
        $init = $this->init['COOKIE'] ?: array();
        $current = $this->hive['COOKIE'] ?: array();
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
    private function sessionStart($startNow = true)
    {
        if ($startNow) {
            if (!headers_sent() && PHP_SESSION_ACTIVE !== session_status()) {
                session_start();
            }

            $this->hive['SESSION'] = &$GLOBALS['_SESSION'];
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
    private function resolveArgs(\ReflectionFunctionAbstract $ref, array $args = null)
    {
        if (0 === $ref->getNumberOfParameters()) {
            return array();
        }

        $resolved = array();
        $methodArgs = (array) $args;
        $positionalArgs = array_filter($methodArgs, 'is_numeric', ARRAY_FILTER_USE_KEY);

        foreach ($ref->getParameters() as $param) {
            if ($param->getClass()) {
                $resolved[] = $this->resolveClassArg($param, $methodArgs, $positionalArgs);
            } elseif ($methodArgs && array_key_exists($param->name, $methodArgs)) {
                $arg = $methodArgs[$param->name];
                $resolved[] = is_string($arg) ? $this->resolveArg($arg) : $arg;
            } elseif ($positionalArgs) {
                $resolved[] = array_shift($positionalArgs);
            }
        }

        return $resolved;
    }

    /**
     * Returns resolved named function parameter.
     *
     * @param string $val
     *
     * @return mixed
     */
    private function resolveArg($val)
    {
        if (class_exists($val)) {
            return $this->service($val);
        }

        if (preg_match('/(.+)?%(.+)%(.+)?/', $val, $match)) {
            // assume it does exists in hive
            $var = $this->ref($match[2], false);
            $match += array(1 => '', 3 => '');

            if (isset($var)) {
                return $match[1].$var.$match[3];
            }

            // it is a service alias
            return $this->service($match[2]);
        }

        return $val;
    }

    /**
     * Returns resolved class function parameter.
     *
     * @param ReflectionParameter $ref
     * @param array               &$args
     * @param array               &$positionalArgs
     *
     * @return mixed
     */
    private function resolveClassArg(\ReflectionParameter $ref, array &$args, array &$positionalArgs)
    {
        if (isset($args[$ref->name])) {
            $arg = $args[$ref->name];

            return is_string($arg) ? $this->resolveArg($arg) : $arg;
        }

        $classname = $ref->getClass()->name;

        if ($positionalArgs && $positionalArgs[0] instanceof $classname) {
            return array_shift($positionalArgs);
        }

        return $this->service($classname);
    }

    /**
     * Load cache by defined CACHE dsn.
     */
    private function cacheLoad()
    {
        $dsn = $this->hive['CACHE'];
        $engine = &$this->hive['CACHE_ENGINE'];
        $ref = &$this->hive['CACHE_REF'];

        if ($engine || !$dsn) {
            return;
        }

        $parts = array_map('trim', explode('=', $dsn) + array(1 => ''));
        $auto = '/^(apcu|apc)/';
        $grep = preg_grep($auto, array_map('strtolower', get_loaded_extensions()));

        // Fallback to filesystem cache
        $fallback = 'folder';
        $fallbackDir = $this->hive['TEMP'].'cache/';

        if ('redis' === $parts[0] && $parts[1] && extension_loaded('redis')) {
            list($host, $port, $db) = explode(':', $parts[1]) + array(1 => 0, 2 => null);

            $engine = 'redis';
            $ref = new \Redis();

            try {
                $ref->connect($host, $port ?: 6379, 2);

                if ($db) {
                    $ref->select($db);
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

                $ref->addServer($host, $port);
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
            self::mkdir($ref);
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
    private function cacheCompact($content, $time, $ttl)
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
    private function cacheParse($key, $raw)
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
    private function logDir()
    {
        $dir = $this->hive['LOG'];

        return $dir && is_dir($dir) ? $dir : $this->hive['TEMP'].$dir;
    }

    /**
     * Write log message.
     *
     * @param string $message
     * @param string $level
     */
    private function logWrite($message, $level)
    {
        $prefix = $this->logDir().'log_';
        $ext = '.log';
        $files = glob($prefix.date('Y-m').'*'.$ext);

        $file = $files ? $files[0] : $prefix.date('Y-m-d').$ext;
        $content = date('Y-m-d G:i:s.u').' '.$level.' '.$message.PHP_EOL;

        self::mkdir(dirname($file));
        self::write($file, $content, true);
    }

    /**
     * Returns trace as string.
     *
     * @param array|null &$trace
     *
     * @return string
     */
    private function trace(array &$trace = null)
    {
        if (!$trace) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_shift($trace);
        }

        $filteredTrace = array();
        $debug = $this->hive['DEBUG'];

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
        $cut = $this->hive['TRACE'];

        // Analyze stack trace
        foreach ($trace as $frame) {
            $line = '';

            if (isset($frame['class'])) {
                $line .= $frame['class'].$frame['type'];
            }

            if (isset($frame['function'])) {
                $line .= $frame['function'];
            }

            $src = self::fixslashes($frame['file']);
            $out .= '['.($cut ? str_replace($cut, '', $src) : $src).':'.$frame['line'].'] '.$line.$eol;
        }

        return $out;
    }

    /**
     * Returns message reference.
     *
     * @param string      $key
     * @param string|null $default
     *
     * @throws UnexpectedValueException If message reference is not a string
     */
    private function langRef($key, $default = null)
    {
        $ref = $this->get('DICT.'.$key);
        $throw = null !== $ref && !is_string($ref);
        $message = 'Message reference is not a string.';

        self::throws($throw, $message, 'UnexpectedValueException');

        return $ref ?: (null === $default ? $key : $default);
    }

    /**
     * Get languages.
     *
     * @return array
     */
    private function langLanguages()
    {
        $langCode = ltrim(preg_replace('/\h+|;q=[0-9.]+/', '', $this->hive['LANGUAGE']).','.$this->hive['FALLBACK'], ',');
        $languages = array();

        foreach (self::split($langCode) as $lang) {
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
    private function langLoad()
    {
        $dict = array();

        foreach ($this->langLanguages() as $lang) {
            foreach (self::arr($this->hive['LOCALES']) as $dir) {
                if (is_file($file = $dir.$lang.'.php')) {
                    $dict = array_replace_recursive($dict, self::requireFile($file, array()));
                }
            }
        }

        return $dict;
    }

    /**
     * Convenience method for checking hive key.
     *
     * @see App::exists
     */
    public function offsetExists($key)
    {
        return $this->exists($key);
    }

    /**
     * Convenience method for assigning hive value.
     *
     * @see App::set
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Convenience method for retrieving hive value.
     *
     * @see App::get
     */
    public function &offsetGet($key)
    {
        $ref = &$this->get($key);

        return $ref;
    }

    /**
     * Convenience method for removing hive key.
     *
     * @see App::clear
     */
    public function offsetUnset($key)
    {
        $this->clear($key);
    }
}

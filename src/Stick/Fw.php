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
 * Main framework engine.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class Fw implements \ArrayAccess
{
    // Framework info
    const PACKAGE = 'eghojansu/stick';
    const VERSION = 'v0.1.0-beta';

    // Events
    const EVENT_BOOT = 'fw.boot';
    const EVENT_ERROR = 'fw.error';
    const EVENT_SHUTDOWN = 'fw.shutdown';
    const EVENT_REROUTE = 'fw.reroute';
    const EVENT_BEFOREROUTE = 'fw.beforeroute';
    const EVENT_AFTERROUTE = 'fw.afterroute';

    // Route pattern
    const ROUTE_PATTERN = '~^([\w|]+)(?:\h+(\w+))?(?:\h+(/[^\h]*))?(?:\h+(ajax|cli|sync))?(?:\h+(\d+))?$~';
    const ROUTE_PARAMS = '~(?:@(\w+)(?::(\w+))?)(?:(\*$)|(?:\(([^\)]+)\)))?~';

    // Cookie date
    const COOKIE_DATE = 'D, d-M-Y H:i:s T';
    const COOKIE_LAX = 'Lax';
    const COOKIE_STRICT = 'Strict';

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

    // Logs
    const LOG_EMERGENCY = 'emergency';
    const LOG_ALERT = 'alert';
    const LOG_CRITICAL = 'critical';
    const LOG_ERROR = 'error';
    const LOG_WARNING = 'warning';
    const LOG_NOTICE = 'notice';
    const LOG_INFO = 'info';
    const LOG_DEBUG = 'debug';

    // Log levels
    const LOG_LEVELS = array(
        self::LOG_EMERGENCY => 0,
        self::LOG_ALERT => 1,
        self::LOG_CRITICAL => 2,
        self::LOG_ERROR => 3,
        self::LOG_WARNING => 4,
        self::LOG_NOTICE => 5,
        self::LOG_INFO => 6,
        self::LOG_DEBUG => 7,
    );

    /** @var array Framework variables hive */
    private $hive;

    /** @var array Framework initial variables hive */
    private $init;

    /**
     * Returns 64bit/base36 hash.
     *
     * @param string      $text
     * @param string|null $suffix
     *
     * @return string
     */
    public static function hash(string $text, string $suffix = null): string
    {
        return str_pad(
            base_convert(substr(sha1($text), -16), 16, 36),
            11,
            '0',
            STR_PAD_LEFT
        ).$suffix;
    }

    /**
     * Returns CamelCase to snake_case.
     *
     * @param string $text
     *
     * @return string
     */
    public static function snakeCase(string $text): string
    {
        return strtolower(preg_replace('/(?!^)\p{Lu}/u', '_\0', $text));
    }

    /**
     * Returns snake_case to camelCase.
     *
     * @param string $text
     *
     * @return string
     */
    public static function camelCase(string $text): string
    {
        return lcfirst(self::pascalCase($text));
    }

    /**
     * Returns snake_case to PascalCase.
     *
     * @param string $text
     *
     * @return string
     */
    public static function pascalCase(string $text): string
    {
        return str_replace('_', '', ucwords(str_replace('-', '_', $text), '_'));
    }

    /**
     * Returns camelCase to "Title Case".
     *
     * @param string $text
     *
     * @return string
     */
    public static function titleCase(string $text): string
    {
        return ucwords(str_replace('_', ' ', self::snakeCase($text)));
    }

    /**
     * Returns UPPER_SNAKE_CASE to Dash-Case.
     *
     * @param string $text
     *
     * @return string
     */
    public static function dashCase(string $text): string
    {
        return ucwords(str_replace('_', '-', strtolower($text)), '-');
    }

    /**
     * Returns class name from full namespace or instance of class.
     *
     * @param string|object $class
     *
     * @return string
     */
    public static function classname($class): string
    {
        return ltrim(
            strrchr('\\'.(is_object($class) ? get_class($class) : $class), '\\'),
            '\\'
        );
    }

    /**
     * Ensure variable is an array.
     *
     * @param string|array|null $var
     * @param string|null       $delimiter
     *
     * @return array
     */
    public static function split($var, string $delimiter = null): array
    {
        if (is_array($var)) {
            return $var;
        }

        if (!$var) {
            return array();
        }

        if (!is_string($var)) {
            return array($var);
        }

        return array_map('trim', preg_split(
            '/['.preg_quote($delimiter ?? ',;|', '/').']/',
            $var,
            0,
            PREG_SPLIT_NO_EMPTY
        ));
    }

    /**
     * Ensure variable is a string.
     *
     * @param mixed       $var
     * @param string|null $glue
     *
     * @return string
     */
    public static function join($var, string $glue = null): string
    {
        if (is_string($var)) {
            return $var;
        }

        if (is_array($var)) {
            return implode($glue ?? ',', $var);
        }

        return (string) $var;
    }

    /**
     * Extending array_column functionality, using self index for every row.
     *
     * @param array $input
     * @param mixed $key
     * @param bool  $raw   No filter
     *
     * @return array
     */
    public static function arrColumn(array $input, $key, bool $raw = true): array
    {
        $result = array();

        foreach ($input as $id => $value) {
            if ($raw || $value[$key]) {
                $result[$id] = $value[$key];
            }
        }

        return $result;
    }

    /**
     * Normalize line feed with new line.
     *
     * @param string $text
     *
     * @return string
     */
    public static function fixLinefeed(string $text): string
    {
        return preg_replace('/\r\n|\r/', "\n", $text);
    }

    /**
     * Remove trailing space.
     *
     * @param string $text
     *
     * @return string
     */
    public static function trimTrailingSpace(string $text): string
    {
        return preg_replace('/^\h+$/m', '', $text);
    }

    /**
     * Native require file wrapper.
     *
     * @param string $file
     *
     * @return mixed
     */
    public static function requireFile(string $file)
    {
        return require $file;
    }

    /**
     * Returns file content with normalized line feed if needed.
     *
     * @param string $file
     * @param bool   $lf   Normalize linefeed
     *
     * @return string
     */
    public static function read(string $file, bool $lf = false): string
    {
        $content = is_file($file) ? file_get_contents($file) : '';

        return $lf && $content ? self::fixLinefeed($content) : $content;
    }

    /**
     * Write content to specified file.
     *
     * @param string $file
     * @param string $content
     * @param int    $append
     *
     * @return int Returns -1 if failed
     */
    public static function write(string $file, string $content, bool $append = false): int
    {
        $result = file_put_contents(
            $file,
            $content,
            LOCK_EX | ((int) $append * FILE_APPEND)
        );

        return false === $result ? -1 : $result;
    }

    /**
     * Returns true if file deleted successfully.
     *
     * @param string $file
     *
     * @return bool
     */
    public static function delete(string $file): bool
    {
        return is_file($file) ? unlink($file) : false;
    }

    /**
     * Returns true if directory exists or created successfully.
     *
     * @param string $path
     * @param int    $mode
     *
     * @return bool
     */
    public static function mkdir(string $path, int $mode = 0755): bool
    {
        return file_exists($path) ? true : mkdir($path, $mode, true);
    }

    /**
     * Returns directory content recursively.
     *
     * @param string $dir
     *
     * @return RecursiveIteratorIterator
     */
    public static function files(string $dir): \RecursiveIteratorIterator
    {
        if (!is_dir($dir)) {
            throw new \LogicException(sprintf('Directory not exists: %s.', $dir));
        }

        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $dir,
                \FilesystemIterator::CURRENT_AS_FILEINFO | \FilesystemIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
    }

    /**
     * Convert variable to php value.
     *
     * @param mixed $var
     *
     * @return mixed
     */
    public static function cast($var)
    {
        if (is_string($var)) {
            $check = trim($var);

            if (defined($check)) {
                return constant($check);
            }

            if (preg_match('/^(?:0x[0-9a-f]+|0[0-7]+|(0b[01]+))$/i', $check, $match)) {
                return intval($check, 0);
            }
        }

        if (is_numeric($var)) {
            return $var + 0;
        }

        return $var;
    }

    /**
     * Convert PHP expression/value to compressed exportable string.
     *
     * @param mixed      $arg
     * @param array|null $stack
     *
     * @return string
     */
    public static function stringify($arg, array $stack = null): string
    {
        if (null === $stack) {
            $stack = array();
        } else {
            foreach ($stack as $node) {
                if ($arg === $node) {
                    return '*RECURSION*';
                }
            }
        }

        $type = gettype($arg);

        if ('object' === $type) {
            $str = '';

            if ($props = get_object_vars($arg)) {
                foreach ($props as $key => $val) {
                    $str .= ','.var_export($key, true).'=>'.
                        self::stringify($val, array_merge($stack, array($arg)));
                }

                $str = '['.ltrim($str, ',').']';
            }

            return addslashes(get_class($arg)).'::__set_state('.$str.')';
        }

        if ('array' === $type) {
            $str = '';
            $num = isset($arg[0]) && ctype_digit(implode('', array_keys($arg)));

            foreach ($arg as $key => $val) {
                $key = $num ? '' : var_export($key, true).'=>';
                $str .= ','.$key.self::stringify($val, array_merge($stack, array($arg)));
            }

            return '['.ltrim($str, ',').']';
        }

        return var_export($arg, true);
    }

    /**
     * Flatten array values and return as CSV string.
     *
     * @param array $arguments
     *
     * @return string
     */
    public static function csv(array $arguments): string
    {
        return implode(',', array_map(
            'stripcslashes',
            array_map(array('Fal\\Stick\\Fw', 'stringify'), $arguments)
        ));
    }

    /**
     * Returns true if ip registered in list.
     *
     * @param string       $ip
     * @param string|array $list
     *
     * @return bool
     */
    public static function ipValid(string $ip, $list): bool
    {
        $list = self::split($list);

        if (in_array($ip, $list)) {
            return true;
        }

        $ipLong = ip2long($ip);

        foreach ($list as $item) {
            $range = explode('..', $item);

            if (
                isset($range[1]) &&
                $ipLong >= ip2long($range[0]) &&
                $ipLong <= ip2long($range[1])
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns full url.
     *
     * @param string      $scheme
     * @param string      $host
     * @param int         $port
     * @param string|null $suffix
     *
     * @return string
     */
    public static function buildUrl(
        string $scheme,
        string $host,
        int $port = 80,
        string $suffix = null
    ): string {
        $prefix = $scheme.'://'.$host;

        if (80 !== $port && 443 !== $port) {
            $prefix .= ':'.$port;
        }

        return $prefix.$suffix;
    }

    /**
     * Returns request headers from server.
     *
     * @param array|null $server
     *
     * @return array
     */
    public static function resolveRequestHeaders(?array $server): array
    {
        if (!$server) {
            return array();
        }

        $headers = array();

        foreach ($server as $key => $value) {
            if ('CONTENT_TYPE' === $key || 'CONTENT_LENGTH' === $key) {
                $headers[self::dashCase($key)] = $value;
            } elseif (0 === strpos($key, 'HTTP_') && $key = substr($key, 5)) {
                $headers[self::dashCase($key)] = $value;
            }
        }

        return $headers;
    }

    /**
     * Returns fixed upload files.
     *
     * @param array|null $files
     *
     * @return array
     */
    public static function resolveUploadFiles(?array $files): array
    {
        if (!$files) {
            return array();
        }

        $fileKeys = array('error', 'name', 'size', 'tmp_name', 'type');
        $resolved = array();

        foreach ($files as $key => $file) {
            ksort($file);

            if ($fileKeys !== array_keys($file)) {
                continue;
            }

            if (is_array($file['name'])) {
                $skip = array();

                foreach ($file as $group => $info) {
                    foreach ($info as $pos => $value) {
                        if (
                            isset($skip[$pos]) ||
                            ('error' === $group && UPLOAD_ERR_NO_FILE === $value)
                        ) {
                            $skip[$pos] = true;
                            continue;
                        }

                        $resolved[$key][$pos][$group] = $value;
                    }
                }
            } elseif (UPLOAD_ERR_NO_FILE !== $file['error']) {
                $resolved[$key] = $file;
            }
        }

        return $resolved;
    }

    /**
     * Returns class instance.
     *
     * @param array|null $parameters
     * @param array|null $query
     * @param array|null $request
     * @param array|null $cookies
     * @param array|null $files
     * @param array|null $server
     *
     * @return Fw
     */
    public static function create(
        array $parameters = null,
        array $query = null,
        array $request = null,
        array $cookies = null,
        array $files = null,
        array $server = null
    ): Fw {
        return new static($parameters, $query, $request, $cookies, $files, $server);
    }

    /**
     * Returns class instance from globals environment.
     *
     * @param array|null $parameters
     *
     * @return Fw
     */
    public static function createFromGlobals(array $parameters = null): Fw
    {
        return new static($parameters, $_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
    }

    /**
     * Initialize engine.
     *
     * @param array|null $parameters initial parameters
     * @param array|null $query      equivalent to $_GET
     * @param array|null $request    equivalent to $_POST
     * @param array|null $cookies    equivalent to $_COOKIE
     * @param array|null $files      equivalent to $_FILES
     * @param array|null $server     equivalent to $_SERVER
     */
    public function __construct(
        array $parameters = null,
        array $query = null,
        array $request = null,
        array $cookies = null,
        array $files = null,
        array $server = null
    ) {
        $start = microtime(true);
        $cli = 'cli' === PHP_SAPI || 'phpdbg' === PHP_SAPI;
        $host = $server['SERVER_NAME'] ?? 'localhost';
        $script = $server['SCRIPT_NAME'] ?? '';
        $script = $cli || !$script || false === strpos($script, '.') ?
            '' : str_replace('\\', '/', $script);
        $base = rtrim(dirname($script), '/');
        $secure = 'on' === ($server['HTTPS'] ?? '') ||
            'https' === ($server['HTTP_X_FORWARDED_PROTO'] ?? '');
        $uri = $server['REQUEST_URI'] ?? '/';
        $url = parse_url($uri);

        // request path
        $path = $script && 0 === strpos($url['path'], $script) ?
            substr($url['path'], strlen($script)) : $url['path'];
        $path = $path ? urldecode($path) : '/';

        // cors
        $cors = array(
            'credentials' => false,
            'expose' => null,
            'headers' => null,
            'origin' => null,
            'ttl' => 0,
        );
        // cookie jar
        $cookieJar = array(
            'expires' => 0,
            'path' => $base,
            'domain' => 'localhost' === $host ? null : $host,
            'secure' => $secure,
            'httponly' => true,
            'raw' => false,
            'samesite' => null,
        );
        $req = self::resolveRequestHeaders($server);
        $trace = ($server['DOCUMENT_ROOT'] ?? dirname(__DIR__, 2)).'/';

        $this->init = $this->hive = array(
            'AGENT' => $req['X-Operamini-Phone-Ua'] ?? $req['X-Skyfire-Phone'] ?? $req['User-Agent'] ?? 'Stick',
            'AJAX' => 'XMLHttpRequest' === ($req['X-Requested-With'] ?? ''),
            'ALIAS' => null,
            'ALIASES' => null,
            'ASSET' => null,
            'ASSET_PREFIX' => '/',
            'ASSETS' => null,
            'AUTOLOAD' => null,
            'AUTOLOAD_FALLBACK' => null,
            'AUTOLOAD_MISSING' => null,
            'BASE' => $base,
            'BLACKLIST' => null,
            'BODY' => null,
            'CACHE' => null,
            'CACHE_ENGINE' => null,
            'CACHE_REF' => null,
            'CASELESS' => true,
            'CLI' => $cli,
            'COOKIE' => $cookies,
            'CORS' => $cors,
            'CSRF' => null,
            'CSRF_KEY' => 'SESSION.csrf',
            'CSRF_PREV' => null,
            'DEBUG' => 0,
            'ERROR' => null,
            'EVENTS' => null,
            'EVENTS_ONCE' => null,
            'FALLBACK' => 'en',
            'FILES' => self::resolveUploadFiles($files),
            'FRONT' => rtrim('/'.basename($script), '/'),
            'GET' => $query,
            'HOST' => $host,
            'IP' => strstr(($req['X-Client-Ip'] ?? $req['X-Forwarded-For'] ?? $server['REMOTE_ADDR'] ?? '').',', ',', true),
            'JAR' => $cookieJar,
            'LANGUAGE' => $req['Accept-Language'] ?? null,
            'LEXICON' => null,
            'LOCALES' => null,
            'LOG' => null,
            'LOG_CONVERT' => null,
            'LOG_THRESHOLD' => 'error',
            'MOCK_LEVEL' => 0,
            'NO_SHUTDOWN_HANDLER' => defined('__STICK_TEST'),
            'OUTPUT' => null,
            'PACKAGE' => self::PACKAGE,
            'PARAMS' => null,
            'PATH' => $path,
            'PATTERN' => null,
            'PORT' => intval($req['X-Forwarded-Port'] ?? $server['SERVER_PORT'] ?? 80),
            'POST' => $request,
            'PROTOCOL' => $server['SERVER_PROTOCOL'] ?? 'HTTP/1.0',
            'QUIET' => false,
            'RAW' => false,
            'REQUEST' => $req,
            'RESPONSE' => null,
            'ROUTES' => null,
            'SCHEME' => $secure ? 'https' : 'http',
            'SEED' => self::hash($host.$base),
            'SERVER' => $server,
            'SERVICES' => null,
            'SESSION' => null,
            'STATUS' => 200,
            'TEMP' => null,
            'TEXT' => self::HTTP_200,
            'TIME' => $start,
            'TRACE_CLEAR' => '/' === $trace ? '' : $trace,
            'URI' => $uri,
            'VERB' => $req['X-Http-Method-Override'] ?? $server['REQUEST_METHOD'] ?? 'GET',
            'VERSION' => self::VERSION,
            'WHITELIST' => null,
            'WITH_PRIOR_ERROR' => true,
            'XFRAME' => 'SAMEORIGIN',
        );

        // assign initial parameters
        empty($parameters) || $this->mset($parameters);

        // defaults register shutdown handler
        $this->hive['NO_SHUTDOWN_HANDLER'] || register_shutdown_function(array($this, 'unload'), getcwd());
    }

    /**
     * {inheritdoc}.
     */
    public function __call($method, $arguments)
    {
        if ($func = $this->get($method)) {
            return $this->call($func, $this, ...$arguments);
        }

        throw new \BadMethodCallException(sprintf(
            'Call to undefined method Fal\\Stick\\Fw::%s.',
            $method
        ));
    }

    /**
     * Allow check hive member as class property.
     *
     * @param string $key
     *
     * @return bool
     */
    public function __isset($key)
    {
        return $this->has($key);
    }

    /**
     * Allow retrieve hive member as class property.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function &__get($key)
    {
        $var = &$this->get($key);

        return $var;
    }

    /**
     * Allow assign hive member as class property.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Allow remove hive member as class property.
     *
     * @param string $key
     */
    public function __unset($key)
    {
        $this->rem($key);
    }

    /**
     * Allow check hive member as array.
     *
     * @param string $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Allow retrieve hive member as array.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function &offsetGet($key)
    {
        $var = &$this->get($key);

        return $var;
    }

    /**
     * Allow assign hive member as array.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Allow remove hive member as array.
     *
     * @param string $key
     */
    public function offsetUnset($key)
    {
        $this->rem($key);
    }

    /**
     * Returns ellapsed time since class construction.
     *
     * @return float
     */
    public function ellapsed(): float
    {
        return microtime(true) - $this->hive['TIME'];
    }

    /**
     * Enable request method overriding.
     *
     * @param string $key
     *
     * @return Fw
     */
    public function overrideRequestMethod(string $key = '_method'): Fw
    {
        if ('POST' === $this->hive['VERB'] && isset($this->hive['POST'][$key])) {
            $this->hive['VERB'] = $this->hive['POST'][$key];
        }

        return $this;
    }

    /**
     * Enable cli request emulation.
     *
     * @return Fw
     */
    public function emulateCliRequest(): Fw
    {
        if ($this->hive['CLI'] && isset($this->hive['SERVER']['argv'])) {
            $uri = '/';
            $argv = $this->hive['SERVER']['argv'];
            array_shift($argv);

            if (isset($argv[0][0]) && '/' === $argv[0][0]) {
                // in form: /path, /path?query=value etc.
                $uri = $argv[0];
            } else {
                // build uri based on arguments and options
                $opts = '';
                $opt = null;

                for ($i = 0, $last = count($argv); $i < $last; ++$i) {
                    $arg = $argv[$i];

                    // we are paranoid!!!
                    if ('-' === $arg) {
                        continue;
                    }

                    // treat arguments properly
                    if ('--' === $arg) {
                        $opts .= '&_arguments[]='.implode(
                            '&_arguments[]=',
                            array_map('urlencode', array_slice($argv, $i + 1))
                        );

                        break;
                    }

                    // not an option?
                    if ('-' !== $arg[0]) {
                        $arg = urlencode($arg);

                        if ($opt) {
                            $val = '';
                            $key = '&'.$opt.'[]=';

                            for ($j = $i + 1; $j < $last; ++$j) {
                                if ('-' === $argv[$j][0]) {
                                    break;
                                }

                                $val .= $key.urlencode($argv[$j]);
                                ++$i;
                            }

                            $opts .= $val ? $key.$arg.$val : '&'.$opt.'='.$arg;
                            $opt = null;
                        } else {
                            $uri .= $arg.'/';
                        }

                        continue;
                    }

                    // long option?
                    if ('-' === $arg[1]) {
                        // opt=value form?
                        if (false === $pos = strpos($arg, '=')) {
                            $opt = urlencode(substr($arg, 2));
                        } else {
                            $opts .= urlencode(substr($arg, 2, $pos - 2)).'=';
                            $opts .= urlencode(substr($arg, $pos + 1));
                        }

                        continue;
                    }

                    // always treat short option as combined
                    if (false === $pos = strpos($arg, '=')) {
                        $all = str_split(substr($arg, 1));
                        $opt = array_pop($all);
                        $line = '';
                    } else {
                        // give value to the last option for o=value form
                        $all = array_filter(str_split(substr($arg, 1, $pos - 2)));
                        $line = '&'.substr($arg, $pos - 1, 1).'=';
                        $line .= urlencode(substr($arg, $pos + 1));
                    }

                    if ($all) {
                        $opts .= '&'.implode('=&', $all);
                    }

                    $opts .= $line;
                }

                if ('/' !== $uri) {
                    $uri = rtrim($uri, '/');
                }

                if ($opt) {
                    $opts .= '&'.$opt;
                }

                if ($opts) {
                    $uri .= '?'.rtrim($opts, '&');
                }
            }

            $url = parse_url($uri);
            parse_str($url['query'] ?? '', $queries);

            $this->hive['VERB'] = 'GET';
            $this->hive['PATH'] = $url['path'];
            $this->hive['URI'] = $uri;
            $this->hive['GET'] = $queries;
        }

        return $this;
    }

    /**
     * Execute framework/application shutdown sequence.
     *
     * *to be used internally by php*
     *
     * @param string $cwd
     *
     * @codeCoverageIgnore
     */
    public function unload(string $cwd): void
    {
        chdir($cwd);

        $error = error_get_last();

        if (null === $error && $this->sessionActive()) {
            session_commit();
        }

        try {
            $this->dispatch(self::EVENT_SHUTDOWN, $this, $error);
        } catch (\Throwable $e) {
            $this->handleException($e);

            $this->send();
            die;
        }

        if ($error['type'] & error_reporting()) {
            // Clear any buffer
            ob_end_clean();

            // show previous error only!
            $this->hive['ERROR'] || $this->error(
                500,
                'Fatal error: '.$error['message'],
                array($error)
            );

            $this->send();
            die;
        }
    }

    /**
     * Register class loader.
     *
     * @param bool $prepend
     *
     * @return Fw
     */
    public function registerAutoloader(bool $prepend = false): Fw
    {
        spl_autoload_register(array($this, 'loadClass'), true, $prepend);

        return $this;
    }

    /**
     * Unregister class loader.
     *
     * @return Fw
     */
    public function unregisterAutoloader(): Fw
    {
        spl_autoload_unregister(array($this, 'loadClass'));

        return $this;
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
        if (isset($this->hive['AUTOLOAD_MISSING'][$class])) {
            return null;
        }

        $file = $this->findFileWithExtension($class, '.php') ??
            $this->findFileWithExtension($class, '.hh');

        if (null === $file) {
            // Remember, this class not exist
            $this->hive['AUTOLOAD_MISSING'][$class] = true;
        }

        return $file;
    }

    /**
     * Load class file.
     *
     * @param string $class
     *
     * @return bool|void
     */
    public function loadClass(string $class)
    {
        if ($file = $this->findClass($class)) {
            self::requireFile($file);

            return true;
        }
    }

    /**
     * Returns base path or base url.
     *
     * @param string $suffix
     * @param bool   $absolute
     *
     * @return string
     */
    public function baseUrl(string $suffix = null, bool $absolute = false): string
    {
        $path = $this->hive['BASE'].$suffix;

        return $absolute ? self::buildUrl(
            $this->hive['SCHEME'],
            $this->hive['HOST'],
            $this->hive['PORT'],
            $path
        ) : $path;
    }

    /**
     * Returns site url.
     *
     * @param string $suffix
     * @param bool   $absolute
     *
     * @return string
     */
    public function siteUrl(string $suffix = null, bool $absolute = false): string
    {
        return $this->baseUrl($this->hive['FRONT'].$suffix, $absolute);
    }

    /**
     * Returns current url.
     *
     * @param bool $absolute
     *
     * @return string
     */
    public function currentUrl(bool $absolute = false): string
    {
        $suffix = rtrim('?'.http_build_query($this->hive['GET'] ?? array()), '?');

        return $this->siteUrl($this->hive['PATH'].$suffix, $absolute);
    }

    /**
     * Returns asset path.
     *
     * @param string $path
     * @param bool   $absolute
     *
     * @return string
     */
    public function asset(string $path, bool $absolute = false): string
    {
        if (isset($this->hive['ASSETS'][$path])) {
            $path = $this->hive['ASSETS'][$path];
        }

        if ('dynamic' === $this->hive['ASSET']) {
            $path .= '?v'.$this->hive['TIME'];
        } else {
            $path .= rtrim('?'.$this->hive['ASSET'], '?');
        }

        return $this->baseUrl($this->hive['ASSET_PREFIX'].$path, $absolute);
    }

    /**
     * Returns variable reference from hive.
     *
     * @param string     $key
     * @param bool       $add
     * @param bool|null  &$found
     * @param array|null &$var
     *
     * @return mixed
     */
    public function &ref(
        string $key,
        bool $add = true,
        bool &$found = null,
        array &$var = null
    ) {
        $parts = explode('.', $key);
        $self = null === $var;

        if (
            'SESSION' === $parts[0] &&
            $self &&
            !headers_sent() &&
            !$this->sessionActive()
        ) {
            session_start();
            $this->hive['SESSION'] = &$GLOBALS['_SESSION'];
        }

        if ($self) {
            if ($add) {
                $var = &$this->hive;
            } else {
                $var = $this->hive;
            }
        }

        foreach ($parts as $part) {
            if (null === $var || is_scalar($var)) {
                $var = array();
            }

            $exists = false;

            if (
                is_array($var) &&
                ($add || $exists = array_key_exists($part, $var))
            ) {
                $found = $exists || array_key_exists($part, $var);
                $var = &$var[$part];
            } elseif (
                is_object($var) &&
                ($add || $exists = property_exists($var, $part))
            ) {
                $found = $exists || property_exists($var, $part);
                $var = &$var->$part;
            } else {
                $found = false;
                $var = null;
                break;
            }
        }

        return $var;
    }

    /**
     * Remove variable reference from hive.
     *
     * @param string     $key
     * @param array|null &$var
     *
     * @return Fw
     */
    public function unref(string $key, array &$var = null): Fw
    {
        if (false === $pos = strrpos($key, '.')) {
            $last = $key;

            if (null === $var) {
                $var = &$this->hive;
            }
        } else {
            $pick = substr($key, 0, $pos);
            $last = substr($key, $pos + 1);
            $var = &$this->ref($pick, true, $found, $var);
        }

        if (is_array($var) || $var instanceof \ArrayAccess) {
            unset($var[$last]);
        } elseif (is_object($var)) {
            unset($var->$last);
        }

        return $this;
    }

    /**
     * Returns variables hive.
     *
     * @return array
     */
    public function hive(): array
    {
        return $this->hive;
    }

    /**
     * Returns true if key exists in hive.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        $this->ref($key, false, $found);

        return $found;
    }

    /**
     * Returns service or hive's value.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function &get(string $key, $default = null)
    {
        // reserved keyword, returning self instance
        if (0 === strcasecmp($key, 'fw') || 'Fal\\Stick\\Fw' === $key) {
            return $this;
        }

        $var = &$this->ref($key, true, $found);

        if (!$found) {
            if (isset($this->hive['SERVICES'][$key])) {
                $obj = $this->hive['SERVICES'][$key];

                if (is_string($obj)) {
                    if ($obj === $call = $this->grab($obj)) {
                        $var = new $obj($this);
                    } else {
                        $var = $call($this);
                    }
                } elseif (is_callable($obj)) {
                    $var = $obj($this);
                } else {
                    $var = $obj;
                }
            } elseif (null === $default && class_exists($key)) {
                // treat as a class
                $var = new $key($this);
            } else {
                $var = $default;
            }
        }

        return $var;
    }

    /**
     * Assign key's value into hive.
     *
     * @param string $key
     * @param mixed  $val
     *
     * @return Fw
     */
    public function set(string $key, $val): Fw
    {
        if ('COOKIE' === $key) {
            return $this->cookies($val);
        } elseif (0 === strpos($key, 'COOKIE.')) {
            return $this->cookie(substr($key, 7), $val);
        } elseif (0 === strpos($key, 'RESPONSE.')) {
            return $this->hset(substr($key, 9), $val);
        }

        $var = &$this->ref($key);
        $var = $val;

        switch ($key) {
            case 'AUTOLOAD':
                $var = $this->autoNormalize($val);
                break;
            case 'AUTOLOAD_FALLBACK':
                $var = $this->autoNormalizePaths($val);
                break;
            case 'CACHE':
                $this->cacheLoad();
                break;
            case 'FALLBACK':
            case 'LANGUAGE':
            case 'LOCALES':
                $this->langLoad();
                break;
            case 'RESPONSE':
                $var = (array) $val;
                break;
            case 'TEMP':
                $this->cacheFallback();
                break;
        }

        return $this;
    }

    /**
     * Remove key's value from hive.
     *
     * @param string $key
     *
     * @return Fw
     */
    public function rem(string $key): Fw
    {
        if ('COOKIE' === $key) {
            return $this->cookies(array());
        } elseif (0 === strpos($key, 'COOKIE.')) {
            return $this->cookie(substr($key, 7));
        }

        $ref = $this->init;
        $val = $this->ref($key, false, $found, $ref);

        if ($found) {
            if ('SESSION' === $key) {
                $this->sessionActive() || session_start();
                // completely remove session
                session_unset();
                session_destroy();

                $this->hrem('Set-Cookie');
            }

            return $this->set($key, $val);
        }

        return $this->unref($key);
    }

    /**
     * Reset hive to initial state.
     *
     * @return Fw
     */
    public function reset(): Fw
    {
        $this->hive = $this->init;

        return $this;
    }

    /**
     * Returns true if hive contains all keys.
     *
     * @param string|array $keys
     *
     * @return bool
     */
    public function mhas($keys): bool
    {
        foreach (self::split($keys) as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns keys's value.
     *
     * @param array $keys
     *
     * @return array
     */
    public function mget(array $keys): array
    {
        $result = array();

        foreach ($keys as $alias => $key) {
            if (is_numeric($alias)) {
                $result[$key] = $this->get($key);
            } else {
                $result[$alias] = $this->get($key);
            }
        }

        return $result;
    }

    /**
     * Assign values into hive.
     *
     * @param array       $values
     * @param string|null $prefix
     *
     * @return Fw
     */
    public function mset(array $values, string $prefix = null): Fw
    {
        foreach ($values as $key => $value) {
            $this->set($prefix.$key, $value);
        }

        return $this;
    }

    /**
     * Remove keys from hive.
     *
     * @param string|array $keys
     *
     * @return Fw
     */
    public function mrem($keys): Fw
    {
        foreach (self::split($keys) as $key) {
            $this->rem($key);
        }

        return $this;
    }

    /**
     * Returns hive value and remove.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function flash(string $key)
    {
        $val = $this->get($key);
        $this->rem($key);

        return $val;
    }

    /**
     * Copy hive internally.
     *
     * @param string $src
     * @param string $dst
     *
     * @return Fw
     */
    public function copy(string $src, string $dst): FW
    {
        return $this->set($dst, $this->get($src));
    }

    /**
     * Move hive internally.
     *
     * @param string $src
     * @param string $dst
     *
     * @return Fw
     */
    public function cut(string $src, string $dst): FW
    {
        $this->set($dst, $this->get($src));

        return $this->rem($src);
    }

    /**
     * Prepend value to hive.
     *
     * @param string $key
     * @param mixed  $value
     * @param bool   $force
     *
     * @return Fw
     */
    public function prepend(string $key, $value, bool $force = false): Fw
    {
        $var = $this->get($key);

        if (null === $var && $force) {
            $var = array();
        }

        if (is_scalar($var)) {
            $var = $value.$var;
        } elseif (is_array($var)) {
            array_unshift($var, $value);
        } else {
            $var = $value;
        }

        return $this->set($key, $var);
    }

    /**
     * Append value to hive.
     *
     * @param string $key
     * @param mixed  $value
     * @param bool   $force
     *
     * @return Fw
     */
    public function append(string $key, $value, bool $force = false): Fw
    {
        $var = $this->get($key);

        if (null === $var && $force) {
            $var = array();
        }

        if (is_scalar($var)) {
            $var .= $value;
        } elseif (is_array($var)) {
            array_push($var, $value);
        } else {
            $var = $value;
        }

        return $this->set($key, $var);
    }

    /**
     * Returns expected service.
     *
     * @param string $key
     * @param string $expected
     *
     * @return mixed
     */
    public function service(string $key, string $expected)
    {
        $service = $this->get($key);

        if (!$service instanceof $expected) {
            throw new \LogicException(sprintf(
                'Instance of %s expected, given %s (key: %s).',
                $expected,
                is_object($service) ? get_class($service) : gettype($service),
                $key
            ));
        }

        return $service;
    }

    /**
     * Register csrf key.
     *
     * @return Fw
     */
    public function csrfRegister(): Fw
    {
        $this->set('CSRF', self::hash(
            $this->hive['SEED'].(extension_loaded('openssl') ?
                implode(unpack('L', openssl_random_pseudo_bytes(4))) : mt_rand()
            )
        ));
        $this->copy($this->hive['CSRF_KEY'], 'CSRF_PREV');
        $this->copy('CSRF', $this->hive['CSRF_KEY']);

        return $this;
    }

    /**
     * Returns true if csrf valid.
     *
     * @param string $csrf
     *
     * @return bool
     */
    public function isCsrfValid(string $csrf): bool
    {
        return $this->hive['CSRF'] && $this->hive['CSRF_PREV'] &&
            0 === strcmp($csrf, $this->hive['CSRF_PREV']);
    }

    /**
     * Returns true if compared verbs equals to current http verb.
     *
     * @param string ...$verbs
     *
     * @return bool
     */
    public function isVerb(string ...$verbs): bool
    {
        foreach ($verbs as $verb) {
            if (0 === strcasecmp($this->hive['VERB'], $verb)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse text, replace token if exists in hive.
     *
     * @param string $text
     *
     * @return string
     */
    public function parse(string $text): string
    {
        return preg_replace_callback('/\$\{\h*([^}]+)\h*\}/', function ($match) {
            return self::join($this->get($match[1]) ?? $match[0]);
        }, $text);
    }

    /**
     * Load configuration from INI-file.
     *
     * @param string $file
     * @param bool   $parse
     *
     * @return Fw
     */
    public function config(string $file, bool $parse = false): Fw
    {
        $pattern = '/(?<=^|\n)(?:'.
            '\[(?<section>.+?)\]|'.
            '(?<lval>[^\h\r\n;].*?)\h*=\h*'.
            '(?<rval>(?:\\\\\h*\r?\n|.+?)*)'.
        ')(?=\r?\n|$)/';

        if (!preg_match_all($pattern, self::read($file), $matches, PREG_SET_ORDER)) {
            return $this;
        }

        $pattern = "/^(config|route|controller|rest|redirect)s\b(?:\.(.+))?/i";
        $section = 'globals';
        $prefix = null;
        $command = array();
        $argument = function ($match) use ($parse) {
            if ($parse) {
                $lval = $this->parse($match['lval']);
                $rval = $this->parse($match['rval']);
            } else {
                $lval = $match['lval'];
                $rval = $match['rval'];
            }

            return array_merge(
                array($lval),
                array_map(array('Fal\\Stick\\Fw', 'cast'), str_getcsv($rval))
            );
        };
        $cast = function ($val) {
            if (is_string($val = self::cast($val))) {
                return $val ? preg_replace('/\\\\"/', '"', trim($val)) : null;
            }

            return $val;
        };

        for ($i = 0, $last = count($matches) - 1; $i <= $last; ++$i) {
            $match = $matches[$i];

            if ($match['section']) {
                $section = $match['section'];
                $prefix = 0 === strcasecmp($section, 'globals') ? null : $section.'.';

                if (!preg_match($pattern, $section, $command)) {
                    continue;
                }

                if (0 === strcasecmp($command[1], 'controller')) {
                    if (empty($command[2])) {
                        throw new \LogicException(sprintf(
                            'The command need first parameter: %s.',
                            $command[1]
                        ));
                    }

                    $pair = array();

                    for ($j = $i + 1; $j <= $last; ++$j) {
                        if ($matches[$j]['section']) {
                            break;
                        }

                        $row = $argument($matches[$j]);
                        $ref = &$this->ref(array_shift($row), true, $found, $pair);
                        $ref = 1 === count($row) ? reset($row) : $row;
                        $i = $j;
                    }

                    $this->{$command[1]}($command[2], $pair);
                }

                continue;
            }

            if ($command) {
                $this->{$command[1]}(...$argument($match));

                continue;
            }

            if ($parse) {
                $key = $this->parse($match['lval']);
                $val = $this->parse($match['rval']);
            } else {
                $key = $match['lval'];
                $val = $match['rval'];
            }

            // Replace newline expression, mark quoted strings with 0x00 whitespace
            $val = preg_replace('/\\\\\h*(\r?\n)/', '\1', $val);
            $val = array_map($cast, str_getcsv(preg_replace(
                '/(?<!\\\\)(")(.*?)\1/',
                "\\1\x00\\2\\1",
                trim($val)
            )));

            $this->set($prefix.$key, count($val) > 1 ? $val : reset($val));
        }

        return $this;
    }

    /**
     * Register route.
     *
     * @param string $route
     * @param mixed  $controller
     *
     * @return Fw
     */
    public function route(string $route, $controller): Fw
    {
        if (
            preg_match(self::ROUTE_PATTERN, $route, $match, PREG_UNMATCHED_AS_NULL) &&
            count($match) < 3
        ) {
            throw new \LogicException(sprintf(
                'Invalid routing pattern: %s.',
                $route
            ));
        }

        $verbs = explode('|', strtoupper($match[1]));
        $alias = $match[2] ?? null;
        $pattern = $match[3] ?? null;
        $mode = $match[4] ?? 'all';
        $ttl = intval($match[5] ?? 0);

        if (!$pattern) {
            if (!isset($this->hive['ALIASES'][$alias])) {
                throw new \LogicException(sprintf(
                    'Route not exists: %s.',
                    $alias
                ));
            }

            $pattern = $this->hive['ALIASES'][$alias];
        }

        foreach ($verbs as $verb) {
            $this->hive['ROUTES'][$pattern][$mode][$verb] = array(
                $controller,
                $alias,
                $ttl,
            );
        }

        if ($alias) {
            $this->hive['ALIASES'][$alias] = $pattern;
        }

        return $this;
    }

    /**
     * Register route class controller.
     *
     * @param string $class
     * @param array  $routes
     *
     * @return Fw
     */
    public function controller(string $class, array $routes): Fw
    {
        foreach ($routes as $route => $method) {
            $this->route($route, $class.'->'.$method);
        }

        return $this;
    }

    /**
     * Register rest class controller.
     *
     * @param string $route
     * @param string $class
     * @param bool   $allowPatch
     *
     * @return Fw
     */
    public function rest(string $route, string $class, bool $allowPatch = false): Fw
    {
        $itemRoute = preg_replace_callback(
            '~^(?:(\w+)\h+)?(/[^\h]*)~', function ($match) {
                return ($match[1] ? $match[1].'_item' : '').' '.
                    ('/' === $match[2] ? '' : $match[2]).'/@item';
            },
            $route
        );

        return $this
            ->route('GET '.$route, $class.'->index')
            ->route('POST '.$route, $class.'->create')
            ->route('GET '.$itemRoute, $class.'->view')
            ->route('PUT'.($allowPatch ? '|PATCH' : '').' '.$itemRoute, $class.'->update')
            ->route('DELETE '.$itemRoute, $class.'->delete')
        ;
    }

    /**
     * Register redirect route.
     *
     * @param string $route
     * @param mixed  $target
     * @param bool   $permanent
     *
     * @return Fw
     */
    public function redirect(string $route, $target, bool $permanent = true): Fw
    {
        return $this->route($route, function () use ($target, $permanent) {
            return $this->reroute($target, $permanent);
        });
    }

    /**
     * Returns route path.
     *
     * @param string       $alias
     * @param string|array $parameters
     *
     * @return string
     */
    public function alias(string $alias, $parameters = null): string
    {
        if (!isset($this->hive['ALIASES'][$alias])) {
            throw new \LogicException(sprintf('Route not exists: %s.', $alias));
        }

        $pattern = $this->hive['ALIASES'][$alias];

        if (!$parameters && false === strpos($pattern, '@')) {
            return $pattern;
        }

        if (is_string($parameters)) {
            parse_str($parameters, $parameters);
        }

        if (!is_array($parameters)) {
            $parameters = (array) $parameters;
        }

        $path = preg_replace_callback(
            self::ROUTE_PARAMS,
            function ($match) use ($alias, &$parameters) {
                $name = $match[1];
                $defaultValue = $match[2] ?? null;
                $matchAll = $match[3] ?? false;
                $pattern = $matchAll ? null : ($match[4] ?? null);
                $param = $parameters[$name] ?? $defaultValue;

                if (empty($param)) {
                    throw new \LogicException(sprintf(
                        'Parameter should be provided (%s@%s).',
                        $name,
                        $alias
                    ));
                }

                if (
                    $pattern &&
                    is_string($param) &&
                    !preg_match('~^'.$pattern.'$~', $param)
                ) {
                    throw new \LogicException(sprintf(
                        'Parameter is not valid, given: %s (%s@%s).',
                        $param,
                        $name,
                        $alias
                    ));
                }

                unset($parameters[$name]);

                if (is_array($param)) {
                    return implode('/', array_map(function ($item) {
                        return is_string($item) ? urlencode($item) : $item;
                    }, $param));
                }

                if (is_string($param)) {
                    return urlencode($param);
                }

                return $param;
            },
            $pattern
        );

        if ($parameters) {
            $path .= '?'.http_build_query($parameters);
        }

        return $path;
    }

    /**
     * Returns route relative/absolute url.
     *
     * @param string       $alias
     * @param string|array $parameters
     * @param bool         $absolute
     *
     * @return string
     */
    public function path(string $alias, $parameters = null, bool $absolute = false): string
    {
        if (isset($this->hive['ALIASES'][$alias])) {
            $path = $this->alias($alias, $parameters);
        } else {
            $path = '/'.ltrim($alias, '/');

            if ($parameters) {
                if (is_array($parameters)) {
                    $parameters = http_build_query($parameters);
                }

                $path .= '?'.$parameters;
            }
        }

        return $this->siteUrl($path, $absolute);
    }

    /**
     * Grab callable expression.
     *
     * @param string $expression
     *
     * @return mixed
     */
    public function grab(string $expression)
    {
        if (
            false !== strpos($expression, '->') &&
            2 === count($parts = explode('->', $expression))
        ) {
            return array($this->get($parts[0]), $parts[1]);
        }

        if (
            false !== strpos($expression, '::') &&
            2 === count($parts = explode('::', $expression))
        ) {
            return $parts;
        }

        return $expression;
    }

    /**
     * Execute callback.
     *
     * @param mixed $callback
     * @param mixed ...$arguments
     *
     * @return mixed
     */
    public function call($callback, ...$arguments)
    {
        if (is_string($callback)) {
            $callback = $this->grab($callback);
        }

        return $callback(...$arguments);
    }

    /**
     * Execute callback in chain with this instance.
     *
     * @param callable $callback
     *
     * @return Fw
     */
    public function chain(callable $callback): Fw
    {
        $callback($this);

        return $this;
    }

    /**
     * Register event listener.
     *
     * @param string $eventName
     * @param mixed  $listener
     * @param bool   $once
     *
     * @return Fw
     */
    public function on(string $eventName, $listener, bool $once = false): Fw
    {
        $this->set('EVENTS.'.$eventName, $listener);

        if ($once) {
            $this->set('EVENTS_ONCE.'.$eventName, true);
        }

        return $this;
    }

    /**
     * Register event listener once.
     *
     * @param string $eventName
     * @param mixed  $listener
     *
     * @return Fw
     */
    public function one(string $eventName, $listener): Fw
    {
        return $this->on($eventName, $listener, true);
    }

    /**
     * Remove event listener.
     *
     * @param string $eventName
     *
     * @return Fw
     */
    public function off(string $eventName): Fw
    {
        return $this->mrem(array(
            'EVENTS.'.$eventName,
            'EVENTS_ONCE.'.$eventName,
        ));
    }

    /**
     * Returns true if all listeners not returns false or no listeners at all.
     *
     * @param string $eventName
     * @param mixed  ...$arguments
     *
     * @return array
     */
    public function dispatch(string $eventName, ...$arguments): array
    {
        $result = array();
        $listener = $this->get('EVENTS.'.$eventName);

        if ($this->get('EVENTS_ONCE.'.$eventName)) {
            $this->off($eventName);
        }

        if ($listener) {
            $result[] = $this->call($listener, ...$arguments);
        }

        return $result;
    }

    /**
     * Sets event dispatch only once.
     *
     * @param string $eventName
     * @param mixed  ...$arguments
     *
     * @return array
     */
    public function dispatchOnce(string $eventName, ...$arguments): array
    {
        $this->set('EVENTS_ONCE.'.$eventName, true);

        return $this->dispatch($eventName, ...$arguments);
    }

    /**
     * Returns true if session active.
     *
     * @return bool
     */
    public function sessionActive(): bool
    {
        return PHP_SESSION_ACTIVE === session_status();
    }

    /**
     * Returns headers name (case insensitive).
     *
     * @param string $name
     *
     * @return string|null
     */
    public function hkey(string $name): ?string
    {
        if (isset($this->hive['RESPONSE'][$name])) {
            return $name;
        }

        if (
            $this->hive['RESPONSE'] &&
            $found = preg_grep(
                '/^'.preg_quote($name, '/').'$/i',
                array_keys($this->hive['RESPONSE'])
            )
        ) {
            return $found[0];
        }

        return null;
    }

    /**
     * Returns true if headers exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hhas(string $name): bool
    {
        return null !== $this->hkey($name);
    }

    /**
     * Returns headers.
     *
     * @param string $name
     *
     * @return array
     */
    public function hget(string $name): array
    {
        if ($key = $this->hkey($name)) {
            return (array) $this->hive['RESPONSE'][$key];
        }

        return array();
    }

    /**
     * Add header.
     *
     * @param string       $name
     * @param string|array $values
     *
     * @return Fw
     */
    public function hadd(string $name, $values): Fw
    {
        $key = $this->hkey($name) ?? $name;
        $update = $this->hget($key);

        foreach (is_array($values) ? $values : array($values) as $value) {
            if (null !== $value && '' !== $value) {
                $update[] = $value;
            }
        }

        $this->hive['RESPONSE'][$key] = $update;

        return $this;
    }

    /**
     * Replace header.
     *
     * @param string       $name
     * @param string|array $values
     *
     * @return Fw
     */
    public function hset(string $name, $values): Fw
    {
        $this->hive['RESPONSE'][$this->hkey($name) ?? $name] = (array) $values;

        return $this;
    }

    /**
     * Remove header.
     *
     * @param string $name
     *
     * @return Fw
     */
    public function hrem(string $name): Fw
    {
        unset($this->hive['RESPONSE'][$this->hkey($name) ?? $name]);

        return $this;
    }

    /**
     * Send cookie header.
     *
     * @param string      $name
     * @param string|null $value
     * @param array|null  $options
     * @param string|null &$cookie
     *
     * @return Fw
     */
    public function cookie(
        string $name,
        string $value = null,
        array $options = null,
        string &$cookie = null
    ): Fw {
        if (empty($name)) {
            throw new \LogicException('Cookie name empty!');
        }

        $jar = (array) $options + $this->hive['JAR'];
        $cookie = $name.'=';

        if (null === $value || '' === $value) {
            $cookie .= 'deleted; Expires=';
            $cookie .= gmdate(self::COOKIE_DATE, time() - 31536001);
            $cookie .= '; Max-Age=0';
        } else {
            $cookie .= $jar['raw'] ? $value : rawurlencode($value);
            $expires = $jar['expires'];

            // convert expiration time to a Unix timestamp
            if ($expires instanceof \DateTimeInterface) {
                $expires = $expires->format('U') + 0;
            } elseif (!is_numeric($expires)) {
                $time = strtotime($expires);

                if (false === $time) {
                    throw new \InvalidArgumentException(sprintf(
                        'Cookie expiration time is not valid: %s.',
                        $expires
                    ));
                }

                $expires = $time;
            } elseif (0 > $expires) {
                $expires = 0;
            }

            if (0 !== $expires) {
                if (0 > $maxAge = $expires - time()) {
                    $maxAge = 0;
                }

                $cookie .= '; Expires='.gmdate(self::COOKIE_DATE, $expires);
                $cookie .= '; Max-Age='.$maxAge;
            }
        }

        if ($jar['path']) {
            $cookie .= '; Path='.$jar['path'];
        }

        if ($jar['domain']) {
            $cookie .= '; Domain='.$jar['domain'];
        }

        if ($jar['secure']) {
            $cookie .= '; Secure';
        }

        if ($jar['httponly']) {
            $cookie .= '; Httponly';
        }

        if (null !== $jar['samesite']) {
            if (
                self::COOKIE_LAX !== $jar['samesite'] &&
                self::COOKIE_STRICT !== $jar['samesite']
            ) {
                throw new \InvalidArgumentException(sprintf(
                    'Samesite parameter value is not valid: %s.',
                    $jar['samesite']
                ));
            }

            $cookie .= '; Samesite='.$jar['samesite'];
        }

        $this->hadd('Set-Cookie', $cookie);
        $this->hive['COOKIE'][$name] = $value;

        return $this;
    }

    /**
     * Modify or retrieve cookies.
     *
     * @param array|null $cookies
     *
     * @return Fw|array|null
     */
    public function cookies(array $cookies = null)
    {
        if (null === $cookies) {
            return $this->hive['COOKIE'];
        }

        foreach ($cookies + (array) $this->hive['COOKIE'] as $key => $value) {
            $this->cookie($key, isset($cookies[$key]) ? $value : null);
        }

        $this->hive['COOKIE'] = $cookies;

        return $this;
    }

    /**
     * Send HTTP status header.
     *
     * @param int $code
     *
     * @return Fw
     */
    public function status(int $code): Fw
    {
        $name = 'self::HTTP_'.$code;

        if (!defined($name)) {
            throw new \LogicException(sprintf(
                'Unsupported HTTP code: %d.',
                $code
            ));
        }

        $this->hive['STATUS'] = $code;
        $this->hive['TEXT'] = constant($name);

        return $this;
    }

    /**
     * Send expire headers.
     *
     * @param int $seconds
     *
     * @return Fw
     */
    public function expire(int $seconds = 0): Fw
    {
        if ($this->hive['PACKAGE']) {
            $this->hset('X-Powered-By', $this->hive['PACKAGE']);
        }

        if ($this->hive['XFRAME']) {
            $this->hset('X-Frame-Options', $this->hive['XFRAME']);
        }

        $this->hset('X-XSS-Protection', '1; mode=block');
        $this->hset('X-Content-Type-Options', 'nosniff');

        if ('GET' == $this->hive['VERB'] && $seconds) {
            $time = time();

            $this->hrem('Pragma');
            $this->hset('Cache-Control', 'max-age='.$seconds);
            $this->hset('Expires', gmdate('r', $time + $seconds));
            $this->hset('Last-Modified', gmdate('r'));
        } else {
            $this->hset('Pragma', 'no-cache');
            $this->hset('Cache-Control', 'no-cache, no-store, must-revalidate');
            $this->hset('Expires', gmdate('r', 0));
        }

        return $this;
    }

    /**
     * Stringify trace.
     *
     * @param array $trace
     *
     * @return string
     */
    public function trace(array $trace = null): string
    {
        if (null === $trace) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        }

        $out = '';
        $eol = "\n";
        $noArgument = 1 >= $this->hive['DEBUG'];
        $clear = self::split($this->hive['TRACE_CLEAR']);

        // Analyze stack trace
        foreach ($trace as $frame) {
            if (isset($frame['file'])) {
                $out .= '['.str_replace($clear, '', $frame['file']).':'.
                    $frame['line'].'] ';

                if (isset($frame['class'])) {
                    $out .= $frame['class'].$frame['type'];
                }

                if (isset($frame['function'])) {
                    $arg = $noArgument ||
                        empty($frame['args']) ? '' : self::csv($frame['args']);
                    $out .= $frame['function'].'('.$arg.')';
                }

                $out .= $eol;
            }
        }

        return $out;
    }

    /**
     * Log message.
     *
     * @param string $level
     * @param string $message
     *
     * @return Fw
     */
    public function log(string $level, string $message): Fw
    {
        // level less than threshold
        if (
            $message &&
            $this->hive['LOG'] &&
            (self::LOG_LEVELS[$level] ?? 9) <= (self::LOG_LEVELS[$this->hive['LOG_THRESHOLD']] ?? 8)
        ) {
            $prefix = $this->hive['LOG'].'log_';
            $suffix = '.log';
            $files = glob($prefix.date('Y-m').'*'.$suffix);

            $file = $files[0] ?? $prefix.date('Y-m-d').$suffix;
            $content = date('r').' ['.$this->hive['IP'].'] '.$level.' '.$message.PHP_EOL;

            self::mkdir($this->hive['LOG']);
            self::write($file, $content, true);
        }

        return $this;
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
     * Returns log level by http code.
     *
     * @param int $code
     *
     * @return string
     */
    public function logLevelHttpCode(int $code): string
    {
        return $this->hive['LOG_CONVERT'][$code] ?? 'error';
    }

    /**
     * Returns translated message.
     *
     * @param string     $message
     * @param array|null $parameters
     * @param bool       $titleize
     *
     * @return string
     */
    public function trans(
        string $message,
        array $parameters = null,
        bool $titleize = false
    ): string {
        if (null === $ref = $this->langRef($message)) {
            return $titleize ? self::titleCase($message) : $message;
        }

        return $parameters ? strtr($ref, $parameters) : $ref;
    }

    /**
     * Returns translated choice message.
     *
     * @param string     $message
     * @param int        $count
     * @param array|null $parameters
     *
     * @return string
     */
    public function choice(string $message, int $count, array $parameters = null): string
    {
        $parameters['#'] = $count;

        if (null === $ref = $this->langRef($message)) {
            $ref = $message;
        }

        foreach (explode('|', $ref) as $key => $choice) {
            if ($count <= $key) {
                return strtr($choice, $parameters);
            }
        }

        return strtr($choice, $parameters);
    }

    /**
     * Translate with alternatives.
     *
     * @param array      $messages
     * @param array|null $parameters
     *
     * @return string
     */
    public function transAlt(array $messages, array $parameters = null): string
    {
        $message = null;

        foreach ($messages as $message) {
            if (null !== $ref = $this->langRef($message)) {
                return $parameters ? strtr($ref, $parameters) : $ref;
            }
        }

        return $message;
    }

    /**
     * Returns true if ip blacklisted.
     *
     * @param string $ip
     *
     * @return bool
     */
    public function blacklisted(string $ip): bool
    {
        if ($this->hive['WHITELIST'] && self::ipValid($ip, $this->hive['WHITELIST'])) {
            return false;
        }

        return $this->hive['BLACKLIST'] && self::ipValid($ip, $this->hive['BLACKLIST']);
    }

    /**
     * Send headers.
     *
     * @return Fw
     */
    public function sendHeaders(): Fw
    {
        if (false === headers_sent()) {
            // response headers
            foreach ($this->hive['RESPONSE'] ?? array() as $name => $values) {
                $replace = 0 === strcasecmp($name, 'Content-Type');

                foreach ($values as $value) {
                    header($name.': '.$value, $replace, $this->hive['STATUS']);
                }
            }

            // status
            header(
                $this->hive['PROTOCOL'].' '.$this->hive['STATUS'].' '.$this->hive['TEXT'],
                true,
                $this->hive['STATUS']
            );
        }

        return $this;
    }

    /**
     * Send content.
     *
     * @return Fw
     */
    public function sendContent(): Fw
    {
        if (
            !$this->hive['QUIET'] &&
            $this->hive['OUTPUT'] &&
            is_string($this->hive['OUTPUT'])
        ) {
            echo $this->hive['OUTPUT'];
        }

        return $this;
    }

    /**
     * Send headers and content.
     *
     * @return Fw
     */
    public function send(): Fw
    {
        $this->sendHeaders();
        $this->sendContent();

        return $this;
    }

    /**
     * Send error response.
     *
     * @param int        $code
     * @param string     $message
     * @param array|null $trace
     *
     * @return Fw
     */
    public function error(int $code, string $message = null, array $trace = null): Fw
    {
        if (null === $trace) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        }

        $this->expire(-1);
        $this->status($code);

        $debug = $this->trace($trace);
        $status = $this->hive['TEXT'];
        $error = 'HTTP '.$code.' ('.$this->hive['VERB'].' '.$this->hive['PATH'].')';
        $text = $message ?: $error;
        $eol = "\n";

        if ($this->hive['WITH_PRIOR_ERROR'] && $prior = $this->hive['ERROR']) {
            $debug .= '[*previous error*:0] '.$prior['text'];
            $debug .= ' ('.$prior['code'].' '.$prior['status'].')'.$eol;
            $debug .= $prior['debug'];
        }

        $this->hive['ERROR'] = array(
            'code' => $code,
            'status' => $status,
            'text' => $text,
            'debug' => $debug,
            'trace' => $trace,
        );

        try {
            $this->log($this->logLevelHttpCode($code), $text.$eol.$debug);

            $dispatch = $this->dispatchOnce(self::EVENT_ERROR, $this, $message);

            // output has been set?
            if ($dispatch && false !== $dispatch[0] && $this->hive['OUTPUT']) {
                return $this;
            }
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }

        if ($this->hive['AJAX']) {
            $response = $this->hive['ERROR'];
            unset($response['trace']);

            if (!$this->hive['DEBUG']) {
                unset($response['debug']);
            }

            $this->hset('Content-Type', 'application/json');
            $this->hive['OUTPUT'] = json_encode($response, JSON_PRETTY_PRINT);
        } elseif ($this->hive['CLI']) {
            $this->hive['OUTPUT'] = $error.$eol.($message ?: $status).$eol;
            $this->hive['OUTPUT'] .= $this->hive['DEBUG'] ? $eol.$debug : '';
        } else {
            $debug = $this->hive['DEBUG'] ? '<pre>'.$debug.'</pre>' : '';

            $this->hset('Content-Type', 'text/html');
            $this->hive['OUTPUT'] = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="description" content="Stick error page">
  <meta name="author" content="Eko Kurniawan">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>$code $status</title>
</head>
<body>
  <h1>$status</h1>
  <p>$text</p>
  $debug
</body>
</html>
HTML;
        }

        return $this;
    }

    /**
     * Create http exception.
     *
     * @param int            $code
     * @param string|null    $message
     * @param Throwable|null $previous
     *
     * @return RuntimeException With message format that can be handled by framework as http exception
     */
    public function eHttp(
        int $code,
        string $message = null,
        \Throwable $previous = null
    ): \RuntimeException {
        return new \RuntimeException(sprintf(
            'http:%d %s',
            $code,
            $message
        ), 0, $previous);
    }

    /**
     * Create not found http exception.
     *
     * @param string|null    $message
     * @param Throwable|null $previous
     *
     * @return RuntimeException
     */
    public function eNotFound(
        string $message = null,
        \Throwable $previous = null
    ): \RuntimeException {
        return $this->eHttp(404, $message ?? self::HTTP_404, $previous);
    }

    /**
     * Create forbidden http exception.
     *
     * @param string|null    $message
     * @param Throwable|null $previous
     *
     * @return RuntimeException
     */
    public function eForbidden(
        string $message = null,
        \Throwable $previous = null
    ): \RuntimeException {
        return $this->eHttp(403, $message ?? self::HTTP_403, $previous);
    }

    /**
     * Wrap engine execution.
     *
     * @return Fw
     */
    public function run(): Fw
    {
        try {
            return $this->doRun();
        } catch (\Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Run and send output.
     *
     * @return Fw
     */
    public function runOut(): Fw
    {
        return $this->run()->send();
    }

    /**
     * Mock route.
     *
     * @param string     $route
     * @param array|null $arguments
     * @param array|null $server
     * @param mixed      $body
     *
     * @return Fw
     */
    public function mock(
        string $route,
        array $arguments = null,
        array $server = null,
        $body = null
    ): Fw {
        $mockPattern = '~^(\w+)\h+([^\h?]+)(\?[^\h]+)?(?:\h+(ajax|cli|sync))?$~';

        if (!preg_match($mockPattern, $route, $parts)) {
            throw new \LogicException(sprintf('Invalid mocking pattern: %s.', $route));
        }

        $verb = strtoupper($parts[1]);
        $target = $parts[2];
        $mode = $parts[4] ?? null;

        if (isset($this->hive['ALIASES'][$target])) {
            $path = $this->hive['ALIASES'][$target];
        } elseif (preg_match('/^(\w+)\(([^(]+)\)$/', $target, $match)) {
            $path = $this->alias($match[1], strtr($match[2], ',', '&'));
        } else {
            $path = urldecode($target);
        }

        $this->hive['VERB'] = $verb;
        $this->hive['PATH'] = $path;
        $this->hive['AJAX'] = 'ajax' === $mode;
        $this->hive['CLI'] = 'cli' === $mode;
        $this->hive['POST'] = null;
        $this->hive['GET'] = null;
        $this->hive['OUTPUT'] = null;
        $this->hive['RESPONSE'] = null;
        $this->hive['BODY'] = $body;
        ++$this->hive['MOCK_LEVEL'];

        empty($parts[3]) || parse_str(ltrim($parts[3], '?'), $this->hive['GET']);

        if (('GET' === $verb || 'HEAD' === $verb) && $arguments) {
            $this->hive['POST'] = null;
            $this->hive['GET'] = array_merge(
                $this->hive['GET'] ?? array(),
                $arguments
            );
        } elseif ('POST' === $verb) {
            $this->hive['POST'] = $arguments;
        } elseif (!$body && $arguments) {
            $this->hive['BODY'] = http_build_query($arguments);
        }

        if ($server) {
            $this->hive['SERVER'] = $server + (array) $this->hive['SERVER'];
        }

        return $this->status(200)->run();
    }

    /**
     * Reroute to route/url.
     *
     * @param string|array|null $target
     * @param bool              $permanent
     *
     * @return Fw
     */
    public function reroute($target = null, bool $permanent = false): Fw
    {
        if (!$target) {
            $url = $this->hive['PATH'];
        } elseif (is_array($target)) {
            list($alias, $parameters) = $target + array(1 => null);
            $url = $this->alias($alias, $parameters);
        } elseif (isset($this->hive['ALIASES'][$target])) {
            $url = $this->hive['ALIASES'][$target];
        } elseif (preg_match('/^(\w+)(?:\(([^)]+)\))?(?:\?(.*))?$/', $target, $match)) {
            $alias = $match[1];
            $parameters = isset($match[2]) ? strtr($match[2], ',', '&') : '';

            if (isset($match[3])) {
                $parameters .= '&'.$match[3];
            }

            $url = $this->alias($alias, $parameters);
        } else {
            $url = $target;
        }

        $dispatch = $this->dispatchOnce(self::EVENT_REROUTE, $this, $url, $permanent);

        if ($dispatch && false !== $dispatch[0]) {
            return $this;
        }

        if ($this->hive['CLI']) {
            return $this->mock('GET '.$url.' cli');
        }

        // check if need base
        if ('/' === $url[0] && (empty($url[1]) || '/' !== $url[1])) {
            $url = $this->path($url);
        }

        $this->status($permanent ? 301 : 302);
        $this->hset('Location', $url);

        return $this;
    }

    /**
     * Returns true if cache exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function chas(string $key): bool
    {
        $ckey = $this->hive['SEED'].'.'.$key;

        switch ($this->hive['CACHE_ENGINE']) {
            case 'apc':
                return apc_exists($ckey);
            case 'apcu':
                return apcu_exists($ckey);
            case 'fallback':
            case 'filesystem':
                $this->cget($key, $info);

                return null !== $info;
            case 'memcache':
            case 'memcached':
                return false !== $this->hive['CACHE_REF']->get($ckey);
            case 'redis':
                return (bool) $this->hive['CACHE_REF']->exists($ckey);
            default:
                return false;
        }
    }

    /**
     * Returns cache value.
     *
     * @param string     $key
     * @param array|null &$info
     *
     * @return mixed
     */
    public function cget(string $key, array &$info = null)
    {
        $ckey = $this->hive['SEED'].'.'.$key;

        switch ($this->hive['CACHE_ENGINE']) {
            case 'apc':
                $raw = apc_fetch($ckey);
                break;
            case 'apcu':
                $raw = apcu_fetch($ckey);
                break;
            case 'fallback':
            case 'filesystem':
                $raw = self::read($this->cacheFile($ckey));
                break;
            case 'memcache':
            case 'memcached':
            case 'redis':
                $raw = $this->hive['CACHE_REF']->get($ckey);
                break;
            default:
                $raw = '';
                break;
        }

        if ($raw && ($cache = (array) unserialize($raw)) && 3 === count($cache)) {
            list($value, $time, $ttl) = $cache;

            if (0 === $ttl || ($time + $ttl > microtime(true))) {
                $info = compact('time', 'ttl');

                return $value;
            }
        }

        return null;
    }

    /**
     * Set cache.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     *
     * @return bool
     */
    public function cset(string $key, $value, int $ttl = 0): bool
    {
        $ckey = $this->hive['SEED'].'.'.$key;
        $cache = serialize(array($value, microtime(true), $ttl));

        switch ($this->hive['CACHE_ENGINE']) {
            case 'apc':
                return apc_store($ckey, $cache, $ttl);
            case 'apcu':
                return apcu_store($ckey, $cache, $ttl);
            case 'fallback':
            case 'filesystem':
                self::mkdir($this->hive['CACHE_REF']);

                return false !== self::write($this->cacheFile($ckey), $cache);
            case 'memcache':
                return $this->hive['CACHE_REF']->set(
                    $ckey,
                    $cache,
                    MEMCACHE_COMPRESSED,
                    $ttl
                );
            case 'memcached':
                return $this->hive['CACHE_REF']->set($ckey, $cache, $ttl);
            case 'redis':
                return $this->hive['CACHE_REF']->set(
                    $ckey,
                    $cache,
                    array_filter(array('ex' => $ttl))
                );
            default:
                return false;
        }
    }

    /**
     * Remove hive key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function crem(string $key): bool
    {
        $ckey = $this->hive['SEED'].'.'.$key;

        switch ($this->hive['CACHE_ENGINE']) {
            case 'apc':
                return apc_delete($ckey);
            case 'apcu':
                return apcu_delete($ckey);
            case 'fallback':
            case 'filesystem':
                return self::delete($this->cacheFile($ckey));
            case 'memcache':
            case 'memcached':
                return $this->hive['CACHE_REF']->delete($ckey);
            case 'redis':
                return 0 < $this->hive['CACHE_REF']->del($ckey);
            default:
                return false;
        }
    }

    /**
     * Reset cache.
     *
     * @param string|null $suffix
     *
     * @return int
     */
    public function creset(string $suffix = null): int
    {
        $prefix = $this->hive['SEED'].'.';
        $pattern = '/^'.preg_quote($prefix, '/').'.+'.preg_quote($suffix ?? '', '/').'$/';
        $delete = null;
        $items = array();
        $success = true;
        $affected = 0;

        switch ($this->hive['CACHE_ENGINE']) {
            case 'apc':
            case 'apcu':
                $engine = $this->hive['CACHE_ENGINE'];
                $delete = $engine.'_delete';
                $infoCall = $engine.'_cache_info';
                $info = $infoCall('apcu' === $engine ? false : 'user');

                foreach ($info['cache_list'] ?? array() as $item) {
                    if (preg_match($pattern, $key = $item['info'] ?? $item['key'])) {
                        $affected += (int) $delete($key);
                    }
                }
                break;
            case 'fallback':
            case 'filesystem':
                $delete = 'unlink';
                $items = glob($this->hive['CACHE_REF'].$prefix.'*'.$suffix);
                break;
            case 'memcache':
            case 'memcached':
                // There is no support to get all keys in memcache class
                // and memcached::getallkeys() is not consistent.
                // Currently there is no way get all memcache keys except with a hack way,
                // so we simply not support reset on memcache.
                break;
            case 'redis':
                $delete = array($this->hive['CACHE_REF'], 'del');
                $items = $this->hive['CACHE_REF']->keys($prefix.'*'.$suffix);
                $success = 1;
                break;
        }

        if ($delete && $items) {
            foreach ($items as $item) {
                $affected += (int) ($success === $delete($item));
            }
        }

        return $affected;
    }

    /**
     * Returns arguments if route match else returns null.
     *
     * @param string      $path
     * @param string      $pattern
     * @param string|null $modifier
     *
     * @return array|null
     */
    public function routeMatch(string $path, string $pattern, string $modifier = null): ?array
    {
        $last = null;

        if (false !== strpos($pattern, '@')) {
            $pattern = preg_replace_callback(
                self::ROUTE_PARAMS,
                function ($match) use (&$last) {
                    $name = $match[1];
                    $matchAll = $match[3] ?? false;
                    $pattern = $match[4] ?? '[^/]+';

                    if ($matchAll) {
                        $pattern = '.+';
                        $last = $name;
                    }

                    return '(?<'.$name.'>'.$pattern.')';
                },
                $pattern
            );
        }

        if (preg_match('~^'.$pattern.'$~'.$modifier, $path, $match)) {
            $parameters = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);

            if ($last) {
                $parameters[$last] = explode('/', $parameters[$last]);
            }

            return $parameters;
        }

        return null;
    }

    /**
     * Find match route.
     *
     * @return array|null
     */
    private function findRoute(): ?array
    {
        $verb = $this->hive['VERB'];
        $path = $this->hive['PATH'];
        $cors = $this->hive['CORS'];
        $modifier = $this->hive['CASELESS'] ? 'i' : '';
        $handleCors = isset($this->hive['REQUEST']['Origin']) && $cors['origin'];
        $preflight = $handleCors &&
            isset($this->hive['REQUEST']['Access-Control-Request-Method']);
        $allowed = array();

        if ($this->hive['AJAX']) {
            $mode = 'ajax';
        } elseif ($this->hive['CLI']) {
            $mode = 'cli';
        } else {
            $mode = 'sync';
        }

        if ($handleCors) {
            $this->hset('Access-Control-Allow-Origin', $cors['origin']);
            $this->hset('Access-Control-Allow-Credentials', var_export($cors['credentials'], true));
        }

        foreach ($this->hive['ROUTES'] as $pattern => $routes) {
            if (null !== $params = $this->routeMatch($path, $pattern, $modifier)) {
                if (isset($routes[$mode]) || isset($routes[$mode = 'all'])) {
                    $route = $routes[$mode];

                    if (isset($route[$verb]) && !$preflight) {
                        if ($cors['expose']) {
                            $this->hset(
                                'Access-Control-Expose-Headers',
                                self::join($cors['expose'])
                            );
                        }

                        return $route[$verb] + array(3 => $pattern, $params);
                    }

                    $allowed = array_keys($route);
                    break;
                }

                return array(
                    function ($fw) { $fw->error(400); },
                    null,
                    null,
                    $pattern,
                    array(),
                );
            }
        }

        if ($allowed) {
            if ('OPTIONS' === $verb) {
                return array(function ($fw, $params) {
                    // Unhandled HTTP method
                    $this->hset('Allow', $params['allowed']);

                    if ($params['cors']) {
                        $noHeaders = empty($params['headers']);
                        $noMaxAge = 0 >= $params['ttl'];

                        $this->hset(
                            'Access-Control-Allow-Methods',
                            'OPTIONS,'.$params['allowed']
                        );

                        $noHeaders || $this->hset('Access-Control-Allow-Headers', self::join($params['headers']));
                        $noMaxAge || $this->hset('Access-Control-Max-Age', $params['ttl']);
                    }
                }, null, null, '/', $cors + array(
                    'cors' => $handleCors,
                    'allowed' => self::join($allowed),
                ));
            }

            return array(
                function ($fw) { $fw->error(405); },
                null,
                null,
                '/',
                array(),
            );
        }

        return null;
    }

    /**
     * Returns language message reference.
     *
     * @param string $message
     *
     * @return string|null
     */
    private function langRef(string $message): ?string
    {
        $ref = $this->ref('LEXICON.'.$message, false);

        if (null !== $ref && !is_string($ref)) {
            throw new \LogicException(sprintf(
                'The message reference is not a string: %s.',
                $message
            ));
        }

        return $ref;
    }

    /**
     * Load language lexicon.
     */
    private function langLoad()
    {
        if (!$this->hive['LOCALES']) {
            return;
        }

        $languages = $this->hive['FALLBACK'];
        $locales = array_unique(self::split($this->hive['LOCALES']));
        $codes = array();
        $lexicon = array();
        $pattern = '/(?<=^|\n)(?:'.
            '\[(?<prefix>.+?)\]|'.
            '(?<lval>[^\h\r\n;].*?)\h*=\h*'.
            '(?<rval>(?:\\\\\h*\r?\n|.+?)*)'.
        ')(?=\r?\n|$)/';
        $ext = '.ini';
        $eol = "\n";

        if ($this->hive['LANGUAGE']) {
            $languages .= ','.preg_replace(
                '/\h+|;q=[0-9.]+/',
                '',
                self::join($this->hive['LANGUAGE'])
            );
        }

        foreach (self::split($languages) as $lang) {
            if (preg_match('/^(\w{2})(?:-(\w{2}))?\b/i', $lang, $parts)) {
                if (isset($parts[2])) {
                    // Specific language
                    $codes[] = $parts[1].'-'.strtoupper($parts[2]);
                }

                // Generic language
                $codes[] = $parts[1];
            }
        }

        foreach (array_unique($codes) as $code) {
            foreach ($locales as $locale) {
                if (
                    preg_match_all(
                        $pattern,
                        self::read($locale.$code.$ext),
                        $matches,
                        PREG_SET_ORDER
                    )
                ) {
                    $prefix = '';

                    foreach ($matches as $match) {
                        if ($match['prefix']) {
                            $prefix = $match['prefix'].'.';

                            continue;
                        }

                        $ref = &$this->ref(
                            $prefix.$match['lval'],
                            true,
                            $found,
                            $lexicon
                        );
                        $ref = trim(preg_replace(
                            '/\\\\\h*\r?\n/',
                            $eol,
                            $match['rval']
                        ));
                    }
                }
            }
        }

        $this->hive['LEXICON'] = $lexicon;
    }

    /**
     * Normalize autoload namespaces.
     *
     * @param mixed $autoload
     *
     * @return array
     */
    private function autoNormalize($autoload): array
    {
        $fixed = array('Fal\\Stick\\' => array(__DIR__));

        if (is_array($autoload)) {
            foreach ($autoload as $namespace => $directories) {
                if ('\\' !== substr($namespace, -1)) {
                    throw new \LogicException(sprintf(
                        'Namespace should ends with backslash: %s.',
                        $namespace
                    ));
                }

                $fixed[$namespace] = $this->autoNormalizePaths($directories);
            }
        }

        return $fixed;
    }

    /**
     * Normalize autoload directories.
     *
     * @param mixed $directories
     *
     * @return array
     */
    private function autoNormalizePaths($directories): array
    {
        $fixed = array();

        foreach (self::split($directories) as $dir) {
            $fixed[] = rtrim($dir, '\\/');
        }

        return $fixed;
    }

    /**
     * Find file class with extension.
     *
     * @param string $class
     * @param string $ext
     *
     * @return string|null
     */
    private function findFileWithExtension(string $class, string $ext): ?string
    {
        // PSR-4 lookup
        $subPath = $class;
        $logicalPath = strtr($class, '\\', DIRECTORY_SEPARATOR);

        while (false !== $lastPos = strrpos($subPath, '\\')) {
            $subPath = substr($subPath, 0, $lastPos);
            $search = $subPath.'\\';

            if (isset($this->hive['AUTOLOAD'][$search])) {
                $pathEnd = DIRECTORY_SEPARATOR.substr($logicalPath, $lastPos + 1);

                foreach ($this->hive['AUTOLOAD'][$search] as $directory) {
                    if (is_file($file = $directory.$pathEnd.$ext)) {
                        return $file;
                    }
                }
            }
        }

        // PSR-4 fallback dirs
        foreach ($this->hive['AUTOLOAD_FALLBACK'] ?? array() as $directory) {
            if (is_file($file = $directory.DIRECTORY_SEPARATOR.$logicalPath.$ext)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Load cache reference.
     */
    private function cacheLoad(): void
    {
        if (!$this->hive['CACHE']) {
            return;
        }

        $parts = explode('=', $this->hive['CACHE']);
        $reference = $parts[1] ?? null;
        $this->hive['CACHE_ENGINE'] = $engine = strtolower($parts[0]);

        if (('apc' === $engine || 'apcu' === $engine) && extension_loaded($engine)) {
            // apc used
        } elseif ('redis' === $engine && $reference && extension_loaded($engine)) {
            try {
                $parts = explode(':', $reference);
                $this->hive['CACHE_REF'] = new \Redis();
                $this->hive['CACHE_REF']->connect($parts[0], intval($parts[1] ?? 6379));
                empty($parts[2]) || $this->hive['CACHE_REF']->select($parts[2]);
            } catch (\Throwable $e) {
                $this->cacheFallback(true);
            }
        } elseif (
            ('memcache' === $engine || 'memcached' === $engine) &&
            $reference &&
            extension_loaded($engine)
        ) {
            $this->hive['CACHE_REF'] = new $engine();

            // remember that we do not check server!
            foreach (self::split($reference) as $serverPort) {
                $parts = explode(':', $serverPort);
                $this->hive['CACHE_REF']->addServer(
                    $parts[0],
                    intval($parts[1] ?? 11211)
                );
            }
        } elseif ($engine && !$reference) {
            // leave it to fallback
            $this->cacheFallback(true);
        } else {
            $this->hive['CACHE_REF'] = $reference;
        }
    }

    /**
     * Cache file path.
     *
     * @param string $key
     *
     * @return string
     */
    private function cacheFile(string $key): string
    {
        return $this->hive['CACHE_REF'].str_replace(array('\\', '/'), '', $key);
    }

    /**
     * Cache fallback logic.
     *
     * @param bool $set
     */
    private function cacheFallback(bool $set = false): void
    {
        if ($set) {
            $this->hive['CACHE_ENGINE'] = 'fallback';
        }

        if ('fallback' === $this->hive['CACHE_ENGINE']) {
            $this->hive['CACHE_REF'] = $this->hive['TEMP'].'cache/';
        }
    }

    /**
     * Start engine.
     *
     * @return Fw
     */
    private function doRun(): Fw
    {
        $dispatch = $this->dispatch(self::EVENT_BOOT, $this);

        if ($dispatch && false === $dispatch[0]) {
            return $this;
        }

        if ($this->hive['IP'] && $this->blacklisted($this->hive['IP'])) {
            return $this->error(403);
        }

        if (empty($this->hive['ROUTES'])) {
            return $this->error(500, 'No routes defined.');
        }

        if (null === $route = $this->findRoute()) {
            return $this->error(404);
        }

        list($controller, $alias, $ttl, $pattern, $arguments) = $route;

        $this->hive['ALIAS'] = $alias;
        $this->hive['PATTERN'] = $pattern;
        $this->hive['PARAMS'] = $arguments;

        // trying to resolve browser and local cache
        if ($ttl && $this->isVerb('GET', 'HEAD')) {
            // Only GET and HEAD requests are cacheable
            $hash = self::hash($this->hive['VERB'].' '.$this->hive['URI'], '.url');
            $cache = $this->cget($hash, $info);
            $now = microtime(true);

            if ($cache) {
                $modified = $this->hive['REQUEST']['If-Modified-Since'] ?? null;

                if ($modified && strtotime($modified) + $ttl > $now) {
                    // not modified
                    return $this->status(304);
                }

                $this->expire(intval($info['time'] + $ttl - $now));

                // Retrieve from cache backend
                list($status, $this->hive['RESPONSE'], $this->hive['OUTPUT']) = $cache;

                return $this->status($status);
            }

            // Expire HTTP client-cached page
            $this->expire($ttl);
        } else {
            $this->expire(0);
        }

        // collect body
        ($this->hive['RAW'] || $this->hive['BODY']) ||
            $this->hive['BODY'] = file_get_contents('php://input');

        // before route
        $dispatch = $this->dispatch(
            self::EVENT_BEFOREROUTE,
            $this,
            $controller,
            $arguments
        );

        if ($dispatch && false === $dispatch[0]) {
            return $this;
        }

        // execute
        $response = $this->call($controller, $this, $arguments);

        if (is_callable($response)) {
            $response($this);
        } elseif (is_array($response)) {
            $this->hset('Content-Type', 'application/json');
            $this->hive['OUTPUT'] = json_encode($response);
        } elseif (is_string($response)) {
            $this->hive['OUTPUT'] = $response;
        }

        // after route
        $dispatch = $this->dispatch(
            self::EVENT_AFTERROUTE,
            $this,
            $controller,
            $arguments,
            $response
        );

        if ($dispatch && false === $dispatch[0]) {
            return $this;
        }

        if (
            $ttl &&
            isset($hash) &&
            $this->hive['OUTPUT'] &&
            is_string($this->hive['OUTPUT'])
        ) {
            $this->cset($hash, array(
                $this->hive['STATUS'],
                $this->hive['RESPONSE'],
                $this->hive['OUTPUT'],
            ), $ttl);
        }

        return $this;
    }

    /**
     * Handle thrown exception.
     *
     * @param Throwable $exception
     *
     * @return Fw
     */
    private function handleException(\Throwable $exception): Fw
    {
        $trace = $exception->getTrace();
        $message = $exception->getMessage();

        array_unshift($trace, array(
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'class' => get_class($exception),
            'type' => null,
        ));

        if (preg_match('/^http:(\d{3})(?:\s+(.+))?$/', trim($message), $match)) {
            $code = 0 + $match[1];
            $message = $match[2];
        } else {
            $code = 500;
        }

        return $this->error($code, $message, $trace);
    }
}

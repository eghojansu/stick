<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Dec 04, 2018 07:54
 */

declare(strict_types=1);

namespace Fal\Stick;

/**
 * Framework main class.
 *
 * It contains routing, logging, caching, service container,
 * event dispatcher and holds application environment.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class Core implements \ArrayAccess
{
    const PACKAGE = 'Stick-Framework';
    const VERSION = 'v0.1.0';

    const ROUTE_PARAMETER_REGEX = '~(?:@(\w+))(?:(\*$)|(?:\(([^\)]+)\)))?~';

    const EVENT_START = 'fw_start';
    const EVENT_SHUTDOWN = 'fw_shutdown';
    const EVENT_PREROUTE = 'fw_preroute';
    const EVENT_POSTROUTE = 'fw_postroute';
    const EVENT_CONTROLLER_ARGUMENTS = 'fw_controller_arguments';
    const EVENT_REROUTE = 'fw_reroute';
    const EVENT_ERROR = 'fw_error';

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

    const LOG_EMERGENCY = 'emergency';
    const LOG_ALERT = 'alert';
    const LOG_CRITICAL = 'critical';
    const LOG_ERROR = 'error';
    const LOG_WARNING = 'warning';
    const LOG_NOTICE = 'notice';
    const LOG_INFO = 'info';
    const LOG_DEBUG = 'debug';

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
     * @param array|null $get
     * @param array|null $post
     * @param array|null $cookie
     * @param array|null $server
     */
    public function __construct(array $get = null, array $post = null, array $cookie = null, array $server = null)
    {
        $time = microtime(true);
        $cli = 'cli' === PHP_SAPI;
        $entry = $this->fixslashes($server['SCRIPT_NAME'] ?? $_SERVER['SCRIPT_NAME']);
        $uri = $server['REQUEST_URI'] ?? '/';
        $host = $server['SERVER_NAME'] ?? gethostname();
        $base = dirname($entry);
        $front = '/'.basename($entry);
        $headers = null;

        if ($cli) {
            $base = '';
            $front = '';
        }

        foreach ($server ?? array() as $key => $val) {
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
        $cors = array(
            'headers' => '',
            'origin' => false,
            'credentials' => false,
            'expose' => false,
            'ttl' => 0,
        );

        $this->hive = $this->init = array(
            'AGENT' => $headers['X-Operamini-Phone-Ua'] ?? $headers['X-Skyfire-Phone'] ?? $headers['User-Agent'] ?? '',
            'AJAX' => 'XMLHttpRequest' === ($headers['X-Requested-With'] ?? null),
            'ALIAS' => null,
            'ASSET' => null,
            'ASSET_MAP' => null,
            'ASSET_VERSION' => null,
            'AUTOLOAD' => null,
            'AUTOLOAD_FALLBACK' => null,
            'BASE' => $base,
            'BASEURL' => $domain.$base,
            'BODY' => null,
            'CACHE' => null,
            'CACHE_ENGINE' => null,
            'CACHE_REFERENCE' => null,
            'CASELESS' => false,
            'CLI' => $cli,
            'CODE' => 200,
            'COOKIE' => null,
            'CORS' => $cors,
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
            'MEMCACHE_HACK' => true,
            'MIME' => null,
            'OUTPUT' => null,
            'PACKAGE' => self::PACKAGE,
            'PARAMETERS' => null,
            'PATH' => preg_replace('/^'.preg_quote($front, '/').'/', '', preg_replace('/^'.preg_quote($base, '/').'/', '', $url['path'])) ?: '/',
            'PATTERN' => null,
            'PORT' => $port,
            'POST' => $post,
            'PROTOCOL' => $server['SERVER_PROTOCOL'] ?? 'HTTP/1.0',
            'QUIET' => false,
            'RAW' => false,
            'REQUEST' => $headers,
            'RESPONSE' => null,
            'ROUTE_ALIASES' => null,
            'ROUTE_COUNTER' => 0,
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
            'THRESHOLD' => self::LOG_ERROR,
            'TIME' => $time,
            'URI' => $uri,
            'URL' => $domain.$uri,
            'VERB' => $server['REQUEST_METHOD'] ?? 'GET',
            'VERSION' => self::VERSION,
            'XFRAME' => 'SAMEORIGIN',
        );
    }

    /**
     * Create class instance.
     *
     * @param array|null $get
     * @param array|null $post
     * @param array|null $cookie
     * @param array|null $server
     *
     * @return Core
     */
    public static function create(array $get = null, array $post = null, array $cookie = null, array $server = null): Core
    {
        return new self($get, $post, $cookie, $server);
    }

    /**
     * Create class instance with globals environment.
     *
     * @return Core
     */
    public static function createFromGlobals(): Core
    {
        return new self($_GET, $_POST, $_COOKIE, $_SERVER);
    }

    /**
     * Returns camelCased text.
     *
     * @param string $text
     *
     * @return string
     */
    public function camelCase(string $text): string
    {
        return str_replace('_', '', lcfirst(ucwords(strtolower($text), '_')));
    }

    /**
     * Returns snake_cased text.
     *
     * @param string $text
     *
     * @return string
     */
    public function snakeCase(string $text): string
    {
        return strtolower(preg_replace('/(?!^)\p{Lu}/u', '_\0', $text));
    }

    /**
     * Returns class name without its namespace.
     *
     * @param string|object $class
     *
     * @return string
     */
    public function className($class): string
    {
        return ltrim(strrchr('\\'.(is_object($class) ? get_class($class) : $class), '\\'), '\\');
    }

    /**
     * Returns text with backslashes converted to slash.
     *
     * @param string $text
     *
     * @return string
     */
    public function fixSlashes(string $text): string
    {
        return str_replace('\\', '/', $text);
    }

    /**
     * Returns variable with type cast to native type.
     *
     * @param mixed $var
     *
     * @return mixed
     */
    public function cast($var)
    {
        if (is_numeric($var)) {
            return $var + 0;
        }

        if (is_string($var)) {
            $var = trim($var);

            if (preg_match('/^\w+$/i', $var) && defined($var)) {
                return constant($var);
            }
        }

        return $var;
    }

    /**
     * Returns true if text is a valid variable name.
     *
     * @param string $text
     *
     * @return bool
     */
    public function variableName(string $text): bool
    {
        return (bool) preg_match('/^[a-z_](\w+)?$/i', $text);
    }

    /**
     * Returns variable as array.
     *
     * @param mixed       $var
     * @param string|null $delimiter
     *
     * @return array
     */
    public function split($var, string $delimiter = null): array
    {
        if (is_array($var)) {
            return $var;
        }

        if (!$var) {
            return array();
        }

        $pattern = '/['.preg_quote($delimiter ?? ',;|', '/').']/';

        return array_map('trim', preg_split($pattern, $var, 0, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * Returns variable as string.
     *
     * @param mixed       $var
     * @param string|null $glue
     *
     * @return string
     */
    public function join($var, string $glue = null): string
    {
        return is_array($var) ? implode($glue ?? ',', $var) : (string) $var;
    }

    /**
     * Returns element value from collections.
     *
     * @param string|int $key
     * @param array|null $collections
     * @param mixed      $default
     * @param bool       $twoTier
     *
     * @return mixed
     */
    public function pick($key, array $collections = null, $default = null, bool $twoTier = false)
    {
        foreach ($twoTier ? $collections ?? array() : array($collections ?? array()) as $collection) {
            if ($collection && is_array($collection) && array_key_exists($key, $collection)) {
                return $collection[$key];
            }
        }

        return $default;
    }

    /**
     * Url encode, take care of string, array and scalar values.
     *
     * @param mixed  $var
     * @param string $glue
     *
     * @return string
     */
    public function urlEncode($var, string $glue = '/'): string
    {
        $result = '';

        foreach (is_array($var) ? $var : array($var) as $item) {
            if (is_string($item)) {
                $result .= $glue.urlencode($item);
            } elseif (is_array($item)) {
                $result .= $glue.$this->urlEncode($item);
            } else {
                $result .= $glue.$item;
            }
        }

        return ltrim($result, $glue);
    }

    /**
     * Native mkdir wrapper with directory check.
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
     * Native file reader with ability to normalize line feed and file check.
     *
     * @param string $file
     * @param bool   $normalizeLinefeed
     *
     * @return string
     */
    public function read(string $file, bool $normalizeLinefeed = false): string
    {
        $out = is_file($file) ? file_get_contents($file) : '';

        return $normalizeLinefeed ? preg_replace('/\r\n|\r/', "\n", $out) : $out;
    }

    /**
     * Native file writer.
     *
     * @param string $file
     * @param string $content
     * @param bool   $append
     *
     * @return int|false
     */
    public function write(string $file, string $content, bool $append = false)
    {
        return file_put_contents($file, $content, LOCK_EX | ((int) $append * FILE_APPEND));
    }

    /**
     * Native file remove with checking.
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
     * Returns variable reference from variables hive.
     *
     * @param string     $key
     * @param bool       $add
     * @param array|null &$var
     * @param bool|null  &$found
     *
     * @return mixed
     */
    public function &reference(string $key, bool $add = true, array &$var = null, bool &$found = null)
    {
        $self = null === $var;
        $parts = explode('.', $key);
        $null = null;

        if ('SESSION' === $parts[0] && $self && !headers_sent() && PHP_SESSION_ACTIVE !== session_status()) {
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
                $found = false;
            }

            if (is_array($var) && (($exists = array_key_exists($part, $var)) || $add)) {
                $var = &$var[$part];
                $found = $exists;
            } elseif (is_object($var) && (($exists = property_exists($var, $part)) || $add)) {
                if ($add && !$this->variableName($part)) {
                    throw new \LogicException(sprintf('Invalid property name: "%s".', $part));
                }

                $var = &$var->$part;
                $found = $exists;
            } else {
                $var = $null;
                $found = false;
                break;
            }
        }

        return $var;
    }

    /**
     * Remove from variables hive.
     *
     * @param string     $key
     * @param array|null &$var
     *
     * @return Core
     */
    public function deReference(string $key, array &$var = null): Core
    {
        $self = null == $var;
        $last = strrchr('.'.$key, '.');
        $remove = ltrim($last, '.');

        if ($self) {
            $var = &$this->hive;
        }

        if ($ref = strstr($key, $last, true)) {
            $var = &$this->reference($ref, true, $var);
        }

        if (is_array($var) || $var instanceof \ArrayAccess) {
            unset($var[$remove]);
        } elseif (is_object($var)) {
            unset($var->$remove);
        }

        if ('SESSION' === $key && $self && PHP_SESSION_ACTIVE === session_status()) {
            session_unset();
            session_destroy();
        }

        return $this;
    }

    /**
     * Returns true if hive member exists.
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists(string $key): bool
    {
        $this->reference($key, false, $hive, $found);

        return $found;
    }

    /**
     * Returns variable reference from hive.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function &get(string $key)
    {
        $reference = &$this->reference($key);

        return $reference;
    }

    /**
     * Sets variable to hive.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return Core
     */
    public function set(string $key, $value): Core
    {
        $maps = array(
            'CONFIGS' => 'config',
            'ROUTES' => 'route',
            'REDIRECTS' => 'redirect',
            'RESTS' => 'rest',
            'CONTROLLERS' => 'controller',
            'RULES' => 'rule',
            'EVENTS' => 'on',
            'SUBSCRIBERS' => 'subscribe',
        );
        $call = $maps[$key] ?? null;

        if ($call) {
            // intercept
            foreach ((array) $value as $arguments) {
                $this->$call(...((array) $arguments));
            }

            return $this;
        }

        $reference = &$this->reference($key);
        $reference = $value;

        switch ($key) {
            case 'CACHE':
                list($this->hive['CACHE_ENGINE'], $this->hive['CACHE_REFERENCE']) = $this->cacheLoad((string) $value);
                break;
            case 'FALLBACK':
            case 'LOCALES':
            case 'LANGUAGE':
                $this->hive['DICT'] = $this->languageLoad();
                break;
        }

        return $this;
    }

    /**
     * Remove variable hive.
     *
     * @param string $key
     *
     * @return Core
     */
    public function clear(string $key): Core
    {
        $init = $this->init;
        $reference = $this->reference($key, false, $init, $found);

        if ($found) {
            return $this->set($key, $reference);
        }

        return $this->deReference($key);
    }

    /**
     * Returns true if all checked keys exists.
     *
     * @param string|array $keys
     *
     * @return bool
     */
    public function allExists($keys): bool
    {
        foreach ($this->split($keys) as $key) {
            if (!$this->exists($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Pick variables from hive.
     *
     * @param string|array $keys
     * @param bool         $lowerize
     * @param array|null   $maps
     *
     * @return array
     */
    public function allGet($keys, bool $lowerize = false, array $maps = null): array
    {
        $pick = array();

        foreach ($this->split($keys) as $key) {
            $newKey = $maps[$key] ?? ($lowerize && is_string($key) ? strtolower($key) : $key);
            $pick[$newKey] = $this->get($key);
        }

        return $pick;
    }

    /**
     * Massive variables set.
     *
     * @param array       $values
     * @param string|null $prefix
     *
     * @return Core
     */
    public function allSet(array $values, string $prefix = null): Core
    {
        foreach ($values as $key => $val) {
            $this->set($prefix.$key, $val);
        }

        return $this;
    }

    /**
     * Remove variables from hive.
     *
     * @param string|array $keys
     * @param string|null  $prefix
     *
     * @return Core
     */
    public function allClear($keys, string $prefix = null): Core
    {
        foreach ($this->split($keys) as $key) {
            $this->clear($prefix.$key);
        }

        return $this;
    }

    /**
     * Copy hive member.
     *
     * @param string $source
     * @param string $destination
     *
     * @return Core
     */
    public function copy(string $source, string $destination): Core
    {
        return $this->set($destination, $this->get($source));
    }

    /**
     * Returns hive variable and remove.
     *
     * @param string $source
     *
     * @return mixed
     */
    public function cut(string $source)
    {
        $var = $this->get($source);
        $this->clear($source);

        return $var;
    }

    /**
     * Prepend variable.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return Core
     */
    public function prepend(string $key, $value): Core
    {
        $var = $this->get($key);

        if (is_array($var)) {
            array_unshift($var, $value);
        } else {
            $var = $value.$var;
        }

        return $this->set($key, $var);
    }

    /**
     * Append variable.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return Core
     */
    public function append(string $key, $value): Core
    {
        $var = $this->get($key);

        if (is_array($var)) {
            array_push($var, $value);
        } else {
            $var .= $value;
        }

        return $this->set($key, $var);
    }

    /**
     * Load configuration file.
     *
     * @param string $source
     *
     * @return Core
     */
    public function config(string $source): Core
    {
        return $this->allSet((array) (is_file($source) ? requireFile($source) : null));
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
        $cacheKey = $this->hive['SEED'].'.'.$key;

        switch ($this->hive['CACHE_ENGINE']) {
            case 'apc':
                return apc_exists($cacheKey);
            case 'apcu':
                return apcu_exists($cacheKey);
            case 'filesystem':
                return (bool) $this->cacheExtract($this->cacheFile($cacheKey));
            case 'memcache':
            case 'memcached':
                return (bool) $this->hive['CACHE_REFERENCE']->get($cacheKey);
            case 'redis':
                return $this->hive['CACHE_REFERENCE']->exists($cacheKey);
            default:
                return false;
        }
    }

    /**
     * Returns cache value.
     *
     * @param string     $key
     * @param bool|null  &$found
     * @param float|null &$time
     * @param int|null   &$ttl
     *
     * @return mixed
     */
    public function cacheGet(string $key, bool &$found = null, float &$time = null, int &$ttl = null)
    {
        $cacheKey = $this->hive['SEED'].'.'.$key;
        $raw = '';
        $value = null;
        $found = false;

        switch ($this->hive['CACHE_ENGINE']) {
            case 'apc':
                $raw = apc_fetch($cacheKey);
                break;
            case 'apcu':
                $raw = apcu_fetch($cacheKey);
                break;
            case 'filesystem':
                $raw = $this->cacheFile($cacheKey);
                break;
            case 'memcache':
            case 'memcached':
            case 'redis':
                $raw = $this->hive['CACHE_REFERENCE']->get($cacheKey);
                break;
        }

        if ($raw && $cache = $this->cacheExtract($raw)) {
            $found = true;
            list($value, $time, $ttl) = $cache;
        }

        return $value;
    }

    /**
     * Sets cache.
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $ttl
     *
     * @return bool
     */
    public function cacheSet(string $key, $value, int $ttl = 0): bool
    {
        $cacheKey = $this->hive['SEED'].'.'.$key;
        $cache = $this->cacheCompact($value, $ttl);

        switch ($this->hive['CACHE_ENGINE']) {
            case 'apc':
                return apc_store($cacheKey, $cache, $ttl);
            case 'apcu':
                return apcu_store($cacheKey, $cache, $ttl);
            case 'filesystem':
                return $this->cacheFile($cacheKey, $cache);
            case 'memcache':
                $this->cacheMemcacheHack($cacheKey);

                return $this->hive['CACHE_REFERENCE']->set($cacheKey, $cache, MEMCACHE_COMPRESSED, $ttl);
            case 'memcached':
                $this->cacheMemcacheHack($cacheKey);

                return $this->hive['CACHE_REFERENCE']->set($cacheKey, $cache, $ttl);
            case 'redis':
                return $this->hive['CACHE_REFERENCE']->set($cacheKey, $cache, array_filter(array('ex' => $ttl)));
            default:
                return false;
        }
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
        $cacheKey = $this->hive['SEED'].'.'.$key;

        switch ($this->hive['CACHE_ENGINE']) {
            case 'apc':
                return apc_delete($cacheKey);
            case 'apcu':
                return apcu_delete($cacheKey);
            case 'filesystem':
                return $this->cacheFile($cacheKey, null, true);
            case 'memcache':
            case 'memcached':
                $this->cacheMemcacheHack($cacheKey, true);

                return $this->hive['CACHE_REFERENCE']->delete($cacheKey);
            case 'redis':
                return (bool) $this->hive['CACHE_REFERENCE']->del($cacheKey);
            default:
                return false;
        }
    }

    /**
     * Reset cache.
     *
     * @param string $suffix
     *
     * @return int
     */
    public function cacheReset(string $suffix = ''): int
    {
        $prefix = $this->hive['SEED'].'.';
        $pattern = '/^'.preg_quote($prefix, '/').'.+'.preg_quote($suffix, '/').'$/';
        $call = null;
        $items = array();

        switch ($this->hive['CACHE_ENGINE']) {
            case 'apc':
                $call = 'apc_delete';
                $items = new \APCIterator('user', $pattern);
                break;
            case 'apcu':
                $call = 'apcu_delete';
                $items = new \APCUIterator($pattern);
                break;
            case 'filesystem':
                $call = 'unlink';
                $items = glob($this->hive['CACHE_REFERENCE'].$prefix.'*'.$suffix);
                break;
            case 'memcache':
                $call = array($this->hive['CACHE_REFERENCE'], 'delete');
                // cachedump support has been removed, so we only can use hack way
                $items = $this->cacheMemcacheHack(null, null, $items);
                break;
            case 'memcached':
                $call = array($this->hive['CACHE_REFERENCE'], 'delete');
                $items = preg_grep($pattern, $this->hive['CACHE_REFERENCE']->getAllKeys());
                $items = $this->cacheMemcacheHack(null, null, $items);
                break;
            case 'redis':
                $call = array($this->hive['CACHE_REFERENCE'], 'del');
                $items = $this->hive['CACHE_REFERENCE']->keys($prefix.'*'.$suffix);
                break;
        }

        $iterator = $items instanceof \Iterator;
        $affected = 0;

        foreach ($call ? $items : array() as $item) {
            $call($iterator ? $item['key'] : $item);
            ++$affected;
        }

        return $affected;
    }

    /**
     * Mark time.
     *
     * @param int|string $mark
     *
     * @return Core
     */
    public function mark($mark = null): Core
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
     * Returns diff between current time and mark time.
     *
     * @param int|string $mark
     * @param bool       $remove
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
                unset($this->hive['MARKS'][$ndx]);
            }
        }

        return microtime(true) - $time;
    }

    /**
     * Returns 64bit/base36 hash.
     *
     * @param string $text
     *
     * @return string
     */
    public function hash(string $text): string
    {
        return str_pad(base_convert(substr(sha1($text), -16), 16, 36), 11, '0', STR_PAD_LEFT);
    }

    /**
     * Returns true if ip blacklisted.
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
     * Register class loader.
     *
     * @param bool $prepend
     *
     * @return Core
     */
    public function registerClassLoader(bool $prepend = false): Core
    {
        spl_autoload_register(array($this, 'loadClass'), true, $prepend);

        return $this;
    }

    /**
     * Unregister class loader.
     *
     * @return Core
     */
    public function unregisterClassLoader(): Core
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
    public function findClass($class): ?string
    {
        if (($file = $this->cacheGet($key = $class.'.class', $found)) && $found) {
            return $file;
        }

        // @codeCoverageIgnoreStart
        if ((!$file = $this->findFileWithExtension($class, '.php')) && defined('HHVM_VERSION')) {
            $file = $this->findFileWithExtension($class, '.hh');
        }
        // @codeCoverageIgnoreEnd

        if ($file) {
            $this->cacheSet($key, $file);
        }

        return $file;
    }

    /**
     * Load class file.
     *
     * @param string $class
     *
     * @return true|null
     */
    public function loadClass($class)
    {
        if ($file = $this->findClass($class)) {
            includeFile($file);

            return true;
        }
    }

    /**
     * Override request method.
     *
     * @return Core
     */
    public function overrideRequestMethod(): Core
    {
        $verb = $this->hive['REQUEST']['X-Http-Method-Override'] ?? $this->hive['VERB'];

        if ('POST' === $verb && isset($this->hive['POST']['_method'])) {
            $verb = strtoupper($this->hive['POST']['_method']);
        }

        $this->hive['VERB'] = $verb;

        return $this;
    }

    /**
     * Emulate CLI Request.
     *
     * @return Core
     */
    public function emulateCliRequest(): Core
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
     * Add route.
     *
     * @param string $expression
     * @param mixed  $handler
     *
     * @return Core
     */
    public function route(string $expression, $handler): Core
    {
        $rule = '~^([\w+|]+)(?:\h+(\w+))?(?:\h+(/[^\h]*))?(?:\h+(ajax|cli|sync))?(?:\h+(\d+))?(?:\h+(\d+))?$~';

        preg_match($rule, trim($expression), $match, PREG_UNMATCHED_AS_NULL);

        if (count($match) < 3) {
            throw new \LogicException(sprintf('Invalid route expression: "%s".', $expression));
        }

        $alias = $match[2] ?? null;
        $route = $match[3] ?? null;
        $mode = $match[4] ?? 'all';
        $ttl = ($match[5] ?? 0) + 0;
        $kbps = ($match[6] ?? 0) + 0;

        if (!$route) {
            if (!isset($this->hive['ROUTE_ALIASES'][$alias])) {
                throw new \LogicException(sprintf('Route not exists: "%s".', $alias));
            }

            $route = $this->hive['ROUTE_ALIASES'][$alias];
        }

        foreach (array_filter(explode('|', strtoupper($match[1]))) as $verb) {
            $this->hive['ROUTES'][$route][$mode][$verb] = $this->hive['ROUTE_COUNTER'];
        }

        if ($alias) {
            $this->hive['ROUTE_ALIASES'][$alias] = $route;
        }

        $this->hive['ROUTE_HANDLERS'][$this->hive['ROUTE_COUNTER']++] = array($handler, $alias, $ttl, $kbps);

        return $this;
    }

    /**
     * Add controller routes.
     *
     * @param string $class
     * @param array  $routes
     *
     * @return Core
     */
    public function controller(string $class, array $routes): Core
    {
        foreach ($routes as $route => $method) {
            $this->route($route, $class.'->'.$method);
        }

        return $this;
    }

    /**
     * Add rest controller.
     *
     * @param string $expression
     * @param string $class
     *
     * @return Core
     */
    public function rest(string $expression, string $class): Core
    {
        $itemExpression = preg_replace_callback('~^(?:(\w+)\h+)?(/[^\h]*)~', function ($match) {
            return ($match[1] ? $match[1].'_item' : '').' '.$match[2].'/@item';
        }, $expression);

        return $this
            ->route('GET '.$expression, $class.'->all')
            ->route('POST '.$expression, $class.'->create')
            ->route('GET '.$itemExpression, $class.'->get')
            ->route('PUT '.$itemExpression, $class.'->put')
            ->route('DELETE '.$itemExpression, $class.'->delete')
        ;
    }

    /**
     * Redirect to target url.
     *
     * @param string $expression
     * @param mixed  $target
     * @param bool   $permanent
     *
     * @return Core
     */
    public function redirect(string $expression, $target, bool $permanent = true): Core
    {
        return $this->route($expression, function () use ($target, $permanent) {
            return $this->reroute($target, $permanent);
        });
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
        $text = $this->hive['ASSET_MAP'][$path] ?? $path;

        if ('dynamic' === $this->hive['ASSET']) {
            $text .= '?'.time();
        } elseif ('static' === $this->hive['ASSET']) {
            $text .= '?'.$this->hive['ASSET_VERSION'];
        }

        return $this->hive['BASEURL'].'/'.ltrim($text, '/');
    }

    /**
     * Generate alias path.
     *
     * @param string $alias
     * @param mixed  $parameters
     *
     * @return string
     */
    public function alias(string $alias, $parameters = null): string
    {
        if (isset($this->hive['ROUTE_ALIASES'][$alias])) {
            $pattern = $this->hive['ROUTE_ALIASES'][$alias];

            if (!$parameters && false === strpos($pattern, '@')) {
                return $pattern;
            }

            $mParameters = $parameters ?? array();

            if (is_string($parameters)) {
                parse_str($parameters, $mParameters);
            }

            return preg_replace_callback(self::ROUTE_PARAMETER_REGEX, function ($match) use ($alias, &$mParameters) {
                $name = $match[1];
                $all = $match[2] ?? null;
                $pattern = $match[3] ?? null;
                $replace = $mParameters[$name] ?? null;

                if ($all) {
                    $replace = $replace ?? $mParameters;
                    $mParameters = array();
                }

                if (!$replace) {
                    throw new \LogicException(sprintf('Route "%s", parameter "%s" should be provided.', $alias, $name));
                }

                if ($pattern && is_string($replace) && !preg_match('~^'.$pattern.'$~', $replace)) {
                    throw new \LogicException(sprintf('Route "%s", parameter "%s" is not valid, given: "%s".', $alias, $name, $replace));
                }

                unset($mParameters[$name]);

                return $this->urlEncode($replace);
            }, $pattern);
        }

        return '/'.ltrim($alias, '/');
    }

    /**
     * Generate path.
     *
     * @param string       $alias
     * @param string|array $parameters
     * @param string|array $query
     *
     * @return string
     */
    public function path(string $alias, $parameters = null, $query = null): string
    {
        $suffix = rtrim('?'.(is_array($query) ? http_build_query($query) : $query), '?');

        return $this->hive['BASE'].$this->hive['FRONT'].$this->alias($alias, $parameters).$suffix;
    }

    /**
     * Create class instance.
     *
     * @param string     $id
     * @param array|null $rule
     *
     * @return object
     */
    public function createInstance(string $id, array $rule = null)
    {
        $class = $rule['use'] ?? $rule['class'] ?? $id;
        $constructor = $rule['constructor'] ?? null;
        $boot = $rule['boot'] ?? null;
        $arguments = $rule['arguments'] ?? null;

        $ref = new \ReflectionClass($class);

        if (!$ref->isInstantiable()) {
            throw new \LogicException(sprintf('Unable to create instance for "%s". Please provide instantiable version of %s.', $id, $ref->name));
        }

        if (is_callable($constructor)) {
            $instance = $this->call($constructor);

            if (!$instance instanceof $ref->name) {
                throw new \LogicException(sprintf('Constructor of "%s" should return instance of %s.', $id, $ref->name));
            }
        } elseif ($ref->hasMethod('__construct')) {
            $instance = $ref->newInstanceArgs($this->resolveArguments($ref->getMethod('__construct'), $arguments));
        } else {
            $instance = $ref->newInstance();
        }

        if (is_callable($boot)) {
            $this->call($boot, array($instance, $this));
        }

        return $instance;
    }

    /**
     * Sets service rule.
     *
     * @param string $id
     * @param mixed  $rule
     *
     * @return Core
     */
    public function rule(string $id, $rule): Core
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

        $this->hive['SERVICE_RULES'][$id] += array('class' => $id, 'service' => false !== $rule);

        if ($this->hive['SERVICE_RULES'][$id]['class'] !== $id) {
            $this->hive['SERVICE_ALIASES'][$id] = $this->hive['SERVICE_RULES'][$id]['class'];
        }

        return $this;
    }

    /**
     * Returns service instance.
     *
     * @param string $id
     * @param bool   $useCreated
     *
     * @return object
     */
    public function service(string $id, bool $useCreated = true)
    {
        if (in_array($id, array('fw', self::class))) {
            return $this;
        }

        if (isset($this->hive['SERVICES'][$id]) && $useCreated) {
            return $this->hive['SERVICES'][$id];
        }

        $realId = $id;

        if ($this->hive['SERVICE_ALIASES'] && $useCreated && $sid = array_search($id, $this->hive['SERVICE_ALIASES'])) {
            if (isset($this->hive['SERVICES'][$sid])) {
                return $this->hive['SERVICES'][$sid];
            }

            $realId = $sid;
        }

        $rule = array_replace_recursive(array(
            'arguments' => null,
            'boot' => null,
            'class' => $id,
            'constructor' => null,
            'service' => false,
            'use' => null,
        ), $this->hive['SERVICE_RULES'][$realId] ?? array());
        $instance = $this->createInstance($id, $rule);

        if ($rule['service']) {
            $this->hive['SERVICES'][$realId] = $instance;
        }

        return $instance;
    }

    /**
     * Execute callback.
     *
     * @param callable   $callback
     * @param array|null $arguments
     *
     * @return mixed
     */
    public function call(callable $callback, array $arguments = null)
    {
        return $callback(...$this->resolveArguments($callback, $arguments));
    }

    /**
     * Execute callback and keep chain.
     *
     * @param callable $callback
     *
     * @return Core
     */
    public function execute(callable $callback): Core
    {
        $this->call($callback);

        return $this;
    }

    /**
     * Grab class::method expression.
     *
     * @param string $expression
     *
     * @return mixed
     */
    public function grab(string $expression)
    {
        if (2 === count($parts = explode('->', $expression))) {
            return array($this->service($parts[0]), $parts[1]);
        }

        if (2 === count($parts = explode('::', $expression))) {
            return $parts;
        }

        return $expression;
    }

    /**
     * Returns translated message.
     *
     * @param string      $message
     * @param array|null  $parameters
     * @param string|null $fallback
     * @param string      $alternatives
     *
     * @return string
     */
    public function trans(string $message, array $parameters = null, string $fallback = null, string ...$alternatives): string
    {
        $mMessage = $this->languageReference($message);

        foreach ($mMessage ? array() : $alternatives as $alternative) {
            if ($reference = $this->languageReference($alternative)) {
                $mMessage = $reference;
                break;
            }
        }

        return strtr($mMessage ?? $fallback ?? $message, $parameters ?? array());
    }

    /**
     * Returns translated choices.
     *
     * @param string      $message
     * @param int         $count
     * @param array|null  $parameters
     * @param string|null $fallback
     *
     * @return string
     */
    public function choice(string $message, int $count, array $parameters = null, string $fallback = null): string
    {
        $parameters['#'] = $count;
        $mMessage = $this->languageReference($message) ?? $fallback ?? $message;

        foreach (explode('|', $mMessage) as $key => $choice) {
            if ($count <= $key) {
                return strtr($choice, $parameters);
            }
        }

        return strtr($choice, $parameters);
    }

    /**
     * Log message by level.
     *
     * @param string $level
     * @param string $message
     *
     * @return Core
     */
    public function log(string $level, string $message): Core
    {
        $write = $this->hive['LOG'] && (self::LOG_LEVELS[$level] ?? 100) <= (self::LOG_LEVELS[$this->hive['THRESHOLD']] ?? 99);

        if ($write) {
            $prefix = $this->hive['LOG'].'log_';
            $suffix = '.log';
            $files = glob($prefix.date('Y-m').'*'.$suffix);

            $file = $files[0] ?? $prefix.date('Y-m-d').$suffix;
            $content = date('Y-m-d G:i:s.u').' '.$level.' '.$message.PHP_EOL;

            $this->mkdir(dirname($file));
            $this->write($file, $content, true);
        }

        return $this;
    }

    /**
     * Log message by error code.
     *
     * @param int    $code
     * @param string $message
     *
     * @return Core
     */
    public function logCode(int $code, string $message): Core
    {
        $map = array(
            E_ERROR => self::LOG_EMERGENCY,
            E_PARSE => self::LOG_EMERGENCY,
            E_CORE_ERROR => self::LOG_EMERGENCY,
            E_COMPILE_ERROR => self::LOG_EMERGENCY,
            E_WARNING => self::LOG_ALERT,
            E_CORE_WARNING => self::LOG_ALERT,
            E_STRICT => self::LOG_CRITICAL,
            E_USER_ERROR => self::LOG_ERROR,
            E_USER_WARNING => self::LOG_WARNING,
            E_NOTICE => self::LOG_NOTICE,
            E_COMPILE_WARNING => self::LOG_NOTICE,
            E_USER_NOTICE => self::LOG_NOTICE,
            E_RECOVERABLE_ERROR => self::LOG_INFO,
            E_DEPRECATED => self::LOG_INFO,
            E_USER_DEPRECATED => self::LOG_INFO,
        );

        return $this->log($map[$code] ?? self::LOG_DEBUG, $message);
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
     * Add event listener.
     *
     * @param string $event
     * @param mixed  $handler
     * @param bool   $once
     *
     * @return Core
     */
    public function on(string $event, $handler, bool $once = false): Core
    {
        $this->hive['EVENTS'][$event] = array($handler, $once);

        return $this;
    }

    /**
     * Add event listener once.
     *
     * @param string $event
     * @param mixed  $handler
     *
     * @return Core
     */
    public function one(string $event, $handler): Core
    {
        return $this->on($event, $handler, true);
    }

    /**
     * Remove listener.
     *
     * @param string $event
     *
     * @return Core
     */
    public function off(string $event): Core
    {
        unset($this->hive['EVENTS'][$event]);

        return $this;
    }

    /**
     * Register event subscriber.
     *
     * @param string|object $subscriber
     *
     * @return Core
     */
    public function subscribe($subscriber): Core
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
     * Dispatch event.
     *
     * @param string     $event
     * @param array|null $args
     * @param bool       $off
     *
     * @return mixed
     */
    public function dispatch(string $event, array $args = null, bool $off = false)
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
     * Sets response status.
     *
     * @param int $code
     *
     * @return Core
     */
    public function status(int $code): Core
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
     * Sets cache control headers.
     *
     * @param int $seconds
     *
     * @return Core
     */
    public function expire(int $seconds = 0): Core
    {
        $headers = &$this->hive['RESPONSE'];

        $headers['X-Powered-By'] = $this->hive['PACKAGE'];
        $headers['X-Frame-Options'] = $this->hive['XFRAME'];
        $headers['X-XSS-Protection'] = '1; mode=block';
        $headers['X-Content-Type-Options'] = 'nosniff';

        if ('GET' === $this->hive['VERB'] && $seconds) {
            $time = time();
            unset($headers['Pragma']);

            $headers['Cache-Control'] = 'max-age='.$seconds;
            $headers['Expires'] = gmdate('r', $time + $seconds);
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
     * Send response.
     *
     * @param int|null    $code
     * @param array|null  $headers
     * @param string|null $content
     * @param string|null $mime
     * @param int         $kbps
     *
     * @return Core
     */
    public function send(int $code = null, array $headers = null, string $content = null, string $mime = null, int $kbps = 0): Core
    {
        if ($this->hive['SENT']) {
            return $this;
        }

        $this->hive['SENT'] = true;

        if (null !== $code) {
            $this->status($code);
        }

        if (null !== $headers) {
            $this->hive['RESPONSE'] = $headers;
        }

        if (null !== $content) {
            $this->hive['OUTPUT'] = $content;
        }

        if (null !== $mime) {
            $this->hive['MIME'] = $mime;
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
     * Generate error response.
     *
     * @param int         $code
     * @param string|null $message
     * @param array|null  $trace
     * @param array|null  $headers
     * @param int|null    $level
     *
     * @return Core
     */
    public function error(int $code, string $message = null, array $trace = null, array $headers = null, int $level = null): Core
    {
        $this->status($code);

        if (!$trace) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_shift($trace);
        }

        $status = $this->hive['STATUS'];
        $text = $message ?: 'HTTP '.$code.' ('.$this->hive['VERB'].' '.$this->hive['PATH'].')';
        $traceText = $this->hive['DEBUG'] ? $this->trace($trace) : '';

        $prior = $this->hive['ERROR'];
        $this->hive['ERROR'] = array(
            'code' => $code,
            'status' => $status,
            'text' => $text,
            'trace' => $traceText,
        );

        if ($prior) {
            return $this;
        }

        $this->hive['RESPONSE'] = $headers;
        $this->expire(-1)->logCode($level ?? E_USER_ERROR, $text.PHP_EOL.$traceText);

        try {
            $response = $this->dispatch(self::EVENT_ERROR, array($message, $trace), true);
        } catch (\Throwable $e) {
            $response = true;
            $this->hive['ERROR'] = null;
            $this->handleError($e);
        }

        if ($response) {
            return $this->sendResponse($response);
        }

        if ($this->hive['AJAX']) {
            $this->hive['MIME'] = 'application/json';
            $this->hive['OUTPUT'] = json_encode(array_filter($this->hive['ERROR']));
        } elseif ($this->hive['CLI']) {
            $this->hive['OUTPUT'] = 'Status: '.$status.PHP_EOL.'Text  : '.$text.PHP_EOL.$traceText.PHP_EOL;
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
                  ($traceText ? '<pre>'.$traceText.'</pre>' : '').
                '</body>'.
                '</html>';
        }

        return $this->send();
    }

    /**
     * Register shutdown handler.
     *
     * @return Core
     *
     * @codeCoverageIgnore
     */
    public function registerShutdownHandler(): Core
    {
        register_shutdown_function(array($this, 'unload'), getcwd());

        return $this;
    }

    /**
     * Shutdown procedure.
     *
     * @param string $workingDirectory
     *
     * @codeCoverageIgnore
     */
    public function unload(string $workingDirectory)
    {
        chdir($workingDirectory);

        $error = error_get_last();
        $fatal = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR);

        if (!$error && PHP_SESSION_ACTIVE === session_status()) {
            session_commit();
        }

        $handled = $this->dispatch(self::EVENT_SHUTDOWN, array($error));

        if (!$handled && $error && in_array($error['type'], $fatal)) {
            $this->error(500, $error['message'], array($error));
            exit;
        }
    }

    /**
     * Start engine.
     *
     * @return Core
     */
    public function run(): Core
    {
        try {
            return $this->handleRequest();
        } catch (\Throwable $e) {
            return $this->handleError($e);
        }
    }

    /**
     * Mock request.
     *
     * @param string      $expression
     * @param array|null  $arguments
     * @param array|null  $server
     * @param string|null $body
     *
     * @return Core
     */
    public function mock(string $expression, array $arguments = null, array $server = null, string $body = null): Core
    {
        $mockPattern = '~^(\w+)\h+([^\h?]+)(\?[^\h]+)?(?:\h+(ajax|cli|sync))?$~';

        if (!preg_match($mockPattern, trim($expression), $match)) {
            throw new \LogicException(sprintf('Invalid mock expression: "%s".', $expression));
        }

        $verb = strtoupper($match[1]);
        $target = $match[2];
        $query = $match[3] ?? '';
        $mode = $match[4] ?? null;
        $path = $target;

        if (isset($this->hive['ROUTE_ALIASES'][$target])) {
            $path = $this->hive['ROUTE_ALIASES'][$target];
        } elseif (preg_match('/^(\w+)\(([^(]+)\)$/', $target, $match)) {
            parse_str(strtr($match[2], ',', '&'), $routeArguments);
            $path = $this->alias($match[1], $routeArguments);
        }

        $this->allClear('SENT,RESPONSE,OUTPUT,BODY');

        $this->hive['VERB'] = $verb;
        $this->hive['PATH'] = $path;
        $this->hive['URI'] = $this->hive['BASE'].$path.$query;
        $this->hive['AJAX'] = 'ajax' === $mode;
        $this->hive['CLI'] = 'cli' === $mode;
        $this->hive['POST'] = 'POST' === $verb ? $arguments : null;
        $this->hive['URL'] = $this->hive['BASEURL'].$this->hive['URI'];

        parse_str(ltrim($query, '?'), $this->hive['GET']);

        if (in_array($verb, array('GET', 'HEAD'))) {
            $this->hive['GET'] = array_merge($this->hive['GET'], $arguments ?? array());
        } else {
            $this->hive['BODY'] = $body ?: http_build_query($arguments ?? array());
        }

        $this->hive['SERVER'] = ($server ?? array()) + (array) $this->hive['SERVER'];

        return $this->run();
    }

    /**
     * Reroute to route/url.
     *
     * @param mixed $target
     * @param bool  $permanent
     *
     * @return Core
     */
    public function reroute($target = null, bool $permanent = false): Core
    {
        if (!$target) {
            $path = $this->hive['PATH'];
            $url = $this->hive['URL'];
        } elseif (is_array($target)) {
            $query = $target[2] ?? null;
            $suffix = rtrim('?'.(is_array($query) ? http_build_query($query) : $query), '?');
            $path = $this->alias(...$target).$suffix;
        } elseif (isset($this->hive['ROUTE_ALIASES'][$target])) {
            $path = $this->hive['ROUTE_ALIASES'][$target];
        } elseif (preg_match('/^(\w+)(?:\(([^(]+)\))?(\?*+)?$/', $target, $match)) {
            parse_str(strtr($match[2] ?? '', ',', '&'), $parameters);
            $path = $this->alias($match[1], $parameters).($match[3] ?? '');
        } else {
            $path = $target;
        }

        if (empty($url)) {
            $url = $path;

            if ('/' === $path[0] && (empty($path[1]) || '/' !== $path[1])) {
                $url = $this->hive['BASEURL'].$this->hive['FRONT'].$path;
            }
        }

        if ($this->dispatch(self::EVENT_REROUTE, array($url, $permanent))) {
            return $this;
        }

        if ($this->hive['CLI']) {
            return $this->mock('GET '.$path.' cli');
        }

        return $this->clear('OUTPUT')->send($permanent ? 301 : 302, array('Location' => $url));
    }

    /**
     * Engine core.
     *
     * @return Core
     */
    private function handleRequest(): Core
    {
        // @codeCoverageIgnoreStart
        if ($this->blacklisted($this->hive['IP'])) {
            return $this->error(403);
        }
        // @codeCoverageIgnoreEnd

        if ($response = $this->dispatch(self::EVENT_PREROUTE)) {
            return $this->sendResponse($response);
        }

        if (!$this->hive['ROUTES']) {
            return $this->error(500, 'No route defined.');
        }

        if (!$route = $this->findRoute()) {
            return $this->error(404);
        }

        list($handler, $alias, $ttl, $kbps, $pattern, $parameters, $headers) = $route;
        $hash = $this->hash($this->hive['VERB'].' '.$this->hive['PATH']).'.url';
        $checkCache = $ttl && in_array($this->hive['VERB'], array('GET', 'HEAD'));

        if ($checkCache) {
            if ($response = $this->getRequestCache($hash, $ttl, $kbps, $modified)) {
                if ($modified) {
                    $this->expire($modified);
                }

                return $this->send(...$response);
            }

            $this->expire($ttl);
        } else {
            $this->expire(0);
        }

        $this->hive['PARAMETERS'] = $parameters;
        $this->hive['PATTERN'] = $pattern;
        $this->hive['ALIAS'] = $alias;
        $this->hive['RESPONSE'] += $headers;

        if (!$this->hive['RAW'] && !$this->hive['BODY']) {
            $this->hive['BODY'] = file_get_contents('php://input');
        }

        if (is_string($handler)) {
            if ($this->handlerInvalid($handler)) {
                return $this->error(404);
            }

            $handler = $this->grab($handler);
        }

        if (!is_callable($handler)) {
            return $this->error(405);
        }

        $arguments = ((array) $this->dispatch(self::EVENT_CONTROLLER_ARGUMENTS, array($handler, $parameters))) ?: $parameters;
        $result = $this->call($handler, $arguments);

        if ($response = $this->dispatch(self::EVENT_POSTROUTE, array($result, $kbps))) {
            $this->sendResponse($response);
        } else {
            if (is_string($result)) {
                $this->hive['OUTPUT'] = $result;
            } elseif (is_callable($result)) {
                $result($this, $kbps);
            } elseif (is_array($result)) {
                $this->hive['OUTPUT'] = json_encode($result);
                $this->hive['MIME'] = 'application/json';
            }

            $this->send(null, null, null, null, $kbps);
        }

        if ($checkCache) {
            $this->setRequestCache($hash, $ttl);
        }

        return $this;
    }

    /**
     * Handle thrown error.
     *
     * @param Throwable $e
     *
     * @return Core
     */
    private function handleError(\Throwable $e): Core
    {
        $httpCode = 500;
        $errorCode = $e->getCode();
        $message = $e->getMessage();
        $trace = $e->getTrace();
        $headers = null;

        if ($e instanceof HttpException) {
            $httpCode = $errorCode;
            $errorCode = E_USER_ERROR;
            $headers = $e->getHeaders();
        }

        return $this->error($httpCode, $message, $trace, $headers, $errorCode);
    }

    /**
     * Find matched route.
     *
     * @return array|null
     */
    private function findRoute(): ?array
    {
        $path = urldecode($this->hive['PATH']);
        $modifier = $this->hive['CASELESS'] ? 'i' : '';
        $cors = isset($this->hive['REQUEST']['Origin']) && $this->hive['CORS']['origin'] ? $this->hive['CORS'] : null;
        $mode = $this->hive['AJAX'] ? 'ajax' : ($this->hive['CLI'] ? 'cli' : 'sync');
        $preflight = false;
        $headers = array();
        $allowed = array();

        if ($cors) {
            $preflight = isset($this->hive['REQUEST']['Access-Control-Request-Method']);
            $headers['Access-Control-Allow-Origin'] = $cors['origin'];
            $headers['Access-Control-Allow-Credentials'] = var_export($cors['credentials'], true);
        }

        foreach ($this->hive['ROUTES'] as $pattern => $routes) {
            if (null === ($parameters = $this->routeMatch($path, $pattern, $modifier))) {
                continue;
            }

            $route = $routes[$mode] ?? $routes['all'] ?? null;

            if (null === $route) {
                continue;
            }

            $handlerId = $route[$this->hive['VERB']] ?? null;

            if (null === $handlerId || $preflight) {
                $allowed = array_merge($allowed, array_keys($route));
                break;
            }

            if ($cors && $cors['expose']) {
                $headers['Access-Control-Expose-Headers'] = $this->join($cors['expose']);
            }

            return $this->hive['ROUTE_HANDLERS'][$handlerId] + array(4 => $pattern, $parameters, $headers);
        }

        if ($allowed) {
            $headers['Allow'] = $this->join(array_unique($allowed));

            if ($cors) {
                $headers['Access-Control-Allow-Methods'] = 'OPTIONS,'.$headers['Allow'];
                $headers['Access-Control-Allow-Headers'] = $cors['headers'] ? $this->join($cors['headers']) : null;
                $headers['Access-Control-Max-Age'] = $cors['ttl'] > 0 ? $cors['ttl'] : null;
            }

            if ('OPTIONS' === $this->hive['VERB']) {
                return array(function () {}, null, 0, 0, null, array(), $headers);
            }

            return array(
                function () use ($headers) {
                    return $this->error(405, null, null, $headers);
                },
                null,
                0,
                0,
                null,
                array(),
                array(),
            );
        }

        return null;
    }

    /**
     * Returns arguments if route match else returns null.
     *
     * @param string $path
     * @param string $pattern
     * @param string $modifier
     *
     * @return array|null
     */
    private function routeMatch(string $path, string $pattern, string $modifier): ?array
    {
        $lastParameter = null;

        if (false !== strpos($pattern, '@')) {
            $wild = preg_replace_callback(self::ROUTE_PARAMETER_REGEX, function ($match) use (&$lastParameter) {
                $name = $match[1];
                $all = $match[2] ?? null;
                $pattern = $match[3] ?? '[^/]+';

                if ($all) {
                    $pattern = '.+';
                    $lastParameter = $name;
                }

                return '(?<'.$name.'>'.$pattern.')';
            }, $pattern);
        }

        if (!preg_match('~^'.($wild ?? $pattern).'$~'.$modifier, $path, $match)) {
            return null;
        }

        $parameters = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);

        if ($lastParameter) {
            $parameters[$lastParameter] = explode('/', $parameters[$lastParameter]);
        }

        return $parameters;
    }

    /**
     * Returns true if class not exists.
     *
     * @param string $handler
     *
     * @return bool
     */
    private function handlerInvalid(string $handler): bool
    {
        return ((false !== ($pos = strpos($handler, '->'))) || false !== ($pos = strpos($handler, '::'))) && !class_exists(substr($handler, 0, $pos));
    }

    /**
     * Returns request cache.
     *
     * @param string   $key
     * @param int      $ttl
     * @param int      $kbps
     * @param int|null &$modified
     *
     * @return array|null
     */
    private function getRequestCache(string $key, int $ttl, int $kbps, int &$modified = null): ?array
    {
        if (!$cache = $this->cacheGet($key, $found, $time, $cacheTtl)) {
            return null;
        }

        $time = time();
        $expDate = $this->hive['REQUEST']['If-Modified-Since'] ?? 0;
        $notModified = $expDate && strtotime($expDate) + $ttl > $time;

        if ($notModified) {
            return array(304);
        }

        list($code, $headers, $response, $mime) = $cache;
        $modified = $cacheTtl + $ttl - $time;

        return array(
            $code,
            (array) $this->hive['RESPONSE'] + (array) $headers,
            $response,
            $mime,
            $kbps,
        );
    }

    /**
     * Sets request cache.
     *
     * @param string $key
     * @param int    $ttl
     */
    private function setRequestCache(string $key, int $ttl): void
    {
        if ($this->hive['OUTPUT'] && is_string($this->hive['OUTPUT'])) {
            $this->cacheSet($key, array(
                $this->hive['CODE'],
                $this->hive['RESPONSE'],
                $this->hive['OUTPUT'],
                $this->hive['MIME'],
            ), $ttl);
        }
    }

    /**
     * Send response.
     *
     * @param mixed $response
     *
     * @return Core
     */
    private function sendResponse($response): Core
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
        $headersNames = array_keys($headers);
        $cookies = $this->collectCookies();

        foreach ($cookies as $cookie) {
            setcookie(...$cookie);
        }

        foreach (array_filter((array) $headers, 'is_scalar') as $name => $value) {
            header($name.': '.$value);
        }

        if ($mime && (!$headers || !preg_grep('/^content-type$/i', $headersNames))) {
            header('Content-Type: '.$mime);
        }

        if (is_string($this->hive['OUTPUT']) && (!$headers || !preg_grep('/^content-length$/i', $headersNames))) {
            header('Content-Length: '.strlen($this->hive['OUTPUT']));
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
     * Returns cookies to send.
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
                $cookie = is_array($value) ? $value : array((string) $value);
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
     * Find file class with extension.
     *
     * @param string $class
     * @param string $extension
     *
     * @return string|null
     */
    private function findFileWithExtension(string $class, string $extension): ?string
    {
        // PSR-4 lookup
        $logicalPath = strtr($class, '\\', DIRECTORY_SEPARATOR).$extension;
        $autoload = (array) $this->hive['AUTOLOAD'] + array('Fal\\Stick\\' => __DIR__.'/');
        $subPath = $class;

        while (false !== $lastPos = strrpos($subPath, '\\')) {
            $subPath = substr($subPath, 0, $lastPos);
            $search = $subPath.'\\';

            if (isset($autoload[$search])) {
                $pathEnd = DIRECTORY_SEPARATOR.substr($logicalPath, $lastPos + 1);

                foreach ($this->split($autoload[$search]) as $dir) {
                    if (is_file($file = rtrim($dir, '/\\').$pathEnd)) {
                        return $file;
                    }
                }
            }
        }

        // PSR-4 fallback dirs
        foreach ($this->split($this->hive['AUTOLOAD_FALLBACK']) as $dir) {
            if (file_exists($file = rtrim($dir, '/\\').DIRECTORY_SEPARATOR.$logicalPath)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Resolve callback arguments.
     *
     * @param callable|ReflectionFunctionAbstract $callable
     * @param array|null                          $arguments
     *
     * @return array
     */
    private function resolveArguments($callable, array $arguments = null): array
    {
        if ($callable instanceof \ReflectionFunctionAbstract) {
            $method = $callable;
        } elseif (is_array($callable)) {
            $method = new \ReflectionMethod(reset($callable), next($callable));
        } else {
            $method = new \ReflectionFunction($callable);
        }

        $resolved = array();
        $mArguments = $arguments ?? array();

        foreach ($method->getParameters() as $parameter) {
            $value = null;

            if (null !== ($key = key($mArguments))) {
                if (is_string($key) && $key !== $parameter->name) {
                    $key = null;
                } else {
                    $value = $mArguments[$key];
                }
            }

            if ($class = $parameter->getClass()) {
                if ($value instanceof $class->name) {
                    $resolved[] = $value;
                } elseif (is_string($value) && is_object($object = $this->resolveArgument($value, true))) {
                    $resolved[] = $object;
                } else {
                    $resolved[] = $this->service($class->name);
                    $key = null;
                }
            } elseif (is_string($value)) {
                $resolved[] = $this->resolveArgument($value);
            } elseif ($parameter->isVariadic() || (null === $key && $parameter->isOptional())) {
                // do nothing
            } else {
                $resolved[] = $value;
            }

            if (null !== $key) {
                unset($mArguments[$key]);
            }
        }

        if ($mArguments) {
            array_push($resolved, ...array_values($mArguments));
        }

        return $resolved;
    }

    /**
     * Resolve argument.
     *
     * @param string $value
     * @param bool   $resolveClass
     *
     * @return mixed
     */
    private function resolveArgument(string $value, bool $resolveClass = false)
    {
        if ($resolveClass && class_exists($value)) {
            return $this->service($value);
        }

        if (preg_match('/^(.+)?%([.\w]+)%(.+)?$/', $value, $match)) {
            $var = $this->reference($match[2], false, $hive, $found);

            if ($found) {
                // it does exists in hive
                return ($match[1] ?? null).$var.($match[3] ?? null);
            }

            // it is a service alias
            return $this->service($match[2]);
        }

        return $value;
    }

    /**
     * Use memcache hack.
     *
     * @param string|null $key
     * @param bool|null   $delete
     * @param array|null  $keys
     *
     * @return mixed
     */
    private function cacheMemcacheHack(string $key = null, bool $delete = null, array $keys = null)
    {
        if (!$this->hive['MEMCACHE_HACK']) {
            return;
        }

        $keyName = 'keys_'.$this->hive['SEED'];
        $storedKeys = (array) $this->hive['CACHE_REFERENCE']->get($keyName);

        if (null !== $keys) {
            if ($storedKeys) {
                array_push($keys, ...array_keys($storedKeys));
            }

            $this->hive['CACHE_REFERENCE']->delete($keyName);

            return array_unique(array_filter($keys, 'is_string'));
        }

        if ($delete) {
            unset($storedKeys[$key]);
        } else {
            $storedKeys[$key] = true;
        }

        return $this->hive['CACHE_REFERENCE']->set($keyName, $storedKeys);
    }

    /**
     * Filesystem cache helper.
     *
     * @param string      $key
     * @param string|null $cache
     * @param bool        $delete
     *
     * @return mixed
     */
    private function cacheFile(string $key, string $cache = null, bool $delete = false)
    {
        $file = $this->hive['CACHE_REFERENCE'].str_replace(array('\\', '/'), '', $key);

        if ($delete) {
            return $this->delete($file);
        }

        if (null !== $cache) {
            $this->mkdir(dirname($file));

            return false !== $this->write($file, $cache);
        }

        return $this->read($file);
    }

    /**
     * Serialize cache.
     *
     * @param mixed $value
     * @param int   $ttl
     *
     * @return string
     */
    private function cacheCompact($value, int $ttl): string
    {
        return serialize(array($value, microtime(true), $ttl));
    }

    /**
     * Unserialize raw cache.
     *
     * @param string $text
     *
     * @return array
     */
    private function cacheExtract(string $text): array
    {
        if ($text && ($cache = (array) unserialize($text)) && 3 === count($cache)) {
            list($value, $time, $ttl) = $cache;

            if (0 === $ttl || ($time + $ttl > microtime(true))) {
                return $cache;
            }
        }

        return array();
    }

    /**
     * Load cache by dsn.
     *
     * @param string $dsn
     *
     * @return array
     */
    private function cacheLoad(string $dsn): array
    {
        if (!$dsn) {
            return array(null, null);
        }

        $parts = explode('=', $dsn) + array(1 => null);

        if (('apc' === $parts[0] && extension_loaded('apc')) ||
            ('apcu' === $parts[0] && extension_loaded('apcu')) ||
            ('filesystem' === $parts[0] && $parts[1])) {
            return $parts;
        }

        if ($parts[1] &&
            ('memcache' === $parts[0] && extension_loaded('memcache')) ||
            ('memcached' === $parts[0] && extension_loaded('memcached'))) {
            try {
                $engine = $parts[0];
                $reference = new $engine();

                foreach ($this->split($parts[1]) as $server) {
                    list($host, $port) = $this->split($server, ':') + array(1 => 11211);

                    $reference->addServer($host, $port + 0);
                    // a hack because addserver always returns true
                    $reference->get('foo');
                }

                return array($engine, $reference);
            } catch (\Throwable $e) {
                // leave it to fallback
            }
        }

        if ('redis' === $parts[0] && $parts[1] && extension_loaded('redis')) {
            try {
                $engine = $parts[0];
                $reference = new \Redis();

                list($host, $port, $db) = $this->split($parts[1], ':') + array(1 => 6379, null);

                $reference->connect($host, $port + 0, 2);

                if (isset($db)) {
                    $reference->select($db + 0);
                }

                return array($engine, $reference);
            } catch (\Throwable $e) {
                // leave it to fallback
            }
        }

        $auto = preg_grep('/^(apcu|apc)/i', get_loaded_extensions());

        return $auto ? array(reset($auto), null) : array('filesystem', $this->hive['TEMP'].'cache/');
    }

    /**
     * Returns language reference.
     *
     * @param string $message
     *
     * @return string|null
     */
    private function languageReference(string $message): ?string
    {
        $mMessage = $this->get('DICT.'.$message);

        if (null !== $mMessage && !is_string($mMessage)) {
            throw new \UnexpectedValueException('Message is not a string.');
        }

        return $mMessage;
    }

    /**
     * Returns compiled language codes.
     *
     * @return array
     */
    private function languageCodes(): array
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
     * Load languages.
     *
     * @return array
     */
    private function languageLoad(): array
    {
        $dict = array();

        foreach ($this->languageCodes() as $code) {
            foreach ($this->split($this->hive['LOCALES']) as $locale) {
                $file = $locale.$code.'.php';
                $dict = array_replace_recursive($dict, (array) requireFile($file));
            }
        }

        return $dict;
    }

    /**
     * Stringify trace.
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
     * Exists alias.
     *
     * @param mixed $key
     *
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->exists((string) $key);
    }

    /**
     * Get alias.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function &offsetGet($key)
    {
        $var = &$this->get((string) $key);

        return $var;
    }

    /**
     * Set alias.
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        $this->set((string) $key, $value);
    }

    /**
     * Clear alias.
     *
     * @param mixed $key
     */
    public function offsetUnset($key)
    {
        $this->clear((string) $key);
    }
}


/**
 * Include wrapper, ensure included file has no access to caller private scope.
 *
 * @param string $file
 *
 * @return mixed
 */
function includeFile(string $file)
{
    return include $file;
}

/**
 * Require wrapper, ensure included file has no access to caller private scope.
 *
 * @param string $file
 *
 * @return mixed
 */
function requireFile(string $file)
{
    return require $file;
}

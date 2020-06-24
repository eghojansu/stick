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

namespace Ekok\Stick;

/**
 * Main framework class.
 *
 * Holds server environment, routes, and locales.
 * It can be extended or add custom function or getter.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Fw implements \ArrayAccess
{
    const EVENT_BOOT = 'fw.boot';
    const EVENT_SHUTDOWN = 'fw.shutdown';
    const EVENT_PREROUTE = 'fw.preroute';
    const EVENT_POSTROUTE = 'fw.postroute';
    const EVENT_CONTROLLER = 'fw.controller';
    const EVENT_CONTROLLER_ARGUMENTS = 'fw.controller_arguments';
    const EVENT_ERROR = 'fw.error';
    const EVENT_REROUTE = 'fw.reroute';
    const EVENT_LOG = 'fw.log';

    const REQUEST_ALL = 0;
    const REQUEST_AJAX = 1;
    const REQUEST_SYNC = 2;

    const ROUTE_PARAMETER_PATTERN = '~(?:@(\w+)(?::(\w+)|:(?:\(([^/]+)\)))?(\*)?)~';

    const COOKIE_DATE_FORMAT = 'D, d M Y H:i:s';
    const COOKIE_SAMESITE_LAX = 'Lax';
    const COOKIE_SAMESITE_STRICT = 'Strict';
    const COOKIE_SAMESITE_NONE = 'None';

    const HTTP_100 = 'Continue';
    const HTTP_101 = 'Switching Protocols';
    const HTTP_103 = 'Early Hints';
    const HTTP_200 = 'OK';
    const HTTP_201 = 'Created';
    const HTTP_202 = 'Accepted';
    const HTTP_203 = 'Non-Authoritative Information';
    const HTTP_204 = 'No Content';
    const HTTP_205 = 'Reset Content';
    const HTTP_206 = 'Partial Content';
    const HTTP_300 = 'Multiple Choices';
    const HTTP_301 = 'Moved Permanently';
    const HTTP_302 = 'Found';
    const HTTP_303 = 'See Other';
    const HTTP_304 = 'Not Modified';
    const HTTP_307 = 'Temporary Redirect';
    const HTTP_308 = 'Permanent Redirect';
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
    const HTTP_413 = 'Payload Too Large';
    const HTTP_414 = 'URI Too Long';
    const HTTP_415 = 'Unsupported Media Type';
    const HTTP_416 = 'Range Not Satisfiable';
    const HTTP_417 = 'Expectation Failed';
    const HTTP_418 = 'I\'m a teapot';
    const HTTP_422 = 'Unprocessable Entity';
    const HTTP_425 = 'Too Early';
    const HTTP_426 = 'Upgrade Required';
    const HTTP_428 = 'Precondition Required';
    const HTTP_429 = 'Too Many Requests';
    const HTTP_431 = 'Request Header Fields Too Large';
    const HTTP_451 = 'Unavailable For Legal Reasons';
    const HTTP_500 = 'Internal Server Error';
    const HTTP_501 = 'Not Implemented';
    const HTTP_502 = 'Bad Gateway';
    const HTTP_503 = 'Service Unavailable';
    const HTTP_504 = 'Gateway Timeout';
    const HTTP_505 = 'HTTP Version Not Supported';
    const HTTP_506 = 'Variant Also Negotiates';
    const HTTP_507 = 'Insufficient Storage';
    const HTTP_508 = 'Loop Detected';
    const HTTP_510 = 'Not Extended';
    const HTTP_511 = 'Network Authentication Required';

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

    protected $context = array();

    /** @var array Initial environment */
    protected $init = array();

    /** @var array Current environment */
    protected $hive = array();

    public function __construct(array $query = null, array $data = null, array $cookie = null, array $files = null, array $server = null)
    {
        $time = microtime(true);
        $charset = ini_get('default_charset');
        $timezone = date_default_timezone_get();
        $script = $server['SCRIPT_NAME'] ?? '';
        $uri = strstr(($server['REQUEST_URI'] ?? '').'?', '?', true);
        $secure = ($server['HTTP_X_FORWARDED_PROTO'] ?? null) === 'https' || ($server['HTTPS'] ?? null) === 'on';
        $verb = $server['REQUEST_METHOD'] ?? 'GET';

        $scheme = $secure ? 'https' : 'http';
        $host = static::resolveServerHost($server);

        $port = intval($server['SERVER_PORT'] ?? 80);
        $base = rtrim(static::fixSlashes(dirname($script)), '/');
        $front = $script && 0 === strpos($uri, $script) ? basename($script) : null;
        $path = $server['PATH_INFO'] ?? (substr($uri, strlen($front ? $script : $base)) ?: '/');
        $protocol = $server['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
        $traceClear = static::fixSlashes($server['DOCUMENT_ROOT'] ?? '').'/';
        $cookieJar = array(
            'lifetime' => 0,
            'path' => $base ?: '/',
            'domain' => is_int(strpos($host, '.')) && !filter_var($host, FILTER_VALIDATE_IP) ? $host : '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => static::COOKIE_SAMESITE_LAX,
        );
        $languages = static::headerParseContent($server['HTTP_ACCEPT_LANGUAGE'] ?? '');
        $language = key($languages);
        $routeGroup = array(
            'alias' => null,
            'path' => null,
            'request' => 'all',
            'mime' => '*/*',
            'handler' => null,
            'extras' => null,
        );
        $logs = array(
            'append_context' => true,
            'date_format' => 'Y-m-d G:i:s.u',
            'directory' => './var/logs/',
            'extension' => 'txt',
            'filename' => null,
            'flush' => false,
            'level_default' => static::LOG_LEVEL_INFO,
            'level_threshold' => static::LOG_LEVEL_ERROR,
            'maps' => null,
            'prefix' => 'log_',
        );

        $this->hive = $this->init = array(
            'ALIAS' => null,
            'ALIASES' => null,
            'ASSET_ABSOLUTE' => false,
            'ASSET_MAP' => null,
            'ASSET_PREFIX' => null,
            'ASSET_VERSION' => null,
            'BASE' => $base,
            'BITMASK' => ENT_COMPAT,
            'BODY' => null,
            'BODY_RAW' => false,
            'CASELESS' => true,
            'CONTENT' => null,
            'CONTENT_SENT' => false,
            'COOKIE' => $cookie,
            'CREATOR' => null,
            'DEBUG' => 0,
            'DICT' => null,
            'ENCODING' => $charset,
            'ERROR' => null,
            'EVENT' => null,
            'EVENT_DISPATCH_RESULT' => null,
            'EXTRAS' => null,
            'FALLBACK' => 'en',
            'FILES' => $files,
            'FRONT' => $front,
            'FUNCTION' => null,
            'GET' => $query,
            'GETTER' => $query,
            'HANDLE_RESULT' => true,
            'HANDLE_SHUTDOWN' => true,
            'HEADER' => null,
            'HEADER_SENT' => false,
            'HOST' => $host,
            'JAR' => $cookieJar,
            'LANGUAGE' => $language,
            'LANGUAGE_AUTOLOAD' => true,
            'LOCALES' => './dict/',
            'LOG' => $logs,
            'METHOD' => null,
            'MIME' => null,
            'PARAMS' => null,
            'PATH' => $path,
            'PATTERN' => null,
            'PORT' => $port,
            'POST' => $data,
            'PROTOCOL' => $protocol,
            'QUIET' => false,
            'RESULT' => null,
            'RESULT_SENT' => false,
            'ROUTE_GROUP' => $routeGroup,
            'ROUTES' => null,
            'SCHEME' => $scheme,
            'SECURE' => $secure,
            'SERVER' => $server,
            'SESSION' => null,
            'STATUS' => 200,
            'TEMP' => './var/',
            'TEXT' => static::HTTP_200,
            'TIME' => $time,
            'TRACE_CLEAR' => $traceClear,
            'TZ' => $timezone,
            'VERB' => $verb,
        );

        $this->sessionModifyCookie($cookieJar);
        unset($this->init['SESSION'], $this->init['COOKIE']);
        register_shutdown_function(array($this, 'handleShutdown'), getcwd());
    }

    public function __isset($key)
    {
        return $this->has($key);
    }

    public function __set($key, $value)
    {
        $this->set($key, $value);
    }

    public function __unset($key)
    {
        $this->rem($key);
    }

    /**
     * @param mixed $method
     * @param mixed $arguments
     *
     * @throws BadMethodCallException if no custom function found
     */
    public function __call($method, $arguments)
    {
        if ($callback = $this->ref('METHOD.'.$method, false)) {
            return $this->call($callback, $this, ...$arguments);
        }

        if ($callback = $this->ref('FUNCTION.'.$method, false)) {
            return $this->call($callback, ...$arguments);
        }

        throw new \BadMethodCallException("Call to unregistered method: '{$method}'.");
    }

    public function &__get($key)
    {
        $ref = &$this->get($key);

        return $ref;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * {@inheritdoc}
     */
    public function &offsetGet($key)
    {
        $ref = &$this->get($key);

        return $ref;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($key)
    {
        $this->rem($key);
    }

    /**
     * Create framework instance from global environment.
     */
    public static function createFromGlobals(): Fw
    {
        return new static($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
    }

    /**
     * Create framework instance.
     */
    public static function create(array $query = null, array $data = null, array $cookie = null, array $files = null, array $server = null): Fw
    {
        return new static($query, $data, $cookie, $files, $server);
    }

    /**
     * Convert cookie expire time to seconds.
     *
     * @param mixed $expires
     */
    public static function cookieExpiresTime($expires): int
    {
        // convert expiration time to a Unix timestamp
        if ($expires instanceof \DateTimeInterface) {
            return $expires->format('U') + 0;
        }

        if ($expires && !is_numeric($expires)) {
            $time = strtotime($expires);

            if (false === $time) {
                throw new \InvalidArgumentException("Invalid cookie expiration time: '{$expires}'.");
            }

            return $time;
        }

        if (0 > $expires) {
            return time() + $expires;
        }

        return $expires ?? 0;
    }

    /**
     * Create cookie header.
     */
    public static function cookieCreate(string $name, string $value = null, array $options = null): string
    {
        $lifetime = $options['lifetime'] ?? null;
        $path = $options['path'] ?? null;
        $domain = $options['domain'] ?? null;
        $secure = $options['secure'] ?? null;
        $httponly = $options['httponly'] ?? null;
        $samesite = $options['samesite'] ?? null;

        if (false === strpos($name, '.')) {
            $cookie = rawurlencode($name).'=';
        } else {
            $parts = array_map('rawurlencode', explode('.', $name));
            $cookie = array_shift($parts).'['.implode('][', $parts).']=';
        }

        if (null === $value || '' === $value) {
            $cookie .= 'deleted';
            $cookie .= '; Expires='.gmdate(static::COOKIE_DATE_FORMAT, 0).' GMT';
            $cookie .= '; Max-Age=0';
        } else {
            $cookie .= rawurlencode($value);
            $time = static::cookieExpiresTime($lifetime);

            if ($time) {
                $maxAge = $time - time();

                $cookie .= '; Expires='.gmdate(static::COOKIE_DATE_FORMAT, $time).' GMT';
                $cookie .= '; Max-Age='.max(0, $maxAge);
            }
        }

        if ($domain) {
            $cookie .= '; Domain='.$domain;
        }

        if ($path) {
            $cookie .= '; Path='.$path;
        }

        if ($secure) {
            $cookie .= '; Secure';
        }

        if ($httponly) {
            $cookie .= '; HttpOnly';
        }

        if ($samesite) {
            if (!defined($name = 'static::COOKIE_SAMESITE_'.strtoupper($samesite))) {
                throw new \LogicException("Invalid cookie samesite: '{$samesite}'.");
            }

            $cookie .= '; SameSite='.constant($name);
        }

        return $cookie;
    }

    /**
     * Parse header content into an array.
     */
    public static function headerParseContent(string $content, bool $sort = true): array
    {
        $result = array();

        foreach (explode(',', $content) as $accept) {
            if (!$accept) {
                continue;
            }

            $preferences = explode(';', $accept);
            $mime = trim(array_shift($preferences));

            $result[$mime] = array();

            foreach ($preferences as $preference) {
                list($key, $value) = explode('=', $preference);

                $result[$mime][trim($key)] = static::cast($value);
            }
        }

        if ($sort && $result) {
            uasort($result, static::class.'::headerQualitySort');
        }

        return $result;
    }

    /**
     * Sort header content after parsed.
     */
    public static function headerQualitySort(array $current, array $next): int
    {
        if (isset($current['level'])) {
            $first = $current['level'];
            $second = $next['level'] ?? 1;
        } else {
            $first = $current['q'] ?? 1;
            $second = $next['q'] ?? 1;
        }

        return $second <=> $first;
    }

    /**
     * Get server host.
     */
    public static function resolveServerHost(?array $server): string
    {
        $host = $server['SERVER_NAME'] ?? 'localhost';

        if ('0.0.0.0' === $host) {
            if (isset($server['SERVER_ADDR'])) {
                return $server['SERVER_ADDR'];
            }

            return strstr(($server['HTTP_HOST'] ?? gethostname()).':', ':', true);
        }

        return $host;
    }

    /**
     * Translate any backslashes into slashes.
     */
    public static function fixSlashes(string $text): string
    {
        return strtr($text, '\\', '/');
    }

    /**
     * Ensure that input is an array.
     *
     * @param mixed $input
     */
    public static function split($input, bool $noEmpty = true): array
    {
        if (is_array($input)) {
            return $input;
        }

        return array_map('trim', preg_split('/[,;|]/', (string) $input, 0, PREG_SPLIT_NO_EMPTY * (int) $noEmpty));
    }

    /**
     * Return value as php value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public static function cast($value)
    {
        if (is_string($value)) {
            $norm = trim($value);

            if (preg_match('/^(?:0x[0-9a-f]+|0[0-7]+|0b[01]+)$/i', $norm)) {
                return intval($norm, 0);
            }

            if (is_numeric($norm)) {
                return $norm + 0;
            }

            if (defined($norm)) {
                return constant($norm);
            }
        }

        return $norm ?? $value;
    }

    /**
     * Wrapper to var_export.
     *
     * @param mixed $input
     */
    public static function export($input): string
    {
        return var_export($input, true);
    }

    /**
     * Return input as string.
     *
     * @param mixed $input
     */
    public static function stringify($input, array $stack = null): string
    {
        foreach ($stack ?? array() as $node) {
            if ($input === $node) {
                return '*RECURSION*';
            }
        }

        $stringify = is_callable($call = static::class.'::stringify'.gettype($input)) ? $call : static::class.'::export';

        return $stringify($input, $stack);
    }

    /**
     * Return object as string.
     *
     * @param mixed $input
     */
    public static function stringifyObject($input, array $stack = null): string
    {
        $output = '';
        $newStack = array_merge($stack ?? array(), array($input));

        foreach (get_object_vars($input) as $key => $value) {
            $output .= ','.static::export($key).'=>'.static::stringify($value, $newStack);
        }

        return get_class($input).'::__set_state(array('.ltrim($output, ',').'))';
    }

    /**
     * Return array as string.
     */
    public static function stringifyArray(array $input, array $stack = null): string
    {
        $output = '';
        $assoc = !isset($input[0]) || !ctype_digit(implode('', array_keys($input)));
        $newStack = array_merge($stack ?? array(), array($input));

        foreach ($input as $key => $value) {
            $output .= ',';

            if ($assoc) {
                $output .= static::export($key).'=>';
            }

            $output .= static::stringify($value, $newStack);
        }

        return 'array('.ltrim($output, ',').')';
    }

    /**
     * Compact array as string.
     */
    public static function csv(array $arguments): string
    {
        $cb = static::class.'::stringify';

        return implode(',', array_map('stripcslashes', array_map($cb, $arguments)));
    }

    /**
     * Parse ini file content.
     */
    public static function iniParse(string $str): array
    {
        preg_match_all('/(?<=^|\n)(?:\[(?<section>.+?)\]|(?<lval>[^\h\r\n;].*?)\h*=\h*(?<rval>(?:\\\\\h*\r?\n|.+?)*))(?=\r?\n|$)/', trim($str), $matches, PREG_SET_ORDER);

        return $matches;
    }

    /**
     * Read and parse ini file.
     */
    public static function iniRead(string $file): array
    {
        return file_exists($file) ? static::iniParse(file_get_contents($file)) : array();
    }

    /**
     * Make variable reference with dot-notation style.
     *
     * @return mixed
     */
    public static function &makeRef(string $key, array &$var = null, bool $add = true, bool &$exists = null, array &$parts = null)
    {
        $parts = explode('.', $key);
        $exists = false;

        foreach ($parts as $part) {
            if (null === $var || is_scalar($var)) {
                $var = (array) $var;
            }

            $found = false;

            if (
                (is_array($var) || $var instanceof \ArrayAccess)
                && ($add || ($found = isset($var[$part]) || array_key_exists($part, $var)))
            ) {
                $exists = $found || isset($var[$part]) || array_key_exists($part, $var);
                $var = &$var[$part];
            } elseif (
                is_object($var)
                && ($add || ($found = isset($var->{$part}) || property_exists($var, $part)))
            ) {
                $exists = $found || isset($var->{$part}) || property_exists($var, $part);
                $var = &$var->{$part};
            } else {
                $var = null;
                $exists = $found;

                break;
            }
        }

        return $var;
    }

    /**
     * Wrapper to htmlspecialchars.
     */
    public function encode(string $text): string
    {
        return htmlspecialchars($text, $this->hive['BITMASK'], $this->hive['ENCODING']);
    }

    /**
     * Wrapper to htmlspecialchars_decode.
     */
    public function decode(string $text): string
    {
        return htmlspecialchars_decode($text, $this->hive['BITMASK']);
    }

    /**
     * Get message translation.
     *
     * @throws LogicException if translated message is not string
     *
     * @return mixed
     */
    public function transRaw(string $message, bool $stringOnly = true)
    {
        $translated = $this->ref('DICT.'.rtrim($message, '.'), false);

        if ($stringOnly && (null !== $translated && !is_string($translated))) {
            throw new \LogicException("Translated message is not a string: '{$message}'.");
        }

        return $translated;
    }

    /**
     * Return translated message.
     */
    public function trans(string $message, array $parameters = null): string
    {
        return strtr($this->transRaw($message) ?? $message, $parameters ?? array());
    }

    /**
     * Return choice message.
     */
    public function choice(string $message, int $count, array $parameters = null): string
    {
        $lines = $this->transRaw($message, false) ?? $message;
        $parameters['#'] = $count;

        if (!is_array($lines)) {
            $lines = explode('|', (string) $lines);
        }

        foreach ($lines as $key => $line) {
            if ($count <= $key) {
                return strtr(trim($line), $parameters);
            }
        }

        return strtr(trim($line), $parameters);
    }

    /**
     * Register configuration files.
     *
     * @param array|string $files
     */
    public function configAll($files): Fw
    {
        foreach (static::split($files) as $file => $parse) {
            if (is_numeric($file)) {
                $file = $parse;
                $parse = false;
            }

            $this->config($file, $parse);
        }

        return $this;
    }

    /**
     * Load configuration from file.
     */
    public function config(string $file, bool $parse = false): Fw
    {
        $section = null;
        $command = null;
        $modifier = null;
        $commandMaps = array(
            'routes' => 'route',
            'configs' => 'config',
            'redirects' => 'redirect',
            'globals' => 'set',
        );

        foreach (static::iniRead($file) as $match) {
            if ($match['section']) {
                $map = strtolower(strstr($match['section'].'.', '.', true));
                $command = $commandMaps[$map] ?? null;
                $section = null;
                $modifier = null;

                if (!$command) {
                    preg_match('/^(?<section>[^:]+)(?:\:(?<func>.+))?/', $match['section'], $parts);

                    $section = ltrim($parts['section'].'.', '.');
                    $modifier = $parts['func'] ?? null;
                }

                continue;
            }

            list('lval' => $key, 'rval' => $value) = $match;

            if ($parse) {
                $key = $this->configParse($key);
                $value = $this->configParse($value);
            }

            $value = $this->configValue($value, $modifier);
            $assign = $command ?? 'set';

            if ('set' === $assign) {
                $key = $section.$key;

                if (isset($value[1]) || array_key_exists(1, $value)) {
                    $value = array($value);
                }
            }

            $this->{$assign}($key, ...$value);
        }

        return $this;
    }

    /**
     * Get hive key reference.
     *
     * @return mixed
     */
    public function &ref(string $key, bool $add = true, bool &$exists = null, array &$parts = null)
    {
        if ('SESSION' === $key || 0 === strpos($key, 'SESSION.')) {
            $this->sessionStart();
        }

        if ($add) {
            $var = &$this->hive;
        } else {
            $var = $this->hive;
        }

        $var = &static::makeRef($key, $var, $add, $exists, $parts);

        return $var;
    }

    /**
     * Remove key reference from hive.
     */
    public function unref(string $key): Fw
    {
        if ('SESSION' === $key || 0 === strpos($key, 'SESSION.')) {
            $this->sessionStart();
        }

        if (isset($this->hive[$key]) || false === strpos($key, '.')) {
            unset($this->hive[$key]);

            return $this;
        }

        $last = strrpos($key, '.');
        $parent = substr($key, 0, $last);
        $child = substr($key, $last + 1);
        $var = &$this->ref($parent);

        unset($var[$child]);

        return $this;
    }

    /**
     * Return true if key exists in hive.
     */
    public function has(string $key): bool
    {
        return $this->ref($key, false, $exists, $parts) || $exists || $this->hasGetter($parts[0]);
    }

    /**
     * Return value of key in hive.
     *
     * @return mixed
     */
    public function &get(string $key)
    {
        $var = &$this->ref($key, true, $exists, $parts);

        if ($exists) {
            return $var;
        }

        if ($this->hasGetter($parts[0], $method)) {
            $var = $this->{$method}($key);
        } elseif ($getter = $this->ref('GETTER.'.$parts[0])) {
            if (isset($parts[1])) {
                $firstCreation = is_array($this->hive[$parts[0]]) && array_key_exists($parts[1], $this->hive[$parts[0]]);

                if ($firstCreation) {
                    unset($var);

                    $this->hive[$parts[0]] = $this->call($getter, $this);
                    $var = &$this->ref($key);
                }
            } else {
                $var = $this->call($getter, $this);
            }
        } elseif ($creator = $this->ref('CREATOR.'.$parts[0])) {
            unset($var);

            $var = $this->call($creator, $this);
        } elseif (class_exists($key)) {
            unset($var);

            $var = new $key($this);
        }

        return $var;
    }

    /**
     * Assign hive key's value.
     *
     * @param mixed $value
     */
    public function set(string $key, $value): Fw
    {
        if ($this->hasSetter(strstr($key.'.', '.', true), $method)) {
            $this->{$method}($key, $value);
        } else {
            $this->setInternal($key, $value);
        }

        return $this;
    }

    /**
     * Remove hive key.
     */
    public function rem(string $key): Fw
    {
        $value = $this->init;
        $value = static::makeRef($key, $value, false, $initExists, $parts);

        if ($initExists) {
            $this->set($key, $value);
        } elseif ('COOKIE' === $parts[0]) {
            $value = isset($parts[1]) ? null : $this->hive['COOKIE'];
            $lifetime = $this->hive['JAR']['lifetime'];
            $this->hive['JAR']['lifetime'] = -1;
            $this->set($key, $value);
            $this->hive['JAR']['lifetime'] = $lifetime;
        } else {
            if ('SESSION' === $parts[0] && empty($parts[1])) {
                $this->sessionDestroy();
            }

            $this->unref($key);
        }

        return $this;
    }

    /**
     * Return true if all keys exists in hive.
     *
     * @param array|string $keys
     */
    public function hasAll($keys): bool
    {
        foreach (static::split($keys) as $key) {
            if (!$this->has($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all keys value.
     *
     * @param array|string $keys
     */
    public function getAll($keys): array
    {
        $result = array();

        foreach (static::split($keys) as $key => $rename) {
            if (is_numeric($key)) {
                $key = $rename;
            }

            $result[$rename] = $this->get($key);
        }

        return $result;
    }

    /**
     * Set all keys value.
     */
    public function setAll(?array $values, string $prefix = null): Fw
    {
        foreach ($values ?? array() as $key => $value) {
            $this->set($prefix.$key, $value);
        }

        return $this;
    }

    /**
     * Remove all key value.
     *
     * @param array|string $keys
     */
    public function remAll($keys, string $prefix = null): Fw
    {
        foreach (static::split($keys) as $key) {
            $this->rem($prefix.$key);
        }

        return $this;
    }

    /**
     * Copy hive value of source to target.
     */
    public function copy(string $sourceKey, string $targetKey): Fw
    {
        return $this->set($targetKey, $this->get($sourceKey));
    }

    /**
     * Get hive value and remove from hive.
     *
     * @return mixed
     */
    public function cut(string $key)
    {
        $value = $this->get($key);
        $this->rem($key);

        return $value;
    }

    /**
     * Move location of hive value.
     */
    public function move(string $sourceKey, string $targetKey): Fw
    {
        return $this->set($targetKey, $this->cut($sourceKey));
    }

    /**
     * Prepend value to hive.
     *
     * @param mixed $value
     */
    public function prepend(string $key, $value, bool $expectArray = false): Fw
    {
        $var = $this->get($key);

        if ($expectArray && !is_array($var)) {
            $var = (array) $var;
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
     * @param mixed $value
     */
    public function append(string $key, $value, bool $expectArray = false): Fw
    {
        $var = $this->get($key);

        if ($expectArray && !is_array($var)) {
            $var = (array) $var;
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
     * Grab callable from string expression.
     *
     * @return mixed
     */
    public function grabExpression(string $expression)
    {
        if (1 === substr_count($expression, '@') && $pos = strpos($expression, '@')) {
            return array($this->get(substr($expression, 0, $pos)), substr($expression, $pos + 1));
        }

        if (1 === substr_count($expression, ':') && $pos = strpos($expression, ':')) {
            return array(substr($expression, 0, $pos), substr($expression, $pos + 1));
        }

        return $expression;
    }

    /**
     * Execute callback and continue framework instance chain.
     */
    public function chain(callable ...$callbacks): Fw
    {
        $prev = null;

        foreach ($callbacks as $callback) {
            $prev = $callback($this, $prev);
        }

        return $this;
    }

    /**
     * Call callback or callback expression.
     *
     * @param mixed $callback
     * @param mixed ...$arguments
     *
     * @return mixed
     */
    public function call($callback, ...$arguments)
    {
        $call = $callback;

        if (is_string($callback)) {
            $call = $this->grabExpression($callback);
        }

        return $call(...$arguments);
    }

    /**
     * Register event handler.
     *
     * @param mixed $handler
     */
    public function on(string $eventName, $handler, bool $once = false): Fw
    {
        return $this->set('EVENT.'.$eventName, array($handler, $once));
    }

    /**
     * Register event handler, once.
     *
     * @param mixed $handler
     */
    public function one(string $eventName, $handler): Fw
    {
        return $this->on($eventName, $handler, true);
    }

    /**
     * Remove event handler.
     */
    public function off(string $eventName): Fw
    {
        return $this->rem('EVENT.'.$eventName);
    }

    /**
     * Dispatch an event.
     *
     * @param mixed &$result
     */
    public function dispatch(string $eventName, array $arguments = null, &$result = null, bool $once = false): bool
    {
        if (!$handlers = $this->ref('EVENT.'.$eventName, false)) {
            return false;
        }

        if ($once) {
            $this->off($eventName);
        }

        if (null === $arguments) {
            $arguments = array();
        }

        $this->hive['EVENT_DISPATCH_RESULT'] = null;

        foreach ($handlers as $key => list($handler, $one)) {
            ($once || !$one) || $this->off($eventName.'.'.$key);

            $this->hive['EVENT_DISPATCH_RESULT'] = $this->call($handler, ...$arguments);
        }

        $result = $this->hive['EVENT_DISPATCH_RESULT'];

        return true;
    }

    /**
     * Log with an arbitrary level.
     */
    public function log(string $level, string $message, array $data = null): Fw
    {
        if (
            $this->logLevelCheck($level)
            && (
                !$this->dispatch(static::EVENT_LOG, array($this, $level, $message, $data), $continue)
                || $continue
            )
            && $this->hive['LOG']['directory']
            && ($threshold = $this->hive['LOG']['level_threshold'])
            && static::LOG_LEVELS[$threshold] >= static::LOG_LEVELS[$level]
        ) {
            $content = $this->logFormatMessage($level, $message, $data);

            $this->logWrite($content);
        }

        return $this;
    }

    /**
     * Return hive value.
     */
    public function hive(): array
    {
        return $this->hive;
    }

    /**
     * Return elapsed time.
     */
    public function elapsed(float $start = null): float
    {
        return microtime(true) - ($start ?? $this->hive['TIME']);
    }

    /**
     * Return asset path.
     */
    public function asset(string $path): string
    {
        $asset = $this->hive['ASSET_PREFIX'];
        $asset .= $this->hive['ASSET_MAP'][$path] ?? $path;

        if ('dynamic' === $this->hive['ASSET_VERSION']) {
            $asset .= '?v'.$this->hive['TIME'];
        } else {
            $asset .= rtrim('?'.$this->hive['ASSET_VERSION'], '?');
        }

        return $this->hive['ASSET_ABSOLUTE'] ? $this->baseUrl($asset) : $this->basePath($asset);
    }

    /**
     * Return base url.
     */
    public function baseUrl(string $suffix = null): string
    {
        $url = $this->hive['SCHEME'].'://'.$this->hive['HOST'];

        if (80 !== $this->hive['PORT'] && 443 !== $this->hive['PORT']) {
            $url .= ':'.$this->hive['PORT'];
        }

        return $url.$this->basePath($suffix);
    }

    /**
     * Return base path.
     */
    public function basePath(string $suffix = null): string
    {
        $add = $suffix && '/' !== $suffix && '/' !== $suffix[0] ? '/' : null;

        return $this->hive['BASE'].$add.$suffix;
    }

    /**
     * Return site url.
     */
    public function siteUrl(string $suffix = null): string
    {
        $url = $this->hive['SCHEME'].'://'.$this->hive['HOST'];

        if (80 !== $this->hive['PORT'] && 443 !== $this->hive['PORT']) {
            $url .= ':'.$this->hive['PORT'];
        }

        return $url.$this->sitePath($suffix);
    }

    /**
     * Return site path.
     */
    public function sitePath(string $suffix = null): string
    {
        $add = $suffix && '/' !== $suffix && '/' !== $suffix[0] ? '/' : null;

        return $this->basePath($this->hive['FRONT'].$add.$suffix);
    }

    /**
     * Return path.
     *
     * @param array|string $parameters
     */
    public function path(string $alias, $parameters = null): string
    {
        if ('/' === $alias[0] || !isset($this->hive['ALIASES'][$alias])) {
            $path = '/'.ltrim($alias, '/');

            if ($parameters) {
                if (is_array($parameters)) {
                    $parameters = http_build_query($parameters);
                }

                $path .= '?'.$parameters;
            }
        } else {
            $path = $this->alias($alias, $parameters);
        }

        return $this->sitePath($path);
    }

    /**
     * Build alias path.
     *
     * @param array|string $parameters
     *
     * @throws LogicException if route not exists
     * @throws LogicException if required parameter is not provided
     * @throws LogicException if parameter is not valid
     */
    public function alias(string $alias, $parameters = null): string
    {
        if (!$pattern = $this->hive['ALIASES'][$alias] ?? null) {
            throw new \LogicException("Route not exists: '{$alias}'.");
        }

        if (!$parameters && false === strpos($pattern, '@')) {
            return $pattern;
        }

        $path = $pattern;

        if (is_string($parameters)) {
            parse_str($parameters, $parameters);
        }

        if (!is_array($parameters)) {
            $parameters = array();
        }

        if (false !== strpos($pattern, '@')) {
            $path = preg_replace_callback(
                static::ROUTE_PARAMETER_PATTERN,
                static function ($match) use ($alias, &$parameters) {
                    list($global, $name, $charClass, $custom, $matchAll) = $match + array(2 => null, null, null);

                    if (empty($parameters[$name])) {
                        throw new \LogicException("Parameter should be provided ({$name}@{$alias}).");
                    }

                    $parameter = $parameters[$name];

                    if ($matchAll) {
                        $pattern = '~^[^\?]*$~';
                    } elseif ($custom) {
                        $pattern = '~'.$custom.'~';
                    } elseif ($charClass) {
                        $pattern = '~^[[:'.$charClass.':]]+$~';
                    } else {
                        $pattern = '~^[^\/\?]+$~';
                    }

                    if ($pattern && is_scalar($parameter) && !preg_match($pattern, (string) $parameter)) {
                        throw new \LogicException("Invalid route parameter: '{$parameter}' ({$name}@{$alias}).");
                    }

                    unset($parameters[$name]);

                    if (is_array($parameter)) {
                        return implode('/', array_map(function ($item) {
                            return is_string($item) ? urlencode($item) : $item;
                        }, $parameter));
                    }

                    if (is_string($parameter)) {
                        return urlencode($parameter);
                    }

                    return $parameter;
                },
                $pattern
            );
        }

        if ($parameters) {
            $path .= '?'.http_build_query($parameters);
        }

        return $path;
    }

    /**
     * Register routes.
     */
    public function routeAll(array $routes): Fw
    {
        foreach ($routes as $route => $arguments) {
            if (is_callable($arguments) || !is_array($arguments)) {
                $arguments = array($arguments);
            }

            $this->route($route, ...$arguments);
        }

        return $this;
    }

    /**
     * Register route handler.
     *
     * @param mixed $handler
     * @param mixed ...$extras
     *
     * @throws LogicException if route pattern invalid
     * @throws LogicException if no path given in route pattern
     * @throws LogicException if no alias and no path given in route pattern
     */
    public function route(string $route, $handler, ...$extras): Fw
    {
        if (!preg_match('~^([\w|]+)(?:\h+([\w]+))?(?:\h+(/[^\h]*))?(?:\h+_(all|ajax|sync))?(?:\h+\*([\w/;=\d.,\h]+))?$~i', trim($route), $parts, PREG_UNMATCHED_AS_NULL)) {
            throw new \LogicException("Invalid route pattern: '{$route}'.");
        }

        list(
            'alias' => $aliasPrefix,
            'path' => $pathPrefix,
            'request' => $defaultRequestName,
            'mime' => $defaultMimeList,
            'handler' => $handlerPrefix,
            'extras' => $defaultExtras) = $this->hive['ROUTE_GROUP'];

        $verbs = $parts[1];
        $alias = $parts[2] ?? null;
        $path = $parts[3] ?? null;
        $requestName = $parts[4] ?? $defaultRequestName;
        $mimeList = $parts[5] ?? $defaultMimeList;

        if ('group' === strtolower($verbs)) {
            // set route group
            $this->hive['ROUTE_GROUP'] = array(
                'alias' => $alias,
                'path' => $path,
                'request' => $requestName,
                'mime' => $mimeList,
                'handler' => is_string($handler) ? str_replace(array('"', "'"), '', $handler) : $handler,
                'extras' => $extras,
            );

            return $this;
        }

        $path = $pathPrefix.$path;

        if (!$path) {
            if (!$alias) {
                throw new \LogicException("Route contains no path: '{$route}'.");
            }

            $alias = $aliasPrefix.$alias;
            $path = $pathPrefix.($this->hive['ALIASES'][$alias] ?? null);

            if (empty($path)) {
                throw new \LogicException("Invalid route alias: '{$alias}'.");
            }
        } elseif ($alias) {
            $alias = $aliasPrefix.$alias;
            $this->hive['ALIASES'][$alias] = $path;
        }

        $request = constant('static::REQUEST_'.strtoupper($requestName));
        $mimes = static::headerParseContent($mimeList);
        $controller = is_string($handler) ? $handlerPrefix.$handler : $handler;
        $data = $defaultExtras ? array_merge($defaultExtras, $extras) : $extras;

        foreach (explode('|', strtoupper($verbs)) as $verb) {
            foreach ($mimes as $mime => $params) {
                $this->hive['ROUTES'][$path][$request][$verb][$mime] = array($controller, $alias, $data, $params);
            }
        }

        return $this;
    }

    /**
     * Redirect routes.
     */
    public function redirectAll(array $patterns): Fw
    {
        foreach ($patterns as $pattern => $arguments) {
            $this->redirect($pattern, ...((array) $arguments));
        }

        return $this;
    }

    /**
     * Register route redirector.
     */
    public function redirect(string $pattern, string $url, bool $permanent = true): Fw
    {
        return $this->route($pattern, static function (Fw $fw) use ($url, $permanent) {
            return $fw->reroute($url, $permanent);
        });
    }

    /**
     * Return arguments if route match.
     */
    public function routeMatch(string $pattern): ?array
    {
        if ($pattern === $this->hive['PATH']) {
            return array();
        }

        $regex = $pattern;
        $modifier = $this->hive['CASELESS'] ? 'i' : '';

        if (false !== strpos($pattern, '@')) {
            $regex = preg_replace_callback(static::ROUTE_PARAMETER_PATTERN, function (array $match) {
                list($global, $name, $characterClasses, $customPattern, $matchAll) = $match + array(2 => null, null, null);

                if ($matchAll) {
                    $pattern = '[^\?]*';
                } elseif ($customPattern) {
                    $pattern = $customPattern;
                } elseif ($characterClasses) {
                    $pattern = '[[:'.$characterClasses.':]]+';
                } else {
                    $pattern = '[^\/\?]+';
                }

                return '(?P<'.$name.'>'.$pattern.')';
            }, $pattern);
        }

        if (preg_match('~^'.$regex.'$~'.$modifier, $this->hive['PATH'], $match)) {
            return array_filter(array_slice($match, 1), 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return null;
    }

    /**
     * Find matched route based on current environment.
     */
    public function findRoute(): array
    {
        $verb = $this->hive['VERB'];
        $type = $this->get('AJAX') ? static::REQUEST_AJAX : static::REQUEST_SYNC;
        $accepts = $this->get('ACCEPT');

        foreach ($this->hive['ROUTES'] ?? array() as $pattern => $types) {
            if (null === $arguments = $this->routeMatch($pattern)) {
                continue;
            }

            $verbs = $types[$type] ?? $types[static::REQUEST_ALL] ?? null;

            if (!$verbs) {
                break;
            }

            $mimes = $verbs[$verb] ?? null;

            if (!$mimes) {
                return $this->generateRouteMethodNotAllowed(array_keys($verbs));
            }

            list($controller, $alias, $mime, $extras) = $this->routeAcceptBest($accepts, $mimes);

            return compact('controller', 'arguments', 'alias', 'pattern', 'mime', 'extras');
        }

        return $this->generateRouteNotFound();
    }

    /**
     * Return trace as string.
     */
    public function trace(array $trace = null): string
    {
        $result = '';
        $eol = "\n";
        $debug = $this->hive['DEBUG'];
        $replace = $this->fixTraceClear();

        foreach ($trace ?? debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS) as $frame) {
            if (empty($frame['file'])) {
                continue;
            }

            $line = '';

            if (isset($frame['class'])) {
                $line .= $frame['class'].$frame['type'];
            }

            if (isset($frame['function'])) {
                $arguments = null;

                if ($debug > 2 && isset($frame['args']) && $frame['args']) {
                    $arguments = $this->csv($frame['args']);
                }

                $line .= $frame['function'].'('.$arguments.')';
            }

            $source = str_replace($replace, '', static::fixSlashes($frame['file'])).':'.$frame['line'];
            $result .= '['.$source.'] '.$line.$eol;
        }

        return $result;
    }

    /**
     * Set http status code.
     *
     * @throws LogicException if http code invalid
     */
    public function status(int $code): Fw
    {
        if (!defined($name = 'static::HTTP_'.$code)) {
            throw new \LogicException("Invalid HTTP Code: {$code}.");
        }

        $this->hive['STATUS'] = $code;
        $this->hive['TEXT'] = constant($name);

        return $this;
    }

    /**
     * Send HTTP headers.
     */
    public function sendHeader(): Fw
    {
        if (!$this->hive['HEADER_SENT']) {
            $this->hive['HEADER_SENT'] = true;

            header("{$this->hive['PROTOCOL']} {$this->hive['STATUS']} {$this->hive['TEXT']}");

            foreach ($this->getFixedHeader() as $name => $header) {
                $replace = 'Set-Cookie' !== $name;

                foreach ($header as $line) {
                    header("{$name}: {$line}", $replace, $this->hive['STATUS']);
                }
            }
        }

        return $this;
    }

    /**
     * Send controller response.
     */
    public function sendContent(): Fw
    {
        if (!$this->hive['CONTENT_SENT'] && !$this->hive['QUIET']) {
            $this->hive['CONTENT_SENT'] = true;

            echo $this->hive['CONTENT'];
        }

        return $this;
    }

    /**
     * Execute controller result.
     */
    public function sendResult(): Fw
    {
        if (!$this->hive['RESULT_SENT']) {
            $this->hive['RESULT_SENT'] = true;

            if ($result = $this->hive['RESULT']) {
                $result($this);
            }
        }

        return $this;
    }

    /**
     * Perform send header, content and result.
     */
    public function send(): Fw
    {
        $this->sendHeader();
        $this->sendResult();
        $this->sendContent();

        return $this;
    }

    /**
     * Perform rerouting to target.
     *
     * @param mixed $target
     */
    public function reroute($target = null, bool $permanent = false): Fw
    {
        if (!$target) {
            $url = $this->hive['PATH'];
        } elseif (is_array($target)) {
            list($alias, $parameters) = $target + array(1 => null);
            $url = $this->alias($alias, $parameters);
        } elseif ($path = $this->hive['ALIASES'][$target] ?? null) {
            $url = $path;
        } elseif (preg_match('/^([\w.]+)(?:\(([^)]+)\))?$/', $target, $match)) {
            $alias = $match[1];
            $parameters = isset($match[2]) ? strtr($match[2], ',', '&') : '';

            $url = $this->alias($alias, $parameters);
        } else {
            $url = $target;
        }

        if ($this->dispatch(static::EVENT_REROUTE, array($this, $url, $permanent), $handled, true) && $handled) {
            return $this;
        }

        // check if need base
        if ('/' === $url[0] && (empty($url[1]) || '/' !== $url[1])) {
            $url = $this->siteUrl($url);
        }

        $this->status($permanent ? 301 : 302);
        $this->set('HEADER.Location', $url);

        return $this;
    }

    /**
     * Set error.
     */
    public function error(int $code, string $message = null, array $originalTrace = null): Fw
    {
        $this->remAll('CONTENT,CONTENT_SENT,HEADER,HEADER_SENT,RESULT,RESULT_SENT');
        $this->status($code);

        $prior = $this->hive['ERROR'];
        $text = $this->hive['TEXT'];
        $trace = $this->trace($originalTrace);
        $message = $message ?: "{$this->hive['VERB']} {$this->hive['PATH']} (HTTP {$code})";

        $error = compact('code', 'text', 'message', 'trace', 'originalTrace');
        $this->hive['ERROR'] = $error;

        try {
            if (
                $this->dispatch(static::EVENT_ERROR, array($this, $error, $prior), $output, true)
                && $output
                && $this->hive['HANDLE_RESULT']
            ) {
                $this->handleResult($output);
            }
        } catch (\Throwable $e) {
            $this->handleException($e);
        }

        if (!$this->hive['CONTENT']) {
            $this->handleError($error);
        }

        return $this;
    }

    /**
     * Route mocking.
     */
    public function mock(string $route, array $arguments = null, string $body = null, array $server = null): Fw
    {
        if (!preg_match('~^(\w+)\h+([^\h?]+)(\?[^\h]+)?(?:\h+(ajax|sync))?$~', $route, $parts)) {
            throw new \LogicException("Invalid mocking pattern: '{$route}'.");
        }

        $verb = strtoupper($parts[1]);
        $target = $parts[2];
        $query = $parts[3] ?? '';
        $mode = strtolower($parts[4] ?? '');

        if ($ref = $this->hive['ALIASES'][$target] ?? null) {
            $path = $ref;
        } elseif (preg_match('/^(\w+)\(([^)]+)\)$/', $target, $match)) {
            $path = $this->alias($match[1], strtr($match[2], ',', '&'));

            if ($pos = strpos($path, '?')) {
                $query .= '&'.substr($path, $pos + 1);
                $path = substr($path, 0, $pos);
            }
        } else {
            $path = urldecode($target);
        }

        $this->hive['VERB'] = $verb;
        $this->hive['PATH'] = $path;
        $this->hive['AJAX'] = 'ajax' === $mode;
        $this->hive['POST'] = null;
        $this->hive['GET'] = null;
        $this->hive['CONTENT'] = null;
        $this->hive['CONTENT_SENT'] = false;
        $this->hive['HEADER'] = null;
        $this->hive['HEADER_SENT'] = false;
        $this->hive['RESULT'] = null;
        $this->hive['RESULT_SENT'] = false;
        $this->hive['BODY'] = $body;

        if ($query) {
            parse_str(ltrim($query, '?'), $this->hive['GET']);
        }

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
            $this->hive['SERVER'] = array_merge($this->init['SERVER'] ?? array(), $server);
        }

        $this->execute();

        return $this;
    }

    /**
     * Perform route matching.
     */
    public function run(): Fw
    {
        try {
            $this->execute();
        } catch (\Throwable $e) {
            $this->handleException($e);
        }

        $this->send();

        return $this;
    }

    /**
     * Handle script unloading.
     */
    public function handleShutdown(string $workingDir): void
    {
        chdir($workingDir);

        if ($error = error_get_last()) {
            ini_get('output_buffering') <= 0 || ob_end_clean();
            !$this->hive['HANDLE_SHUTDOWN'] || $this->error(500, "Fatal error: '{$error['message']}'", array($error))->send();
        } else {
            $this->sessionCommit();
        }

        $this->logCloseFile();
    }

    /**
     * Execute route matching logic.
     */
    protected function execute(): void
    {
        if ($this->hive['LANGUAGE_AUTOLOAD']) {
            $this->loadLanguage(null);
        }

        if (null === $this->hive['BODY'] && !$this->hive['BODY_RAW']) {
            $this->hive['BODY'] = file_get_contents('php://input');
        }

        if ($this->dispatch(static::EVENT_BOOT, array($this), $continue, true) && !$continue) {
            return;
        }

        $route = $this->findRoute();
        list('controller' => $controller, 'arguments' => $arguments, 'alias' => $alias, 'pattern' => $pattern, 'mime' => $mime, 'extras' => $extras) = $route;

        $this->hive['PARAMS'] = $arguments;
        $this->hive['ALIAS'] = $alias;
        $this->hive['PATTERN'] = $pattern;
        $this->hive['MIME'] = $mime;
        $this->hive['EXTRAS'] = $extras;

        if (!$this->dispatch(static::EVENT_PREROUTE, array($this, $route), $result) || !$result) {
            if (
                $this->dispatch(static::EVENT_CONTROLLER, array($this, $controller), $newController)
                && $newController
            ) {
                $controller = $newController;
            }

            if (
                $this->dispatch(static::EVENT_CONTROLLER_ARGUMENTS, array($this, $controller, $arguments), $newArguments)
                && $newArguments
            ) {
                $arguments = $newArguments;
            }

            $result = $this->call($controller, $this, $arguments);
        }

        if (
            (!$this->dispatch(static::EVENT_POSTROUTE, array($this, $result, $route), $newResult) || $newResult)
            && $this->hive['HANDLE_RESULT']
        ) {
            $this->handleResult($newResult ?? $result);
        }

        $this->dispatch(static::EVENT_SHUTDOWN, array($this), $return, true);
    }

    /**
     * Handle handler result.
     *
     * @param mixed $result
     */
    protected function handleResult($result): void
    {
        if (is_string($result)) {
            $this->hive['CONTENT'] = $result;
        } elseif (is_callable($result)) {
            $this->hive['RESULT'] = $result;
        } elseif (is_array($result)) {
            $this->hive['CONTENT'] = json_encode($result);

            if (empty($this->hive['HEADER']['Content-Type'])) {
                $this->hive['HEADER']['Content-Type'] = array('application/json');
            }
        }
    }

    /**
     * Handle any exception thrown.
     */
    protected function handleException(\Throwable $e): void
    {
        $file = str_replace($this->fixTraceClear(), '', static::fixSlashes($e->getFile()));
        $message = "{$e->getMessage()} [{$file}:{$e->getLine()}]";
        $trace = $e->getTrace();

        $this->error(500, $message, $trace);
    }

    /**
     * Handle error response.
     */
    protected function handleError(array $error): void
    {
        if ($this->get('AJAX')) {
            $output = $error;

            if (!$this->hive['DEBUG']) {
                unset($output['trace'], $output['originalTrace']);
            }
        } else {
            $eol = "\n";
            $output = '<!DOCTYPE html>'.$eol.
                '<html>'.$eol.
                '<head>'.
                    '<title>'.$error['code'].' '.$error['text'].'</title>'.
                '</head>'.$eol.
                '<body>'.$eol.
                    '<h1>'.$error['text'].'</h1>'.$eol.
                    '<p>'.$this->encode($error['message']).'</p>'.$eol.
                    ($this->hive['DEBUG'] ? ('<pre>'.$error['trace'].'</pre>'.$eol) : '').
                '</body>'.$eol.
                '</html>';
        }

        $this->handleResult($output);
    }

    /**
     * Return response 405.
     */
    protected function generateRouteMethodNotAllowed(): array
    {
        return array(
            'controller' => static function (Fw $fw) {
                return $fw->error(405);
            },
            'arguments' => array(),
            'alias' => null,
            'pattern' => null,
            'mime' => null,
            'extras' => null,
        );
    }

    /**
     * Return response 404.
     */
    protected function generateRouteNotFound(): array
    {
        return array(
            'controller' => static function (Fw $fw) {
                return $fw->error(404);
            },
            'arguments' => array(),
            'alias' => null,
            'pattern' => null,
            'mime' => null,
            'extras' => null,
        );
    }

    /**
     * Return fixed header to send.
     */
    protected function getFixedHeader(): array
    {
        $headers = $this->hive['HEADER'] ?? array();

        if (!isset($headers['Content-Type']) && $this->hive['MIME'] && false === strpos($this->hive['MIME'], '*')) {
            $headers['Content-Type'] = array($this->hive['MIME']);
        }

        if (
            !isset($headers['Content-Length'])
            && isset($headers['Content-Type'])
            && is_string($this->hive['CONTENT'])
        ) {
            $headers['Content-Length'] = array(strlen($this->hive['CONTENT']));
        }

        return $headers;
    }

    /**
     * Return best route accept.
     */
    protected function routeAcceptBest(array $accepts, array $routes): array
    {
        $best = null;
        $routeKeys = array_keys($routes);

        foreach ($accepts as $mime => $preferences) {
            if (isset($routes[$mime])) {
                $best = $routes[$mime];

                break;
            }

            if ($match = preg_grep('~^'.str_replace('*', '.+', $mime).'$~i', $routeKeys)) {
                $mime = reset($match);
                $best = $routes[$mime];

                break;
            }
        }

        if (!$best) {
            $routeAccepts = array_combine($routeKeys, array_values(array_column($routes, 3)));
            uasort($routeAccepts, static::class.'::headerQualitySort');

            $mime = key($routeAccepts);
            $best = $routes[$mime];
        }

        list($handler, $alias, $extras) = $best;

        return array($handler, $alias, $mime, $extras);
    }

    /**
     * Update session cookie parameter.
     */
    protected function sessionModifyCookie(array $jar): void
    {
        if (PHP_SESSION_ACTIVE !== session_status()) {
            $jar['lifetime'] = static::cookieExpiresTime($jar['lifetime']);
            $arguments = version_compare(PHP_VERSION, '7.3.0') >= 0 ? array($jar) : array_values($jar);

            session_set_cookie_params(...array_slice($arguments, 0, 5));
        }
    }

    /**
     * Start session if not started.
     */
    protected function sessionStart(): void
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            return;
        }

        session_start();
        $this->hive['SESSION'] = &$GLOBALS['_SESSION'];
    }

    /**
     * Commit session if it's already started.
     */
    protected function sessionCommit(): void
    {
        if (PHP_SESSION_ACTIVE === session_status()) {
            session_commit();
        }
    }

    /**
     * Destroy session.
     */
    protected function sessionDestroy(): void
    {
        $this->sessionStart();

        session_unset();
        session_destroy();
        $this->rem('COOKIE.'.session_name());
    }

    /**
     * Convert given log level is valid.
     */
    protected function logLevelCheck(string $level): bool
    {
        if (!defined('static::LOG_LEVEL_'.strtoupper($level))) {
            throw new \UnexpectedValueException("Invalid log level: '{$level}'.");
        }

        return true;
    }

    /**
     * Format log message.
     */
    protected function logFormatMessage(string $level, string $message, array $context = null): string
    {
        $timestamp = (new \DateTime())->format($this->hive['LOG']['date_format']);
        $message = "[{$timestamp}] [{$level}] {$message}";

        if ($this->hive['LOG']['append_context'] && $context) {
            $indent = '    ';
            $context = json_encode($context, JSON_PRETTY_PRINT);
            $message .= PHP_EOL.$indent.str_replace("\n", "\n".$indent, $context);
        }

        return $message.PHP_EOL;
    }

    /**
     * Close log file handle if exists.
     */
    protected function logCloseFile(): void
    {
        if (isset($this->context['logFileHandler'])) {
            fclose($this->context['logFileHandler']);
        }

        unset($this->context['logFileHandler']);
    }

    /**
     * Open log file handle if not exists.
     */
    protected function logPrepareHandler(): void
    {
        if (isset($this->context['logFileHandler'])) {
            return;
        }

        list('prefix' => $prefix, 'extension' => $extension, 'directory' => $directory) = $this->hive['LOG'];

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $filepath = $directory.$prefix.date('Y-m-d.').$extension;

        $this->context['logFileHandler'] = fopen($filepath, 'a');
    }

    /**
     * Write log.
     */
    protected function logWrite(string $log): void
    {
        $this->logPrepareHandler();

        fwrite($this->context['logFileHandler'], $log);

        if ($this->hive['LOG']['flush']) {
            fflush($this->context['logFileHandler']);
        }
    }

    /**
     * Parse configuration line expression.
     */
    protected function configParse(string $str): string
    {
        if (false === strpos($str, '{')) {
            return $str;
        }

        preg_match_all('/[$@]\{([^}]+)\}/', $str, $matches, PREG_SET_ORDER);

        foreach ($matches as list($search, $key)) {
            $replace = '@' === $search[0] ? constant($key) : $this->get($key);
            $result = str_replace($search, $replace, $result ?? $str);
        }

        return $result ?? $str;
    }

    /**
     * Parse configuration value.
     */
    protected function configValue(string $str, string $modifier = null): array
    {
        // Remove escape mark (slash)
        $tmp = preg_replace('/\\\\\h*(\r?\n)/', '\1', $str);

        // Mark quoted strings with 0x00 whitespace
        $tmp = str_getcsv(preg_replace('/(?<!\\\\)(")(.*?)\1/', "\\1\x00\\2\\1", trim($tmp)));

        // Result
        $value = array();

        foreach ($tmp as $val) {
            $val = static::cast($val);

            if (is_string($val)) {
                $val = $val ? preg_replace('/\\\\"/', '"', $val) : null;
            }

            $value[] = $val;
        }

        if ($modifier) {
            $value = (array) $this->call($modifier, ...$value);
        }

        return $value;
    }

    /**
     * Load language lexicon.
     *
     * @param string $language
     */
    protected function loadLanguage(?string $language, string $directories = null): void
    {
        $lang = $language ?? $this->hive['LANGUAGE'] ?? $this->hive['FALLBACK'];
        $glob = false === ($pos = strpos($lang, '-')) ? null : substr($lang, 0, $pos);
        $dirs = $directories ?? $this->hive['LOCALES'];
        $ext = '.ini';

        foreach (static::split($dirs) as $dir) {
            if ($glob) {
                $this->loadLanguageContent($dir.$glob.$ext);
            }

            $this->loadLanguageContent($dir.$lang.$ext);
        }

        $this->hive['LANGUAGE_AUTOLOAD'] = false;
    }

    /**
     * Load language from file.
     */
    protected function loadLanguageContent(string $file): void
    {
        $prefix = null;

        foreach (static::iniRead($file) as $match) {
            if ($match['section']) {
                $prefix = ltrim($match['section'].'.', '.');

                continue;
            }

            list('lval' => $key, 'rval' => $value) = $match;

            $this->set('DICT.'.$prefix.$key, trim($value));
        }
    }

    /**
     * Get fixed trace truncate.
     */
    protected function fixTraceClear(): array
    {
        return array_map(static::class.'::fixSlashes', static::split($this->hive['TRACE_CLEAR']));
    }

    /**
     * Check if getter exists.
     *
     * @param null|string &$method
     */
    protected function hasGetter(string $key, string &$method = null): bool
    {
        return method_exists($this, $method = '_get'.$key) && ctype_upper($key);
    }

    /**
     * Check if setter exists.
     *
     * @param null|string &$method
     */
    protected function hasSetter(string $key, string &$method = null): bool
    {
        return method_exists($this, $method = '_set'.$key) && ctype_upper($key);
    }

    /**
     * Set internal hive.
     *
     * @param mixed $value
     */
    protected function setInternal(string $key, $value): void
    {
        $var = &$this->ref($key);
        $var = $value;
    }

    /**
     * Return HTTP Accept mime.
     */
    protected function _getAccept(): array
    {
        return static::headerParseContent($this->hive['SERVER']['HTTP_ACCEPT'] ?? '*/*');
    }

    /**
     * Return client user agent.
     */
    protected function _getAgent(): string
    {
        return $this->hive['SERVER']['HTTP_USER_AGENT'] ?? 'none';
    }

    /**
     * Return ajax request status.
     */
    protected function _getAjax(): bool
    {
        return 'XMLHttpRequest' === ($this->hive['SERVER']['HTTP_X_REQUESTED_WITH'] ?? null);
    }

    /**
     * Return client ip address.
     */
    protected function _getIp(): string
    {
        if ($forwarded = $this->hive['SERVER']['HTTP_X_FORWARDED_FOR'] ?? null) {
            return strstr($forwarded.',', ',', true);
        }

        return $this->hive['SERVER']['REMOTE_ADDR'] ?? $this->hive['SERVER']['HTTP_CLIENT_IP'] ?? '::1';
    }

    /**
     * Set cookie value.
     *
     * @param mixed $value
     */
    protected function _setCookie(string $key, $value): void
    {
        if (false === strpos($key, '.')) {
            $this->setAll($value, $key.'.');
        } else {
            $cookie = static::cookieCreate(ltrim(strstr($key, '.'), '.'), (string) $value, $this->hive['JAR']);

            $this->setInternal($key, $value);
            $this->_setHeader('HEADER.Set-Cookie', $cookie);
        }
    }

    /**
     * Set header.
     *
     * @param mixed $content
     */
    protected function _setHeader(string $key, $content): void
    {
        if (false === strpos($key, '.')) {
            $this->setAll($content, $key.'.');
        } else {
            $header = array_merge((array) $this->ref($key, false), (array) $content);

            $this->setInternal($key, $header);
        }
    }

    /**
     * Register events.
     *
     * @param mixed $handler
     */
    protected function _setEvent(string $key, $handler): void
    {
        if (false === strpos($key, '.')) {
            $this->setAll($handler, $key.'.');
        } else {
            $events = $this->ref($key, false);
            $events[] = is_array($handler) && is_bool($handler[1] ?? null) ? $handler : array($handler, false);

            $this->setInternal($key, $events);
        }
    }

    /**
     * Set cookie options.
     *
     * @param mixed $value
     */
    protected function _setJar(string $key, $value): void
    {
        $this->setInternal($key, $value);
        $this->sessionModifyCookie($this->hive['JAR']);
    }

    /**
     * Set log options.
     *
     * @param mixed $value
     */
    protected function _setLog(string $key, $value): void
    {
        $this->logCloseFile();
        $this->setInternal($key, $value);
    }

    /**
     * Perform side-effect of encoding changes.
     */
    protected function _setEncoding(string $key, string $encoding): void
    {
        $this->setInternal($key, $encoding);
        ini_set('default_charset', $encoding);
    }

    /**
     * Perform side-effect of changing timezone.
     */
    protected function _setTz(string $key, string $timezone): void
    {
        $this->setInternal($key, $timezone);
        date_default_timezone_set($timezone);
    }

    /**
     * Set language.
     */
    protected function _setLanguage(string $key, string $expression): void
    {
        $languages = static::headerParseContent($expression);
        $language = key($languages);

        $this->setInternal($key, $language);
        $this->loadLanguage($language);
    }

    /**
     * Set lexicon directories.
     */
    protected function _setLocales(string $key, string $directories): void
    {
        $this->setInternal($key, $directories);
        $this->loadLanguage(null, $directories);
    }
}

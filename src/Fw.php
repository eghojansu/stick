<?php

declare(strict_types=1);

namespace Ekok\Stick;

use Ekok\Stick\Event\RequestEvent;
use Ekok\Stick\Event\ResponseEvent;
use Ekok\Stick\Event\ControllerEvent;
use Ekok\Stick\Event\RequestErrorEvent;
use Ekok\Stick\Event\FinishRequestEvent;
use Ekok\Stick\Event\ControllerArgumentsEvent;
use Ekok\Stick\Event\FinishResponseEvent;
use Ekok\Stick\Event\RerouteEvent;
use Ekok\Stick\Event\RouteEvent;
use Ekok\Stick\Event\SendResponseEvent;

class Fw implements \ArrayAccess
{
    const UPLOADED_FILE_KEYS = array('error', 'name', 'size', 'tmp_name', 'type');

    const COOKIE_DATE_FORMAT = 'D, d-M-Y H:i:s T';
    const COOKIE_SAMESITE_LAX = 'lax';
    const COOKIE_SAMESITE_STRICT = 'strict';
    const COOKIE_SAMESITE_NONE = 'none';
    const COOKIE_RESERVED_CHARS_FROM = ['=', ',', ';', ' ', "\t", "\r", "\n", "\v", "\f"];
    const COOKIE_RESERVED_CHARS_TO = ['%3D', '%2C', '%3B', '%20', '%09', '%0D', '%0A', '%0B', '%0C'];
    const COOKIE_RESERVED_CHARS_LIST = "=,; \t\r\n\v\f";

    const REQUEST_REQUEST = 'fw.request';
    const REQUEST_CONTROLLER = 'fw.controller';
    const REQUEST_CONTROLLER_ARGUMENTS = 'fw.controller_arguments';
    const REQUEST_FINISH = 'fw.finish_request';
    const REQUEST_RESPONSE = 'fw.response';
    const REQUEST_SEND_RESPONSE = 'fw.send_response';
    const REQUEST_FINISH_RESPONSE = 'fw.finish_response';
    const REQUEST_ERROR = 'fw.error';
    const REQUEST_ROUTE = 'fw.route';
    const REQUEST_REROUTE = 'fw.reroute';

    const LOG_LEVEL_EMERGENCY = 'emergency';
    const LOG_LEVEL_ALERT     = 'alert';
    const LOG_LEVEL_CRITICAL  = 'critical';
    const LOG_LEVEL_ERROR     = 'error';
    const LOG_LEVEL_WARNING   = 'warning';
    const LOG_LEVEL_NOTICE    = 'notice';
    const LOG_LEVEL_INFO      = 'info';
    const LOG_LEVEL_DEBUG     = 'debug';

    const HTTP_100 = "Continue";
    const HTTP_101 = "Switching Protocols";
    const HTTP_103 = "Early Hints";
    const HTTP_200 = "OK";
    const HTTP_201 = "Created";
    const HTTP_202 = "Accepted";
    const HTTP_203 = "Non-Authoritative Information";
    const HTTP_204 = "No Content";
    const HTTP_205 = "Reset Content";
    const HTTP_206 = "Partial Content";
    const HTTP_300 = "Multiple Choices";
    const HTTP_301 = "Moved Permanently";
    const HTTP_302 = "Found";
    const HTTP_303 = "See Other";
    const HTTP_304 = "Not Modified";
    const HTTP_307 = "Temporary Redirect";
    const HTTP_308 = "Permanent Redirect";
    const HTTP_400 = "Bad Request";
    const HTTP_401 = "Unauthorized";
    const HTTP_402 = "Payment Required";
    const HTTP_403 = "Forbidden";
    const HTTP_404 = "Not Found";
    const HTTP_405 = "Method Not Allowed";
    const HTTP_406 = "Not Acceptable";
    const HTTP_407 = "Proxy Authentication Required";
    const HTTP_408 = "Request Timeout";
    const HTTP_409 = "Conflict";
    const HTTP_410 = "Gone";
    const HTTP_411 = "Length Required";
    const HTTP_412 = "Precondition Failed";
    const HTTP_413 = "Payload Too Large";
    const HTTP_414 = "URI Too Long";
    const HTTP_415 = "Unsupported Media Type";
    const HTTP_416 = "Range Not Satisfiable";
    const HTTP_417 = "Expectation Failed";
    const HTTP_418 = "I'm a teapot";
    const HTTP_422 = "Unprocessable Entity";
    const HTTP_425 = "Too Early";
    const HTTP_426 = "Upgrade Required";
    const HTTP_428 = "Precondition Required";
    const HTTP_429 = "Too Many Requests";
    const HTTP_431 = "Request Header Fields Too Large";
    const HTTP_451 = "Unavailable For Legal Reasons";
    const HTTP_500 = "Internal Server Error";
    const HTTP_501 = "Not Implemented";
    const HTTP_502 = "Bad Gateway";
    const HTTP_503 = "Service Unavailable";
    const HTTP_504 = "Gateway Timeout";
    const HTTP_505 = "HTTP Version Not Supported";
    const HTTP_506 = "Variant Also Negotiates";
    const HTTP_507 = "Insufficient Storage";
    const HTTP_508 = "Loop Detected";
    const HTTP_510 = "Not Extended";
    const HTTP_511 = "Network Authentication Required";

    public static $mimes = array(
        'any' => '*/*',
        'html' => 'text/html',
        'json' => 'application/json',
    );

    private $hive = array();
    private $keys = array();
    private $events = array();
    private $onces = array();
    private $routes = array();
    private $aliases = array();
    private $rules = array();
    private $factories = array();
    private $response = array(
        'headers_sent' => false,
        'content_sent' => false,
        'code' => 200,
        'text' => self::HTTP_200,
        'output' => null,
        'handler' => null,
        'headers' => null,
        'headers_keys' => null,
    );
    private $logs = array(
        'mode' => 'file',
        'extension' => 'txt',
        'date_format' => 'Y-m-d G:i:s.u',
        'filename' => null,
        'flush_frequency' => 0,
        'prefix' => 'log_',
        'log_format' => null,
        'append_context' => true,
        'permission' => 0755,
        'threshold' => self::LOG_LEVEL_DEBUG,
        'directory' => null,
        'http_level' => null,
        'username' => null,
        'count' => 0,
        'line' => null,
        'filepath' => null,
        'handle' => null,
        'sqlite' => null,
        'levels' => array(
            self::LOG_LEVEL_EMERGENCY => 0,
            self::LOG_LEVEL_ALERT     => 1,
            self::LOG_LEVEL_CRITICAL  => 2,
            self::LOG_LEVEL_ERROR     => 3,
            self::LOG_LEVEL_WARNING   => 4,
            self::LOG_LEVEL_NOTICE    => 5,
            self::LOG_LEVEL_INFO      => 6,
            self::LOG_LEVEL_DEBUG     => 7,
        ),
    );

    public static function createFromGlobals(): self
    {
        return new static($_POST, $_GET, $_FILES, $_COOKIE, $_SERVER, $_ENV);
    }

    public static function cookieCreate(string $name, $value = null, array $options = null, bool $raw = true): string
    {
        if (!$name) {
            throw new \InvalidArgumentException('The cookie name cannot be empty.');
        }

        if ($raw && false !== strpbrk($name, self::COOKIE_RESERVED_CHARS_LIST)) {
            throw new \InvalidArgumentException("The cookie name contains invalid characters: '{$name}'.");
        }

        $lifetime = $options['lifetime'] ?? 0;
        $path = $options['path'] ?? '/';
        $domain = $options['domain'] ?? null;
        $secure = $options['secure'] ?? null;
        $httponly = $options['httponly'] ?? null;
        $samesite = $options['samesite'] ?? null;

        if ($raw) {
            $str = $name;
        } else {
            $str = str_replace(self::COOKIE_RESERVED_CHARS_FROM, self::COOKIE_RESERVED_CHARS_TO, $name);
        }

        $str .= '=';

        if (null === $value || '' === $value) {
            $str .= 'deleted; expires=' . gmdate(self::COOKIE_DATE_FORMAT, time() - 31536001) . '; max-age=0';
        } else {
            $str .= $raw ? $value : rawurlencode($value);
            $expire = $lifetime;

            if ($expire instanceof \DateTimeInterface) {
                $expire = $expire->format('U');
            } elseif (!is_numeric($expire)) {
                $expire = strtotime($expire);

                if (false === $expire) {
                    throw new \InvalidArgumentException('The cookie expiration time is not valid.');
                }
            }

            if ($expire > 0) {
                $str .= '; expires=' . gmdate(self::COOKIE_DATE_FORMAT, (int) $expire) . '; max-age=' . max(0, $expire - time());
            }
        }

        if ($path) {
            $str .= '; path=' . $path;
        }

        if ($domain) {
            $str .= '; domain=' . $domain;
        }

        if ($secure) {
            $str .= '; secure';
        }

        if ($httponly) {
            $str .= '; httponly';
        }

        if ($samesite) {
            if (!defined($name = 'static::COOKIE_SAMESITE_' . strtoupper($samesite))) {
                throw new \InvalidArgumentException("The cookie samesite is not valid: '{$samesite}'.");
            }

            $str .= '; samesite=' . constant($name);
        }

        return $str;
    }

    public static function normFiles(?array $files): array
    {
        $norm = array();

        foreach ($files ?? array() as $key => $file) {
            if (is_array($multiple = $file['name'] ?? null)) {
                $norm[$key] = array_map(static function($pos) use ($file) {
                    return array_reduce(self::UPLOADED_FILE_KEYS, static function(array $norm, $key) use ($pos, $file) {
                        return $norm + array($key => $file[$key][$pos]);
                    }, array());
                }, array_keys($multiple));
            } else {
                $norm[$key] = $file;
            }
        }

        return $norm;
    }

    public static function normSlash(string $str, bool $suffix = false): string
    {
        return rtrim(strtr($str, '\\', '/'), '/') . ($suffix ? '/' : null);
    }

    public static function cast($value)
    {
        if (preg_match('/^(?:0x[0-9a-f]+|0[0-7]+|0b[01]+)$/i', $value)) {
            return intval($value, 0);
        }

        if (is_numeric($value)) {
            return $value + 0;
        }

        $checked = trim($value);

        if (preg_match('/^\w+$/i', $checked) && defined($checked)) {
            return constant($checked);
        }

        return $checked;
    }

    public static function arrIndexed(?array $data): bool
    {
        return isset($data[0]) && ctype_digit(implode('', array_keys($data)));
    }

    public static function stringify($data, bool $state = false, array $stack = null): string
    {
        foreach ($stack ?? array() as $node) {
            if ($data === $node) {
                return '*RECURSION*';
            }
        }

        $type = gettype($data);

        if ('object' === $type) {
            $objStack = array();
            $objState = '';

            foreach ($state ? get_object_vars($data) : array() as $key => $value) {
                $objState .= var_export($key, true) . '=>' . static::stringify($value, $state, array_merge($objStack, array($data))) . ',';
            }

            return get_class($data) . '::__set_state([' . rtrim($objState, ',') . '])';
        }

        if ('array' === $type) {
            $arrStack = array();
            $flat = '';
            $assoc = !static::arrIndexed($data);

            foreach ($data as $key => $value) {
                if ($assoc) {
                    $flat .= var_export($key, true) . '=>';
                }

                $flat .= static::stringify($value, $state, array_merge($arrStack, array($data))) . ',';
            }

            return '[' . rtrim($flat, ',') . ']';
        }

        return var_export($data, true);
    }

    public static function parseExpression(string $expression): array
    {
        $parsed = array();
        $parts = explode('|', $expression);

        foreach ($parts as $part) {
            if ($part) {
                list($rule, $argumentLine) = explode(':', $part) + array(1 => null);

                $parsed[trim($rule)] = $argumentLine ? array_map('static::cast', explode(',', $argumentLine)) : array();
            }
        }

        return $parsed;
    }

    public static function &refCreate(array &$var, string $field)
    {
        $parts = explode('.', $field);

        foreach ($parts as $part) {
            if (null === $var || is_scalar($var)) {
                $var = array();
            }

            $var = &$var[$part];
        }

        return $var;
    }

    public static function refValue(array $data, string $field, bool &$exists = null)
    {
        if (false === strpos($field, '.')) {
            $exists = isset($data[$field]) || array_key_exists($field, $data);

            return $data[$field] ?? null;
        }

        $value = $data;
        $parts = explode('.', $field);
        $exists = false;

        foreach ($parts as $part) {
            if (is_array($value) && (isset($value[$part]) || array_key_exists($part, $value))) {
                $value = &$value[$part];
                $exists = true;
            } else {
                $value = null;
                $exists = false;
                break;
            }
        }

        return $value;
    }

    public static function hash(string $str): string
    {
        return str_pad(base_convert(substr(sha1($str), -16), 16, 36), 11, '0', STR_PAD_LEFT);
    }

    public static function loadFile(string $file)
    {
        $load = static function () {
            if (file_exists(func_get_arg(0))) {
                return require func_get_arg(0);
            }
        };

        return $load($file);
    }

    public function __construct(
        array $post = null,
        array $get = null,
        array $files = null,
        array $cookie = null,
        array $server = null,
        array $env = null,
        string $body = null
    ) {
        $headers = $server ? array_reduce(preg_grep('/^HTTP_/', array_keys($server)), static function (array $headers, string $key) use ($server) {
            $headers[ucwords(strtr(strtolower(substr($key, 5)), '_', '-'), '-')] = $server[$key];

            return $headers;
        }, array()) : array();
        $method = $server['REQUEST_METHOD'] ?? 'GET';
        $contentMime = $headers['Content-Type'] ?? $server['CONTENT_TYPE'] ?? '*/*';
        $contentType = static::$mimes[$contentMime] ?? 'any';
        $protocol = $server['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
        $port = intval($server['SERVER_PORT'] ?? 80);
        $host = strstr(($headers['Host'] ?? $server['SERVER_NAME'] ?? 'localhost') . ':', ':', true);
        $secure = 'on' === ($server['HTTPS'] ?? $headers['X-Forwarded-Ssl'] ?? null) || 'https' === ($headers['X-Forwarded-Proto'] ?? null);
        $scheme = $secure ? 'https' : 'http';
        $baseUrl = $scheme . '://' . $host;
        $basePath = '';
        $entry = '';
        $ajax = 'XMLHttpRequest' === ($headers['X-Requested-With'] ?? null);
        $cli = 'cli' === PHP_SAPI;
        $ip = $headers['Client-Ip'] ?? $headers['X-Forwarded-For'] ?? $headers['X-Forwarded'] ?? $headers['X-Cluster-Client-Ip'] ?? $headers['Forwarded-For'] ?? $headers['Forwarded'] ?? $server['REMOTE_ADDR'] ?? '';

        if (false === strpos($ip, ',')) {
            $ip = filter_var($ip, FILTER_VALIDATE_IP);
        } else {
            $ip = array_reduce(explode(',', $ip), static function ($found, $ip) {
                return $found ?: filter_var($ip, FILTER_VALIDATE_IP);
            });
        }

        if (!in_array($port, array(80, 443))) {
            $baseUrl .= ':' . $port;
        }

        if (isset($server['SCRIPT_NAME'])) {
            $basePath = static::normSlash(dirname($server['SCRIPT_NAME']));
            $entry = basename($server['SCRIPT_NAME']);
        }

        if (isset($server['PATH_INFO'])) {
            $path = $server['PATH_INFO'];
        } elseif ($entry && isset($server['REQUEST_URI'])) {
            $uri = strstr($server['REQUEST_URI'] . '?', '?', true);

            if (false === $pos = strpos($uri, $entry)) {
                $path = $uri;
            } else {
                $path = (substr($uri, $pos + strlen($entry))) ?: '/';
            }

            if ('/' !== $path && $basePath && 0 === strpos($path, $basePath)) {
                $path = (substr($path, strlen($basePath))) ?: '/';
            }
        } else {
            $path = '/';
        }

        $entryScript = !empty($entry);
        $cookieJar = array(
            'lifetime' => 0,
            'path' => $basePath ?: '/',
            'domain' => filter_var($host, FILTER_VALIDATE_IP) ? null : $host,
            'secure' => $secure,
            'httponly' => true,
            'samesite' => self::COOKIE_SAMESITE_LAX,
        );

        $this->hive = array(
            'AJAX' => $ajax,
            'ARGUMENTS_RAW' => null,
            'ARGUMENTS' => null,
            'BASE_PATH' => $basePath,
            'BASE_URL' => $baseUrl,
            'BODY' => $body,
            'CASELESS' => true,
            'CLI' => $cli,
            'CONTENT_MIME' => $contentMime,
            'CONTENT_TYPE' => $contentType,
            'CONTENT' => $body,
            'COOKIE_JAR' => $cookieJar,
            'COOKIE' => $cookie,
            'DEBUG' => false,
            'ENTRY_SCRIPT' => $entryScript,
            'ENTRY' => $entry,
            'ENV' => $env,
            'FILES' => static::normFiles($files),
            'GET' => $get,
            'HEADER' => $headers,
            'HOST' => $host,
            'IP' => $ip,
            'METHOD_OVERRIDE_KEY' => '_method',
            'METHOD_OVERRIDE' => false,
            'METHOD' => $method,
            'OPTIONS' => null,
            'PATH' => $path,
            'PORT' => $port,
            'POST' => $post,
            'PROTOCOL' => $protocol,
            'RAW' => false,
            'SCHEME' => $scheme,
            'SECURE' => $secure,
            'SERVER' => $server,
            'SERVICE' => null,
            'SESSION_KEY' => 'web',
            'SESSION_STARTED' => false,
            'SESSION' => null,
            'STACK' => null,
            'TZ' => date_default_timezone_get(),
        );
        $this->keys = array_fill_keys(array_keys($this->hive), true);
    }

    public function __destruct()
    {
        if ($this->logs['handle']) {
            fclose($this->logs['handle']);
        }

        $this->logs['sqlite'] = null;
    }

    public function offsetExists($offset)
    {
        return $this->keys[$offset] ?? false;
    }

    public function &offsetGet($offset)
    {
        if ($this->keys[$offset] ?? false) {
            $this->triggerValue($offset);

            return $this->hive[$offset];
        }

        $this[$offset] = null;

        return $this->hive[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->triggerValue($offset);

        $this->hive[$offset] = $value;
        $this->keys[$offset] = true;

        $this->triggerValue($offset, 'set');
    }

    public function offsetUnset($offset)
    {
        if ($this->keys[$offset] ?? false) {
            $this->triggerValue($offset);

            unset($this->hive[$offset], $this->keys[$offset]);

            $this->triggerValue($offset, 'unset');
        }
    }

    public function addRule(string $name, $rule = null): self
    {
        $useRule = $rule ?? compact('name');

        if (is_string($rule)) {
            if (false !== strpos($rule, '@') || false !== strpos($rule, ':')) {
                $useRule = array('name' => $name, 'factory' => $rule);
            } else {
                $useRule = array('name' => $name, 'class' => $rule);
            }
        } elseif (is_callable($rule)) {
            $useRule = array('name' => $name, 'factory' => $rule);
        } elseif (!is_array($useRule)) {
            $type = gettype($rule);

            throw new \InvalidArgumentException("Rule should be null, string, array or callable, {$type} given for rule {$name}.");
        }

        $this->rules[$name] = $useRule + ($this->rules[$name] ?? array());

        return $this;
    }

    public function getRule(string $name): array
    {
        return $this->rules[$name] ?? $this->findRule($name) ?? array(
            'name' => $name,
            'fallback' => true,
        );
    }

    public function create(string $name, array $arguments = null)
    {
        if (isset($this->hive['SERVICE'][$name])) {
            return $this->hive['SERVICE'][$name];
        }

        if ($name === __CLASS__ || is_subclass_of($name, __CLASS__)) {
            return $this;
        }

        $rule = $this->getRule($name);
        $useName = $rule['name'] ?? $name;

        if (isset($this->hive['SERVICE'][$useName])) {
            return $this->hive['SERVICE'][$useName];
        }

        $factory = $this->getFactory($useName, $rule);

        return $factory($this, $arguments ?? $rule['arguments'] ?? null);
    }

    public function grabCallable(string $callable, bool $throw = true): callable
    {
        if (false !== $pos = strpos($callable, '@')) {
            $call = array(
                $this->create(substr($callable, 0, $pos)),
                substr($callable, $pos + 1),
            );
        } elseif (false !== $pos = strpos($callable, ':')) {
            $call = array(
                substr($callable, 0, $pos),
                substr($callable, ($callable[$pos + 1] ?? null) === ':' ? $pos + 2 : $pos + 1),
            );
        } elseif (method_exists($callable, '__invoke')) {
            $call = $this->create($callable);
        } else {
            $call = $callable;
        }

        if (is_callable($call)) {
            return $call;
        }

        if (!$throw) {
            throw new \LogicException("Unable to grab callable: {$callable}.");
        }

        if (is_array($call)) {
            $class = is_string($call[0]) ? $call[0] : get_class($call[0]);
            $method = $call[1] ?? '*undefined*';

            throw new \BadMethodCallException("Call to undefined method {$class}::{$method}.");
        }

        throw new \BadFunctionCallException("Call to undefined function {$call}.");
    }

    public function getFactory(string $name, array $rule = null): callable
    {
        if (isset($this->factories[$name])) {
            return $this->factories[$name];
        }

        $this->factories[$name] = $rule['factory'] ?? $this->createFactory($name, $rule);

        if (is_string($this->factories[$name])) {
            $this->factories[$name] = $this->grabCallable($this->factories[$name]);
        }

        return $this->factories[$name];
    }

    public function call($callable, array $arguments = null)
    {
        $call = is_string($callable) ? $this->grabCallable($callable) : $callable;

        return $arguments ? $call(...$arguments) : $call();
    }

    public function callWithResolvedArguments($callable, array $arguments = null)
    {
        $call = is_string($callable) ? $this->grabCallable($callable) : $callable;

        if (is_array($call)) {
            $fun = new \ReflectionMethod(...$call);
        } else {
            $fun = new \ReflectionFunction($call);
        }

        return $call(...$this->resolveArguments($fun, $arguments));
    }

    public function keys(): array
    {
        return array_keys($this->keys);
    }

    public function hive(): array
    {
        return $this->hive;
    }

    public function loadConfiguration(string $file, string $root = null, bool $recursive = true): self
    {
        if ($root) {
            $this[$root] = static::loadFile($file);
        } else {
            $this->merge((array) static::loadFile($file), $recursive);
        }

        return $this;
    }

    public function loadConfigurations(array $files, bool $recursive = true): self
    {
        foreach ($files as $root => $file) {
            if (is_numeric($root)) {
                $this->loadConfiguration($file, null, $recursive);
            } else {
                $this->loadConfiguration($file, $root, $recursive);
            }
        }

        return $this;
    }

    public function merge(array $data, bool $recursive = true): self
    {
        static $map = array(
            'on' => 'on',
            'one' => 'one',
            'off' => 'off',
            'logs' => 'setLogs',
            'header' => 'setHeaders',
        );

        foreach ($data as $key => $value) {
            if (
                is_string($key)
                && (false !== $pos = strpos($key, '.'))
                && ($call = $map[strtolower(substr($key, 0, $pos))] ?? null)
            ) {
                $arguments = array_values((array) $value);
                $arg1 = substr($key, $pos + 1);

                if ($arg1 && '' !== $useArg1 = strstr($arg1 . '#', '#', true)) {
                    array_unshift($arguments, $useArg1);
                }

                $this->$call(...$arguments);
            } elseif (
                $recursive
                && is_array($value)
                && isset($this[$key])
                && is_array($this[$key])
            ) {
                $this[$key] = array_merge_recursive($this[$key], $value);
            } else {
                $this[$key] = $value;
            }
        }

        return $this;
    }

    public function events(): array
    {
        return $this->events;
    }

    public function on(string $event, $handler, int $priority = 0): self
    {
        $this->events[$event][] = array($handler, $priority);

        return $this;
    }

    public function one(string $event, $handler, int $priority = 0): self
    {
        $position = 0;

        if (isset($this->events[$event]) && false !== end($this->events[$event])) {
            $position = key($this->events[$event]) + 1;
        }

        $this->onces[$event][$position] = true;

        return $this->on($event, $handler, $priority);
    }

    public function off(string $event, int $position = null): self
    {
        if (null === $position) {
            unset($this->events[$event], $this->onces[$event]);
        } else {
            unset($this->events[$event][$position], $this->onces[$event][$position]);
        }

        return $this;
    }

    public function dispatch(string $event, Event $argument, bool $once = false): bool
    {
        $events = $this->events[$event] ?? null;

        if (!$events) {
            return false;
        }

        if ($once) {
            $this->off($event);
        }

        usort($events, static function (array $a, array $b) {
            return $b[1] <=> $a[1];
        });

        foreach ($events as $position => list($dispatch)) {
            if ($argument->isPropagationStopped()) {
                break;
            }

            $one = $this->onces[$event][$position] ?? false;

            if ($one) {
                $this->off($event, $position);
            }

            $this->callWithResolvedArguments($dispatch, array($argument));
        }

        return true;
    }

    public function aliases(): array
    {
        return $this->aliases;
    }

    public function routes(): array
    {
        return $this->routes;
    }

    public function build(string $route, array $parameters = null): string
    {
        $path = $this->aliases[$route] ?? null;

        if (!$path) {
            throw new \InvalidArgumentException("Route not found: {$route}.");
        }

        $result = $path;
        $restParameters = $parameters;

        if (false !== strpos($path, '@')) {
            $used = array();
            $defaults = $this->routes[$path][0]['defaults'] ?? null;
            $result = preg_replace_callback('~(?:@([\w:]+)([*])?)~', static function ($match) use ($route, $parameters, $defaults, &$used) {
                list($name) = explode(':', $match[1]);
                $modifier = $match[2] ?? null;
                $value = $parameters[$name] ?? $defaults[$name] ?? null;
                $used[$name] = true;

                if ($modifier) {
                    return is_array($value) ? implode('/', array_map('urlencode', $value)) : urlencode((string) ($value ?? ''));
                }

                if (null === $value || '' === $value) {
                    throw new \InvalidArgumentException("Route parameter is required: {$name}@{$route}.");
                }

                return urlencode((string) $value);
            }, $path);
            $restParameters = $parameters && $used ? array_diff_key($parameters, $used) : $parameters;
        }

        if ($restParameters) {
            $result .= '?' . http_build_query($restParameters);
        }

        return $result;
    }

    public function baseUrl(string $path = null): string
    {
        $result = $this->hive['BASE_URL'];

        if (!$this->hive['BASE_PATH'] || '/' !== $this->hive['BASE_PATH'][0]) {
            $result .= '/';
        }

        $result = rtrim($result . $this->hive['BASE_PATH'], '/');

        if ($path) {
            $result .= rtrim('/' === $path[0] ? $path : '/' . $path, '/');
        }

        return $result;
    }

    public function asset(string $path): string
    {
        if (!$path) {
            throw new \InvalidArgumentException('Empty path!');
        }

        $prefix = '';

        if (!$this->hive['BASE_PATH'] || '/' !== $this->hive['BASE_PATH'][0]) {
            $prefix .= '/';
        }

        $prefix = rtrim($prefix . $this->hive['BASE_PATH'], '/');

        return $prefix . rtrim('/' === $path[0] ? $path : '/' . $path, '/');
    }

    public function path(string $path = null, array $parameters = null): string
    {
        $prefix = '';

        if (!$this->hive['BASE_PATH'] || '/' !== $this->hive['BASE_PATH'][0]) {
            $prefix .= '/';
        }

        $prefix = rtrim($prefix . $this->hive['BASE_PATH'], '/');

        if ($this->hive['ENTRY_SCRIPT'] && $this->hive['ENTRY']) {
            $prefix .= '/' . $this->hive['ENTRY'];
        }

        if ($path && isset($this->aliases[$path])) {
            return $prefix . $this->build($path, $parameters);
        }

        if ($path && '/' !== $path[0]) {
            $prefix .= '/';
        }

        $queryString = $parameters ? '?' . http_build_query($parameters) : null;

        return $prefix . ($path ?? $this->hive['PATH']) . $queryString;
    }

    public function url(string $path = null, array $parameters = null): string
    {
        return $this->hive['BASE_URL'] . $this->path($path, $parameters);
    }

    public function route(string $definition, $controller, array $options = null): self
    {
        if (
            !preg_match(
                '/^(?:\h*)?([\w|]+)(?:\h+([\w+.]+))?\h+([^\h]+)(?:\h*)$/',
                $definition,
                $matches,
                PREG_UNMATCHED_AS_NULL
            )
        ) {
            throw new \InvalidArgumentException("Invalid route: '{$definition}'.");
        }

        $methods = explode('|', strtoupper($matches[1]));
        $alias = $matches[2] ?? null;
        $path = $matches[3];

        if (!$alias && $path && isset($this->aliases[$path])) {
            $alias = null;
            $path = $this->aliases[$path];
        }

        $this->routes[$path][] = compact('methods', 'controller', 'options', 'alias');

        if ($alias) {
            $this->aliases[$alias] = $path;
        }

        return $this;
    }

    public function redirect(string $definition, $url, bool $permanent = true, array $headers = null, array $options = null): self
    {
        return $this->route($definition, static function(Fw $self) use ($url, $permanent, $headers) {
            return $self->reroute($url, $permanent, $headers);
        }, $options);
    }

    public function emulateCliRequest(): self
    {
        if ($this->hive['CLI']) {
            $this->hive['METHOD'] = 'CLI';
            $this->hive['RAW_ARGUMENTS'] = $this->hive['SERVER']['argv'] ?? array($this->hive['ENTRY']);
            $this->hive['ENTRY'] = array_shift($this->hive['RAW_ARGUMENTS']);
        }

        return $this;
    }

    public function findRoute(): ?array
    {
        $found = $this->findSatisfyingRoute();

        return $found ? reset($found) : null;
    }

    public function findSatisfyingRoute(): array
    {
        $method = $this->hive['METHOD'];
        $matchedRoutes = $this->findMatchedRoutes();
        $satisfied = array_filter($matchedRoutes, function (array $route) use ($method) {
            $check = $route['options']['check'] ?? null;

            return (in_array($method, $route['methods']) || in_array('ANY', $route['methods'])) && (!$check || $this->callWithResolvedArguments($check));
        });

        usort($satisfied, static function (array $a, array $b) {
            return intval($b['options']['priority'] ?? 0) <=> intval($a['options']['priority'] ?? 0);
        });

        return $satisfied;
    }

    public function findMatchedRoutes(): array
    {
        if (isset($this->routes[$this->hive['PATH']])) {
            return $this->routes[$this->hive['PATH']];
        }

        foreach ($this->routes as $path => $routes) {
            if ($found = $this->findMatchedRoutesForPath($path, $routes)) {
                return $found;
            }
        }

        return array();
    }

    public function findMatchedRoutesForPath(string $path, array $routes): array
    {
        $found = array();

        foreach ($routes as $route) {
            if (null !== $parameters = $this->routeMatch($path, $route['options']['requirements'] ?? null)) {
                $found[] = $route + compact('parameters');
            }
        }

        return $found;
    }

    public function routeMatch(string $path, array $requirements = null): ?array
    {
        $wild = $path;
        $alls = array();

        if (false !== strpos($path, '@')) {
            $wild = preg_replace_callback('~(?:@([\w:]+)([*])?)~', static function ($match) use ($requirements, &$alls) {
                list($name, $characterClass) = explode(':', $match[1]) + array(1 => null);
                $modifier = $match[2] ?? null;
                $pattern = $requirements[$name] ?? ('*' === $modifier ? '.*' : ($characterClass ? '[[:' . $characterClass . ':]]+' : '[^/]+'));

                if ('*' === $modifier) {
                    $alls[] = $name;
                }

                return "(?<{$name}>{$pattern})";
            }, $path);
        }

        $modifier = ($this->hive['CASELESS'] ?? false) ? 'i' : null;
        $pattern = "~^{$wild}$~{$modifier}";

        if (preg_match($pattern, $this->hive['PATH'], $matches)) {
            $parameters = array_map('static::cast', array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY));

            if ($alls) {
                return array_reduce($alls, static function(array $parameters, string $name) {
                    $parameters[$name] = array_map('static::cast', array_map('urldecode', explode('/', $parameters[$name])));

                    return $parameters;
                }, $parameters);
            }

            return $parameters;
        }

        return null;
    }

    public function run(): void
    {
        $this->execute();
        $this->finishRun();
    }

    public function execute(): self
    {
        try {
            $this->doExecute();
        } catch (\Throwable $e) {
            $this->handleException($e);
        }

        return $this;
    }

    public function mock(string $definition, array $arguments = null, array $headers = null, $body = null, array $merge = null): self
    {
        if (
            !preg_match(
                '/^(?:\h*)?([\w|]+)\h+(?:(\w+)(?:\((.+?)\))?|(.+))(?:\h*)$/',
                $definition,
                $matches,
                PREG_UNMATCHED_AS_NULL
            )
        ) {
            throw new \InvalidArgumentException("Invalid mock pattern: '{$definition}'.");
        }

        $method = strtoupper($matches[1]);
        $route = $matches[2] ?? null;
        $routeParameters = $matches[3] ?? '';
        $path = $matches[4] ?? null;

        if ($route) {
            parse_str($routeParameters, $useRouteParameters);

            $path = $this->path($route, $useRouteParameters);
        }

        // backup
        $backup = array(
            'BODY',
            'GET',
            'HEADER',
            'METHOD',
            'PATH',
            'POST',
        );

        if ($merge) {
            array_push($backup, ...array_keys($merge));
        }

        $this->hive['STACK'][] = array_intersect_key($this->hive, array_fill_keys($backup, true));

        if (false === $pos = strpos($path, '?')) {
            $query = null;
        } else {
            parse_str(substr($path, $pos + 1), $query);

            $path = strstr($path, '?', true);
        }

        $this->hive['METHOD'] = $method;
        $this->hive['PATH'] = $path;
        $this->hive['HEADER'] = $headers;

        if ('GET' === $method || 'HEAD' === $method) {
            $this->hive['GET'] = $arguments ? array_merge($query ?? array(), $arguments) : $query;
            $this->hive['POST'] = null;
            $this->hive['BODY'] = $body;
        } elseif ('POST' === $method) {
            $this->hive['GET'] = $query;
            $this->hive['POST'] = $arguments;
            $this->hive['BODY'] = $body;
        } else {
            $this->hive['GET'] = $query;
            $this->hive['POST'] = null;
            $this->hive['BODY'] = $body ?? http_build_query($arguments ?? array());
        }

        $this->merge($merge ?? array());

        return $this->execute();
    }

    public function reroute($url = null, bool $permanent = false, array $headers = null): self
    {
        if (!$url) {
            $path = $this->path();
        } elseif (is_array($url)) {
            $path = $this->path(...$url);
        } elseif (preg_match('/^(?:([^\/()?#]+)(?:\((.+?)\))*(#.+)*)/', $url, $match) && false === $schemePos = strpos($url, '//')) {
            $route = $match[1];
            parse_str($match[2] ?? '', $parameters);

            $path = $this->path($route, $parameters) . ($match[3] ?? null);
        } elseif (false === $schemePos = ($schemePos ?? strpos($url, '//'))) {
            $path = $this->path($url);
        } else {
            $path = $url;
        }

        $event = new RerouteEvent($path, $url, $permanent, $headers);

        if ($this->dispatch(self::REQUEST_REROUTE, $event) && $event->isResolved()) {
            return $this;
        }

        $isPath = false === ($schemePos ?? false);

        if ($this->hive['CLI'] && $isPath) {
            return $this->mock("GET {$path}");
        }

        $location = $isPath ? $this->url($path) : $path;

        $this->status($permanent ? 301 : 302);
        $this->setHeaders($headers ?? array(), true);
        $this->addHeader('Location', $location, false);

        return $this;
    }

    public function error(int $code, string $message = null, array $headers = null, \Throwable $error = null): self
    {
        $this->removeHeaders();
        $this->status($code, $text);

        $useMessage = $message ?: "HTTP {$code} ({$this->hive['METHOD']} {$this->hive['PATH']})";

        if ($level = $this->logLevel($code)) {
            $context = array('key' => static::hash((string) mt_rand()));
            $this->log($level, $useMessage, $context);

            if ($error) {
                $this->log($error->getTraceAsString(), $useMessage, $context);
            }
        }

        try {
            $event = new RequestErrorEvent($code, $text, $useMessage, $headers, $error);

            if ($this->dispatch(self::REQUEST_ERROR, $event, true) && $event->hasResponse()) {
                return $this->setResponse($event->getResponse());
            }
        } catch (\Throwable $e) {
            $this->handleException($e);

            return $this;
        }

        if ($this->wantsJson()) {
            return $this->setResponse(array(
                'code' => $code,
                'text' => $text,
                'message' => $useMessage,
            ) + ($error && $this->hive['DEBUG'] ? array('trace' => $error->getTraceAsString()) : array()));
        }

        if ($this->hive['CLI']) {
            $trace = $error && $this->hive['DEBUG'] ? PHP_EOL . $error->getTraceAsString() : null;
            $response = <<<CLI
HTTP {$code} ({$text})
{$useMessage}
{$trace}

CLI;
        } else {
            $useMessage = htmlspecialchars($useMessage);
            $trace = $error && $this->hive['DEBUG'] ? '<pre class="trace">' . $error->getTraceAsString() . '</pre>' : null;
            $response = <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Stick error page">
  <meta name="keywords" content="stick, php, framework">
  <meta name="author" content="Eko Kurniawan">
  <title>{$code} {$text}</title>
  <style>
  </style>
</head>
<body>
  <div class="container">
    <h1>[{$code}] {$text}</h1>

    <div class="description">{$useMessage}</div>
    {$trace}
  </div>
</body>
</html>
HTML;
        }

        return $this->setResponse($response);
    }

    public function wantsJson(): bool
    {
        return $this->hive['AJAX'] || false !== strpos($this->hive['CONTENT_MIME'], 'application/json');
    }

    public function status(int $code, string &$text = null): self
    {
        if (!defined($name = 'static::HTTP_' . $code)) {
            throw new \InvalidArgumentException("Unsupported http code: {$code}.");
        }

        $text = constant($name);

        $this->response['code'] = $code;
        $this->response['text'] = $text;

        return $this;
    }

    public function getCode(): int
    {
        return $this->response['code'];
    }

    public function getText(): string
    {
        return $this->response['text'];
    }

    public function getOutput()
    {
        return $this->response['output'];
    }

    public function getHandler()
    {
        return $this->response['handler'];
    }

    public function addCookie(string $name, $value = null, array $options = null, bool $raw = false): self
    {
        $useOptions = ($options ?? array()) + $this->hive['COOKIE_JAR'];
        $cookie = static::cookieCreate($name, $value, $useOptions, $raw);

        $this->hive['COOKIE'][$name] = $value;
        $this->addHeader('Set-Cookie', $cookie);

        return $this;
    }

    public function removeCookie(string $name, array $options = null, bool $raw = false): self
    {
        return $this->addCookie($name, null, $options, $raw);
    }

    public function checkHeader(string $header, string &$key = null): bool
    {
        return isset($this->response['headers'][$header])
            || (($key = $this->response['headers_keys'][$header] ?? strtolower($header)) && isset($this->response['headers'][$key]))
            || (($key = $this->response['headers_keys'][$key] ?? $key) && isset($this->response['headers'][$key]));
    }

    public function hasHeader(string $header): bool
    {
        return $this->checkHeader($header);
    }

    public function getHeader(string $header): ?array
    {
        return $this->response['headers'][$header] ?? ($this->checkHeader($header, $key) ? $this->response['headers'][$key] ?? null : null);
    }

    public function getHeaders(): ?array
    {
        return $this->response['headers'];
    }

    public function addHeaderIfNotExists(string $header, $content): self
    {
        if (!$this->checkHeader($header, $key)) {
            $this->addHeader($key, $content, false);
        }

        return $this;
    }

    public function addHeader(string $header, $content, bool $append = true): self
    {
        if ($this->checkHeader($header, $key) && $append) {
            $this->response['headers'][$key ?? $header][] = $content;
        } else {
            $this->response['headers'][$header] = array($content);
            $this->response['headers_keys'][$key] = $header;
        }

        return $this;
    }

    public function addHeaders(string $header, array $contents, bool $append = true): self
    {
        if ($this->checkHeader($header, $key) && $append) {
            $this->response['headers'][$key ?? $header] = array_merge($this->response['headers'][$key ?? $header], $contents);
        } else {
            $this->response['headers'][$header] = $contents;
            $this->response['headers_keys'][$key] = $header;
        }

        return $this;
    }

    public function setHeaders(array $headers, bool $replace = false): self
    {
        if ($replace) {
            $this->removeHeaders();
        }

        foreach ($headers as $key => $value) {
            $this->addHeaders($key, (array) $value);
        }

        return $this;
    }

    public function removeHeader(string $header): self
    {
        if ($this->checkHeader($header, $key)) {
            unset($this->response['headers'][$header]);

            if ($key) {
                unset($this->response['headers'][$key], $this->response['headers_keys'][$key]);
            }
        }

        return $this;
    }

    public function removeHeaders(): self
    {
        $this->response['headers'] = null;
        $this->response['headers_keys'] = null;

        return $this;
    }

    public function setResponse($response, bool $json = false): self
    {
        if (is_string($response)) {
            $this->response['output'] = $response;

            $this->addHeaderIfNotExists('Content-Type', 'text/html');
            $this->addHeaderIfNotExists('Content-Length', strlen($this->response['output']));
        } elseif (is_callable($response)) {
            $this->response['handler'] = $response;
        } elseif (is_array($response) || $json) {
            $this->response['output'] = is_string($response) ? $response : json_encode($response);

            $this->addHeaderIfNotExists('Content-Type', 'application/json');
            $this->addHeaderIfNotExists('Content-Length', strlen($this->response['output']));
        }

        return $this;
    }

    public function sendHeaders(): self
    {
        if (!$this->response['headers_sent']) {
            $this->response['headers_sent'] = true;

            foreach ($this->response['headers'] ?? array() as $key => $values) {
                $replace = 'Set-Cookie' !== $key && !preg_match('/^set-cookie$/', $key);

                foreach ($values as $value) {
                    header($key . ': ' . $value, $replace, $this->response['code']);
                }
            }

            header($this->hive['PROTOCOL'] . ' ' . $this->response['code'] . ' ' . $this->response['text'], true, $this->response['code']);
        }

        return $this;
    }

    public function sendContent(): self
    {
        if (!$this->response['content_sent']) {
            $this->response['content_sent'] = true;

            if ($this->response['handler']) {
                $this->response['handler']();
            } else {
                echo $this->response['output'];
            }
        }

        return $this;
    }

    public function send(): self
    {
        $this->sendHeaders();
        $this->sendContent();

        return $this;
    }

    public function getLogs(): array
    {
        return $this->logs;
    }

    public function setLogs(array $options): self
    {
        static $internals = array(
            'handle' => true,
            'sqlite' => true,
        );

        $this->logs = array_merge($this->logs, array_intersect_key(array_diff_key($options, $internals), $this->logs));

        if ('sqlite' === $this->logs['mode']) {
            if (!$this->logs['filepath'] && !($this->logs['directory'] && $this->logs['filename'])) {
                throw new \InvalidArgumentException('Sqlite log mode require filepath or directory and filename to be provided.');
            }

            if (!$this->logs['filepath']) {
                is_dir($this->logs['directory']) || mkdir($this->logs['directory'], $this->logs['permission'], true);

                $this->logs['filepath'] = static::normSlash($this->logs['directory'], true) . $this->logs['prefix'] . $this->logs['filename'] . '.' . $this->logs['extension'];
            }

            $createTable = <<<'SQL'
CREATE TABLE IF NOT EXISTS stick_logs (
    log_id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    log_level VARCHAR(16) NOT NULL,
    log_priority SMALLINT NOT NULL,
    log_content TEXT NOT NULL,
    log_context TEXT NULL,
    logged_at DATETIME NOT NULL,
    logged_by VARCHAR(64) NULL
)
SQL;
            $this->logs['sqlite'] = new \PDO("sqlite:{$this->logs['filepath']}");
            $this->logs['sqlite']->exec($createTable);
        } else {
            if (!$this->logs['directory']) {
                throw new \InvalidArgumentException('File log mode require directory to be provided.');
            }

            if (0 === strpos($this->logs['directory'], 'php://')) {
                $this->logs['filepath'] = $this->logs['directory'];
                $this->logs['handle'] = fopen($this->logs['filepath'], 'w+');
            } else {
                is_dir($this->logs['directory']) || mkdir($this->logs['directory'], $this->logs['permission'], true);

                $this->logs['filepath'] = static::normSlash($this->logs['directory'], true) . $this->logs['prefix'] . ($this->logs['filename'] ?? date('Y-m-d')) . '.' . $this->logs['extension'];
                $this->logs['handle'] = fopen($this->logs['filepath'], 'a');
            }
        }

        return $this;
    }

    public function log(string $level, string $message, array $context = null): self
    {
        if (
            isset($this->logs['levels'][$level])
            && $this->logs['levels'][$this->logs['threshold']] >= $this->logs['levels'][$level]
        ) {
            if ('sqlite' === $this->logs['mode']) {
                $this->logSqlite($level, $message, $context);
            } else {
                $this->logWrite($level, $message, $context);
            }
        }

        return $this;
    }

    protected function logWrite(string $level, string $message, array $context = null): void
    {
        if ($this->logs['handle']) {
            if ($this->logs['log_format']) {
                $content = str_replace(array(
                    '{user}',
                    '{date}',
                    '{level}',
                    '{level-padding}',
                    '{priority}',
                    '{message}',
                    '{context}',
                ), array(
                    $this->logs['username'],
                    date($this->logs['date_format']),
                    strtoupper($level),
                    str_repeat(' ', 9 - strlen($level)),
                    $this->logs['levels'][$level],
                    $message,
                    json_encode($context),
                ), $this->logs['log_format']);
            } else {
                $content = '[' . date($this->logs['date_format']) . '] [' . $this->logs['username'] . '] [' . $level . '] ' . $message;
            }

            if ($context && $this->logs['append_context']) {
                $content .= PHP_EOL . $this->logIndent($this->logContext($context));
            }

            $content .= PHP_EOL;

            fwrite($this->logs['handle'], $content);

            $this->logs['line'] = $content;
            $this->logs['count']++;

            if ($this->logs['flush_frequency'] && $this->logs['count'] % $this->logs['flush_frequency'] === 0) {
                fflush($this->logs['handle']);
            }
        }
    }

    protected function logSqlite(string $level, string $message, array $context = null): void
    {
        if ($this->logs['sqlite']) {
            /** @var \PDO */
            $pdo = $this->logs['sqlite'];
            $sql = 'INSERT INTO stick_logs (log_level, log_priority, log_content, log_context, logged_at, logged_by) VALUES (?, ?, ?, ?, ?, ?)';
            $query = $pdo->prepare($sql);
            $query->execute(array(
                $level,
                $this->logs['levels'][$level],
                $message,
                json_encode($context),
                date($this->logs['date_format']),
                $this->logs['username'],
            ));

            $this->logs['line'] = $message;
        }
    }

    protected function logContext(array $context)
    {
        $export = '';

        foreach ($context as $key => $value) {
            $export .= "{$key}: ";
            $export .= preg_replace(array(
                '/=>\s+([a-zA-Z])/im',
                '/array\(\s+\)/im',
                '/^  |\G  /m'
            ), array(
                '=> $1',
                'array()',
                '    '
            ), str_replace('array (', 'array(', var_export($value, true)));
            $export .= PHP_EOL;
        }

        return str_replace(array('\\\\', '\\\''), array('\\', '\''), rtrim($export));
    }

    protected function logIndent(string $context, string $indent = '    '): string
    {
        return $indent . str_replace("\n", "\n" . $indent, $context);
    }

    protected function logLevel(int $code): ?string
    {
        foreach ($this->logs['http_level'] ?? array() as $status => $level) {
            if (
                $code == $status
                || preg_match('/^' . preg_replace('/\D/', '\d', "{$status}") . '/', "{$code}")
            ) {
                return $level;
            }
        }

        return null;
    }

    protected function doExecute(): void
    {
        $event = new RequestEvent();

        if ($this->dispatch(self::REQUEST_REQUEST, $event) && $event->hasResponse()) {
            $response = $event->getResponse();
        } else {
            // TODO: handle CLI Request
            if (!$this->hive['BODY'] && !$this->hive['RAW']) {
                $this->hive['BODY'] = file_get_contents('php://input');
            }

            $route = $this->findRoute();

            if (!$route) {
                throw new HttpException(404);
            }

            $event = new RouteEvent($route);

            if ($this->dispatch(self::REQUEST_ROUTE, $event) && $event->hasResponse()) {
                $response = $event->getResponse();
            } else {
                $controller = $route['controller'];
                $event = new ControllerEvent($controller);

                if ($this->dispatch(self::REQUEST_CONTROLLER, $event) && $event->hasController()) {
                    $controller = $event->getController();
                }

                if (is_string($controller)) {
                    $controller = $this->grabCallable($controller);
                }

                $arguments = $route['parameters'] ?? array();
                $event = new ControllerArgumentsEvent($arguments);

                if ($this->dispatch(self::REQUEST_CONTROLLER_ARGUMENTS, $event) && $event->hasArguments()) {
                    $arguments = $event->getArguments();
                }

                $response = $this->callWithResolvedArguments($controller, $arguments);
                $event = new ResponseEvent($response);

                if ($this->dispatch(self::REQUEST_RESPONSE, $event) && $event->hasResponse()) {
                    $response = $event->getResponse();
                }
            }
        }

        $this->setResponse($response);

        $event = new FinishRequestEvent($route ?? null, $controller ?? null, $arguments ?? null, $response);
        $this->dispatch(self::REQUEST_FINISH, $event);
    }

    protected function finishRun(): void
    {
        try {
            $event = new SendResponseEvent();

            if (!$this->dispatch(self::REQUEST_SEND_RESPONSE, $event, true) && !$event->sent()) {
                $this->send();
            }

            $this->dispatch(self::REQUEST_FINISH_RESPONSE, new FinishResponseEvent(), true);
        } catch (\Throwable $error) {
            $this->handleException($error);
            $this->send();
        }
    }

    protected function handleException(\Throwable $error): void
    {
        $code = 500;
        $message = $error->getMessage();
        $headers = null;

        if ($error instanceof HttpException) {
            $code = $error->getHttpCode();
            $headers = $error->getHttpHeaders();
        }

        $this->error($code, $message, $headers, $error);
    }

    protected function findRule(string $name): ?array
    {
        foreach ($this->rules as $key => $rule) {
            if (
                '*' !== $key
                && (isset($rule['class']) || isset($rule['name']))
                && (
                    (($rule['class'] ?? $rule['name']) === $name)
                    || (
                        is_subclass_of($name, $rule['class'] ?? $rule['name'])
                        && ($rule['inherit'] ?? true)
                    )
                )
            ) {
                return $rule + array('name' => $key);
            }
        }

        return $this->rules['*'] ?? null;
    }

    protected function createFactory(string $name, array $rule = null)
    {
        $useName = $rule['name'] ?? $name;
        $class = new \ReflectionClass($rule['class'] ?? $name);
        $constructor = $class->getConstructor();
        $className = $class->getName();

        if ($constructor) {
            $factory = static function (Fw $self, ?array $arguments) use ($className, $constructor) {
                return new $className(...$self->resolveArguments($constructor, $arguments));
            };
        } else {
            $factory = static function () use ($className) {
                return new $className();
            };
        }

        if ($rule['shared'] ?? false) {
            if (null === $constructor || $class->isInternal()) {
                $factory = static function (Fw $self, ?array $arguments) use ($factory, $useName) {
                    return $self['SERVICE'][$useName] = $factory($self, $arguments);
                };
            } else {
                $factory = static function (Fw $self, ?array $arguments) use ($class, $constructor, $useName) {
                    $self['SERVICE'][$useName] = $class->newInstanceWithoutConstructor();
                    $constructor->invokeArgs($self['SERVICE'][$useName], $self->resolveArguments($constructor, $arguments));

                    return $self['SERVICE'][$useName];
                };
            }
        }

        if ($calls = $rule['calls'] ?? null) {
            $factory = static function (Fw $self, ?array $arguments) use ($calls, $class, $factory) {
                $obj = $factory($self, $arguments);

                foreach ($calls as $call => $callArguments) {
                    if (is_numeric($call)) {
                        $obj->$callArguments(...$self->resolveArguments($class->getMethod($callArguments)));
                    } else {
                        $obj->$call(...$self->resolveArguments($class->getMethod($call), (array) $callArguments));
                    }
                }

                return $obj;
            };
        }

        return $factory;
    }

    protected function resolveArguments(\ReflectionFunctionAbstract $fun, array $namedArguments = null): array
    {
        $arguments = array();
        $named = $namedArguments ?? array();
        $findInstance = static function(Fw $self, ?\ReflectionType $type) use (&$named) {
            if (!$type instanceof \ReflectionNamedType || $type->isBuiltin()) {
                return null;
            }

            $class = $type->getName();

            foreach ($named as $key => $value) {
                if ($value instanceof $class) {
                    return array_splice($named, $key, 1)[0];
                }
            }

            return $self->create($class);
        };

        foreach ($fun->getParameters() as $parameter) {
            if (isset($named[$parameter->name]) || array_key_exists($parameter->name, $named)) {
                if ($parameter->isVariadic() && is_array($named[$parameter->name]) && static::arrIndexed($named[$parameter->name])) {
                    array_push($arguments, ...$named[$parameter->name]);
                } else {
                    $arguments[] = $named[$parameter->name];
                }

                unset($named[$parameter->name]);
            } elseif ($parameter->isVariadic()) {
                array_push($arguments, ...array_values($named));
            } elseif ($argument = $findInstance($this, $parameter->getType())) {
                $arguments[] = $argument;
            } elseif ($named) {
                $arguments[] = array_splice($named, 0, 1)[0];
            } elseif ($parameter->isDefaultValueAvailable()) {
                $arguments[] = $parameter->getDefaultValue();
            } elseif ($parameter->allowsNull()) {
                $arguments[] = null;
            } elseif (!$parameter->isOptional()) {
                $required = $fun->getNumberOfParameters();
                $resolved = count($arguments);

                throw new \ArgumentCountError("{$fun->name} expect at least {$required} parameters, {$resolved} resolved.");
            }
        }

        return $arguments;
    }

    protected function triggerValue($key, string $prefix = 'init'): void
    {
        if (method_exists($this, $trigger = "_{$prefix}_{$key}")) {
            $this->$trigger();
        }
    }

    protected function _set_method_override(): void
    {
        if ($this->hive['METHOD_OVERRIDE'] && ($override = $this->hive['POST'][$this->hive['METHOD_OVERRIDE_KEY']] ?? null)) {
            $this->hive['METHOD'] = $override;
        }
    }

    protected function _set_method_override_key(): void
    {
        $this->_set_method_override();
    }

    protected function _set_tz(): void
    {
        date_default_timezone_set($this->hive['TZ']);
    }

    protected function _init_session(): void
    {
        if (!$this->hive['SESSION_STARTED'] && PHP_SESSION_NONE === session_status()) {
            headers_sent() || session_start();

            $this->hive['SESSION_STARTED'] = true;
            $this->hive['SESSION'] = &$_SESSION[$this->hive['SESSION_KEY']];
        }
    }

    protected function _set_session(): void
    {
        $_SESSION[$this->hive['SESSION_KEY']] = $this->hive['SESSION'];
        session_regenerate_id();
    }

    protected function _unset_session(): void
    {
        $this->hive['SESSION'] = null;
        unset($_SESSION[$this->hive['SESSION_KEY']]);
    }
}

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
 * Service and configuration container
 */
class Container implements \ArrayAccess
{
    /** Mapped PHP globals */
    const GLOBALS = 'GET|POST|COOKIE|REQUEST|SESSION|FILES|SERVER|ENV';

    /** @var array Initial value */
    private $init = [];

    /** @var array Vars hive */
    private $hive;

    /** @var array */
    protected $services = [];

    /** @var array Service aliases */
    protected $aliases = [];

    /** @var array Response headers key map */
    protected $rheaders = [];

    /** @var Closure */
    protected $onUpdate;

    /**
     * Class constructor
     *
     * @param array $hive
     */
    public function __construct(array $hive = [])
    {
        $init = [
            'JAR' => [
                'expire' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true
            ],
            'RHEADERS' => [],
            'SERIALIZER' => extension_loaded('igbinary') ? 'igbinary' : 'php',
            'SERVICE' => [],
            'TZ' => date_default_timezone_get(),
        ];
        foreach ($init['JAR'] as $key => $value) {
            $hive['JAR'][$key] = $hive['JAR'][$key] ?? $value;
        }

        $this->hive = $init + $hive;

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
    }

    /**
     * Set onUpdate
     *
     * @param Closure $onUpdate
     * @return Container
     */
    public function setOnUpdate(\Closure $onUpdate): Container
    {
        $this->onUpdate = $onUpdate;

        return $this;
    }

    /**
     * Add headers
     *
     * @param  array       $headers
     *
     * @return Container
     */
    public function headers(array $headers): Container
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
     * @return Container
     */
    public function header(string $name, string $content = ''): Container
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
     * @return Container
     */
    public function removeHeader(string $name = null): Container
    {
        if ($name) {
            unset(
                $this->hive['RHEADERS'][$name],
                $this->hive['RHEADERS'][ucfirst($name)],
                $this->hive['RHEADERS'][strtolower($name)]
            );
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
        if (in_array($id, ['container', self::class])) {
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
                    $settled = false;
                    foreach (['get', 'is'] as $p) {
                        $get = $p . $part;

                        if (method_exists($var, $get)) {
                            $ref = $var->$get();
                            $var =& $ref;
                            $settled = true;
                            break;
                        }
                    }

                    if (!$settled) {
                        $var =& $null;
                        break;
                    }
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
        $var = $this->ref($key, false);

        return $var ?? $default;
    }

    /**
     * Set to hive
     *
     * @param string $key
     * @param mixed $val
     * @param int $ttl For cookie set
     *
     * @return Container
     */
    public function set(string $key, $val, int $ttl = null): Container
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
            }

            // defaults it's a service
            $val += ['keep' => true];

            if (isset($val['class']) && $val['class'] !== $match[3]) {
                $this->aliases[$val['class']] = $match[3];
            }

            // remove existing service
            $this->services[$match[1]] = null;
        }

        $var =& $this->ref($key);
        $var = $val;

        if (isset($match[4]) && $match[4]) {
            $this->hive['JAR']['expire'] -= microtime(true);
        } else {
            switch ($key) {
                case 'TZ':
                    date_default_timezone_set($val);
                    break;
                case 'SERIALIZER':
                    serialize(null, $val);
                    unserialize(null, $val);
                    break;
            }
        }

        if ($this->onUpdate) {
            $onUpdate = $this->onUpdate;
            $this->onUpdate = null;
            $onUpdate($key, $val, $this);
            $this->onUpdate = $onUpdate;
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
     * @return Container
     */
    public function clear(string $key): Container
    {
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
     * @return Container
     */
    public function sets(array $vars, string $prefix = ''): Container
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
     * @return Container
     */
    public function clears(array $vars): Container
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
     * @return Container
     */
    public function copy(string $src, string $dst): Container
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
     * @return string|Container
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
     * @return Container
     */
    public function push(string $key, $val): Container
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
    protected function grab(string $callback, bool $create = true)
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

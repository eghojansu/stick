<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick;

class Core implements \ArrayAccess
{
    const PACKAGE = 'Stick-Framework';
    const VERSION = 'v0.1.0-beta';

    const ROUTE_PARAMETER_REGEX = '~(?:@(\w+))(?:(\*$)|(?:\(([^\)]+)\)))?~';

    const ON_REQUEST = 'core.request';
    const ON_CONTROLLER_ARGUMENTS = 'core.controller_arguments';
    const ON_VIEW = 'core.view';
    const ON_FINISH = 'core.finish';
    const ON_ERROR = 'core.error';
    const ON_REROUTE = 'core.reroute';
    const ON_SHUTDOWN = 'core.shutdown';

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

    const LOG_EMERGENCY = 0;
    const LOG_ALERT = 1;
    const LOG_CRITICAL = 2;
    const LOG_ERROR = 3;
    const LOG_WARNING = 4;
    const LOG_NOTICE = 5;
    const LOG_INFO = 6;
    const LOG_DEBUG = 7;

    protected $hive;
    protected $init;

    public static function createFromGlobals()
    {
        return new static($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
    }

    public static function hash($text)
    {
        return str_pad(base_convert(substr(sha1($text), -16), 16, 36), 11, '0', STR_PAD_LEFT);
    }

    public static function camelCase($text)
    {
        return str_replace('_', '', lcfirst(ucwords(strtolower($text), '_')));
    }

    public static function snakeCase($text)
    {
        return strtolower(preg_replace('/(?!^)\p{Lu}/u', '_\0', $text));
    }

    public static function className($class): string
    {
        return ltrim(strrchr('\\'.(is_object($class) ? get_class($class) : $class), '\\'), '\\');
    }

    public static function requestHeaders(array $server = null)
    {
        if (!$server) {
            return [];
        }

        $headers = [];
        $contents = [
            'CONTENT_TYPE'   => 'Content-Type',
            'CONTENT_LENGTH' => 'Content-Length',
        ];

        foreach ($server as $key => $value) {
            if (isset($contents[$key])) {
                $headers[$contents[$key]] = $value;
            } elseif (0 === strpos($key, 'HTTP_') && $name = substr($key, 5)) {
                $name = ucwords(str_replace('_', '-', strtolower($name)), '-');
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    public static function normalizeUploadFiles(array $files = null)
    {
        if (!$files) {
            return [];
        }

        $template = ['type', 'size', 'tmp_name', 'error'];
        $normalized = [];

        foreach ($files as $key => $file) {
            if (is_array($file['name'])) {
                foreach ($file['name'] as $position => $value) {
                    $normalized[$key][$position]['name'] = $value;

                    foreach ($template as $pick) {
                        $normalized[$key][$position][$pick] = isset($file[$pick][$position]) ? $file[$pick][$position] : null;
                    }
                }
            } else {
                $normalized[$key] = [$file];
            }
        }

        return $normalized;
    }

    public static function split($var, $delimiter = null)
    {
        if (is_array($var)) {
            return $var;
        }

        if (!$var) {
            return [];
        }

        $pattern = '/['.preg_quote($delimiter ?: ',;|', '/').']/';

        return array_map('trim', preg_split($pattern, $var, 0, PREG_SPLIT_NO_EMPTY));
    }

    public static function join($var, $glue = null)
    {
        return is_array($var) ? implode($glue ?: ',', $var) : (string) $var;
    }

    public static function cast($var)
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

    public function __construct(array $query = null, array $request = null, array $cookie = null, array $files = null, array $server = null)
    {
        $this->initialize($query, $request, $cookie, $files, $server);
    }

    public function initialize(array $query = null, array $request = null, array $cookie = null, array $files = null, array $server = null)
    {
        $cli = in_array(PHP_SAPI, ['cli', 'phpdbg']);
        $headers = static::requestHeaders($server);
        $ajax = isset($headers['X-Requested-With']) && 'XMLHttpRequest' === $headers['X-Requested-With'];
        $script = str_replace('\\', '/', isset($server['SCRIPT_NAME']) ? $server['SCRIPT_NAME'] : $_SERVER['SCRIPT_NAME']);
        $base = dirname($script);
        $front = basename($script);
        $components = parse_url(isset($server['REQUEST_URI']) ? $server['REQUEST_URI'] : '/');
        if (0 === strpos($components['path'], $script)) {
            $path = substr($components['path'], strlen($script)) ?: '/';
        } else {
            $path = $components['path'];
        }
        $verb = isset($server['REQUEST_METHOD']) ? $server['REQUEST_METHOD'] : 'GET';
        $host = isset($server['SERVER_NAME']) ? $server['SERVER_NAME'] : 'localhost';

        if (isset($headers['X-Client-Ip'])) {
            $ip = $headers['X-Client-Ip'];
        } elseif (isset($headers['X-Forwarded-For'])) {
            $ip = strstr($headers['X-Forwarded-For'].',', ',', true);
        } elseif (isset($server['REMOTE_ADDR'])) {
            $ip = $server['REMOTE_ADDR'];
        } else {
            $ip = '';
        }

        if (isset($headers['X-Forwarded-Proto'])) {
            $secure = 'https' === $headers['X-Forwarded-Proto'];
        } elseif (isset($server['HTTPS'])) {
            $secure = 'on' === $server['HTTPS'];
        } else {
            $secure = false;
        }

        if (isset($headers['X-Forwarded-Port'])) {
            $port = $headers['X-Forwarded-Port'];
        } elseif (isset($server['SERVER_PORT'])) {
            $port = $server['SERVER_PORT'];
        } else {
            $port = $secure ? 443 : 80;
        }

        if ($cli) {
            $base = '';
            $front = '';
        }

        $scheme = $secure ? 'https' : 'http';
        $cookieJar = [
            'expire'   => 0,
            'path'     => $base,
            'domain'   => (false === strpos($host, '.') || filter_var($host, FILTER_VALIDATE_IP)) ? '' : $host,
            'secure'   => $secure,
            'httponly' => true,
        ];

        $this->hive = $this->init = [
            'AJAX'              => $ajax,
            'ALIAS'             => null,
            'ALIASES'           => null,
            'ASSETS'            => null,
            'ASSETS_VERSION'    => null,
            'BASE'              => $base,
            'CACHE'             => null,
            'CACHE_ENGINE'      => null,
            'CACHE_REFERENCE'   => null,
            'CASELESS'          => true,
            'CLI'               => $cli,
            'CODE'              => 200,
            'CONTENT'           => null,
            'COOKIE'            => $cookie,
            'COOKIE_JAR'        => $cookieJar,
            'COOKIE_SET'        => null,
            'DEBUG'             => false,
            'ERROR'             => null,
            'FILES'             => $files,
            'FRONT'             => $front,
            'HEADERS'           => $headers,
            'HOST'              => $host,
            'IP'                => $ip,
            'KBPS'              => 0,
            'LANGUAGE'          => null,
            'LANGUAGE_DICT'     => null,
            'LANGUAGE_FALLBACK' => 'en',
            'LANGUAGE_LOCALES'  => null,
            'LOG'               => true,
            'LOG_DIR'           => null,
            'LOG_LEVEL'         => null,
            'LOG_THRESHOLD'     => static::LOG_ERROR,
            'OUTPUT'            => null,
            'PACKAGE'           => static::PACKAGE,
            'PARAMS'            => null,
            'PATH'              => $path,
            'PORT'              => $port,
            'PROTOCOL'          => 'HTTP/1.1',
            'QUERY'             => $query,
            'QUIET'             => false,
            'RAW'               => true,
            'REQUEST'           => $request,
            'RESPONSE'          => null,
            'ROUTE'             => null,
            'ROUTES'            => null,
            'SCHEME'            => $scheme,
            'SECURE'            => $secure,
            'SEED'              => static::hash($base),
            'SENT'              => false,
            'SERVER'            => $server,
            'SERVICE_ALIASES'   => null,
            'SERVICE_RULES'     => null,
            'SERVICES'          => null,
            'SESSION'           => null,
            'STATUS'            => static::HTTP_200,
            'TZ'                => date_default_timezone_get(),
            'VERB'              => $verb,
            'VERSION'           => static::VERSION,
            'XFRAME'            => 'SAMEORIGIN',
        ];

        return $this;
    }

    public function overrideRequestMethod($hack = '_method')
    {
        if (isset($this->hive['HEADERS']['X-Http-Method-Override'])) {
            $this->hive['VERB'] = strtoupper($this->hive['HEADERS']['X-Http-Method-Override']);
        } elseif ($hack && 'POST' === $this->hive['VERB'] && isset($this->hive['REQUEST'][$hack])) {
            $this->hive['VERB'] = strtoupper($this->hive['REQUEST'][$hack]);
        }

        return $this;
    }

    public function emulateCliRequest()
    {
        if ($this->hive['CLI'] && isset($this->hive['SERVER']['argv'])) {
            $argv = $this->hive['SERVER']['argv'] + [1 => '/'];

            if ('/' === $argv[1][0]) {
                $req = $argv[1];
            } else {
                $req = '';
                $opts = '';

                for ($i = count($argv) - 1; $i > 0; $i--) {
                    $arg = $argv[$i];

                    if ('-' === $arg[0]) {
                        $m = explode('=', $arg) + [1 => ''];

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

            $components = parse_url($req);

            $this->hive['VERB'] = 'GET';
            $this->hive['PATH'] = $components['path'];

            if (isset($components['query'])) {
                parse_str($components['query'], $this->hive['QUERY']);
            }
        }

        return $this;
    }

    public function registerErrorHandler()
    {
        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);

        return $this;
    }

    public function unregisterErrorHandler()
    {
        restore_error_handler();
        restore_exception_handler();

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function registerShutdownHandler()
    {
        register_shutdown_function([$this, 'shutdown'], getcwd());

        return $this;
    }

    public function &reference($key, $add = true, array &$var = null, &$found = null)
    {
        $parts = static::split($key, '.');
        $self = null === $var;
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
                $var = [];
                $found = false;
            }

            if (is_array($var) && ($add || $exists = array_key_exists($part, $var))) {
                $found = isset($exists) && $exists;
                $var = &$var[$part];
            } elseif (is_object($var) && ($add || $exists = property_exists($var, $part))) {
                $found = isset($exists) && $exists;
                $var = &$var->$part;
            } else {
                $var = $null;
                $found = false;
                break;
            }

            unset($exists);
        }

        return $var;
    }

    public function deReference($key, array &$var = null)
    {
        $parts = static::split($key, '.');
        $remove = array_pop($parts);
        $self = null === $var;

        if ($self) {
            $var = &$this->hive;
        }

        if ($parts) {
            $var = &$this->reference(implode('.', $parts), true, $var);
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

    public function exists($key)
    {
        $this->reference($key, false, $hive, $found);

        return $found;
    }

    public function get($key)
    {
        return $this->reference($key, false);
    }

    public function set($key, $value)
    {
        $reference = &$this->reference($key);
        $reference = $value;

        if ('CACHE' === $key) {
            $this->cacheLoad();
        } elseif (in_array($key, ['LANGUAGE', 'LANGUAGE_FALLBACK', 'LANGUAGE_LOCALES'])) {
            $this->languageLoad();
        }

        return $this;
    }

    public function clear($key)
    {
        $init = $this->init;
        $reference = $this->reference($key, false, $init, $found);

        if ($found) {
            return $this->set($key, $reference);
        }

        return $this->deReference($key);
    }

    public function allExists($keys)
    {
        foreach (static::split($keys) as $key) {
            if (!$this->exists($key)) {
                return false;
            }
        }

        return true;
    }

    public function allGet($keys, $lowerize = false, array $maps = null)
    {
        $pick = [];

        foreach (static::split($keys) as $key) {
            if (isset($maps[$key])) {
                $newKey = $maps[$key];
            } elseif ($lowerize && is_string($key)) {
                $newKey = strtolower($key);
            } else {
                $newKey = $key;
            }

            $pick[$newKey] = $this->get($key);
        }

        return $pick;
    }

    public function allSet(array $values, $prefix = null)
    {
        foreach ($values as $key => $val) {
            $this->set($prefix.$key, $val);
        }

        return $this;
    }

    public function allClear($keys, $prefix = null)
    {
        foreach (static::split($keys) as $key) {
            $this->clear($prefix.$key);
        }

        return $this;
    }

    public function copy($source, $destination)
    {
        return $this->set($destination, $this->get($source));
    }

    public function cut($source)
    {
        $var = $this->get($source);
        $this->clear($source);

        return $var;
    }

    public function prepend($key, $value, $force = false)
    {
        $var = $this->get($key);

        if ($force) {
            $var = (array) $var;
        }

        if (is_array($var)) {
            array_unshift($var, $value);
        } else {
            $var = $value.$var;
        }

        return $this->set($key, $var);
    }

    public function append($key, $value, $force = false)
    {
        $var = $this->get($key);

        if ($force) {
            $var = (array) $var;
        }

        if (is_array($var)) {
            array_push($var, $value);
        } else {
            $var .= $value;
        }

        return $this->set($key, $var);
    }

    public function config($files)
    {
        $configPattern = '/(?<=^|\n)(?:'.
            '\[(?<section>.+?)\]|'.
            '(?<lval>[^\h\r\n;].*?)\h*=\h*'.
            '(?<rval>(?:\\\\\h*\r?\n|.+?)*)'.
        ')(?=\r?\n|$)/';
        $sectionPattern = '/^(?!(?:global|config|route|controller|rest|redirect)s\b)((?:\.?\w)+)/i';
        $commandPattern = '/^(config|route|controller|rest|redirect)s\b/i';

        foreach (static::split($files) as $file) {
            if (!is_file($file) || !preg_match_all($configPattern, file_get_contents($file), $matches, PREG_SET_ORDER)) {
                continue;
            }

            $section = 'globals';
            $commands = [];

            foreach ($matches as $match) {
                if ($match['section']) {
                    $section = $match['section'];

                    if (preg_match($sectionPattern, $section, $subSections) && !$this->exists($subSections[0])) {
                        $this->set($subSections[0], null);
                    }

                    preg_match($commandPattern, $section, $commands);

                    continue;
                }

                if ($commands) {
                    $arguments = array_merge([$match['lval']], array_map([$this, 'cast'], str_getcsv($match['rval'])));

                    if ('config' === strtolower($commands[1]) && $arguments[1]) {
                        // relative to current configuration file
                        $arguments[0] = dirname($file).'/'.$arguments[0];
                    }

                    call_user_func_array([$this, $commands[1]], $arguments);
                } else {
                    $rval = preg_replace('/\\\\\h*(\r?\n)/', '\1', $match['rval']);
                    $value = array_map(
                        function ($val) {
                            $val = static::cast($val);
                            if (is_string($val)) {
                                $val = $val ? preg_replace('/\\\\"/', '"', $val) : null;
                            }

                            return $val;
                        },
                        // Mark quoted strings with 0x00 whitespace
                        str_getcsv(preg_replace('/(?<!\\\\)(")(.*?)\1/', "\\1\x00\\2\\1", trim($rval)))
                    );
                    $key = $match['lval'];

                    if ('globals' !== strtolower($section)) {
                        $key = $section.'.'.$key;
                    }

                    $this->set($key, count($value) > 1 ? $value : reset($value));
                }
            }
        }

        return $this;
    }

    public function createInstance($id, array $rule = null)
    {
        if (isset($rule['use'])) {
            $ref = new \ReflectionClass($rule['use']);
        } elseif (isset($rule['class'])) {
            $ref = new \ReflectionClass($rule['class']);
        } else {
            $ref = new \ReflectionClass($id);
        }

        if (!$ref->isInstantiable()) {
            throw new \LogicException(sprintf('Cannot instantiate %s (%s)', $ref->name, $id));
        }

        if (isset($rule['factory'])) {
            $instance = $this->call($rule['factory']);

            if (!$instance instanceof $ref->name) {
                throw new \LogicException(sprintf('Factory should return instance of %s (%s)', $ref->name, $id));
            }
        } elseif ($ref->hasMethod('__construct')) {
            $arguments = isset($rule['arguments']) ? $rule['arguments'] : null;
            $instance = $ref->newInstanceArgs($this->resolveArguments($ref->getMethod('__construct'), $arguments));
        } else {
            $instance = $ref->newInstance();
        }

        if (isset($rule['boot'])) {
            $this->call($rule['boot'], [$instance, $this]);
        }

        return $instance;
    }

    public function rule($id, $rule)
    {
        unset($this->hive['SERVICES'][$id]);

        if (is_callable($rule)) {
            $this->hive['SERVICE_RULES'][$id] = ['factory' => $rule];
        } elseif (is_object($rule)) {
            $this->hive['SERVICE_RULES'][$id] = ['class' => get_class($rule)];
            $this->hive['SERVICES'][$id] = $rule;
        } elseif (is_string($rule)) {
            $this->hive['SERVICE_RULES'][$id] = ['class' => $rule];
        } else {
            $this->hive['SERVICE_RULES'][$id] = (array) $rule;
        }

        $this->hive['SERVICE_RULES'][$id] += ['class' => $id, 'shared' => false !== $rule];

        if ($this->hive['SERVICE_RULES'][$id]['class'] !== $id) {
            $this->hive['SERVICE_ALIASES'][$id] = $this->hive['SERVICE_RULES'][$id]['class'];
        }

        return $this;
    }

    public function service($id, $service = true)
    {
        if (in_array($id, ['core', static::class])) {
            return $this;
        }

        if (isset($this->hive['SERVICES'][$id]) && $service) {
            return $this->hive['SERVICES'][$id];
        }

        $sid = $id;

        if ($this->hive['SERVICE_ALIASES'] && $service && $xid = array_search($id, $this->hive['SERVICE_ALIASES'])) {
            if (isset($this->hive['SERVICES'][$xid])) {
                return $this->hive['SERVICES'][$xid];
            }

            $sid = $xid;
        }

        $rule = isset($this->hive['SERVICE_RULES'][$sid]) ? $this->hive['SERVICE_RULES'][$sid] : ['shared' => false];
        $instance = $this->createInstance($id, $rule);

        if ($service && isset($rule['shared']) && $rule['shared']) {
            $this->hive['SERVICES'][$sid] = $instance;
        }

        return $instance;
    }

    public function call(callable $callback, array $arguments = null)
    {
        return call_user_func_array($callback, $this->resolveArguments($callback, $arguments));
    }

    public function chain(callable $callback)
    {
        $this->call($callback);

        return $this;
    }

    public function grab($expression)
    {
        if (2 === count($parts = explode('->', $expression))) {
            return [$this->service($parts[0]), $parts[1]];
        }

        if (2 === count($parts = explode('::', $expression))) {
            return $parts;
        }

        return $expression;
    }

    public function route($route, $handler)
    {
        $rule = '~^([\w+|]+)(?:\h+(\w+))?(?:\h+(/[^\h]*))?(?:\h+(ajax|cli|sync))?(?:\h+(\d+))?(?:\h+(\d+))?$~';

        preg_match($rule, trim($route), $match, PREG_UNMATCHED_AS_NULL);

        if (count($match) < 3) {
            throw new \LogicException(sprintf('Invalid route: "%s"', $route));
        }

        $alias = isset($match[2]) ? $match[2] : null;

        if (isset($match[3])) {
            $pattern = $match[3];
        } else {
            if (!isset($this->hive['ALIASES'][$alias])) {
                throw new \LogicException(sprintf('Route not exists: "%s"', $alias));
            }

            $pattern = $this->hive['ALIASES'][$alias];
        }

        $mode = isset($match[4]) ? $match[4] : 'all';
        $definition = [
            $handler, $alias,
            isset($match[5]) ? $match[5] : 0,
            isset($match[6]) ? $match[6] : 0,
        ];

        foreach (array_filter(explode('|', strtoupper($match[1]))) as $verb) {
            $this->hive['ROUTES'][$pattern][$mode][$verb] = $definition;
        }

        if ($alias) {
            $this->hive['ALIASES'][$alias] = $pattern;
        }

        return $this;
    }

    public function controller($class, array $routes)
    {
        foreach ($routes as $route => $method) {
            $this->route($route, $class.'->'.$method);
        }

        return $this;
    }

    public function rest($route, $class)
    {
        $itemRoute = preg_replace_callback('~^(?:(\w+)\h+)?(/[^\h]*)~', function ($match) {
            return ($match[1] ? $match[1].'_item' : '').' '.$match[2].'/@item';
        }, $route);

        return $this
            ->route('GET '.$route, $class.'->all')
            ->route('POST '.$route, $class.'->create')
            ->route('GET '.$itemRoute, $class.'->get')
            ->route('PUT '.$itemRoute, $class.'->put')
            ->route('DELETE '.$itemRoute, $class.'->delete');
    }

    public function redirect($route, $target, bool $permanent = true)
    {
        return $this->route($route, function () use ($target, $permanent) {
            return $this->reroute($target, $permanent);
        });
    }

    public function alias($alias, $parameters = null)
    {
        if (isset($this->hive['ALIASES'][$alias])) {
            $pattern = $this->hive['ALIASES'][$alias];

            if (false === strpos($pattern, '@')) {
                return $pattern;
            }

            $mParameters = (array) $parameters;

            if (is_string($parameters)) {
                parse_str($parameters, $mParameters);
            }

            return preg_replace_callback(static::ROUTE_PARAMETER_REGEX, function ($match) use (&$mParameters) {
                $name = $match[1];
                $parameter = isset($mParameters[$name]) ? $mParameters[$name] : null;

                if (isset($match[2]) && $match[2]) {
                    if (!$parameter) {
                        $parameter = array_values($mParameters);
                    }

                    $mParameters = [];
                }

                if (!$parameter) {
                    throw new \LogicException(sprintf('Parameter "%s" should be provided', $name));
                }

                if (isset($match[3]) && is_string($parameter) && !preg_match('~^'.$match[3].'$~', $parameter)) {
                    throw new \LogicException(sprintf('Parameter "%s" is not valid, given: "%s"', $name, $parameter));
                }

                unset($mParameters[$name]);

                return is_array($parameter) ? implode('/', array_map('urlencode', $parameter)) : urlencode($parameter);
            }, $pattern);
        }

        return '/'.ltrim($alias, '/');
    }

    public function asset($path)
    {
        $use = isset($this->hive['ASSETS'][$path]) ? $this->hive['ASSETS'][$path] : $path;
        $suffix = null;

        if ($version = $this->hive['ASSETS_VERSION']) {
            $suffix = '?'.('dynamic' === $version ? time() : $version);
        }

        return $this->hive['BASE'].'/'.$use.$suffix;
    }

    public function path($alias, $parameters = null, $query = null)
    {
        $prefix = rtrim('/'.$this->hive['FRONT'], '/');
        $suffix = rtrim('?'.(is_array($query) ? http_build_query($query) : $query), '?');

        return $this->hive['BASE'].$prefix.$this->alias($alias, $parameters).$suffix;
    }

    public function on($event, $handler, $priority = 0, $once = false)
    {
        $ref = &$this->reference('EVENTS.'.$event);
        $ref[] = [$handler, $once, $priority];

        if (count($ref) > 1) {
            usort($ref, function ($a, $b) {
                if ($a[2] === $b[2]) {
                    return 0;
                }

                return $a[2] < $b[2] ? 1 : -1;
            });
        }

        return $this;
    }

    public function off($event, $key = null)
    {
        return $this->deReference('EVENTS.'.$event.'.'.$key);
    }

    public function one($event, $handler, $priority = 0)
    {
        return $this->on($event, $handler, $priority, true);
    }

    public function dispatch($event, Event $argument, $off = false)
    {
        if (!$handlers = $this->reference($key = 'EVENTS.'.$event)) {
            return $this;
        }

        foreach ($handlers as $key => $handler) {
            list($call, $once) = $handler;

            if (is_string($call)) {
                $call = $this->grab($call);
            }

            if (!is_callable($call)) {
                throw new \LogicException(sprintf('Handler is not callable: "%s"', $event));
            }

            if ($once) {
                $this->off($event, $key);
            }

            $call($argument);

            if ($argument->isPropagationStopped()) {
                break;
            }
        }

        return $off ? $this->off($event) : $this;
    }

    public function log($level, $message)
    {
        if (!$this->hive['LOG'] || !$this->hive['LOG_DIR'] || $level > $this->hive['LOG_THRESHOLD']) {
            return $this;
        }

        $prefix = $this->hive['LOG_DIR'].'log_';
        $suffix = '.log';
        $files = glob($prefix.date('Y-m').'*'.$suffix);

        $file = isset($files[0]) ? $files[0] : $prefix.date('Y-m-d').$suffix;
        $content = date('Y-m-d G:i:s.u').' '.$level.' '.$message.PHP_EOL;

        is_dir($dir = dirname($file)) || mkdir($dir, 0755, true);
        file_put_contents($file, $content, FILE_APPEND);

        return $this;
    }

    public function logFiles($from = null, $to = null)
    {
        if (!$this->hive['LOG_DIR']) {
            return [];
        }

        $pattern = $this->hive['LOG_DIR'].'log_*.log';
        $files = glob($pattern);

        if (!$from) {
            return $files;
        }

        $fromTime = strtotime($from);
        $toTime = $to ? strtotime($to) : $fromTime;

        if (!$fromTime || !$toTime) {
            return [];
        }

        $filteredFiles = [];
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

    public function cookie($name, $value = '', $expires = null, $raw = false, $path = null, $domain = null, $secure = null, $httponly = null)
    {
        $this->hive['COOKIE_SET'][$name] = [
            'call'      => $raw ? 'setrawcookie' : 'setcookie',
            'arguments' => [
                $name,
                $value,
                isset($expires) ? $expires : $this->hive['COOKIE_JAR']['expire'],
                isset($path) ? $path : $this->hive['COOKIE_JAR']['path'],
                isset($domain) ? $domain : $this->hive['COOKIE_JAR']['domain'],
                isset($secure) ? $secure : $this->hive['COOKIE_JAR']['secure'],
                isset($httponly) ? $httponly : $this->hive['COOKIE_JAR']['httponly'],
            ],
        ];

        return $this;
    }

    public function cookieRemove($name)
    {
        return $this->cookie($name, '', strtotime('last month'));
    }

    public function status(int $code)
    {
        $name = 'static::HTTP_'.$code;

        if (!defined($name)) {
            throw new \DomainException(sprintf('Unsupported HTTP code: %d', $code));
        }

        $this->hive['CODE'] = $code;
        $this->hive['STATUS'] = constant($name);

        return $this;
    }

    public function expire($seconds = 0)
    {
        $headers = &$this->hive['RESPONSE'];

        $headers['X-Powered-By'] = $this->hive['PACKAGE'].'-'.$this->hive['VERSION'];
        $headers['X-Frame-Options'] = $this->hive['XFRAME'];
        $headers['X-XSS-Protection'] = '1; mode=block';
        $headers['X-Content-Type-Options'] = 'nosniff';

        if ('GET' === $this->hive['VERB'] && $seconds) {
            unset($headers['Pragma']);

            $headers['Cache-Control'] = 'max-age='.$seconds;
            $headers['Expires'] = gmdate('r', time() + $seconds);
            $headers['Last-Modified'] = gmdate('r');
        } else {
            $headers['Pragma'] = 'no-cache';
            $headers['Cache-Control'] = 'no-cache, no-store, must-revalidate';
            $headers['Expires'] = gmdate('r', 0);
        }

        unset($headers);

        return $this;
    }

    public function sendHeaders()
    {
        if ($this->hive['CLI']) {
            return $this;
        }

        header_remove();

        foreach ((array) $this->hive['COOKIE_SET'] as $cookie) {
            if (isset($cookie['call'], $cookie['arguments'])) {
                call_user_func_array($cookie['call'], $cookie['arguments']);
            }
        }

        foreach ((array) $this->hive['RESPONSE'] as $header => $content) {
            foreach (is_array($content) ? $content : [$content] as $row) {
                header($header.': '.$row);
            }
        }

        header($this->hive['PROTOCOL'].' '.$this->hive['CODE'].$this->hive['STATUS'], true);

        return $this;
    }

    public function sendContent()
    {
        if ($this->hive['QUIET'] || !is_scalar($this->hive['OUTPUT'])) {
            return $this;
        }

        if ($this->hive['KBPS'] <= 0) {
            echo $this->hive['OUTPUT'];

            return;
        }

        $kbps = $this->hive['KBPS'];
        $now = microtime(true);
        $ctr = 0;

        foreach (str_split($this->hive['OUTPUT'], 1024) as $part) {
            // Throttle output
            $ctr++;

            if ($ctr / $kbps > ($elapsed = microtime(true) - $now) && !connection_aborted()) {
                usleep((int) (1e6 * ($ctr / $kbps - $elapsed)));
            }

            echo $part;
        }

        return $this;
    }

    public function send()
    {
        if (!$this->hive['SENT']) {
            $this->hive['SENT'] = true;

            $this->sendHeaders();
            $this->sendContent();
        }

        return $this;
    }

    public function stringify($argument)
    {
        if (is_object($argument)) {
            return 'Object('.get_class($argument).')';
        }

        if (is_array($argument)) {
            return '['.implode(', ', array_map([$this, 'stringify'], $argument)).']';
        }

        return var_export($argument, true);
    }

    public function trace(array $trace = null)
    {
        $text = '';

        if (null === $trace) {
            $trace = debug_backtrace();
            array_shift($trace);
        }

        foreach ($trace as $row) {
            $text .= PHP_EOL.'['.$row['file'].':'.$row['line'].']';

            if (isset($row['function'])) {
                $text .= ' ';

                if (isset($row['class'])) {
                    $text .= $row['class'].$row['type'];
                }

                $text .= $row['function'].'(';

                if ($row['args']) {
                    $text .= implode(', ', array_map([$this, 'stringify'], $row['args']));
                }

                $text .= ')';
            }
        }

        return ltrim($text);
    }

    public function error($code, $message = null, array $trace = null)
    {
        if ($this->hive['ERROR']) {
            return $this;
        }

        $this->allClear('SENT,RESPONSE,OUTPUT,KBPS');
        $this->status($code);
        $this->expire();

        $this->hive['ERROR'] = [
            'code'   => $this->hive['CODE'],
            'status' => $this->hive['STATUS'],
            'text'   => $message ?: sprintf('%s %s (%s %s)', $this->hive['VERB'], $this->hive['PATH'], $this->hive['CODE'], $this->hive['STATUS']),
            'trace'  => $this->hive['DEBUG'] ? $this->trace($trace) : null,
        ];

        $logLevel = isset($this->hive['LOG_LEVEL'][$code]) ? $this->hive['LOG_LEVEL'][$code] : static::LOG_WARNING;
        $logMessage = trim($this->hive['ERROR']['text'].PHP_EOL.$this->hive['ERROR']['trace']);
        $this->log($logLevel, $logMessage);

        $event = new Event\GetResponseForErrorEvent($this, $this->hive['ERROR']);
        $this->dispatch(static::ON_ERROR, $event, true);

        if ($event->isPropagationStopped()) {
            return $this->send();
        }

        if ($this->hive['AJAX']) {
            $this->hive['OUTPUT'] = json_encode($this->hive['ERROR']);
            $this->hive['RESPONSE']['Content-Type'] = 'application/json';
            $this->hive['RESPONSE']['Content-Length'] = strlen($this->hive['OUTPUT']);
        } else {
            $this->hive['OUTPUT'] = '<!DOCTYPE html>'.
                '<html lang="en">'.
                    '<head>'.
                        '<meta charset="UTF-8">'.
                        '<title>'.$this->hive['CODE'].' '.$this->hive['STATUS'].'</title>'.
                    '</head>'.
                    '<body>'.
                        '<h1>'.$this->hive['CODE'].' - '.$this->hive['STATUS'].'</h1>'.
                        '<p>'.$this->hive['ERROR']['text'].'</p>'.
                        '<pre>'.$this->hive['ERROR']['trace'].'</pre>'.
                    '</body>'.
                '</html>';
            $this->hive['RESPONSE']['Content-Type'] = 'text/html';
            $this->hive['RESPONSE']['Content-Length'] = strlen($this->hive['OUTPUT']);
        }

        return $this->send();
    }

    public function run()
    {
        $event = new Event\GetResponseEvent($this);
        $this->dispatch(static::ON_REQUEST, $event);

        if ($event->isPropagationStopped()) {
            return $this->send();
        }

        if (!$route = $this->findRoute()) {
            return $this->hive['SENT'] ? $this : $this->error(404);
        }

        list($handler, $alias, $ttl, $kbps, $arguments, $pattern) = $route;

        $this->hive['ALIAS'] = $alias;
        $this->hive['ROUTE'] = $pattern;
        $this->hive['KBPS'] = $kbps;
        $this->hive['PARAMS'] = $arguments;

        $hash = static::hash($this->hive['VERB'].' '.$this->hive['PATH']).'.url';
        $cacheCheck = $ttl && in_array($this->hive['VERB'], ['GET', 'HEAD']);

        if ($cacheCheck) {
            if ($cache = $this->cacheGet($hash)) {
                $time = time();
                $modified = isset($this->hive['HEADERS']['If-Modified-Since']) ? strtotime($this->hive['HEADERS']['If-Modified-Since']) : 0;

                if ($modified + $ttl > $time) {
                    $this->status(304);

                    return $this->send();
                }

                $seconds = $cache->getTtl() + $ttl - $time;
                list($code, $headers, $content) = $cache->getValue();

                $this->status($code);
                $this->expire($seconds);
                $this->hive['RESPONSE'] = $headers;
                $this->hive['OUTPUT'] = $content;

                return $this->send();
            }

            $this->expire($ttl);
        } else {
            $this->expire(0);
        }

        if (is_string($handler)) {
            $handler = $this->grab($handler);
        }

        if (!is_callable($handler)) {
            return $this->error(500);
        }

        $event = new Event\GetControllerArgumentsEvent($this, $handler, $arguments);
        $this->dispatch(static::ON_CONTROLLER_ARGUMENTS, $event);

        if ($event->isPropagationStopped()) {
            return $this->hive['SENT'] ? $this : $this->error(500);
        }

        $result = $this->call($event->getController(), $event->getArguments());

        $event = new Event\FilterControllerResultEvent($this, $result);
        $this->dispatch(static::ON_VIEW, $event);

        if (!$event->isPropagationStopped()) {
            $response = $event->getResult();

            if (is_callable($response)) {
                $response($this);
            } elseif (is_string($response)) {
                $this->hive['OUTPUT'] = $response;
            } elseif (is_array($response)) {
                $this->hive['OUTPUT'] = json_encode($response);
            }
        }

        $this->send();

        if ($cacheCheck) {
            $this->cacheSet($hash, new CacheItem([
                $this->hive['CODE'],
                $this->hive['RESPONSE'],
                $this->hive['OUTPUT'],
            ], $ttl));
        }

        $event = new Event\PostResponseEvent($this);
        $this->dispatch(static::ON_FINISH, $event);

        return $this;
    }

    public function mock($expression, array $arguments = null, $content = null)
    {
        $mockPattern = '~^(\w+)\h+([^\h?]+)(\?[^\h]+)?(?:\h+(ajax|cli|sync))?$~';

        if (!preg_match($mockPattern, trim($expression), $match)) {
            throw new \LogicException(sprintf('Invalid mock expression: "%s"', $expression));
        }

        $verb = strtoupper($match[1]);
        $target = $match[2];
        $mode = isset($match[4]) ? $match[4] : null;
        $path = $target;

        parse_str(isset($match[3]) ? substr($match[3], 1) : '', $query);

        if (isset($this->hive['ALIASES'][$target])) {
            $path = $this->hive['ALIASES'][$target];
        } elseif (preg_match('/^(\w+)\(([^(]+)\)$/', $target, $match)) {
            parse_str(strtr($match[2], ',', '&'), $routeArguments);
            $path = $this->alias($match[1], $routeArguments);
        }

        $this->allClear('SENT,RESPONSE,OUTPUT,CONTENT,KBPS');

        $this->hive['VERB'] = $verb;
        $this->hive['PATH'] = $path;
        $this->hive['AJAX'] = 'ajax' === $mode;
        $this->hive['CLI'] = 'cli' === $mode;
        $this->hive['CONTENT'] = $content;
        $this->hive['RAW'] = !$content;
        $this->hive['QUERY'] = null;
        $this->hive['REQUEST'] = null;

        if ('POST' === $verb) {
            $this->hive['QUERY'] = $query;
            $this->hive['REQUEST'] = $arguments;
        } elseif (in_array($verb, ['GET', 'HEAD'])) {
            $this->hive['QUERY'] = array_merge((array) $arguments, $query);
            $this->hive['REQUEST'] = null;
        } elseif (!$content && $arguments) {
            $this->hive['CONTENT'] = http_build_query($arguments);
        }

        return $this->run();
    }

    public function reroute($target = null, $permanent = false)
    {
        $self = true;

        if (!$target) {
            $path = $this->hive['PATH'];
            $url = $this->path($path);
        } elseif (is_array($target)) {
            $path = call_user_func_array([$this, 'alias'], $target);
            $url = $this->path($path, null, isset($target[2]) ? $target[2] : null);
        } elseif (isset($this->hive['ALIASES'][$target])) {
            $path = $this->hive['ALIASES'][$target];
            $url = $this->path($path);
        } elseif (preg_match('/^(\w+)(?:\(([^(]+)\))?(\?*+)?$/', $target, $match)) {
            parse_str(strtr(isset($match[2]) ? $match[2] : '', ',', '&'), $parameters);
            $path = $this->alias($match[1], $parameters);
            $url = $this->path($path, null, isset($match[3]) ? $match[3] : null);
        } else {
            $components = parse_url($target);
            $self = empty($components['scheme']) || 0 === strpos($components['path'], $this->hive['BASE']);
            $path = $target;
            $url = $self ? $this->path($path) : $path;
        }

        $event = new Event\RerouteEvent($this, $url, $permanent);
        $this->dispatch(static::ON_REROUTE, $event, true);

        if ($event->isPropagationStopped()) {
            return $this->send();
        }

        if ($self && $this->hive['CLI']) {
            return $this->mock('GET '.$path.' cli');
        }

        $this->status($permanent ? 301 : 302);
        $this->allSet([
            'OUTPUT'   => null,
            'RESPONSE' => ['Location' => $url],
        ]);

        return $this->send();
    }

    public function handleError($errno, $errstr, $errfile, $errline)
    {
        $this->error(500, $errstr, [[
            'file' => $errfile,
            'line' => $errline,
        ]]);
    }

    public function handleException($exception)
    {
        $this->error(500, $exception->getMessage(), $exception->getTrace());
    }

    public function shutdown($cwd)
    {
        chdir($cwd);

        $error = error_get_last();
        $event = new Event\ShutdownEvent($this, $cwd, $error);
        $this->dispatch(static::ON_SHUTDOWN, $event, true);

        if ($event->isPropagationStopped() || !$error || !($error['type'] & error_reporting())) {
            return;
        }

        // @codeCoverageIgnoreStart
        $this->error(500, $error['message'], [$error]);
        // @codeCoverageIgnoreEnd
    }

    public function trans($message, array $parameters = null, $fallback = null)
    {
        $ref = $this->languageReference($message) ?: ($fallback ?: $message);

        return strtr($ref, (array) $parameters);
    }

    public function choice($message, $count, array $parameters = null, $fallback = null)
    {
        $parameters['#'] = $count;
        $ref = $this->languageReference($message) ?: ($fallback ?: $message);

        foreach (explode('|', $ref) as $key => $choice) {
            if ($count <= $key) {
                return strtr($choice, $parameters);
            }
        }

        return strtr($choice, $parameters);
    }

    public function transAlt(array $messages, array $parameters = null)
    {
        foreach ($messages as $message) {
            if (null !== $ref = $this->languageReference($message)) {
                return strtr($ref, (array) $parameters);
            }
        }

        return end($messages);
    }

    public function cacheExists($name)
    {
        $key = $this->hive['SEED'].$name;

        switch ($this->hive['CACHE_ENGINE']) {
            case 'apc':
                return apc_exists($key);
            case 'apcu':
                return apcu_exists($key);
            case 'filesystem':
                if ($content = $this->cacheFile($key)) {
                    ($exists = CacheItem::fromString($content)->isValid()) || $this->cacheClear($name);

                    return $exists;
                }

                return false;
            case 'memcache':
            case 'memcached':
                return (bool) $this->hive['CACHE_REFERENCE']->get($key);
            case 'redis':
                return $this->hive['CACHE_REFERENCE']->exists($key);
            default:
                return false;
        }
    }

    public function cacheGet($name)
    {
        $key = $this->hive['SEED'].$name;
        $raw = '';

        switch ($this->hive['CACHE_ENGINE']) {
            case 'apc':
                $raw = apc_fetch($key);
                break;
            case 'apcu':
                $raw = apcu_fetch($key);
                break;
            case 'filesystem':
                if ($raw = $this->cacheFile($key)) {
                    $cache = CacheItem::fromString($raw);

                    if ($cache->isValid()) {
                        return $cache;
                    }
                }

                $this->cacheClear($name);

                return;
            case 'memcache':
            case 'memcached':
            case 'redis':
                $raw = $this->hive['CACHE_REFERENCE']->get($key);
                break;
        }

        return $raw ? CacheItem::fromString($raw) : null;
    }

    public function cacheSet($name, CacheItem $item)
    {
        $key = $this->hive['SEED'].$name;
        $ttl = $item->getTtl();
        $raw = $item->toString();

        switch ($this->hive['CACHE_ENGINE']) {
            case 'apc':
                return apc_store($key, $raw, $ttl);
            case 'apcu':
                return apcu_store($key, $raw, $ttl);
            case 'filesystem':
                return $this->cacheFile($key, $raw);
            case 'memcache':
                $this->cacheMemcache($key);

                return $this->hive['CACHE_REFERENCE']->set($key, $raw, MEMCACHE_COMPRESSED, $ttl);
            case 'memcached':
                $this->cacheMemcache($key);

                return $this->hive['CACHE_REFERENCE']->set($key, $raw, $ttl);
            case 'redis':
                return $this->hive['CACHE_REFERENCE']->set($key, $raw, array_filter(['ex' => $ttl]));
            default:
                return false;
        }
    }

    public function cacheClear($name)
    {
        $key = $this->hive['SEED'].$name;

        switch ($this->hive['CACHE_ENGINE']) {
            case 'apc':
                return apc_delete($key);
            case 'apcu':
                return apcu_delete($key);
            case 'filesystem':
                return $this->cacheFile($key, null, true);
            case 'memcache':
            case 'memcached':
                $this->cacheMemcache($key, true);

                return $this->hive['CACHE_REFERENCE']->delete($key);
            case 'redis':
                return (bool) $this->hive['CACHE_REFERENCE']->del($key);
            default:
                return false;
        }
    }

    public function cacheReset($suffix = null)
    {
        $prefix = $this->hive['SEED'];
        $pattern = '/^'.preg_quote($prefix, '/').'.+'.preg_quote($suffix ?: '', '/').'$/';
        $call = null;
        $items = [];

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
                $call = [$this->hive['CACHE_REFERENCE'], 'delete'];
                // cachedump support has been removed, so we only can use hack way
                $items = $this->cacheMemcache(null, null, $items);
                break;
            case 'memcached':
                $call = [$this->hive['CACHE_REFERENCE'], 'delete'];
                $items = preg_grep($pattern, $this->hive['CACHE_REFERENCE']->getAllKeys());
                // cachedump support has been removed, so we only can use hack way
                $items = $this->cacheMemcache(null, null, $items);
                break;
            case 'redis':
                $call = [$this->hive['CACHE_REFERENCE'], 'del'];
                $items = $this->hive['CACHE_REFERENCE']->keys($prefix.'*'.$suffix);
                break;
        }

        $iterator = $items instanceof \Iterator;
        $affected = 0;

        foreach ($call ? $items : [] as $item) {
            $call($iterator ? $item['key'] : $item);
            $affected++;
        }

        return $affected;
    }

    protected function findRoute()
    {
        $path = urldecode($this->hive['PATH']);
        $modifier = $this->hive['CASELESS'] ? 'i' : '';
        $verb = $this->hive['VERB'];
        $mode = $this->requestMode();

        if (empty($this->hive['ROUTES'])) {
            throw new \LogicException('No route defined');
        }

        foreach ($this->hive['ROUTES'] as $pattern => $routes) {
            if ($this->routeMatch($path, $pattern, $modifier, $arguments)) {
                if (isset($routes[$m = $mode]) || isset($routes[$m = 'all'])) {
                    if (isset($routes[$m][$verb])) {
                        return $routes[$m][$verb] + [4 => $arguments, $pattern];
                    }
                }

                $this->error(403);
                break;
            }
        }
    }

    protected function routeMatch($path, $pattern, $modifier, array &$arguments = null)
    {
        $lastParameter = null;
        $arguments = null;

        if (false !== strpos($pattern, '@')) {
            $pattern = preg_replace_callback(static::ROUTE_PARAMETER_REGEX, function ($match) use (&$lastParameter) {
                $name = $match[1];
                $pattern = isset($match[3]) ? $match[3] : '[^/]+';

                if (isset($match[2]) && $match[2]) {
                    // all
                    $pattern = '.+';
                    $lastParameter = $name;
                }

                return '(?<'.$name.'>'.$pattern.')';
            }, $pattern);
        }

        if (preg_match('~^'.$pattern.'$~'.$modifier, $path, $match)) {
            $arguments = array_filter($match, 'is_string', ARRAY_FILTER_USE_KEY);

            if ($lastParameter) {
                $arguments[$lastParameter] = explode('/', $arguments[$lastParameter]);
            }

            return true;
        }

        return false;
    }

    protected function requestMode()
    {
        if ($this->hive['AJAX']) {
            return 'ajax';
        }

        if ($this->hive['CLI']) {
            return 'cli';
        }

        return 'sync';
    }

    protected function resolveArguments($callable, array $arguments = null)
    {
        if ($callable instanceof \ReflectionFunctionAbstract) {
            $method = $callable;
        } elseif (is_array($callable)) {
            $method = new \ReflectionMethod(reset($callable), next($callable));
        } else {
            $method = new \ReflectionFunction($callable);
        }

        $resolved = [];
        $mArguments = (array) $arguments;

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
            } elseif (null === $key && $parameter->isOptional()) {
                // do nothing
            } else {
                $resolved[] = $value;
            }

            if (null !== $key) {
                unset($mArguments[$key]);
            }
        }

        foreach ($mArguments as $value) {
            $resolved[] = $value;
        }

        return $resolved;
    }

    protected function resolveArgument($value, $resolveClass = false)
    {
        if ($resolveClass && class_exists($value)) {
            return $this->service($value);
        }

        if (preg_match('/^(.+)?%([.\w]+)%(.+)?$/', $value, $match)) {
            $var = $this->reference($match[2], false, $hive, $found);

            if ($found) {
                // it does exists in hive
                $prefix = isset($match[1]) ? $match[1] : null;
                $suffix = isset($match[3]) ? $match[3] : null;

                return $prefix.$var.$suffix;
            }

            // it is a service alias
            return $this->service($match[2]);
        }

        return $value;
    }

    protected function languageReference($message)
    {
        $ref = $this->get('LANGUAGE_DICT.'.$message);

        if (null !== $ref && !is_string($ref)) {
            throw new \UnexpectedValueException('Message is not a string.');
        }

        return $ref;
    }

    protected function languageCodes()
    {
        $languages = preg_replace('/\h+|;q=[0-9.]+/', '', $this->hive['LANGUAGE'] ?: '').','.$this->hive['LANGUAGE_FALLBACK'];
        $final = [];

        foreach (static::split($languages) as $lang) {
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

    protected function languageLoad()
    {
        $pattern = '/(?<=^|\n)(?:'.
            '\[(?<prefix>.+?)\]|'.
            '(?<lval>[^\h\r\n;].*?)\h*=\h*'.
            '(?<rval>(?:\\\\\h*\r?\n|.+?)*)'.
        ')(?=\r?\n|$)/';

        $this->hive['LANGUAGE_DICT'] = [];

        foreach ($this->languageCodes() as $code) {
            foreach (static::split($this->hive['LANGUAGE_LOCALES']) as $locale) {
                $file = rtrim($locale, ',;|').$code.'.ini';
                $prefix = '';

                if (!is_file($file) || !preg_match_all($pattern, file_get_contents($file), $matches, PREG_SET_ORDER)) {
                    continue;
                }

                foreach ($matches as $match) {
                    if ($match['prefix']) {
                        $prefix = $match['prefix'].'.';
                    } else {
                        $ref = &$this->reference('LANGUAGE_DICT.'.$prefix.$match['lval']);
                        $ref = trim(preg_replace('/\\\\\h*\r?\n/', "\n", $match['rval']));
                    }
                }
            }
        }
    }

    protected function cacheFile($key, $content = null, $delete = false)
    {
        $file = $this->hive['CACHE_REFERENCE'].str_replace(['\\', '/'], '', $key);

        if ($delete) {
            return file_exists($file) ? unlink($file) : false;
        }

        if (null !== $content) {
            is_dir($dir = dirname($file)) || mkdir($dir, 0755, true);

            return false !== file_put_contents($file, $content);
        }

        return is_file($file) ? file_get_contents($file) : '';
    }

    protected function cacheMemcache($key = null, $delete = null, array $keys = null)
    {
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

    protected function cacheLoad()
    {
        if (!$this->hive['CACHE']) {
            return;
        }

        list($engine, $reference) = explode('=', (string) $this->hive['CACHE']) + [1 => null];

        if (('apc' === $engine && extension_loaded('apc')) ||
            ('apcu' === $engine && extension_loaded('apcu'))) {
            $reference = null;
        } elseif ('filesystem' === $engine && $reference) {
            $reference = str_replace('\\', '/', $reference);
        } elseif ($reference &&
            ('memcache' === $engine && extension_loaded('memcache')) ||
            ('memcached' === $engine && extension_loaded('memcached'))) {
            try {
                $servers = static::split($reference);
                $reference = new $engine();

                foreach ($servers as $server) {
                    list($host, $port) = explode(':', $server) + [1 => 11211];

                    $reference->addServer($host, $port + 0);
                    // a hack because addserver always returns true
                    $reference->get('foo');
                }
            } catch (\Exception $e) {
                $engine = null;
                $reference = null;
            }
        } elseif ('redis' === $engine && $reference && extension_loaded('redis')) {
            try {
                list($host, $port, $db) = explode(':', $reference) + [1 => 6379, null];

                $reference = new \Redis();
                $reference->connect($host, $port + 0, 2);

                if (isset($db)) {
                    $reference->select($db + 0);
                }
            } catch (\Exception $e) {
                $engine = null;
                $reference = null;
            }
        } else {
            $engine = null;
            $reference = null;
        }

        if (!$engine && $auto = preg_grep('/^(apcu|apc)/i', get_loaded_extensions())) {
            $engine = reset($auto);
        }

        $this->hive['CACHE_ENGINE'] = $engine;
        $this->hive['CACHE_REFERENCE'] = $reference;
    }

    public function offsetExists($key)
    {
        return $this->exists($key);
    }

    public function offsetGet($key)
    {
        return $this->get($key);
    }

    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    public function offsetUnset($key)
    {
        $this->clear($key);
    }
}

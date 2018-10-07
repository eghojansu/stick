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
 * Request and response information also live in this class.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class App extends Magic
{
    const PACKAGE = 'Stick-Framework';
    const VERSION = 'v0.1.0';

    const REQ_ALL = 0;
    const REQ_AJAX = 1;
    const REQ_CLI = 2;
    const REQ_SYNC = 3;

    const EVENT_BOOT = 'app_boot';
    const EVENT_SHUTDOWN = 'app_shutdown';
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
     * @param array|null $get    Equivalent to $_GET
     * @param array|null $post   Equivalent to $_POST
     * @param array|null $cookie Equivalent to $_COOKIE
     * @param array|null $server Equivalent to $_SERVER
     */
    public function __construct(array $get = null, array $post = null, array $cookie = null, array $server = null)
    {
        $time = microtime(true);
        $charset = 'UTF-8';

        ini_set('default_charset', $charset);

        $cli = 'cli' === PHP_SAPI;
        $headers = $cli ? null : Util::requestHeaders($server);
        $scriptname = Util::fixslashes($server['SCRIPT_NAME'] ?? $_SERVER['SCRIPT_NAME']);
        $uri = $server['REQUEST_URI'] ?? '/';
        $host = $server['SERVER_NAME'] ?? gethostname();
        $uriHost = preg_match('/^\w+:\/\//', $uri) ? '' : '//'.$host;
        $url = parse_url($uriHost.$uri);
        $base = dirname($scriptname);
        $port = (int) ($headers['X-Forwarded-Port'] ?? $server['SERVER_PORT'] ?? 80);
        $secure = 'on' === ($server['HTTPS'] ?? '') || 'https' === ($headers['X-Forwarded-Proto'] ?? '');
        $scheme = rtrim('https', chr(115 * ((int) !$secure)));
        $baseUrl = $scheme.'://'.$host.(in_array($port, array(80, 443)) ? '' : ':'.$port);
        $entry = '/'.basename($scriptname);

        if ($cli) {
            $base = '';
            $entry = '';
        }

        $cookieJar = array(
            'expire' => 0,
            'path' => $base,
            'domain' => (false === strpos($host, '.') || filter_var($host, FILTER_VALIDATE_IP)) ? '' : $host,
            'secure' => $secure,
            'httponly' => true,
        );

        $this->_hive = array(
            'AGENT' => $headers['X-Operamini-Phone-Ua'] ?? $headers['X-Skyfire-Phone'] ?? $headers['User-Agent'] ?? '',
            'AJAX' => 'XMLHttpRequest' === ($headers['X-Requested-With'] ?? null),
            'ALIAS' => null,
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
            'DEBUG' => false,
            'DICT' => null,
            'DNSBL' => null,
            'ENCODING' => $charset,
            'ENTRY' => $entry,
            'ERROR' => false,
            'EVENTS' => null,
            'EXEMPT' => null,
            'FALLBACK' => 'en',
            'GET' => $get,
            'HOST' => $host,
            'IP' => $headers['X-Client-Ip'] ?? Util::cutbefore($headers['X-Forwarded-For'] ?? '', ',', $server['REMOTE_ADDR'] ?? ''),
            'JAR' => $cookieJar,
            'LANGUAGE' => null,
            'LOCALES' => './dict/',
            'LOG' => null,
            'MIME' => null,
            'OUTPUT' => null,
            'PACKAGE' => self::PACKAGE,
            'PARAMS' => null,
            'PATH' => Util::cutprefix(Util::cutprefix(urldecode($url['path']), $base), $entry, '/'),
            'PATTERN' => null,
            'PORT' => $port,
            'POST' => $post,
            'PROTOCOL' => $server['SERVER_PROTOCOL'] ?? 'HTTP/1.0',
            'QUIET' => false,
            'RAW' => false,
            'REALM' => $baseUrl.$uri,
            'REQUEST' => $headers,
            'RESPONSE' => null,
            'ROUTE_ALIASES' => array(),
            'ROUTE_HANDLER_CTR' => -1,
            'ROUTE_HANDLERS' => array(),
            'ROUTES' => array(),
            'SCHEME' => $scheme,
            'SEED' => Util::hash($host.$base),
            'SENT' => false,
            'SERVER' => $server,
            'SERVICE_ALIASES' => array(),
            'SERVICE_RULES' => array(),
            'SERVICES' => array(),
            'SESSION' => null,
            'STATUS' => self::HTTP_200,
            'TEMP' => './var/',
            'THRESHOLD' => self::LOG_LEVEL_ERROR,
            'TIME' => $time,
            'TRACE' => Util::fixslashes(realpath(dirname($server['SCRIPT_FILENAME'] ?? $_SERVER['SCRIPT_FILENAME']).'/..').'/'),
            'TZ' => date_default_timezone_get(),
            'URI' => $uri,
            'VERB' => $server['REQUEST_METHOD'] ?? 'GET',
            'VERSION' => self::VERSION,
            'XFRAME' => 'SAMEORIGIN',
        );
        $this->_init = array('GET' => null, 'POST' => null) + $this->_hive;

        register_shutdown_function(array($this, 'unload'), getcwd());
    }

    /**
     * Create App instance with ease.
     *
     * @param array|null $get    Equivalent to $_GET
     * @param array|null $post   Equivalent to $_POST
     * @param array|null $cookie Equivalent to $_COOKIE
     * @param array|null $server Equivalent to $_SERVER
     *
     * @return App
     */
    public static function create(array $get = null, array $post = null, array $cookie = null, array $server = null): App
    {
        return new static($get, $post, $server, $cookie);
    }

    /**
     * Create App instance from globals environment.
     *
     * @return App
     */
    public static function createFromGlobals(): App
    {
        return new static($_GET, $_POST, $_COOKIE, $_SERVER);
    }

    /**
     * Returns ellapsed time since application prepared.
     *
     * @return string
     */
    public function ellapsed(): string
    {
        return number_format(microtime(true) - $this->_hive['TIME'], 5).' seconds';
    }

    /**
     * Returns trace as string.
     *
     * @param array $trace
     *
     * @return string
     */
    public function trace(array $trace): string
    {
        $out = '';
        $eol = "\n";
        $cut = (string) $this->_hive['TRACE'];
        $fix = array(
            'function' => null,
            'line' => null,
            'file' => '',
            'class' => null,
            'type' => null,
        );

        foreach ($trace as $key => $frame) {
            $frame += $fix;

            $file = str_replace($cut, '', Util::fixslashes($frame['file']));
            $out .= '['.$file.':'.$frame['line'].'] '.$frame['class'].$frame['type'].$frame['function'].$eol;
        }

        return $out;
    }

    /**
     * Override request method with Custom http method override or request post method hack.
     *
     * @return App
     */
    public function overrideRequestMethod(): App
    {
        $verb = $this->_hive['REQUEST']['X-Http-Method-Override'] ?? $this->_hive['VERB'];

        if ('POST' === $verb && isset($this->_hive['POST']['_method'])) {
            $verb = strtoupper($this->_hive['POST']['_method']);
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
            $this->_hive['URI'] = $req;
            $this->_hive['REALM'] = $this->_hive['BASEURL'].$req;
            parse_str($uri['query'], $this->_hive['GET']);
        }

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
        $fatalError = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR);

        if (!$error && PHP_SESSION_ACTIVE === session_status()) {
            session_commit();
        }

        if (!$this->trigger(self::EVENT_SHUTDOWN, array($error)) && $error && in_array($error['type'], $fatalError)) {
            $this->error(500, $error['message'], array($error));
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
        if ($this->_hive['DNSBL'] && !in_array($ip, Util::arr($this->_hive['EXEMPT']))) {
            // Reverse IPv4 dotted quad
            $rev = implode('.', array_reverse(explode('.', $ip)));

            foreach (Util::arr($this->_hive['DNSBL']) as $server) {
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
     * Returns value of hive member.
     *
     * *Dot notation access allowed*
     *
     * @param string $key
     *
     * @return mixed
     */
    public function &get(string $key)
    {
        $ref = &$this->ref($key);

        return $ref;
    }

    /**
     * Sets value of hive.
     *
     * *Dot notation access allowed*
     *
     * @param string $key
     * @param mixed  $val
     *
     * @return Magic
     */
    public function set(string $key, $val): Magic
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
                $this->_hive['DICT'] = $this->langLoad(...array(
                    $this->_hive['LOCALES'],
                    $this->langLanguages((string) $this->_hive['LANGUAGE'], $this->_hive['FALLBACK']),
                ));
                break;
            case 'TZ':
                date_default_timezone_set($val);
                break;
        }

        return $this;
    }

    /**
     * Remove member of hive.
     *
     * *Dot notation access allowed*
     *
     * @param string $key
     *
     * @return Magic
     */
    public function clear(string $key): Magic
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
     * Returns hive member as boolean value.
     *
     * @param string $key
     *
     * @return bool
     */
    public function is(string $key): bool
    {
        return (bool) $this->get($key);
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
        foreach (Util::arr($keys) as $key) {
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
        $content = file_exists($file) ? Util::requireFile($file, array()) : array();

        foreach ($content as $key => $val) {
            $lkey = strtolower($key);

            if (isset($maps[$lkey])) {
                Util::walk((array) $val, array($this, $maps[$lkey]), false);
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
                return (bool) $this->cacheParse($key, Util::read($this->_hive['CACHE_REF'].$ndx));
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
                $raw = Util::read($this->_hive['CACHE_REF'].$ndx);
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
                return false !== Util::write($this->_hive['CACHE_REF'].str_replace(array('/', '\\'), '', $ndx), $content);
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
                return Util::delete($this->_hive['CACHE_REF'].$ndx);
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

                    Util::walk($items, 'apc_delete');
                }
                break;
            case 'apcu':
                $info = apcu_cache_info(false);
                if ($info && isset($info['cache_list']) && $info['cache_list']) {
                    $key = array_key_exists('info', $info['cache_list'][0]) ? 'info' : 'key';
                    $items = preg_grep($regex, array_column($info['cache_list'], $key));

                    Util::walk($items, 'apcu_delete');
                }
                break;
            case 'folder':
                $files = glob($this->_hive['CACHE_REF'].$prefix.'*'.$suffix);

                Util::walk((array) $files, 'unlink');
                break;
            case 'memcached':
                $keys = preg_grep($regex, (array) $this->_hive['CACHE_REF']->getAllKeys());

                Util::walk($keys, array($this->_hive['CACHE_REF'], 'delete'));
                break;
            case 'redis':
                $keys = $this->_hive['CACHE_REF']->keys($prefix.'*'.$suffix);

                Util::walk($keys, array($this->_hive['CACHE_REF'], 'del'));
                break;
        }

        return $this;
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
        $call = is_string($callback) ? $this->grab($callback) : $callback;

        if (is_array($call)) {
            $ref = new \ReflectionMethod(reset($call), next($call));
        } else {
            $ref = new \ReflectionFunction($call);
        }

        return $call(...$this->resolveArgs($ref, (array) $args));
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

        if (empty($this->_hive['SERVICES'][$id]) && $sid = array_search($id, $this->_hive['SERVICE_ALIASES'])) {
            $id = $sid;
        }

        if (isset($this->_hive['SERVICES'][$id])) {
            return $this->_hive['SERVICES'][$id];
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

        if (!$ref->isInstantiable()) {
            throw new \LogicException('Unable to create instance for "'.$id.'". Please provide instantiable version of '.$ref->name.'.');
        }

        if ($rule['constructor'] && is_callable($rule['constructor'])) {
            $instance = $this->call($rule['constructor']);

            if (!$instance instanceof $ref->name) {
                throw new \LogicException('Constructor of "'.$id.'" should return instance of '.$ref->name.'.');
            }
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
        return $this->on($eventName, $handler, true);
    }

    /**
     * Register event handler.
     *
     * @param string $eventName
     * @param mixed  $handler
     * @param bool   $once
     *
     * @return App
     */
    public function on(string $eventName, $handler, bool $once = false): App
    {
        $this->_hive['EVENTS'][$eventName] = array($handler, $once);

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
        unset($this->_hive['EVENTS'][$eventName]);

        return $this;
    }

    /**
     * Trigger event.
     *
     * @param string     $eventName
     * @param array|null $event
     * @param bool       $off
     *
     * @return mixed
     */
    public function trigger(string $eventName, array $args = null, bool $off = false)
    {
        if (empty($this->_hive['EVENTS'][$eventName])) {
            return;
        }

        list($handler, $once) = $this->_hive['EVENTS'][$eventName];

        if ($once || $off) {
            $this->off($eventName);
        }

        return $this->call($handler, $args);
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
        $q = rtrim('?'.(is_array($query) ? http_build_query($query) : $query), '?');

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

                return str_replace($search, $replace, $pattern).$q;
            }

            return $pattern.$q;
        }

        return '/'.ltrim($alias, '/').$q;
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
        return $this->_hive['BASE'].$this->_hive['ENTRY'].$this->alias($alias, $args, $query);
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
            $path = $this->alias(...$target);
        } elseif (isset($this->_hive['ROUTE_ALIASES'][$target])) {
            $path = $this->_hive['ROUTE_ALIASES'][$target];
        } elseif (preg_match('/^(\w+)(?:\(([^(]+)\))?((?:\?).+)?$/', $target, $match)) {
            parse_str(strtr($match[2] ?? '', ',', '&'), $args);
            $path = $this->alias($match[1], $args, $match[3] ?? null);
        } else {
            $path = $target;
        }

        if (empty($url)) {
            $url = $path;

            if ('/' === $path[0] && (empty($path[1]) || '/' !== $path[1])) {
                $url = $this->_hive['BASEURL'].$this->_hive['ENTRY'].$path;
            }
        }

        if ($this->trigger(self::EVENT_REROUTE, array($url, $permanent))) {
            return $this;
        }

        if ($this->_hive['CLI']) {
            $this->mock('GET '.$path.' cli');

            return $this;
        }

        $this->status(302 - (int) $permanent);
        $this->_hive['RESPONSE']['Location'] = $url;
        $this->_hive['OUTPUT'] = null;

        $this->send();

        return $this;
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
     * @param string $route
     * @param mixed  $handler
     * @param int    $ttl
     * @param int    $kbps
     *
     * @return App
     */
    public function route(string $route, $handler, int $ttl = 0, int $kbps = 0): App
    {
        $pattern = '/^([\w+|]+)(?:\h+(\w+))?(?:\h+(\/[^\h]*))?(?:\h+(all|ajax|cli|sync))?$/i';

        preg_match($pattern, $route, $match);

        if (count($match) < 3) {
            throw new \LogicException('Route should contains at least a verb and path, given "'.$route.'".');
        }

        list($verbs, $alias, $path, $mode) = array_slice($match, 1) + array(1 => '', '', 'all');

        if (!$path) {
            if (empty($this->_hive['ROUTE_ALIASES'][$alias])) {
                throw new \LogicException('Route "'.$alias.'" not exists.');
            }

            $path = $this->_hive['ROUTE_ALIASES'][$alias];
        }

        $ptr = ++$this->_hive['ROUTE_HANDLER_CTR'];
        $mMode = constant('self::REQ_'.strtoupper($mode));

        foreach (array_filter(explode('|', strtoupper($verbs))) as $verb) {
            $this->_hive['ROUTES'][$path][$mMode][$verb] = $ptr;
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
     * @param string      $route
     * @param array|null  $args
     * @param array|null  $server
     * @param string|null $body
     */
    public function mock(string $route, array $args = null, array $server = null, string $body = null): void
    {
        $tmp = array_map('trim', explode(' ', $route));

        if (1 === count($tmp)) {
            throw new \LogicException('Mock should contains at least a verb and path, given "'.$route.'".');
        }

        $verb = strtoupper($tmp[0]);
        $targetExpr = urldecode($tmp[1]);
        $mode = strtolower($tmp[2] ?? 'none');
        $target = Util::cutbefore($targetExpr, '?');
        $query = Util::cutafter($targetExpr, '?', '', true);
        $path = $target;

        if (isset($this->_hive['ROUTE_ALIASES'][$target])) {
            $path = $this->_hive['ROUTE_ALIASES'][$target];
        } elseif (preg_match('/^(\w+)\(([^(]+)\)$/', $target, $match)) {
            parse_str(strtr($match[2], ',', '&'), $args);
            $path = $this->alias($match[1], $args);
        }

        $this->mclear('SENT,RESPONSE,OUTPUT,BODY');

        $this->_hive['VERB'] = $verb;
        $this->_hive['PATH'] = $path;
        $this->_hive['URI'] = $this->_hive['BASE'].$path.$query;
        $this->_hive['AJAX'] = 'ajax' === $mode;
        $this->_hive['CLI'] = 'cli' === $mode;
        $this->_hive['POST'] = 'POST' === $verb ? $args : array();

        parse_str(ltrim($query, '?'), $this->_hive['GET']);

        if (in_array($verb, array('GET', 'HEAD'))) {
            $this->_hive['GET'] = array_merge($this->_hive['GET'], (array) $args);
        } else {
            $this->_hive['BODY'] = $body ?: http_build_query((array) $args);
        }

        $this->_hive['SERVER'] = (array) $server + (array) $this->_hive['SERVER'];

        $this->run();
    }

    /**
     * Run kernel logic.
     */
    public function run(): void
    {
        $this->trigger(self::EVENT_BOOT, null, true);

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
     * @return App
     */
    public function send(int $code = null, array $headers = null, string $content = null, string $mime = null, int $kbps = 0): App
    {
        if ($this->_hive['SENT']) {
            return $this;
        }

        if ($code) {
            $this->status($code);
        }

        $assign = array(
            'RESPONSE' => $headers,
            'OUTPUT' => $content,
            'MIME' => $mime,
        );

        foreach (array_filter($assign) as $key => $value) {
            $this->_hive[$key] = $value;
        }

        if (!$this->_hive['CLI'] && !headers_sent()) {
            $this->sendHeaders(...array(
                $this->_hive['PROTOCOL'],
                $this->_hive['CODE'],
                $this->_hive['STATUS'],
                $this->_hive['MIME'],
                $this->_hive['RESPONSE'],
                $this->cookies($this->_hive['JAR'], $this->_hive['COOKIE'], $this->_init['COOKIE']),
            ));
        }

        if (!$this->_hive['QUIET'] && $this->_hive['OUTPUT']) {
            $this->sendContent($this->_hive['OUTPUT'], $kbps);
        }

        $this->_hive['SENT'] = true;

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
        $headers = &$this->_hive['RESPONSE'];

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

        unset($headers);

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

        if (!defined($name)) {
            throw new \DomainException('Unsupported HTTP code: '.$code.'.');
        }

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
     * @param array|null  $headers
     * @param int|null    $level
     *
     * @return App
     */
    public function error(int $httpCode, string $message = null, array $trace = null, array $headers = null, int $level = null): App
    {
        $this->status($httpCode);

        if (!$trace) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_shift($trace);
        }

        $status = $this->_hive['STATUS'];
        $text = $message ?: 'HTTP '.$httpCode.' ('.$this->_hive['VERB'].' '.$this->_hive['PATH'].')';
        $mTrace = $this->_hive['DEBUG'] ? $this->trace($trace) : '';

        $prior = $this->_hive['ERROR'];
        $this->_hive['ERROR'] = true;

        if ($prior) {
            return $this;
        }

        $this->_hive['RESPONSE'] = (array) $headers;

        $this->expire(-1)->logByCode($level ?? E_USER_ERROR, $text.PHP_EOL.$mTrace);

        if ($response = $this->trigger(self::EVENT_ERROR, array($message, $mTrace), true)) {
            $this->sendResponse($response);

            return $this;
        }

        if ($this->_hive['AJAX']) {
            $this->_hive['MIME'] = 'application/json';
            $this->_hive['OUTPUT'] = json_encode(array_filter(array(
                'status' => $status,
                'text' => $text,
                'trace' => $mTrace,
            )));
        } elseif ($this->_hive['CLI']) {
            $this->_hive['OUTPUT'] = 'Status : '.$status.PHP_EOL.
                                      'Text   : '.$text.PHP_EOL.
                                      $mTrace.PHP_EOL;
        } else {
            $this->_hive['MIME'] = 'text/html';
            $this->_hive['OUTPUT'] = '<!DOCTYPE html>'.
                '<html>'.
                '<head>'.
                  '<meta charset="'.$this->_hive['ENCODING'].'">'.
                  '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">'.
                  '<title>'.$httpCode.' '.$status.'</title>'.
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
            $this->logWrite($this->logDir(), $message, $level);
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
            E_ERROR => self::LOG_LEVEL_EMERGENCY,
            E_PARSE => self::LOG_LEVEL_EMERGENCY,
            E_CORE_ERROR => self::LOG_LEVEL_EMERGENCY,
            E_COMPILE_ERROR => self::LOG_LEVEL_EMERGENCY,
            E_WARNING => self::LOG_LEVEL_ALERT,
            E_CORE_WARNING => self::LOG_LEVEL_ALERT,
            E_STRICT => self::LOG_LEVEL_CRITICAL,
            E_USER_ERROR => self::LOG_LEVEL_ERROR,
            E_USER_WARNING => self::LOG_LEVEL_WARNING,
            E_NOTICE => self::LOG_LEVEL_NOTICE,
            E_COMPILE_WARNING => self::LOG_LEVEL_NOTICE,
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
    private function doRun(): void
    {
        if ($response = $this->trigger(self::EVENT_PREROUTE)) {
            $this->sendResponse($response);

            return;
        }

        if (!$route = $this->findRoute()) {
            $this->error(404);

            return;
        }

        list($handler, $alias, $ttl, $kbps, $pattern, $params) = $route;
        $hash = Util::hash($this->_hive['VERB'].' '.$this->_hive['PATH']).'.url';
        $checkCache = $ttl && in_array($this->_hive['VERB'], array('GET', 'HEAD'));

        if ($checkCache) {
            if ($response = $this->isRequestCached($hash, $ttl, $kbps)) {
                $this->send(...$response);

                return;
            }

            $this->expire($ttl);
        } else {
            $this->expire(0);
        }

        $this->_hive['PARAMS'] = $params;
        $this->_hive['PATTERN'] = $pattern;
        $this->_hive['ALIAS'] = $alias;

        $controller = is_string($handler) ? $this->grabController($handler) : $handler;

        if (!is_callable($controller)) {
            $this->error(405);

            return;
        }

        $args = (array) ($this->trigger(self::EVENT_CONTROLLER_ARGS, array($controller, $params)) ?? $params);
        $result = $this->call($controller, $args);

        if ($response = $this->trigger(self::EVENT_POSTROUTE, array($result))) {
            $this->sendResponse($response);
        } else {
            if (is_string($result)) {
                $this->_hive['OUTPUT'] = $result;
            } elseif (is_callable($result)) {
                $result($this);
            } elseif (is_array($result)) {
                $this->_hive['OUTPUT'] = json_encode($result);
                $this->_hive['MIME'] = 'application/json';
            }

            $this->send();
        }

        if ($checkCache) {
            $this->cacheRequest($hash, $ttl);
        }
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
                if ($handler = $this->findHandler($routes)) {
                    return $handler + array(4 => $pattern, $this->collectParams($match));
                }

                return null;
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
     *
     * @return array|null
     */
    private function findHandler(array $routes): ?array
    {
        $mode = $this->_hive['AJAX'] ? self::REQ_AJAX : ($this->_hive['CLI'] ? self::REQ_CLI : self::REQ_SYNC);
        $route = $routes[$mode] ?? $routes[self::REQ_ALL] ?? null;
        $handlerId = $route[$this->_hive['VERB']] ?? null;

        return null === $handlerId ? null : $this->_hive['ROUTE_HANDLERS'][$handlerId];
    }

    /**
     * Grab controller from handler expression.
     *
     * @param  string $handler
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
     * Handle response from trigger result.
     *
     * @param string|array $response
     */
    private function sendResponse($response): void
    {
        $mResponse = $response;

        if (is_string($response)) {
            $mResponse = array(null, null, $response);
        }

        if (is_array($mResponse)) {
            $this->send(...$mResponse);
        }
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
            Util::mkdir($ref);
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
     * Check is current request cached.
     *
     * @param string $key
     * @param int    $ttl
     * @param int    $kbps
     *
     * @return array|null
     */
    private function isRequestCached(string $key, int $ttl, int $kbps): ?array
    {
        if (!$this->isCached($key, $cache)) {
            return null;
        }

        $time = time();
        $expDate = $this->_hive['REQUEST']['If-Modified-Since'] ?? 0;
        $notModified = $expDate && strtotime($expDate) + $ttl > $time;

        if ($notModified) {
            return array(304);
        }

        list($content, $lastModified) = $cache;
        list($code, $headers, $response, $mime) = $content;

        $newExpDate = $lastModified + $ttl - $time;

        $this->expire($newExpDate);

        $newHeaders = $this->_hive['RESPONSE'];

        return array(
            $code,
            $newHeaders + (array) $headers,
            $response,
            $mime,
            $kbps,
        );
    }

    /**
     * Cache output.
     *
     * @param string $key
     * @param int    $ttl
     */
    private function cacheRequest(string $key, int $ttl): void
    {
        if ($this->_hive['OUTPUT'] && is_string($this->_hive['OUTPUT'])) {
            $this->cacheSet($key, array(
                $this->_hive['CODE'],
                $this->_hive['RESPONSE'],
                $this->_hive['OUTPUT'],
                $this->_hive['MIME'],
            ), $ttl);
        }
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

        if (null !== $ref && !is_string($ref)) {
            throw new \UnexpectedValueException('Message reference is not a string.');
        }

        return $ref;
    }

    /**
     * Write log message.
     *
     * @param string $dir
     * @param string $message
     * @param string $level
     */
    private function logWrite(string $dir, string $message, string $level): void
    {
        $prefix = $dir.'log_';
        $ext = '.log';
        $files = glob($prefix.date('Y-m').'*'.$ext);

        $file = $files[0] ?? $prefix.date('Y-m-d').$ext;
        $content = date('Y-m-d G:i:s.u').' '.$level.' '.$message.PHP_EOL;

        Util::mkdir(dirname($file));
        Util::write($file, $content, true);
    }

    /**
     * Get languages.
     *
     * @param string $language
     * @param string $fallback
     *
     * @return array
     */
    private function langLanguages(string $language, string $fallback): array
    {
        $langCode = ltrim(preg_replace('/\h+|;q=[0-9.]+/', '', $language).','.$fallback, ',');
        $languages = array();

        foreach (Util::split($langCode) as $lang) {
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
     * @param string|array $dir
     * @param array        $languages
     *
     * @return array
     */
    private function langLoad($dir, array $languages): array
    {
        $dict = array();

        foreach ($languages as $language) {
            foreach (Util::arr($dir) as $dir) {
                if (is_file($file = $dir.$language.'.php')) {
                    $dict = array_replace_recursive($dict, Util::requireFile($file, array()));
                }
            }
        }

        return $dict;
    }

    /**
     * Send response headers.
     *
     * @param string $protocol
     * @param int    $code
     * @param string $status
     * @param string $mime
     * @param array  $headers
     * @param array  $cookies
     */
    private function sendHeaders(string $protocol, int $code, string $status, string $mime = null, array $headers = null, array $cookies = null): void
    {
        foreach ((array) $cookies as $cookie) {
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
     */
    private function sendContent(string $response = null, int $kbps = 0): void
    {
        if ($kbps <= 0) {
            echo $response;

            return;
        }

        $now = microtime(true);
        $ctr = 0;

        foreach (str_split($response, 1024) as $part) {
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
    private function cookies(array $jar, array $current = null, array $init = null): array
    {
        $jar = array_combine(range(2, count($jar) + 1), array_values($jar));
        $mInit = (array) $init;
        $mCurrent = (array) $current;
        $cookies = array();

        foreach ($mCurrent as $name => $value) {
            if (!isset($mInit[$name]) || $mInit[$name] !== $value) {
                $cookies[$name] = array($name, $value) + $jar;
            }
        }

        foreach ($mInit as $name => $value) {
            if (!isset($mCurrent[$name])) {
                $cookies[$name] = array($name, '', strtotime('-1 year')) + $jar;
            }
        }

        return $cookies;
    }
}

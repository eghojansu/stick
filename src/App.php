<?php

declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick;

use Fal\Stick\Sql\Mapper;

/**
 * Main framework class.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class App implements \ArrayAccess
{
    // Framework details
    const PACKAGE = 'Stick-PHP';
    const VERSION = '0.1.0-beta';

    // Request types
    const REQ_SYNC = 1;
    const REQ_AJAX = 2;
    const REQ_CLI = 4;

    // Request methods
    const VERBS = 'GET|HEAD|POST|PUT|PATCH|DELETE|CONNECT|OPTIONS';

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

    // App events
    const EVENT_BOOT = 'app.boot';
    const EVENT_SHUTDOWN = 'app.shutdown';
    const EVENT_PREROUTE = 'app.preroute';
    const EVENT_POSTROUTE = 'app.postroute';
    const EVENT_REROUTE = 'app.reroute';
    const EVENT_ERROR = 'app.error';

    // Default rule
    const RULE_DEFAULT = [
        'class' => null,
        'use' => null,
        'args' => null,
        'service' => true,
        'constructor' => null,
        'boot' => null,
    ];

    // Default group
    const GROUP_DEFAULT = [
        'name' => '',
        'mode' => 'all',
        'class' => '',
        'instance' => true,
        'prefix' => '',
        'suffix' => '',
    ];

    // Config map
    const CONFIG_MAP = [
        'configs' => 'config',
        'routes' => 'route',
        'maps' => 'map',
        'redirects' => 'redirect',
        'rules' => 'set',
        'listeners' => 'on',
        'groups' => 'group',
    ];

    /**
     * Initial value.
     *
     * @var array
     */
    private $init;

    /**
     * Variable hive.
     *
     * @var array
     */
    private $hive;

    /**
     * Prepare system environment.
     */
    public function __construct()
    {
        ini_set('default_charset', 'UTF-8');
        ini_set('display_errors', 'off');
        session_cache_limiter('');
        // Intercept errors/exceptions; PHP5.3-compatible
        error_reporting((E_ALL | E_STRICT) & ~(E_NOTICE | E_USER_NOTICE));

        $headers = [
            'Content-Type' => $_SERVER['CONTENT_TYPE'] ?? null,
            'Content-Length' => $_SERVER['CONTENT_LENGTH'] ?? null,
        ];

        foreach ($_SERVER as $key => $val) {
            if ($header = Helper::cutafter($key, 'HTTP_')) {
                $headers[Helper::toHKey($header)] = $val;
            }
        }

        $cli = PHP_SAPI === 'cli';
        $domain = $_SERVER['SERVER_NAME'] ?? gethostname();
        $urireq = $_SERVER['REQUEST_URI'] ?? '/';
        $uridomain = preg_match('~^\w+://~', $urireq) ? '' : '//'.$domain;
        $uri = parse_url($uridomain.$urireq) + ['query' => '', 'fragment' => ''];
        $base = $cli ? '' : rtrim(Helper::fixslashes(dirname($_SERVER['SCRIPT_NAME'])), '/');
        $entry = '/'.basename($_SERVER['SCRIPT_NAME']);
        $port = $headers['X-Forwarded-Port'] ?? $_SERVER['SERVER_PORT'] ?? 80;
        $secure = ($_SERVER['HTTPS'] ?? '') === 'on' || ($headers['X-Forwarded-Proto'] ?? '') === 'https';
        $scheme = $secure ? 'https' : 'http';
        $cpath = urldecode(Helper::cutafter($uri['path'], $base, $uri['path']));
        $path = Helper::cutafter($cpath, $entry, $cpath) ?: '/';

        // Early assignment
        $this->hive = [
            'BASE' => $base,
            'ENTRY' => $cli ? '' : $entry,
            'HEADERS' => $headers,
            'HOST' => $domain,
            'PATH' => $path,
            'PORT' => $port,
            'SCHEME' => $scheme,
        ];
        // Full assignment
        $this->hive = [
            '_BOOTED' => false,
            '_GROUP' => [],
            '_GROUP_DEPTH' => 0,
            '_HANDLE_MAPPER' => false,
            '_LISTENERS' => [],
            '_ONCE' => [],
            '_ROUTE_ALIASES' => [],
            '_ROUTES' => [],
            '_SERVICE_RULES' => [
                'cache' => [
                    'class' => Cache::class,
                    'args' => [
                        'dsn' => '%CACHE%',
                        'prefix' => '%SEED%',
                        'fallback' => '%TEMP%cache/',
                    ],
                ],
                'logger' => [
                    'class' => Logger::class,
                    'args' => [
                        'dir' => '%TEMP%log/',
                        'logLevelThreshold' => '%THRESHOLD%',
                    ],
                ],
            ],
            '_SERVICE_ALIASES' => [
                Cache::class => 'cache',
                Logger::class => 'logger',
            ],
            '_SERVICES' => [],
            '_SESSION_DRY' => true,
            '_SESSION_INVALID' => false,
            '_SESSION_FLY' => true,
            'AGENT' => $this->agent(),
            'AJAX' => $this->ajax(),
            'ALIAS' => '',
            'BASE' => $base,
            'BODY' => '',
            'CACHE' => '',
            'CASELESS' => false,
            'CLI' => $cli,
            'CODE' => 200,
            'CORS' => [
                'HEADERS' => '',
                'ORIGIN' => false,
                'CREDENTIALS' => false,
                'EXPOSE' => false,
                'TTL' => 0,
            ],
            'DEBUG' => 0,
            'DNSBL' => '',
            'ENCODING' => 'UTF-8',
            'ENTRY' => $cli ? '' : $entry,
            'ERROR' => [],
            'EXEMPT' => [],
            'FRAGMENT' => $uri['fragment'],
            'HEADERS' => $headers,
            'HOST' => $domain,
            'IP' => $this->ip(),
            'JAR' => [
                'EXPIRE' => 0,
                'PATH' => $base ?: '/',
                'DOMAIN' => (false === strpos($domain, '.') || filter_var($domain, FILTER_VALIDATE_IP)) ? '' : $domain,
                'SECURE' => $secure,
                'HTTPONLY' => true,
            ],
            'KBPS' => 0,
            'LANGUAGE' => $headers['Accept-Language'] ?? '',
            'MATCH' => '',
            'MAPPER' => false,
            'NAMESPACE' => [],
            'OUTPUT' => '',
            'PACKAGE' => self::PACKAGE,
            'PARAMS' => [],
            'PATTERN' => '',
            'PATH' => $path,
            'PORT' => $port,
            'PREMAP' => '',
            'PROTOCOL' => $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0',
            'QUERY' => $uri['query'],
            'QUIET' => false,
            'RAW' => false,
            'REALM' => $this->path($path, null, true, $uri['query'], $uri['fragment']),
            'RESPONSE' => [],
            'ROOT' => realpath($_SERVER['DOCUMENT_ROOT']),
            'SCHEME' => $scheme,
            'SEED' => Helper::hash($domain.$base),
            'SENT' => false,
            'STATUS' => self::HTTP_200,
            'TEMP' => './var/',
            'TIME' => microtime(true),
            'TRACE' => is_dir($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : dirname($_SERVER['DOCUMENT_ROOT']),
            'THRESHOLD' => Logger::LEVEL_ERROR,
            'TZ' => date_default_timezone_get(),
            'URI' => $uri['path'].rtrim('?'.$uri['query'], '?').rtrim('#'.$uri['fragment'], '#'),
            'VERB' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'VERSION' => self::VERSION,
            'XFRAME' => 'SAMEORIGIN',
        ];

        // Save a copy of hive
        $this->init = $this->hive;

        // Copy globals initial value
        $this->hive['GET'] = $GLOBALS['_GET'];
        $this->hive['POST'] = $GLOBALS['_POST'];
        $this->hive['COOKIE'] = $GLOBALS['_COOKIE'];
        $this->hive['SESSION'] = [];
        $this->hive['FILES'] = $GLOBALS['_FILES'];
        $this->hive['SERVER'] = $GLOBALS['_SERVER'];
        $this->hive['ENV'] = $GLOBALS['_ENV'];

        // Copy some initial globals env
        $this->init['GET'] = [];
        $this->init['POST'] = [];
        $this->init['COOKIE'] = $GLOBALS['_COOKIE'];
        $this->init['SESSION'] = [];
        $this->init['FILES'] = [];
        $this->init['SERVER'] = $GLOBALS['_SERVER'];
        $this->init['ENV'] = $GLOBALS['_ENV'];
    }

    /**
     * Return current user agent.
     *
     * @return string
     */
    public function agent(): string
    {
        $use = $this->hive['HEADERS'];

        return $use['X-Operamini-Phone-Ua'] ?? $use['X-Skyfire-Phone'] ?? $use['User-Agent'] ?? '';
    }

    /**
     * Return ajax status.
     *
     * @return bool
     */
    public function ajax(): bool
    {
        $use = $this->hive['HEADERS'];

        return 'xmlhttprequest' === strtolower($use['X-Requested-With'] ?? '');
    }

    /**
     * Return ip address.
     *
     * @return string
     */
    public function ip(): string
    {
        $use = $this->hive['HEADERS'];

        return $use['Client-Ip'] ?? (isset($use['X-Forwarded-For']) ? Helper::split($use['X-Forwarded-For'])[0] : $_SERVER['REMOTE_ADDR'] ?? '');
    }

    /**
     * Check if ip is blacklisted.
     *
     * @param string|null $ip
     *
     * @return bool
     */
    public function blacklisted(string $ip = null): bool
    {
        $use = $ip ?? $this->hive['IP'];
        $dnsbl = Helper::reqarr($this->hive['DNSBL'] ?? '');
        $exempt = Helper::reqarr($this->hive['EXEMPT'] ?? '');

        if ($dnsbl && !in_array($use, $exempt)) {
            // We skip this part to test
            // @codeCoverageIgnoreStart
            // Reverse IPv4 dotted quad
            $rev = implode('.', array_reverse(explode('.', $use)));
            foreach ($dnsbl as $server) {
                // DNSBL lookup
                if (checkdnsrr($rev.'.'.$server, 'A')) {
                    return true;
                }
            }
            // @codeCoverageIgnoreEnd
        }

        return false;
    }

    /**
     * Mimic composer autoload behaviour, can be used as class autoloader.
     *
     * @param string $class
     *
     * @return mixed
     */
    public function autoload($class)
    {
        $logicalPath = Helper::fixslashes($class).'.php';
        $subPath = $class;

        while (false !== $lastPos = strrpos($subPath, '\\')) {
            $subPath = substr($subPath, 0, $lastPos);
            $paths = $this->hive['NAMESPACE'][$subPath.'\\'] ?? null;

            if ($paths) {
                $pathEnd = substr($logicalPath, $lastPos + 1);

                foreach (Helper::reqarr($paths) as $dir) {
                    if (file_exists($file = $dir.$pathEnd)) {
                        Helper::exinclude($file);

                        return true;
                    }
                }
            }
        }

        // fallback (root)
        foreach (Helper::reqarr($this->hive['NAMESPACE']['\\'] ?? []) as $dir) {
            if (file_exists($file = $dir.$logicalPath)) {
                Helper::exinclude($file);

                return true;
            }
        }
    }

    /**
     * Register autoloader.
     *
     * In case you do not need composer full feature, register this autoloader.
     *
     * @return App
     *
     * @codeCoverageIgnore
     */
    public function registerAutoloader(): App
    {
        spl_autoload_register([$this, 'autoload']);

        return $this;
    }

    /**
     * Register shutdown handler.
     *
     * @return App
     *
     * @codeCoverageIgnore
     */
    public function registerShutdownHandler(): App
    {
        register_shutdown_function([$this, 'unload'], getcwd());

        return $this;
    }

    /**
     * Override request method.
     *
     * @return App
     */
    public function overrideRequestMethod(): App
    {
        $method = $this->hive['HEADERS']['X-HTTP-Method-Override'] ?? $_SERVER['REQUEST_METHOD'] ?? $this->hive['VERB'];

        if ('POST' === $method && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        $this->hive['VERB'] = $method;

        return $this;
    }

    /**
     * Emulate CLI request.
     *
     * @return App
     */
    public function emulateCliRequest(): App
    {
        if (!$this->hive['CLI']) {
            return $this;
        }

        if (!isset($_SERVER['argv'][1])) {
            ++$_SERVER['argc'];
            $_SERVER['argv'][1] = '/';
        }

        if ($_SERVER['argv'][1][0] === '/') {
            $req = $_SERVER['argv'][1];
        } else {
            $req = '';
            $opts = '';

            for ($i = 1; $i < $_SERVER['argc']; ++$i) {
                $arg = $_SERVER['argv'][$i];

                if ('-' === $arg[0]) {
                    $m = explode('=', $arg);
                    if ('-' === $arg[1]) {
                        $opts .= '&'.urlencode(substr($m[0], 2)).'=';
                    } else {
                        $opts .= '&'.implode('=&', array_map('urlencode', str_split(substr($m[0], 1)))).'=';
                    }
                    $opts = ltrim($opts, '&').($m[1] ?? '');
                } else {
                    $req .= '/'.$arg;
                }
            }

            $req = '/'.ltrim(rtrim($req.'?'.$opts, '?'), '/');
        }

        $uri = parse_url($req) + ['query' => '', 'fragment' => ''];
        $this->hive['PATH'] = $uri['path'];
        $this->hive['QUERY'] = $uri['query'];
        $this->hive['FRAGMENT'] = $uri['fragment'];
        $this->hive['URI'] = $req;
        $this->hive['VERB'] = 'GET';
        parse_str($uri['query'], $this->hive['GET']);

        return $this;
    }

    /**
     * Register route with group.
     *
     * Available options:
     *
     *  * name     : route name prefix (Concatenate with parent mode)
     *  * mode     : request mode allowed (all, sync, ajax or cli) (Inherited from parent)
     *  * class    : class name handler (Dependant)
     *  * instance : is class should be instantiated? (Dependant)
     *  * prefix   : path prefix (Concatenate with parent mode)
     *  * suffix   : path suffix (Concatenate with parent mode)
     *
     * Example:
     *
     *      $app->group(['prefix'=>'/foo', 'route'=>'foo'], function($app) {
     *          $app->route('GET bar /bar', function() {
     *              # ...
     *          });
     *      });
     *
     * @param array    $options
     * @param callable $callback
     *
     * @return App
     */
    public function group(array $options, callable $callback): App
    {
        ++$this->hive['_GROUP_DEPTH'];

        $use = $options + self::GROUP_DEFAULT;

        if ($this->hive['_GROUP_DEPTH'] > 1) {
            $use = [
                'name' => $this->hive['_GROUP']['name'].$use['name'],
                'mode' => $use['mode'] ?? $this->hive['_GROUP']['mode'],
                'class' => $use['class'],
                'instance' => $use['instance'] ?? self::GROUP_DEFAULT['instance'],
                'prefix' => $this->hive['_GROUP']['prefix'].$use['prefix'],
                'suffix' => $this->hive['_GROUP']['suffix'].$use['suffix'],
                '_parent' => $this->hive['_GROUP'],
            ];
        }

        $this->hive['_GROUP'] = $use;

        call_user_func_array($callback, [$this]);

        $this->hive['_GROUP'] = --$this->hive['_GROUP_DEPTH'] > 0 ? $this->hive['_GROUP']['_parent'] ?? self::GROUP_DEFAULT : self::GROUP_DEFAULT;

        return $this;
    }

    /**
     * Register route.
     *
     * Pattern rule: "METHOD [routeName] /path [requestMode]".
     *
     *  * Multiple method can be defined with pipe character as separator.
     *  * Route name should not contains '.' (dot) or space.
     *  * /path can be a valid routeName or pure path, if routeName given the previous path of routeName will be used
     *  * Supported request mode: sync, ajax, cli
     *
     * Pattern example:
     *
     *      * GET /foo
     *      * GET|POST /bar
     *      * GET routeName /baz
     *      * POST routeName (Above path '/baz' will be used)
     *      * GET syncModeOnly /sync sync
     *      * GET ajaxModeOnly /ajax ajax
     *      * GET cliModeOnly /cli ajax
     *      * GET /arguments/{arg}
     *      * GET /arguments/{arg:alpha}/{arg2:digit}/{arg3:word}/{arg4:alnum}
     *      * GET /arguments/{arg:lower}/{arg2:upper}
     *
     * Handler example:
     *
     *      * function() { ... } # no argument
     *      * function($arg) { ... } # named arg in pattern
     *      * function(App $app) { ... } # if instance of App needed
     *      * function(OtherService $instance) { ... } # if instance of other service needed
     *      * [$instanceOfController, 'method'] # if controller instantiate manually
     *      * 'ControllerName->method' # same as above but controller will be instantiated by the Framework
     *      * ['ControllerName', 'method'] # if controller method is static
     *      * 'ControllerName::method' # same as above
     *
     * @param string          $rule
     * @param string|callable $handler
     * @param int             $ttl
     * @param int             $kbps
     *
     * @return App
     *
     * @throws LogicException If route rule is not valid
     */
    public function route(string $rule, $handler, int $ttl = 0, int $kbps = 0): App
    {
        preg_match('/^([\|\w]+)(?:\h+(\w+))?(?:\h+([^\h]+))(?:\h+(sync|ajax|cli))?$/i', $rule, $match);

        $alias = $match[2] ?? null;
        $pattern = $match[3] ?? null;
        $group = $this->hive['_GROUP'] ?: self::GROUP_DEFAULT;

        if (!$alias && $pattern && isset($this->hive['_ROUTE_ALIASES'][$pattern])) {
            $alias = $pattern;
            $pattern = $this->hive['_ROUTE_ALIASES'][$alias];
        } else {
            if ($alias) {
                $alias = $group['name'].$alias;
            }

            $pattern = $group['prefix'].$pattern.$group['suffix'];
        }

        if (!$pattern) {
            throw new \LogicException('Route rule should contain at least request method and path, given "'.$rule.'"');
        }

        $type = Helper::constant(self::class.'::REQ_'.strtoupper($match[4] ?? $group['mode']), 0);
        $pattern = '/'.trim($pattern, '/');
        $use = $handler;

        if (is_string($handler) && $group['class']) {
            if (is_string($group['class']) && $group['instance']) {
                $use = $group['class'].'->'.$handler;
            } else {
                $use = [$group['class'], $handler];
            }
        }

        foreach (Helper::split(strtoupper($match[1])) as $verb) {
            $this->hive['_ROUTES'][$pattern][$type][$verb] = [$use, $ttl, $kbps, $alias];
        }

        if ($alias) {
            $this->hive['_ROUTE_ALIASES'][$alias] = $pattern;
        }

        return $this;
    }

    /**
     * Register pattern to handle ReST.
     *
     * Pattern is same as App::route but without method.
     *
     * App::VERBS will be registered with verb name as its method.
     * Controller method can be prefixed with PREMAP value.
     *
     * Pattern example:
     *
     *  * /foo
     *  * routeName /bar
     *  * routeName /path sync
     *
     * Class example:
     *
     *      * $instanceOfController
     *      * 'ControllerName'
     *
     * @param string        $rule
     * @param string|object $class
     * @param int           $ttl
     * @param int           $kbps
     *
     * @return App
     */
    public function map(string $rule, $class, int $ttl = 0, int $kbps = 0): App
    {
        $str = is_string($class);
        $prefix = $this->hive['PREMAP'];

        foreach (Helper::split(self::VERBS) as $verb) {
            $this->route($verb.' '.$rule, $str ? $class.'->'.$prefix.$verb : [$class, $prefix.$verb], $ttl, $kbps);
        }

        return $this;
    }

    /**
     * Redirect rule to specified url.
     *
     * Pattern is same as App::route.
     *
     * Url can be string or array.
     *
     * Url example:
     *
     *      * /target/path
     *      * routeName
     *      * ['routeName', ['arg'=>'foo']]
     *
     * @param string       $rule
     * @param string|array $url
     * @param bool         $permanent
     *
     * @return App
     *
     * @throws LogicException If url empty
     */
    public function redirect(string $rule, $url, bool $permanent = true): App
    {
        if (!$url) {
            throw new \LogicException('Url cannot be empty');
        }

        return $this->route($rule, function () use ($url, $permanent) {
            $this->reroute($url, $permanent);
        });
    }

    /**
     * Set response status code.
     *
     * @param int $code
     *
     * @return App
     *
     * @throws DomainException If given code is not supported
     */
    public function status(int $code): App
    {
        $status = Helper::constant(self::class.'::HTTP_'.$code);

        if (!$status) {
            throw new \DomainException('Unsupported http code: '.$code);
        }

        $this->hive['CODE'] = $code;
        $this->hive['STATUS'] = $status;

        return $this;
    }

    /**
     * Set expire headers.
     *
     * @param int $secs
     *
     * @return App
     */
    public function expire(int $secs = 0): App
    {
        $this->hive['RESPONSE']['X-Powered-By'] = $this->hive['PACKAGE'];
        $this->hive['RESPONSE']['X-Frame-Options'] = $this->hive['XFRAME'];
        $this->hive['RESPONSE']['X-XSS-Protection'] = '1; mode=block';
        $this->hive['RESPONSE']['X-Content-Type-Options'] = 'nosniff';

        if ('GET' === $this->hive['VERB'] && $secs) {
            $expires = (int) (microtime(true) + $secs);

            unset($this->hive['RESPONSE']['Pragma']);
            $this->hive['RESPONSE']['Cache-Control'] = 'max-age='.$secs;
            $this->hive['RESPONSE']['Expires'] = gmdate('r', $expires);
            $this->hive['RESPONSE']['Last-Modified'] = gmdate('r');
        } else {
            $this->hive['RESPONSE']['Pragma'] = 'no-cache';
            $this->hive['RESPONSE']['Cache-Control'] = 'no-cache, no-store, must-revalidate';
            $this->hive['RESPONSE']['Expires'] = gmdate('r', 0);
        }

        return $this;
    }

    /**
     * Count ellapsed time since initial TIME.
     *
     * @return string
     */
    public function ellapsedTime(): string
    {
        return number_format(microtime(true) - $this->hive['TIME'], 5).' seconds';
    }

    /**
     * Build absolute or relative url path.
     *
     * @param string     $path     Path or alias (alias will check first)
     * @param array|null $args
     * @param bool       $abs
     * @param string     $query
     * @param string     $fragment
     *
     * @return string
     */
    public function path(string $path = null, array $args = null, bool $abs = false, string $query = '', string $fragment = ''): string
    {
        $use = $path ? (isset($this->hive['_ROUTE_ALIASES'][$path]) ? $this->alias($path, $args) : $path) : $this->hive['PATH'];
        $host = $abs ? $this->hive['SCHEME'].'://'.$this->hive['HOST'].(in_array($this->hive['PORT'], [80, 443]) ? '' : (':'.$this->hive['PORT'])) : '';

        return $host.rtrim($this->hive['BASE'].$this->hive['ENTRY'].$use.'?'.$query.'#'.$fragment, '?#');
    }

    /**
     * Build url from given alias.
     *
     * @param string     $alias
     * @param array|null $args
     *
     * @return string
     *
     * @throws LogicException If named route (alias) does not exists
     */
    public function alias(string $alias, array $args = null): string
    {
        if (!isset($this->hive['_ROUTE_ALIASES'][$alias])) {
            throw new \LogicException('Route "'.$alias.'" does not exists');
        }

        $lookup = (array) $args;

        return preg_replace_callback('/\{(\w+)(?:\:\w+)?\}/', function ($m) use ($lookup) {
            return array_key_exists($m[1], $lookup) ? $lookup[$m[1]] : $m[0];
        }, $this->hive['_ROUTE_ALIASES'][$alias]);
    }

    /**
     * Build url from string expression, example: @foo#bar(argument=value).
     *
     * @param string $expr
     *
     * @return string
     */
    public function build(string $expr): string
    {
        if (!preg_match('/^(\w+)(#\w+)?(?:\(([^\)]+)\))?$/', $expr, $match)) {
            // no route alias declaration
            return $expr;
        }

        $args = [];
        $route = $match[1];
        $fragment = $match[2] ?? '';
        $defArgs = $match[3] ?? '';

        foreach (Helper::split($defArgs) as $arg) {
            $pair = explode('=', $arg);
            $args[trim($pair[0])] = trim($pair[1] ?? '');
        }

        return $this->alias($route, $args).$fragment;
    }

    /**
     * Reroute to given url or alias or do reload current url.
     *
     * @param string|array|null $url
     * @param bool              $permanent
     */
    public function reroute($url = null, bool $permanent = false): void
    {
        $use = $url ? (is_array($url) ? $this->alias(...$url) : $this->build($url)) : $this->hive['REALM'];

        $this->sessionCommit();

        if ($this->trigger(self::EVENT_REROUTE, [$use, $permanent])) {
            return;
        }

        if ('/' === $use[0] && (empty($use[1]) || '/' !== $use[1])) {
            $use = $this->path($use, null, true);
        }

        if ($this->hive['CLI']) {
            $this->mock('GET '.$use.' cli');

            return;
        }

        $this->hive['RESPONSE']['Location'] = $use;
        $this->status($permanent ? 301 : 302)->sendHeaders();
    }

    /**
     * Start route matching.
     *
     * @return App
     */
    public function run(): App
    {
        try {
            $this->doRun();
        } catch (\Throwable $e) {
            $this->error($e instanceof ResponseErrorException ? $e->getCode() : 500, $e->getMessage() ?: null, $e->getTrace());
        }

        return $this;
    }

    /**
     * Mock request.
     *
     * @param string      $pattern
     * @param array|null  $args
     * @param array|null  $headers
     * @param string|null $body
     *
     * @return App
     *
     * @throws LogicException If mock pattern is not valid
     */
    public function mock(string $pattern, array $args = null, array $headers = null, string $body = null): App
    {
        preg_match('/^([\w]+)(?:\h+([^\h]+))(?:\h+(sync|ajax|cli))?$/i', $pattern, $match);

        if (empty($match[2])) {
            throw new \LogicException('Mock pattern should contain at least request method and path, given "'.$pattern.'"');
        }

        $method = strtoupper($match[1]);
        $path = $this->build($match[2]);
        $mode = strtolower($match[3] ?? '');
        $uri = parse_url($path) + ['query' => '', 'fragment' => ''];

        $this->hive['VERB'] = $method;
        $this->hive['PATH'] = $uri['path'];
        $this->hive['URI'] = $this->hive['BASE'].$uri['path'];
        $this->hive['FRAGMENT'] = $uri['fragment'];
        $this->hive['AJAX'] = 'ajax' === $mode;
        $this->hive['CLI'] = 'cli' === $mode;
        $this->hive['HEADERS'] = (array) $headers;
        $this->hive['REALM'] = $this->path($uri['path'], null, true, $uri['query'], $uri['fragment']);
        $this->hive['TIME'] = microtime(true);

        parse_str($uri['query'], $this->hive['GET']);

        if (in_array($method, ['GET', 'HEAD']) && $args) {
            $this->hive['GET'] = array_merge($this->hive['GET'], $args);
        } else {
            $this->hive['BODY'] = $body ?? ($args ? http_build_query($args) : '');
        }

        $this->hive['POST'] = 'POST' === $method ? ($args ?? []) : [];

        if ($this->hive['GET']) {
            $this->hive['QUERY'] = http_build_query($this->hive['GET']);
            $this->hive['URI'] .= '?'.$this->hive['QUERY'];
        }

        $this->mclear('RESPONSE', 'OUTPUT', 'CODE', 'STATUS', 'ERROR');

        return $this->run();
    }

    /**
     * Send error.
     *
     * @param int    $code
     * @param string $message
     * @param array  $trace
     * @param string $level
     *
     * @return App
     */
    public function error(int $code, string $message = null, array $trace = null, string $level = null): App
    {
        $this->mclear('RESPONSE', 'OUTPUT')->status($code);

        $status = $this->hive['STATUS'];
        $text = $message ?? 'HTTP '.$code.' ('.rtrim($this->hive['VERB'].' '.$this->hive['PATH'].'?'.$this->hive['QUERY'], '?').')';
        $sTrace = $this->tracify($trace);

        $this->get('logger')->log($level ?? self::decideLogLevel($code), $text.PHP_EOL.$sTrace);

        $prior = $this->hive['ERROR'];
        $this->hive['ERROR'] = [
            'status' => $status,
            'code' => $code,
            'text' => $text,
            'trace' => $sTrace,
        ];

        $this->expire(-1);

        $handled = false;

        try {
            $handled = $this->trigger(self::EVENT_ERROR, [$this->hive['ERROR'], $prior]);
        } catch (\Throwable $e) {
            // clear handler
            unset($this->hive['_LISTENERS'][self::EVENT_ERROR]);
            $this->offsetUnset('ERROR');

            $this->error($e instanceof ResponseErrorException ? $e->getCode() : 500, $e->getMessage() ?: null);
            $handled = true;
        }

        if ($handled || $prior) {
            return $this;
        }

        if ($this->hive['AJAX']) {
            $this->hive['RESPONSE']['Content-Type'] = 'application/json';
            $this->hive['OUTPUT'] = json_encode(array_diff_key(
                $this->hive['ERROR'],
                $this->hive['DEBUG'] ? [] : ['trace' => 1]
            ));
        } elseif ($this->hive['CLI']) {
            $this->hive['RESPONSE']['Content-Type'] = 'text/plain';
            $this->hive['OUTPUT'] = Helper::contexttostring(array_diff_key(
                $this->hive['ERROR'],
                $this->hive['DEBUG'] ? [] : ['trace' => 1]
            ));
        } else {
            $this->hive['RESPONSE']['Content-Type'] = 'text/html';
            $this->hive['OUTPUT'] = '<!DOCTYPE html>'.
                '<html>'.
                '<head>'.
                  '<meta charset="'.$this->hive['ENCODING'].'">'.
                  '<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">'.
                  '<title>'.$code.' '.$status.'</title>'.
                '</head>'.
                '<body>'.
                  '<h1>'.$status.'</h1>'.
                  '<p>'.$text.'</p>'.
                  ($this->hive['DEBUG'] ? '<pre>'.$sTrace.'</pre>' : '').
                '</body>'.
                '</html>'
            ;
        }

        return $this->send();
    }

    /**
     * Framework shutdown sequence.
     *
     * @param string $cwd
     *
     * @codeCoverageIgnore
     */
    public function unload(string $cwd): void
    {
        chdir($cwd);

        $this->sessionCommit();

        if ($this->trigger(self::EVENT_SHUTDOWN, [$cwd])) {
            return;
        }

        $error = error_get_last();

        if ($error) {
            if (in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
                // Fatal error detected
                $this->error(500, 'Fatal error: '.$error['message'], [$error]);
            } else {
                $message = '['.$error['type'].'] '.$error['message'].' in '.$error['file'].' on '.$error['line'];

                $this->get('logger')->log(self::decideLogLevel(), $message);
            }
        }
    }

    /**
     * Set event listener.
     *
     * Listener should return true to give control back to the caller
     *
     * @param string   $event
     * @param callable $listener
     *
     * @return App
     */
    public function on(string $event, callable $listener): App
    {
        if (isset($this->hive['_LISTENERS'][$event])) {
            $this->hive['_LISTENERS'][$event][] = $listener;
        } else {
            $this->hive['_LISTENERS'][$event] = [$listener];
        }

        return $this;
    }

    /**
     * Set event listener that run once only.
     *
     * @param string   $event
     * @param callable $listener
     *
     * @return App
     */
    public function one(string $event, callable $listener): App
    {
        $this->hive['_ONCE'][$event] = true;

        return $this->on($event, $listener);
    }

    /**
     * Trigger event listener.
     *
     * @param string     $event
     * @param array|null $args
     *
     * @return bool
     */
    public function trigger(string $event, array $args = null): bool
    {
        if (!isset($this->hive['_LISTENERS'][$event])) {
            return false;
        }

        $listeners = $this->hive['_LISTENERS'][$event];

        if (isset($this->hive['_ONCE'][$event])) {
            unset($this->hive['_ONCE'][$event], $this->hive['_LISTENERS'][$event]);
        }

        foreach ($listeners as $listener) {
            if (true === $this->call($listener, $args)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Grab class->method declaration from a string.
     *
     * @param string $def
     * @param bool   $create
     *
     * @return mixed
     */
    public function grab(string $def, bool $create = true)
    {
        $obj = explode('->', $def);
        $static = explode('::', $def);
        $grabbed = $def;

        if (2 === count($obj)) {
            $grabbed = [$create ? $this->get($obj[0]) : $obj[0], $obj[1]];
        } elseif (2 === count($static)) {
            $grabbed = $static;
        }

        return $grabbed;
    }

    /**
     * Call callback.
     *
     * Framework will trying to find required arguments by looking up in services
     * and given args.
     *
     * Callback example:
     *
     *  * regular callback is supported
     *  * 'ClassName->method', instance of class name will be instantiated by the framework
     *  * 'ClassName->method', static callable will be grabbed (['ClassName', 'method'])
     *
     * @param mixed      $callback
     * @param array|null $args
     *
     * @return mixed
     *
     * @throws BadMethodCallException   When method is not callable
     * @throws BadFunctionCallException When function is not callable
     */
    public function call($callback, array $args = null)
    {
        $func = is_string($callback) ? $this->grab($callback) : $callback;

        if (!is_callable($func)) {
            if (is_array($func)) {
                throw new \BadMethodCallException('Call to undefined method '.get_class($func[0]).'::'.$func[1]);
            } else {
                throw new \BadFunctionCallException('Call to undefined function '.$func);
            }
        }

        if (is_array($func)) {
            $rArgs = $this->resolveArgs(new \ReflectionMethod($func[0], $func[1]), $args);
        } else {
            $rArgs = $this->resolveArgs(new \ReflectionFunction($func), $args);
        }

        return call_user_func_array($func, $rArgs);
    }

    /**
     * Register service rule.
     *
     * Available options:
     *
     *  * class: class name
     *  * use: use this class name instead one defined as class name (in case your class is an interface or abstract class)
     *  * args: an array of required arguments
     *  * service: true or false
     *  * boot: a callable to call after instance creation
     *
     * Usage example:
     *
     *  * $app->rule('YourClassName') # class name will be service id
     *  * $app->rule('serviceId', 'YourClassName') # using class name
     *  * $app->rule('serviceId', ['class'=>'YourClassName']) # same as above
     *  * $app->rule('serviceId', $yourClassInstance) # using class instance
     *  * $app->rule('serviceId', ['class'=>'YourClassName','service'=>false])
     *
     * @param string $id
     * @param mixed  $rule
     *
     * @return array
     */
    public function set(string $id, $rule = null): App
    {
        unset($this->hive['_SERVICES'][$id]);

        if (is_callable($rule)) {
            $use = ['class' => $id, 'constructor' => $rule];
        } elseif (is_object($rule)) {
            $use = ['class' => get_class($rule)];
            $this->hive['_SERVICES'][$id] = $rule;
        } elseif (is_string($rule)) {
            $use = ['class' => $rule];
        } else {
            $use = $rule;
        }

        $this->hive['_SERVICE_RULES'][$id] = array_filter(array_replace(self::RULE_DEFAULT, [
            'class' => $id,
        ], $use ?? []), function ($val) {
            return null !== $val;
        });
        $this->hive['_SERVICE_ALIASES'][$this->hive['_SERVICE_RULES'][$id]['class']] = $id;

        return $this;
    }

    /**
     * Create/get instance of a class.
     *
     * @param string     $id
     * @param array|null $args
     *
     * @return mixed
     */
    public function get(string $id, array $args = null)
    {
        if (in_array($id, ['app', self::class])) {
            return $this;
        } elseif (isset($this->hive['_SERVICES'][$id])) {
            return $this->hive['_SERVICES'][$id];
        } elseif (isset($this->hive['_SERVICE_ALIASES'][$id])) {
            $id = $this->hive['_SERVICE_ALIASES'][$id];

            if (isset($this->hive['_SERVICES'][$id])) {
                return $this->hive['_SERVICES'][$id];
            }
        }

        return $this->create($id, $args);
    }

    /**
     * Create instance of a class.
     *
     * @param string     $id
     * @param array|null $args
     *
     * @return mixed
     *
     * @throws LogicException If instance is not instantiable, eg: Interface, Abstract class
     */
    public function create(string $id, array $args = null)
    {
        $rule = ($this->hive['_SERVICE_RULES'][$id] ?? []) + [
            'class' => $id,
            'args' => $args,
            'service' => false,
        ] + self::RULE_DEFAULT;

        $ref = new \ReflectionClass($rule['use'] ?? $rule['class']);

        if (!$ref->isInstantiable()) {
            throw new \LogicException('Unable to create instance for "'.$id.'". Please provide instantiable version of '.$ref->name);
        }

        if (isset($rule['constructor']) && is_callable($rule['constructor'])) {
            $instance = $this->call($rule['constructor']);

            if (!$instance instanceof $ref->name) {
                throw new \LogicException('Constructor of "'.$id.'" should return instance of '.$ref->name);
            }
        } elseif ($ref->hasMethod('__construct')) {
            $instance = $ref->newInstanceArgs($this->resolveArgs($ref->getMethod('__construct'), $rule['args']));
        } else {
            $instance = $ref->newInstance();
        }

        unset($ref);

        if ($rule['boot'] && is_callable($rule['boot'])) {
            call_user_func_array($rule['boot'], [$instance, $this]);
        }

        if ($rule['service']) {
            $this->hive['_SERVICES'][$id] = $instance;
        }

        return $instance;
    }

    /**
     * Return app hive.
     *
     * @return array
     */
    public function hive(): array
    {
        return $this->hive;
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
     *  * maps: to register maps
     *  * redirects: to register redirection
     *  * rules: to register services
     *  * listeners: to register event listener
     *  * groups: to register route groups
     *
     * @param string $file
     *
     * @return App
     */
    public function config(string $file): App
    {
        foreach (file_exists($file) ? Helper::exrequire($file, []) : [] as $key => $val) {
            $lkey = strtolower($key);

            if (isset(self::CONFIG_MAP[$lkey])) {
                $call = self::CONFIG_MAP[$lkey];

                foreach ((array) $val as $arg) {
                    $args = array_values((array) $arg);

                    $this->$call(...$args);
                }
            } else {
                $this->offsetSet($key, $val);
            }
        }

        return $this;
    }

    /**
     * Massive set.
     *
     * @param array $values
     *
     * @return App
     */
    public function mset(array $values): App
    {
        foreach ($values as $key => $val) {
            $this->offsetSet($key, $val);
        }

        return $this;
    }

    /**
     * Massive clear.
     *
     * @param ...string $keys
     *
     * @return App
     */
    public function mclear(string ...$keys): App
    {
        foreach ($keys as $key) {
            $this->offsetUnset($key);
        }

        return $this;
    }

    /**
     * Send content and headers.
     *
     * @return App
     */
    public function send(): App
    {
        $this->sendHeaders();
        $this->sendContent();

        return $this;
    }

    /**
     * Send content.
     *
     * @return App
     */
    public function sendContent(): App
    {
        if ($this->hive['QUIET'] || $this->hive['SENT']) {
            return $this;
        }

        $this->hive['SENT'] = true;

        if ($this->hive['KBPS'] <= 0) {
            echo $this->hive['OUTPUT'];

            return $this;
        }

        $now = microtime(true);
        $ctr = 0;

        foreach (str_split($this->hive['OUTPUT'], 1024) as $part) {
            // Throttle output
            ++$ctr;

            if ($ctr / $this->hive['KBPS'] > ($elapsed = microtime(true) - $now) && !connection_aborted()) {
                usleep((int) (1e6 * ($ctr / $this->hive['KBPS'] - $elapsed)));
            }

            echo $part;
        }

        return $this;
    }

    /**
     * Send response headers and cookies.
     *
     * @return App
     */
    public function sendHeaders(): App
    {
        if ($this->hive['CLI'] || headers_sent()) {
            return $this;
        }

        foreach ($this->cookieCollectAll() as $name => $value) {
            setcookie($name, ...$value);
        }

        foreach ($this->hive['RESPONSE'] as $name => $value) {
            header($name.': '.$value);
        }

        header($this->hive['PROTOCOL'].' '.$this->hive['CODE'].' '.$this->hive['STATUS'], true);

        return $this;
    }

    /**
     * Convert and modify trace as string.
     *
     * @param array|null &$trace
     *
     * @return string
     */
    public function tracify(array &$trace = null): string
    {
        if (!$trace) {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_shift($trace);
        }

        $trace = array_filter($trace, function ($frame) {
            return
                isset($frame['file']) &&
                (
                    $this->hive['DEBUG'] > 1
                    || (__FILE__ !== $frame['file'] || $this->hive['DEBUG'])
                    && (
                        empty($frame['function'])
                        || !preg_match('/^(?:(?:trigger|user)_error|__call|call_user_func)/', $frame['function'])
                    )
                )
            ;
        });

        $out = '';
        $eol = "\n";

        // Analyze stack trace
        foreach ($trace as $frame) {
            $line = '';

            if (isset($frame['class'])) {
                $line .= $frame['class'].$frame['type'];
            }

            if (isset($frame['function'])) {
                $line .= $frame['function'].'('.
                         ($this->hive['DEBUG'] > 2 && isset($frame['args']) ? Helper::csv($frame['args']) : '').
                         ')';
            }

            $src = Helper::fixslashes(str_replace($this->hive['TRACE'].'/', '', $frame['file']));
            $out .= '['.$src.':'.$frame['line'].'] '.$line.$eol;
        }

        return $out;
    }

    /**
     * Ignite the fire.
     */
    private function doRun(): void
    {
        if (!$this->hive['_BOOTED']) {
            $this->trigger(self::EVENT_BOOT);
            $this->hive['_BOOTED'] = true;
        }

        // We skip this part to test
        // @codeCoverageIgnoreStart
        if ($this->blacklisted()) {
            // Spammer detected
            throw new ResponseErrorException("Sorry, you're not allowed to visit this site.", 403);
        }
        // @codeCoverageIgnoreEnd

        if (!$this->hive['_ROUTES']) {
            // No routes defined
            throw new ResponseErrorException('No route specified');
        }

        $verb = $this->hive['VERB'];
        $entry = $this->hive['ENTRY'];
        $headers = $this->hive['HEADERS'];
        $type = $this->hive['CLI'] ? self::REQ_CLI : ((int) $this->hive['AJAX']) + 1;
        $modifier = $this->hive['CASELESS'] ? 'i' : '';
        $path = Helper::cutbefore($this->hive['PATH'], '/') ?: $this->hive['PATH'];
        $preflight = false;
        $cors = null;
        $allowed = [];

        if (isset($headers['Origin']) && $this->hive['CORS']['ORIGIN']) {
            $cors = $this->hive['CORS'];
            $preflight = isset($headers['Access-Control-Request-Method']);

            $this->hive['RESPONSE']['Access-Control-Allow-Origin'] = $cors['ORIGIN'];
            $this->hive['RESPONSE']['Access-Control-Allow-Credentials'] = Helper::reqstr($cors['CREDENTIALS']);
        }

        foreach ($this->hive['_ROUTES'] as $pattern => $routes) {
            if (self::noMatch($entry, $path, $pattern, $modifier, $args)) {
                continue;
            } elseif (isset($routes[$type][$verb])) {
                $route = $routes[$type];
            } elseif (isset($routes[0])) {
                $route = $routes[0];
            } else {
                continue;
            }

            if (!isset($route[$verb]) || $preflight) {
                $allowed = array_merge($allowed, array_keys($route));

                continue;
            }

            list($handler, $ttl, $kbps, $alias) = $route[$verb];

            // Capture values of route pattern tokens
            $this->hive['MATCH'] = array_shift($args);
            $this->hive['PARAMS'] = $args;
            // Save matching route
            $this->hive['ALIAS'] = $alias;
            $this->hive['PATTERN'] = $pattern;

            // Expose if defined
            if ($cors && $cors['EXPOSE']) {
                $this->hive['RESPONSE']['Access-Control-Expose-Headers'] = Helper::reqstr($cors['EXPOSE']);
            }

            if (is_string($handler)) {
                // Replace route pattern tokens in handler if any
                $handler = Helper::interpolate($handler, $args, '{}');
                $check = $this->grab($handler, false);

                if (is_array($check) && !class_exists($check[0])) {
                    throw new ResponseErrorException(null, 404);
                }
            }

            if ($this->trigger(self::EVENT_PREROUTE, $args)) {
                return;
            }

            // Process request
            $now = microtime(true);
            $body = '';
            $cached = null;

            if ($ttl && in_array($verb, ['GET', 'HEAD'])) {
                // Only GET and HEAD requests are cacheable
                $cache = $this->get('cache');
                $hash = Helper::hash($verb.' '.$this->hive['URI']).'.url';

                if ($cache->exists($hash)) {
                    if (isset($headers['If-Modified-Since']) && strtotime($headers['If-Modified-Since']) + $ttl > $now) {
                        $this->status(304)->sendHeaders();

                        return;
                    }

                    // Retrieve from cache backend
                    $cached = $cache->get($hash);
                    list($headers, $body) = $cached[0];

                    $this->hive['RESPONSE'] += $headers;
                    $this->expire((int) ($cached[1] + $ttl - $now))->sendHeaders();
                } else {
                    // Expire HTTP client-cached page
                    $this->expire($ttl);
                }
            } else {
                $this->expire(0);
            }

            if (null === $cached) {
                if (!$this->hive['RAW'] && !$this->hive['BODY']) {
                    $this->hive['BODY'] = file_get_contents('php://input');
                }

                $call = is_string($handler) ? $this->grab($handler) : $handler;

                if (!is_callable($call)) {
                    throw new ResponseErrorException(null, 405);
                }

                $this->hive['_HANDLE_MAPPER'] = $this->hive['MAPPER'];
                $result = $this->call($call, $args);
                $this->hive['_HANDLE_MAPPER'] = false;

                if (is_scalar($result)) {
                    $body = (string) $result;
                } elseif (is_array($result)) {
                    $body = json_encode($result);
                } elseif ($result instanceof \Closure) {
                    $result($this);
                }

                if ($this->hive['ERROR']) {
                    return;
                }

                if (isset($hash) && '' !== $body) {
                    $headers = $this->hive['RESPONSE'];
                    unset($headers['Set-Cookie']);

                    // Save to cache backend
                    $cache->set($hash, [$headers, $body], $ttl);
                }

                if ($this->trigger(self::EVENT_POSTROUTE, $args)) {
                    return;
                }
            }

            $this->hive['OUTPUT'] = $body;
            $this->hive['KBPS'] = $kbps;

            $this->send();

            if ('OPTIONS' !== $verb) {
                return;
            }
        }

        if (!$allowed) {
            // URL doesn't match any route
            throw new ResponseErrorException(null, 404);
        }

        if (!$this->hive['CLI']) {
            // Unhandled HTTP method
            $allowed = Helper::reqstr(array_unique($allowed));

            $this->hive['RESPONSE']['Allow'] = $allowed;

            if ($cors) {
                $this->hive['RESPONSE']['Access-Control-Allow-Methods'] = 'OPTIONS,'.$allowed;

                if ($cors['HEADERS']) {
                    $this->hive['RESPONSE']['Access-Control-Allow-Headers'] = Helper::reqstr($cors['HEADERS']);
                }

                if ($cors['TTL'] > 0) {
                    $this->hive['RESPONSE']['Access-Control-Max-Age'] = $cors['TTL'];
                }
            }

            if ('OPTIONS' !== $verb) {
                throw new ResponseErrorException(null, 405);
            }
        }

        $this->sendHeaders();
    }

    /**
     * Resolve function/method arguments.
     *
     * @param ReflectionFunctionAbstract $ref
     * @param array|null                 $args
     *
     * @return array
     */
    private function resolveArgs(\ReflectionFunctionAbstract $ref, array $args = null): array
    {
        if (0 === $ref->getNumberOfParameters()) {
            return [];
        }

        $resolved = [];
        $use = (array) $args;
        $pArgs = array_filter($use, 'is_numeric', ARRAY_FILTER_USE_KEY);

        foreach ($ref->getParameters() as $param) {
            if ($param->getClass()) {
                $resolved[] = $this->resolveClassArg($param, $use, $pArgs);
            } elseif (array_key_exists($param->name, $use)) {
                $resolved[] = is_string($use[$param->name]) ? $this->resolveArg($use[$param->name]) : $use[$param->name];
            } elseif ($param->isVariadic()) {
                $resolved = array_merge($resolved, $pArgs);
            } elseif ($pArgs) {
                $resolved[] = array_shift($pArgs);
            }
        }

        return $resolved;
    }

    /**
     * Resolve string argument.
     *
     * @param string $val
     *
     * @return mixed
     */
    private function resolveArg(string $val)
    {
        if (class_exists($val)) {
            return $this->get($val);
        } elseif (preg_match('/(.+)?%(.+)%(.+)?/', $val, $match)) {
            // assume it does exists in hive
            $var = Helper::ref($match[2], $this->hive, false);

            if (isset($var)) {
                return ($match[1] ?? '').$var.($match[3] ?? '');
            }

            // it is service alias
            return $this->get($match[2]);
        }

        return $val;
    }

    /**
     * Resolve class arguments.
     *
     * @param ReflectionParameter $ref
     * @param array               &$lookup
     * @param array               &$pArgs
     *
     * @return mixed
     */
    private function resolveClassArg(\ReflectionParameter $ref, array &$lookup, array &$pArgs)
    {
        $classname = $ref->getClass()->name;

        if (!isset($lookup[$ref->name])) {
            if ($pArgs && $pArgs[0] instanceof $classname) {
                return array_shift($pArgs);
            }

            return $this->get($classname);
        }

        // special handling for converting REQ.PARAMS to mapper instance
        if ($this->hive['_HANDLE_MAPPER'] && is_subclass_of($classname, Mapper::class)) {
            return $this->resolveMapperArg($classname, $ref->name, $lookup);
        }

        return is_string($lookup[$ref->name]) ? $this->resolveArg($lookup[$ref->name]) : $lookup[$ref->name];
    }

    /**
     * Resolve mapper parameter.
     *
     * @param string $className
     * @param string $paramName
     * @param array  &$lookup
     *
     * @return Mapper
     */
    private function resolveMapperArg(string $className, string $paramName, array &$lookup): Mapper
    {
        $mapper = $this->get($className);
        $keys = $mapper->getKeys();
        $kcount = count($keys);
        $vals = Helper::pickstartsat($lookup, $paramName, $kcount);
        $vcount = count($vals);

        if ($vcount !== $kcount) {
            throw new ResponseErrorException('Insufficient primary keys value, expect value of "'.implode(', ', $keys).'"');
        }

        $use = array_values($vals);
        $lookup = array_diff_key($lookup, $vals);

        $mapper->find(...$use);

        if ($mapper->dry()) {
            throw new ResponseErrorException('Record of '.$mapper->getTable().' not found ('.$this->hive['VERB'].' '.$this->hive['PATH'].')', 404);
        }

        return $mapper;
    }

    /**
     * Start session.
     *
     * @param string $key
     */
    private function sessionStart(string $key = 'SESSION'): void
    {
        if ('SESSION' === $key && !headers_sent() && PHP_SESSION_ACTIVE !== session_status()) {
            session_start();

            if ($this->hive['_SESSION_DRY']) {
                $this->hive['_SESSION_DRY'] = false;
                $this->hive['SESSION'] = $GLOBALS['_SESSION'] ?? [];
            }
        }
    }

    /**
     * Commit session.
     */
    private function sessionCommit(): void
    {
        if ($this->hive['_SESSION_FLY'] && PHP_SESSION_ACTIVE === session_status()) {
            $this->hive['_SESSION_FLY'] = false;

            if ($this->hive['_SESSION_INVALID']) {
                session_unset();
                session_destroy();
            } else {
                $GLOBALS['_SESSION'] = $this->hive['SESSION'];

                session_commit();
            }
        }
    }

    /**
     * Collect cookies.
     *
     * @return array
     */
    private function cookieCollectAll(): array
    {
        $jar = array_values($this->hive['JAR']);
        $cookies = [];

        foreach ($this->hive['COOKIE'] as $name => $value) {
            if (!isset($this->init['COOKIE'][$name]) || $this->init['COOKIE'][$name] !== $value) {
                $cookies[$name] = self::cookieModifySet($jar, $value);
            }
        }

        foreach ($this->init['COOKIE'] as $name => $value) {
            if (!isset($this->hive['COOKIE'][$name])) {
                $cookies[$name] = self::cookieModifySet($jar, ['', '_jar' => [strtotime('-1 year')]]);
            }
        }

        return $cookies;
    }

    /**
     * Modify cookie before send.
     *
     * @param array $jar
     * @param mixed $val
     *
     * @return array
     */
    private static function cookieModifySet(array $jar, $val): array
    {
        $custom = [];

        if (is_array($val) && isset($val['_jar'])) {
            $custom = $val['_jar'];
            unset($val['_jar']);
        }

        return array_merge([is_array($val) ? array_shift($val) : $val], array_replace($jar, $custom));
    }

    /**
     * Perform pattern match, return true if no match.
     *
     * @param string     $entry
     * @param string     $path
     * @param string     $pattern
     * @param string     $modifier
     * @param array|null &$args
     *
     * @return bool
     */
    private static function noMatch(string $entry, string $path, string $pattern, string $modifier, array &$args = null): bool
    {
        $wild = preg_replace_callback(
            '/\{(\w+)(?:\:(?:(alnum|alpha|digit|lower|upper|word)|(\w+)))?\}/',
            function ($m) {
                return '(?<'.$m[1].'>[[:'.(empty($m[2]) ? 'alnum' : $m[2]).':]]+)';
            },
            $pattern
        );
        $regex = '~^'.$entry.$wild.'$~'.$modifier;

        $res = preg_match($regex, $entry.$path, $match);

        $args = [];
        $prev = null;

        foreach ($match as $key => $value) {
            if ((is_string($key) || 0 === $key) || (is_numeric($key) && !is_string($prev))) {
                $args[$key] = $value;
            }

            $prev = $key;
        }

        return !$res;
    }

    /**
     * Decide log level based on HTTP error code.
     *
     * @param int $code
     *
     * @return string
     */
    private static function decideLogLevel(int $code = 0): string
    {
        if (500 === $code) {
            return Logger::LEVEL_ERROR;
        }

        return Logger::LEVEL_INFO;
    }

    /**
     * Convenience method to check hive value.
     *
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        $this->sessionStart($offset);

        return isset($this->hive[$offset]);
    }

    /**
     * Convenience method to get hive value.
     *
     * @param string $offset
     *
     * @return mixed
     */
    public function &offsetGet($offset)
    {
        $this->sessionStart($offset);

        if (!isset($this->hive[$offset])) {
            $this->hive[$offset] = null;
        }

        return $this->hive[$offset];
    }

    /**
     * Convenience method to set hive value.
     *
     * @param string $offset
     * @param mixed  $value
     */
    public function offsetSet($offset, $value)
    {
        $this->sessionStart($offset);

        if (isset($this->init[$offset]) && is_array($this->init[$offset])) {
            $this->hive[$offset] = array_replace_recursive($this->hive[$offset] ?? [], (array) $value);
        } else {
            $this->hive[$offset] = $value;
        }

        switch ($offset) {
            case 'ENCODING':
                ini_set('default_charset', $value);
                break;
            case 'ENTRY':
                $this->hive['REALM'] = $this->path($this->hive['PATH'], null, true, $this->hive['QUERY'], $this->hive['FRAGMENT']);
                break;
            case 'TZ':
                date_default_timezone_set($value);
                break;
        }
    }

    /**
     * Convenience method to clear hive value.
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        $this->sessionStart($offset);

        if ('COOKIE' === $offset) {
            $this->hive[$offset] = [];
        } elseif ('SESSION' === $offset) {
            $this->hive[$offset] = [];
            $this->hive['_SESSION_INVALID'] = true;
        } elseif (isset($this->init[$offset])) {
            $this->hive[$offset] = $this->init[$offset];
        } else {
            unset($this->hive[$offset]);
        }
    }
}

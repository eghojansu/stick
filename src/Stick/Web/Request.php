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

namespace Fal\Stick\Web;

use Fal\Stick\Util;

/**
 * Request class.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Request
{
    const AJAX = 'ajax';
    const ALL = 'all';
    const SYNC = 'sync';

    /**
     * @var string
     */
    protected $method;

    /**
     * @var bool
     */
    protected $secure;

    /**
     * @var string
     */
    protected $scheme;

    /**
     * @var string
     */
    protected $host;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var string
     */
    protected $base;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $script;

    /**
     * @var string
     */
    protected $requestUri;

    /**
     * @var string
     */
    protected $baseUrl;

    /**
     * @var string
     */
    protected $front;

    /**
     * @var bool
     */
    protected $ajax;

    /**
     * @var string
     */
    protected $ip;

    /**
     * @var string
     */
    protected $userAgent;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var ParameterBag
     */
    public $query;

    /**
     * @var ParameterBag
     */
    public $request;

    /**
     * @var ParameterBag
     */
    public $cookies;

    /**
     * @var FileBag
     */
    public $files;

    /**
     * @var ServerBag
     */
    public $server;

    /**
     * @var HeaderBag
     */
    public $headers;

    /**
     * Create request from globals.
     *
     * @return Request
     */
    public static function createFromGlobals(): Request
    {
        $request = new static($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);

        if (0 === strpos($request->headers->first('Content-Type') ?? '', 'application/x-www-form-urlencoded') && in_array($request->server->get('REQUEST_METHOD') ?? 'GET', array('PUT', 'DELETE', 'PATCH'))) {
            parse_str($request->getContent(), $data);
            $request->request = new ParameterBag($data);
        }

        return $request;
    }

    /**
     * Creates a Request based on a given URI and configuration.
     *
     * The information contained in the URI always take precedence
     * over the other information (server and parameters).
     *
     * @param string               $uri        The URI
     * @param string               $method     The HTTP method
     * @param array                $parameters The query (GET) or request (POST) parameters
     * @param array                $cookies    The request cookies ($_COOKIE)
     * @param array                $files      The request files ($_FILES)
     * @param array                $server     The server parameters ($_SERVER)
     * @param string|resource|null $content    The raw body data
     *
     * @return Request
     */
    public static function create(string $uri, string $method = null, array $parameters = null, array $cookies = null, array $files = null, array $server = null, $content = null): Request
    {
        $server = array_replace(array(
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'HTTP_HOST' => 'localhost',
            'HTTP_USER_AGENT' => 'Fal/Stick',
            'HTTP_ACCEPT' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'HTTP_ACCEPT_LANGUAGE' => 'en-us,en;q=0.5',
            'HTTP_ACCEPT_CHARSET' => 'ISO-8859-1,utf-8;q=0.7,*;q=0.7',
            'REMOTE_ADDR' => '::1',
            'SCRIPT_NAME' => '',
            'SCRIPT_FILENAME' => '',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'REQUEST_TIME' => time(),
        ), $server ?? array(), array(
            'PATH_INFO' => '',
            'REQUEST_METHOD' => strtoupper($method ?? 'GET'),
        ));

        $components = parse_url($uri);

        if (isset($components['host'])) {
            $server['SERVER_NAME'] = $components['host'];
            $server['HTTP_HOST'] = $components['host'];
        }

        if (isset($components['scheme'])) {
            if ('https' === $components['scheme']) {
                $server['HTTPS'] = 'on';
                $server['SERVER_PORT'] = 443;
            } else {
                unset($server['HTTPS']);
                $server['SERVER_PORT'] = 80;
            }
        }

        if (isset($components['port'])) {
            $server['SERVER_PORT'] = $components['port'];
            $server['HTTP_HOST'] .= ':'.$components['port'];
        }

        if (isset($components['user'])) {
            $server['PHP_AUTH_USER'] = $components['user'];
        }

        if (isset($components['pass'])) {
            $server['PHP_AUTH_PW'] = $components['pass'];
        }

        if (!isset($components['path'])) {
            $components['path'] = '/';
        }

        $request = array();
        $query = $parameters ?? array();

        if (in_array($server['REQUEST_METHOD'], array('POST', 'PUT', 'DELETE', 'PATCH'))) {
            if ('PATCH' !== $server['REQUEST_METHOD'] && !isset($server['CONTENT_TYPE'])) {
                $server['CONTENT_TYPE'] = 'application/x-www-form-urlencoded';
            }

            $request = $parameters;
            $query = array();
        }

        if (isset($components['query'])) {
            parse_str(html_entity_decode($components['query']), $qs);

            $query = array_replace($qs, $query);
        }

        $server['QUERY_STRING'] = http_build_query($query, '', '&');
        $server['REQUEST_URI'] = $components['path'].rtrim('?'.$server['QUERY_STRING'], '?');

        return new static($query, $request, $cookies, $files, $server, $content);
    }

    /**
     * Class constructor.
     *
     * @param array|null $query
     * @param array|null $request
     * @param array|null $cookies
     * @param array|null $files
     * @param array|null $server
     * @param mixed      $content
     */
    public function __construct(array $query = null, array $request = null, array $cookies = null, array $files = null, array $server = null, $content = null)
    {
        $this->query = new ParameterBag($query);
        $this->request = new ParameterBag($request);
        $this->cookies = new ParameterBag($cookies);
        $this->files = new FileBag($files);
        $this->server = new ServerBag($server);
        $this->headers = new HeaderBag($this->server->getHeaders());
        $this->content = $content;
    }

    /**
     * Clones a request and overrides some of its parameters.
     *
     * @param array $query   The GET parameters
     * @param array $request The POST parameters
     * @param array $cookies The COOKIE parameters
     * @param array $files   The FILES parameters
     * @param array $server  The SERVER parameters
     *
     * @return static
     */
    public function duplicate(array $query = null, array $request = null, array $cookies = null, array $files = null, array $server = null)
    {
        $dup = clone $this;

        if (null !== $query) {
            $dup->query = new ParameterBag($query);
        }

        if (null !== $request) {
            $dup->request = new ParameterBag($request);
        }

        if (null !== $cookies) {
            $dup->cookies = new ParameterBag($cookies);
        }

        if (null !== $files) {
            $dup->files = new FileBag($files);
        }

        if (null !== $server) {
            $dup->server = new ServerBag($server);
            $dup->headers = new HeaderBag($dup->server->getHeaders());
        }

        $dup->method = null;
        $dup->secure = null;
        $dup->scheme = null;
        $dup->host = null;
        $dup->port = null;
        $dup->base = null;
        $dup->path = null;
        $dup->script = null;
        $dup->requestUri = null;
        $dup->baseUrl = null;
        $dup->front = null;
        $dup->ajax = null;
        $dup->ip = null;
        $dup->userAgent = null;

        return $dup;
    }

    /**
     * Clones the current request.
     */
    public function __clone()
    {
        $this->query = clone $this->query;
        $this->request = clone $this->request;
        $this->cookies = clone $this->cookies;
        $this->files = clone $this->files;
        $this->server = clone $this->server;
        $this->headers = clone $this->headers;
    }

    /**
     * Returns true if request ajax.
     *
     * @return bool
     */
    public function isAjax(): bool
    {
        if (null === $this->ajax) {
            $this->ajax = 'XMLHttpRequest' === $this->headers->first('X-Requested-With');
        }

        return $this->ajax;
    }

    /**
     * Returns client ip.
     *
     * @return string
     */
    public function getIp(): string
    {
        if (null === $this->ip) {
            if ($ip = $this->headers->first('X-Client-Ip')) {
                $this->ip = $ip;
            } elseif ($ip = $this->headers->first('X-Forwarded-For')) {
                $this->ip = strstr($ip.',', ',', true);
            } elseif ($ip = $this->server->get('REMOTE_ADDR')) {
                $this->ip = $ip;
            } else {
                $this->ip = $this->server->get('SERVER_ADDR') ?? '::1';
            }
        }

        return $this->ip;
    }

    /**
     * Returns user agent.
     *
     * @return string
     */
    public function getUserAgent(): string
    {
        if (null === $this->userAgent) {
            $this->userAgent = $this->headers->get('X-Operamini-Phone-Ua') ?? $this->headers->get('X-Skyfire-Phone') ?? $this->headers->get('User-Agent') ?? 'Fal/Stick';
        }

        return $this->userAgent;
    }

    /**
     * Returns request method.
     *
     * @return string
     */
    public function getMethod(): string
    {
        if (null === $this->method) {
            $method = $this->headers->first('X-Http-Method-Override') ?? $this->server->get('REQUEST_METHOD') ?? 'GET';

            if ('POST' === $method && $override = $this->request->get('_method')) {
                $method = $override;
            }

            $this->method = strtoupper($method);
        }

        return $this->method;
    }

    /**
     * Returns true if method is identical.
     *
     * @param string $method
     *
     * @return bool
     */
    public function isMethod(string $method): bool
    {
        return strtoupper($method) === $this->getMethod();
    }

    /**
     * Checks whether the method is cacheable or not.
     *
     * @see https://tools.ietf.org/html/rfc7231#section-4.2.3
     *
     * @return bool True for GET and HEAD, false otherwise
     */
    public function isMethodCacheable()
    {
        return in_array($this->getMethod(), array('GET', 'HEAD'));
    }

    /**
     * Returns true if request is secure.
     *
     * @return bool
     */
    public function isSecure(): bool
    {
        if (null === $this->secure) {
            if ($secure = $this->headers->first('X-Forwarded-Proto')) {
                $this->secure = 'https' === $secure;
            } elseif ($secure = $this->server->get('HTTPS')) {
                $this->secure = 'on' === $secure;
            } else {
                $this->secure = false;
            }
        }

        return $this->secure;
    }

    /**
     * Returns request scheme.
     *
     * @return string
     */
    public function getScheme(): string
    {
        if (null === $this->scheme) {
            $this->scheme = $this->isSecure() ? 'https' : 'http';
        }

        return $this->scheme;
    }

    /**
     * Returns request host.
     *
     * @return string
     */
    public function getHost(): string
    {
        if (null === $this->host) {
            $this->host = $this->server->get('SERVER_NAME') ?? 'localhost';
        }

        return $this->host;
    }

    /**
     * Returns request port.
     *
     * @return int
     */
    public function getPort(): int
    {
        if (null === $this->port) {
            $this->port = intval($this->headers->first('X-Forwarded-Port') ?? $this->server->get('SERVER_PORT') ?? ($this->isSecure() ? 443 : 80));
        }

        return $this->port;
    }

    /**
     * Returns the user.
     *
     * @return string|null
     */
    public function getUser(): ?string
    {
        return $this->server->get('PHP_AUTH_USER');
    }

    /**
     * Returns the password.
     *
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->server->get('PHP_AUTH_PW');
    }

    /**
     * Returns the user info.
     *
     * @return string A user name and, optionally, scheme-specific information about how to gain authorization to access the server
     */
    public function getUserInfo(): ?string
    {
        $userinfo = $this->getUser();

        if ('' != $pass = $this->getPassword()) {
            $userinfo .= ":$pass";
        }

        return $userinfo;
    }

    /**
     * Returns the HTTP host being requested.
     *
     * The port name will be appended to the host if it's non-standard.
     *
     * @return string
     */
    public function getHttpHost(): string
    {
        $scheme = $this->getScheme();
        $port = $this->getPort();

        if (('http' == $scheme && 80 == $port) || ('https' == $scheme && 443 == $port)) {
            return $this->getHost();
        }

        return $this->getHost().':'.$port;
    }

    /**
     * Gets the scheme and HTTP host.
     *
     * If the URL was called with basic authentication, the user
     * and the password are not added to the generated string.
     *
     * @return string The scheme and HTTP host
     */
    public function getSchemeAndHttpHost()
    {
        return $this->getScheme().'://'.$this->getHttpHost();
    }

    /**
     * Returns the requested URI (path and query string).
     *
     * @return string The raw URI (i.e. not URI decoded)
     */
    public function getRequestUri(): string
    {
        if (null === $this->requestUri) {
            $uri = '';

            if ($this->server->exists('REQUEST_URI')) {
                $components = parse_url($this->server->get('REQUEST_URI'));

                if (isset($components['path'])) {
                    $uri = $components['path'];
                }

                if (isset($components['query'])) {
                    $uri .= '?'.$components['query'];
                }
            }

            $this->requestUri = $uri;
        }

        return $this->requestUri;
    }

    /**
     * Generates the normalized query string for the Request.
     *
     * It builds a normalized query string, where keys/value pairs are alphabetized
     * and have consistent escaping.
     *
     * @return string|null A normalized query string for the Request
     */
    public function getQueryString(): ?string
    {
        return $this->server->get('QUERY_STRING');
    }

    /**
     * Returns the root URL from which this request is executed.
     *
     * The base URL never ends with a /.
     *
     * This is similar to getBasePath(), except that it also includes the
     * script filename (e.g. index.php) if one exists.
     *
     * @return string The raw URL (i.e. not urldecoded)
     */
    public function getBaseUrl(): string
    {
        if (null === $this->baseUrl) {
            $this->baseUrl = $this->getSchemeAndHttpHost().$this->getBase();
        }

        return $this->baseUrl;
    }

    /**
     * Generates a normalized URI (URL) for the Request.
     *
     * @return string A normalized URI (URL) for the Request
     *
     * @see getQueryString()
     */
    public function getUri()
    {
        if ($qs = $this->getQueryString()) {
            $qs = '?'.$qs;
        }

        return $this->getBaseUrl().$this->getPath().$qs;
    }

    /**
     * Returns entry script filepath.
     *
     * @return string
     */
    public function getScript(): string
    {
        if (null === $this->script) {
            $script = 'cli' === PHP_SAPI || 'phpdbg' === PHP_SAPI ? '' : str_replace('\\', '/', $this->server->get('SCRIPT_NAME') ?? '');

            $this->script = $script;
        }

        return $this->script;
    }

    /**
     * Returns entry script filename.
     *
     * @return string
     */
    public function getFront(): string
    {
        if (null === $this->front) {
            $this->front = basename($this->getScript());
        }

        return $this->front;
    }

    /**
     * Returns base path.
     *
     * @return string
     */
    public function getBase(): string
    {
        if (null === $this->base) {
            $this->base = rtrim(dirname($this->getScript()), '/');
        }

        return $this->base;
    }

    /**
     * Returns path.
     *
     * @return string
     */
    public function getPath(): string
    {
        if (null === $this->path) {
            $url = parse_url($this->server->get('REQUEST_URI') ?? '/');
            $script = $this->getScript();
            $path = $script && 0 === strpos($url['path'], $script) ? substr($url['path'], strlen($script)) : $url['path'];

            $this->path = $path ? urldecode($path) : '/';
        }

        return $this->path;
    }

    /**
     * Assign request path.
     *
     * @param string $path
     *
     * @return Request
     */
    public function setPath(string $path): Request
    {
        $this->server->set('REQUEST_URI', $this->getBase().$path);

        if (false !== strpos($path, '?')) {
            list($path, $qs) = explode('?', $path);
            parse_str($qs, $query);

            $this->query->replace($query);
            $this->server->set('QUERY_STRING', $qs);
        }

        $this->path = $path;
        $this->requestUri = null;

        return $this;
    }

    /**
     * Returns request mode name.
     *
     * @return string
     */
    public function getMode(): string
    {
        if ($this->isAjax()) {
            return static::AJAX;
        }

        return static::SYNC;
    }

    /**
     * Returns request hash.
     *
     * @return string
     */
    public function getHash(): string
    {
        return Util::hash($this->getMethod().' '.$this->getPath());
    }

    /**
     * Returns request content.
     *
     * @param bool $asResource
     *
     * @return mixed
     */
    public function getContent(bool $asResource = false)
    {
        $currentContentIsResource = is_resource($this->content);

        if ($asResource) {
            if ($currentContentIsResource) {
                rewind($this->content);

                return $this->content;
            }

            // Content passed in parameter (test)
            if (is_string($this->content)) {
                $resource = fopen('php://temp', 'r+');
                fwrite($resource, $this->content);
                rewind($resource);

                return $resource;
            }

            $this->content = null;

            return fopen('php://input', 'rb');
        }

        if ($currentContentIsResource) {
            rewind($this->content);

            return stream_get_contents($this->content);
        }

        if (null === $this->content) {
            $this->content = file_get_contents('php://input');
        }

        return $this->content;
    }
}

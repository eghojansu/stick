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
 * Request and server related helper
 */
class Request implements \ArrayAccess
{
    /** Mapped PHP globals */
    const GLOBALS = 'GET|POST|COOKIE|REQUEST|SESSION|FILES|SERVER|ENV';

    /** @var array */
    protected $init;

    /** @var array */
    protected $hive;

    /** @var Helper */
    protected $helper;

    /**
     * Class constructor
     */
    public function __construct(Helper $helper)
    {
        $cli = 'cli' === PHP_SAPI;

        // @codeCoverageIgnoreStart
        if (function_exists('apache_setenv')) {
            // Work around Apache pre-2.4 VirtualDocumentRoot bug
            $_SERVER['DOCUMENT_ROOT'] = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['SCRIPT_FILENAME']);
            apache_setenv('DOCUMENT_ROOT', $_SERVER['DOCUMENT_ROOT']);
        }
        // @codeCoverageIgnoreEnd

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if ('CONTENT_TYPE' === $key || 'CONTENT_LENGTH' === $key) {
                $headers[dashcase($key)] = $value;
            } elseif (0 === strpos($key, 'HTTP_')) {
                $headers[dashcase(substr($key, 5))] = $value;
            }
        }

        $_SERVER['SERVER_NAME']     = $_SERVER['SERVER_NAME'] ?? gethostname();
        $_SERVER['SERVER_PROTOCOL'] = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0';
        $_SERVER['REQUEST_METHOD']  = $headers['X-HTTP-Method-Override'] ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($cli) {
            $_SERVER['REQUEST_METHOD'] = 'GET';
            if (!isset($_SERVER['argv'][1])) {
                $_SERVER['argc']++;
                $_SERVER['argv'][1] = '/';
            }

            if ('/' === $_SERVER['argv'][1][0]) {
                $_SERVER['REQUEST_URI'] = $_SERVER['argv'][1];
            } else {
                $req = '';
                $opts = '';
                for ($i=1; $i < $_SERVER['argc']; $i++) {
                    $arg = $_SERVER['argv'][$i];
                    if ('-' === $arg[0]) {
                        $m = explode('=', $arg);
                        if ('-' === $arg[1]) {
                            $opts .= '&' . urlencode(substr($m[0], 2)) . '=';
                        } else {
                            $opts .= '&' . implode('=&', array_map('urlencode', str_split(substr($m[0], 1)))) . '=';
                        }
                        $opts = ltrim($opts, '&') . ($m[1] ?? '');
                    } else {
                        $req .= '/' . $arg;
                    }
                }

                $_SERVER['REQUEST_URI'] = '/' . ltrim(rtrim($req . '?'. $opts, '?'), '/');
                parse_str($opts, $GLOBALS['_GET']);
            }
        }

        $base   = $cli ? '' : rtrim(fixslashes(dirname($_SERVER['SCRIPT_NAME'])), '/');
        $uri    = parse_url((preg_match('/^\w+:\/\//', $_SERVER['REQUEST_URI']) ? '':'//'. $_SERVER['SERVER_NAME']). $_SERVER['REQUEST_URI']);
        $path   = preg_replace('/^' . preg_quote($base, '/') . '/', '', $uri['path']);
        $scheme = 'on' === ($_SERVER['HTTPS'] ?? '') || 'https' === ($headers['X-Forwarded-Proto'] ?? '') ? 'https' : 'http';
        $port   = $headers['X-Forwarded-Port'] ?? $_SERVER['SERVER_PORT'] ?? 80;
        $jar    = [
            'expire' => 0,
            'path' => $base ?: '/',
            'domain' => is_int(strpos($_SERVER['SERVER_NAME'], '.')) && !filter_var($_SERVER['SERVER_NAME'], FILTER_VALIDATE_IP)? $_SERVER['SERVER_NAME'] : '',
            'secure' => ('https' === $scheme),
            'httponly' => true
        ];

        $_SERVER['REQUEST_URI']   = $uri['path'].(isset($uri['query'])?'?'.$uri['query']:'').(isset($uri['fragment'])?'#'.$uri['fragment']:'');
        $_SERVER['DOCUMENT_ROOT'] = realpath($_SERVER['DOCUMENT_ROOT']);

        session_cache_limiter('');
        call_user_func_array('session_set_cookie_params', $jar);

        $this->hive = ['HEADERS' => $headers];
        $this->hive = [
            'AGENT'    => $this->agent(),
            'AJAX'     => $this->ajax(),
            'BASE'     => $base,
            'BODY'     => '',
            'CLI'      => $cli,
            'FRAGMENT' => $uri['fragment'] ?? '',
            'HEADERS'  => $headers,
            'HOST'     => $_SERVER['SERVER_NAME'],
            'IP'       => $this->ip(),
            'JAR'      => $jar,
            'PATH'     => urldecode($path),
            'PORT'     => $port,
            'QUERY'    => $uri['query'] ?? '',
            'REALM'    => $scheme . '://' . $_SERVER['SERVER_NAME'] . ($port && !in_array($port, [80, 443])? (':' . $port):'') . $_SERVER['REQUEST_URI'],
            'ROOT'     => $_SERVER['DOCUMENT_ROOT'],
            'SCHEME'   => $scheme,
            'URI'      => $_SERVER['REQUEST_URI'],
            'METHOD'   => $_SERVER['REQUEST_METHOD'],
            'XFRAME'   => 'SAMEORIGIN',
        ];
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

        $this->helper = $helper;
    }

    /**
     * Get client browser name
     *
     * @return string
     */
    public function agent(): string
    {
        return (
            $this->hive['HEADERS']['X-Operamini-Phone-Ua'] ??
            $this->hive['HEADERS']['X-Skyfire-Phone'] ??
            $this->hive['HEADERS']['User-Agent'] ??
            ''
        );
    }

    /**
     * Get XMLHttpRequest (ajax) status
     *
     * @return bool
     */
    public function ajax(): bool
    {
        return 'xmlhttprequest' === strtolower($this->hive['HEADERS']['X-Requested-With'] ?? '');
    }

    /**
     * Get client ip address
     *
     * @return string
     */
    public function ip(): string
    {
        return
            $this->hive['HEADERS']['Client-Ip'] ?? (
                isset($this->hive['HEADERS']['X-Forwarded-For']) ?
                    explode(',', $this->hive['HEADERS']['X-Forwarded-For'])[0] :
                    ($_SERVER['REMOTE_ADDR'] ?? '')
            );
    }

    /**
     * Expose content
     *
     * @return array
     */
    public function data(): array
    {
        return $this->hive;
    }

    /**
     * Convenient way to check hive item
     *
     * @param  string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        $ref = $this->ref($offset, false);

        return isset($ref);
    }

    /**
     * Convenient way get hive item
     *
     * @param  string $offset
     *
     * @return mixed
     */
    public function &offsetGet($offset)
    {
        $ref =& $this->ref($offset);

        return $ref;
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
    public function offsetSet($offset, $value)
    {
        if (is_object($value) || is_resource($value)) {
            // No support for object and resource
            throw new \InvalidArgumentException('Object value is not allowed');
        }

        preg_match('/^(?:(?:(?:GET|POST)\b(.+))|(JAR\b.+))$/', $offset, $match);
        if (isset($match[1]) && $match[1]) {
            $this->offsetSet('REQUEST' . $match[1], $value);
        } elseif ('URI' === $offset) {
            $_SERVER['REQUEST_URI'] = $value;
        } elseif ('METHOD' === $offset) {
            $_SERVER['REQUEST_METHOD'] = $value;
        }

        $ref =& $this->ref($offset);
        $ref = $value;

        if (isset($match[2]) && $match[2]) {
            $jar = $this->helper->unserialize($this->helper->serialize($this->hive['JAR']));
            $jar['expire'] -= microtime(true);

            call_user_func_array('session_set_cookie_params', $jar);
        }
    }

    /**
     * Convenient way to remove hive item
     *
     * @param  string $offset
     *
     * @return void
     */
    public function offsetUnset($offset)
    {
        preg_match('/^(?:(?:(?:GET|POST)\b(.+))|(?:COOKIE\.(.+))|(SESSION(?:\.(.+))?))$/', $offset, $match);

        if (isset($match[1]) && $match[1]) {
            $this->offsetSet('REQUEST' . $match[1], $value);
        } elseif (isset($match[2]) && $match[2]) {
            $this->offsetSet('REQUEST.' . $match[2], $value);
            $parts         = explode('.', $match[2]);
            $jar           = $this->hive['JAR'];
            $jar['expire'] = strtotime('-1 year');

            call_user_func_array('setcookie', array_merge([$parts[0], null], $jar));
            unset($_COOKIE[$parts[0]]);
        } elseif (isset($match[4]) && $match[4]) {
            $this->startSession();
        } elseif (isset($match[3]) && $match[3]) {
            $this->startSession();

            // End session
            session_unset();
            session_destroy();
            $this->offsetUnset('COOKIE.' . session_name());

            $this->sync('SESSION');
        }

        $parts = explode('.', $offset);

        if (empty($parts[1]) && array_key_exists($parts[0], $this->init)) {
            $this->hive[$parts[0]] = $this->init[$parts[0]];

            return;
        }

        $last  = count($parts) - 1;
        $var   =& $this->hive;

        foreach ($parts as $key => $part) {
            if (!is_array($var)) {
                break;
            }
            if ($last === $key) {
                unset($var[$part]);
            } else {
                $var =& $var[$part];
            }
        }
        unset($var);

        if (isset($match[3]) && $match[3]) {
            session_commit();
            session_start();
        }
    }

    /**
     * Get ref from $GLOBALS
     *
     * @param  string       $key
     * @param  bool|boolean $add
     * @return mixed
     */
    protected function &ref(string $key, bool $add = true)
    {
        $null  = null;
        $parts = explode('.', $key);

        $this->startSession('SESSION' === $parts[0]);

        if ($add) {
            $var =& $this->hive;
        } else {
            $var = $this->hive;
        }

        foreach ($parts as $part) {
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

        return $var;
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
}

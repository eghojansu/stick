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
 * Response helper
 */
class Response
{
    /** HTTP status codes (RFC 2616) */
    const
        HTTP_100 = 'Continue',
        HTTP_101 = 'Switching Protocols',
        HTTP_103 = 'Early Hints',
        HTTP_200 = 'OK',
        HTTP_201 = 'Created',
        HTTP_202 = 'Accepted',
        HTTP_203 = 'Non-Authorative Information',
        HTTP_204 = 'No Content',
        HTTP_205 = 'Reset Content',
        HTTP_206 = 'Partial Content',
        HTTP_300 = 'Multiple Choices',
        HTTP_301 = 'Moved Permanently',
        HTTP_302 = 'Found',
        HTTP_303 = 'See Other',
        HTTP_304 = 'Not Modified',
        HTTP_305 = 'Use Proxy',
        HTTP_307 = 'Temporary Redirect',
        HTTP_400 = 'Bad Request',
        HTTP_401 = 'Unauthorized',
        HTTP_402 = 'Payment Required',
        HTTP_403 = 'Forbidden',
        HTTP_404 = 'Not Found',
        HTTP_405 = 'Method Not Allowed',
        HTTP_406 = 'Not Acceptable',
        HTTP_407 = 'Proxy Authentication Required',
        HTTP_408 = 'Request Timeout',
        HTTP_409 = 'Conflict',
        HTTP_410 = 'Gone',
        HTTP_411 = 'Length Required',
        HTTP_412 = 'Precondition Failed',
        HTTP_413 = 'Request Entity Too Large',
        HTTP_414 = 'Request-URI Too Long',
        HTTP_415 = 'Unsupported Media Type',
        HTTP_416 = 'Requested Range Not Satisfiable',
        HTTP_417 = 'Expectation Failed',
        HTTP_500 = 'Internal Server Error',
        HTTP_501 = 'Not Implemented',
        HTTP_502 = 'Bad Gateway',
        HTTP_503 = 'Service Unavailable',
        HTTP_504 = 'Gateway Timeout',
        HTTP_505 = 'HTTP Version Not Supported';

    /** @var Request */
    protected $request;

    /** @var Helper */
    protected $helper;

    /** @var array Header list */
    protected $headers = [];

    /** @var array */
    protected $cookies = [];

    /** @var integer */
    protected $statusCode;

    /** @var string */
    protected $statusText;

    /** @var callable */
    protected $output;

    /** @var string */
    protected $body = '';

    /**
     * Class constructor
     *
     * @param Request $request
     * @param Helper $helper
     */
    public function __construct(Request $request, Helper $helper)
    {
        $this->request = $request;
        $this->helper = $helper;
        $this->status(200);
    }

    /**
     * Set HTTP status header; Return text equivalent of status code
     *
     * @param  int $code
     *
     * @return Response
     */
    public function status(int $code): Response
    {
        $this->statusCode = $code;
        $this->statusText = constant(static::class . '::HTTP_' . $code, '');

        return $this;
    }

    /**
     * Get statusCode
     *
     * @return integer
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get statusText
     *
     * @return string
     */
    public function getStatusText(): string
    {
        return $this->statusText;
    }

    /**
     * Remove header
     *
     * @param string $name
     *
     * @return Response
     */
    public function removeHeader(string $name = null): Response
    {
        if ($name) {
            foreach ($this->headers as $key => $content) {
                if (0 === strpos($content, "$name:")) {
                    unset($this->headers[$key]);
                }
            }
        } else {
            $this->headers = [];
        }

        return $this;
    }

    /**
     * Get headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get headers without cookie
     *
     * @return array
     */
    public function getHeadersWithoutCookie(): array
    {
        return preg_grep('/Set-Cookie\:/', $this->headers, PREG_GREP_INVERT);
    }

    /**
     * Get header
     *
     * @param string $name
     *
     * @return array
     */
    public function getHeader(string $name): string
    {
        $grep = preg_grep('/^' . $name . '\:/i', $this->headers);
        $header = '';

        if ($grep) {
            $header = trim(substr(current($grep), strlen($name) + 1));
        }

        return $header;
    }

    /**
     * Add headers
     *
     * @param  array       $headers
     *
     * @return Response
     */
    public function setHeaders(array $headers): Response
    {
        foreach ($headers as $name => $content) {
            if (is_numeric($name)) {
                $this->headers[] = $content;
            } else {
                $this->headers[] = "$name: $content";
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
     * @return Response
     */
    public function setHeader(string $name, string $content = ''): Response
    {
        if ($content) {
            $this->headers[] = "$name: $content";
        }

        return $this;
    }

    /**
     * Get cookies
     *
     * @return array
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Set cookie
     *
     * @param  string      $name
     * @param  mixed      $value
     * @param  int|integer $ttl
     *
     * @return Response
     */
    public function setCookie(string $name, $value, int $ttl = 0): Response
    {
        $this->cookies[] = [$name, $value, $ttl];

        return $this;
    }

    /**
     * Send headers
     *
     * @return Response
     */
    public function sendHeader(): Response
    {
        if (!$this->request['CLI'] && !headers_sent()) {
            // send cookies
            foreach ($this->cookies as $value) {
                $this->sendCookie($value[0], $value[1], $value[2]);
            }

            array_walk($this->headers, 'header');

            header($_SERVER['SERVER_PROTOCOL'] . ' ' . $this->statusCode . ' ' . $this->statusText, true);
        }

        return $this;
    }

    /**
     * Send content
     *
     * @param int $kbps
     *
     * @return Response
     */
    public function sendContent(int $kbps = 0): Response
    {
        $output = $this->output;
        if (!$output && $this->body) {
            if ($kbps) {
                $output = $this->throttle($this->body, $kbps);
            } else {
                echo $this->body;
            }
        }

        if ($output) {
            $output();
        }

        return $this;
    }

    /**
     * Send response
     *
     * @param int $kbps
     *
     * @return Response
     */
    public function send(int $kbps = 0): Response
    {
        $this->sendHeader();
        $this->sendContent($kbps);

        return $this;
    }

    /**
     * Set html header and output closure
     *
     * @param  string $content
     *
     * @return Response
     */
    public function html(string $content): Response
    {
        $this->setHeaders([
            'Content-Type' => 'text/html;charset=' . ini_get('default_charset'),
            'Content-Length' => strlen($content),
        ]);

        $this->body = $content;

        return $this;
    }

    /**
     * Set JSON header and output closure
     *
     * @param  array  $data
     *
     * @return Response
     */
    public function json(array $data): Response
    {
        $this->body = json_encode($data);

        $this->setHeaders([
            'Content-Type' => 'application/json;charset=' . ini_get('default_charset'),
            'Content-Length' => strlen($this->body),
        ]);

        return $this;
    }

    /**
     * Create output closure
     *
     * @param  string      $content
     * @param  int|integer $kbps
     *
     * @return Closure
     */
    public function throttle(string $content, int $kbps = 0): \Closure
    {
        return function() use ($content, $kbps) {
            $now = microtime(true);
            $ctr = 0;
            foreach (str_split($content, 1024) as $part) {
                // Throttle output
                $ctr++;
                if ($ctr/$kbps > ($elapsed = microtime(true) - $now) && !connection_aborted()) {
                    usleep((int) (1e6 * ($ctr / $kbps - $elapsed)));
                }

                echo $part;
            }
        };
    }

    /**
     * Set output
     *
     * @param callable $output
     *
     * @return Response
     */
    public function setOutput(callable $output = null): Response
    {
        $this->output = $output;

        return $this;
    }

    /**
     * Get output
     *
     * @return callable
     */
    public function getOutput(): ?callable
    {
        return $this->output;
    }

    /**
     * Get body
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Set body
     *
     * @param string $body
     * @return Response
     */
    public function setBody(string $body): Response
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Check body
     *
     * @return boolean
     */
    public function hasBody(): bool
    {
        return '' !== $this->body;
    }

    /**
     * Clear output
     *
     * @return Response
     */
    public function clearOutput(): Response
    {
        $this->output = null;
        $this->body = '';
        $this->headers = [];
        $this->cookies = [];

        return $this;
    }

    /**
     * Send cookie
     *
     * @param  string      $name
     * @param  mixed      $value
     * @param  int|integer $ttl
     *
     * @return Response
     */
    protected function sendCookie(string $name, $value, int $ttl = 0): Response
    {
        $jar  = $this->helper->unserialize($this->helper->serialize($this->request['JAR']));

        if (isset($_COOKIE[$name])) {
            $jar['expire'] = strtotime('-1 year');
            call_user_func_array('setcookie', array_merge([$name, null], $jar));
        }
        if ($ttl) {
            $jar['expire'] = microtime(true) + $ttl;
        }

        call_user_func_array('setcookie', [$name, $value] + $jar);

        $_COOKIE[$name] = $value;

        return $this;
    }
}

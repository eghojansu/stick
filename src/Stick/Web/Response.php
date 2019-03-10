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

/**
 * Response class.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Response
{
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

    /**
     * @var ResponseHeaderBag
     */
    public $headers;

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var string
     */
    protected $statusText;

    /**
     * @var string
     */
    protected $charset = 'UTF-8';

    /**
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    protected $version = '1.0';

    /**
     * Class create.
     *
     * @param string     $content
     * @param int|null   $code
     * @param array|null $headers
     *
     * @return Response
     */
    public static function create($content = null, int $code = null, array $headers = null): Response
    {
        return new static($content, $code, $headers);
    }

    /**
     * Class constructor.
     *
     * @param string     $content
     * @param int|null   $code
     * @param array|null $headers
     */
    public function __construct($content = null, int $code = null, array $headers = null)
    {
        $this->headers = new ResponseHeaderBag($headers);
        $this->status($code ?? 200);
        $this->setContent($content);
    }

    /**
     * Clones the current Response instance.
     */
    public function __clone()
    {
        $this->headers = clone $this->headers;
    }

    /**
     * Returns status code.
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Returns status text.
     *
     * @return string
     */
    public function getStatusText(): string
    {
        return $this->statusText;
    }

    /**
     * Set response status.
     *
     * @param int $code
     *
     * @return Response
     */
    public function status(int $code): Response
    {
        $name = 'static::HTTP_'.$code;

        if (!defined($name)) {
            throw new \DomainException(sprintf('Unsupported HTTP code: %d.', $code));
        }

        $this->statusCode = $code;
        $this->statusText = constant($name);

        return $this;
    }

    /**
     * Returns charset.
     *
     * @return string
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Assign charset.
     *
     * @param string $charset
     *
     * @return Response
     */
    public function setCharset(string $charset): Response
    {
        $this->charset = $charset;

        return $this;
    }

    /**
     * Returns response content.
     *
     * @return mixed
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Assign response content.
     *
     * @param mixed $content
     *
     * @return Response [<description>]
     */
    public function setContent($content): Response
    {
        $this->content = $content;

        if (is_string($content)) {
            $this->headers->set('Content-Length', strlen($content));
        }

        return $this;
    }

    /**
     * Returns protocol version.
     *
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->version;
    }

    /**
     * Assign protocl version.
     *
     * @param string $version
     *
     * @return Response
     */
    public function setProtocolVersion(string $version): Response
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Prepare request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function prepare(Request $request): Response
    {
        $headers = $this->headers;

        if ($this->isInformational() || $this->isEmpty()) {
            $this->setContent(null);
            $headers->clear('Content-Type');
            $headers->clear('Content-Length');
        } else {
            // Fix Content-Type
            if (!$headers->exists('Content-Type')) {
                $headers->set('Content-Type', 'text/html; charset='.$this->charset);
            } elseif (0 === stripos($headers->first('Content-Type'), 'text/') && false === stripos($headers->first('Content-Type'), 'charset')) {
                // add the charset
                $headers->set('Content-Type', $headers->first('Content-Type').'; charset='.$this->charset);
            }

            // Fix Content-Length
            if ($headers->exists('Transfer-Encoding')) {
                $headers->clear('Content-Length');
            }

            if ($request->isMethod('HEAD')) {
                // cf. RFC2616 14.13
                $length = $headers->first('Content-Length');
                $this->setContent(null);

                if ($length) {
                    $headers->set('Content-Length', $length);
                }
            }
        }

        // Fix protocol
        if ('HTTP/1.0' != $request->server->get('SERVER_PROTOCOL')) {
            $this->setProtocolVersion('1.1');
        }

        // Check if we need to send extra expire info headers
        if ('1.0' == $this->getProtocolVersion() && false !== strpos($headers->first('Cache-Control'), 'no-cache')) {
            $headers->set('Pragma', 'no-cache');
            $headers->set('Expires', -1);
        }

        if ($request->isSecure()) {
            foreach ($headers->getFlatCookies() as $cookie) {
                $cookie->setSecureDefault(true);
            }
        }

        // more attributes
        if (!$headers->exists('X-Powered-By')) {
            $headers->set('X-Powered-By', Kernel::PACKAGE.' - '.Kernel::VERSION);
        }

        if (!$headers->exists('X-Frame-Options')) {
            $headers->set('X-Frame-Options', 'SAMEORIGIN');
        }

        if (!$headers->exists('X-XSS-Protection')) {
            $headers->set('X-XSS-Protection', '1; mode=block');
        }

        if (!$headers->exists('X-Content-Type-Options')) {
            $headers->set('X-Content-Type-Options', 'nosniff');
        }

        return $this;
    }

    /**
     * Send response headers.
     *
     * @return Response
     */
    public function sendHeaders(): Response
    {
        if (!headers_sent()) {
            foreach ($this->headers->all() as $name => $headers) {
                $replace = 0 === strcasecmp($name, 'Content-Type');

                foreach ($headers as $value) {
                    header($name.': '.$value, $replace, $this->statusCode);
                }
            }

            foreach ($this->headers->getFlatCookies() as $cookie) {
                header('Set-Cookie: '.$cookie->getName().strstr($cookie->toString(), '='), false, $this->statusCode);
            }

            header(sprintf('HTTP/%s %s %s', $this->version, $this->statusCode, $this->statusText), true, $this->statusCode);
        }

        return $this;
    }

    /**
     * Send response content.
     *
     * @return Response
     */
    public function sendContent(): Response
    {
        echo $this->content;

        return $this;
    }

    /**
     * Send response headers and content.
     *
     * @return Response
     */
    public function send(): Response
    {
        $this->sendHeaders();
        $this->sendContent();

        return $this;
    }

    /**
     * Is response invalid?
     *
     * @see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html
     *
     * @return bool
     */
    public function isInvalid(): bool
    {
        return $this->statusCode < 100 || $this->statusCode >= 600;
    }

    /**
     * Is response informative?
     *
     * @return bool
     */
    public function isInformational(): bool
    {
        return $this->statusCode >= 100 && $this->statusCode < 200;
    }

    /**
     * Is response successful?
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Is the response a redirect?
     *
     * @return bool
     */
    public function isRedirection(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    public function isClientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Was there a server side error?
     *
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Is the response OK?
     *
     * @return bool
     */
    public function isOk(): bool
    {
        return 200 === $this->statusCode;
    }

    /**
     * Is the response forbidden?
     *
     * @return bool
     */
    public function isForbidden(): bool
    {
        return 403 === $this->statusCode;
    }

    /**
     * Is the response a not found error?
     *
     * @return bool
     */
    public function isNotFound(): bool
    {
        return 404 === $this->statusCode;
    }

    /**
     * Is the response a redirect of some form?
     *
     * @param string|null $location
     *
     * @return bool
     */
    public function isRedirect(string $location = null): bool
    {
        return in_array($this->statusCode, array(201, 301, 302, 303, 307, 308)) && (null === $location || $location == $this->headers->get('Location'));
    }

    /**
     * Is the response empty?
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return in_array($this->statusCode, array(204, 304));
    }

    /**
     * Set expire headers.
     *
     * @param int $seconds
     *
     * @return Response
     */
    public function expire(int $seconds = 0): Response
    {
        if ($seconds) {
            $this->headers->clear('Pragma');

            $this->headers->set('Cache-Control', 'max-age='.$seconds);
            $this->headers->set('Expires', gmdate('r', time() + $seconds));
            $this->headers->set('Last-Modified', gmdate('r'));
        } else {
            $this->headers->set('Pragma', 'no-cache');
            $this->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $this->headers->set('Expires', gmdate('r', 0));
        }

        return $this;
    }

    /**
     * Modifies the response so that it conforms to the rules defined for a 304 status code.
     *
     * This sets the status, removes the body, and discards any headers
     * that MUST NOT be included in 304 responses.
     *
     * @return Response
     *
     * @see http://tools.ietf.org/html/rfc2616#section-10.3.5
     */
    public function setNotModified(): Response
    {
        $this->status(304);
        $this->setContent(null);

        $headers = array(
            'Allow',
            'Content-Encoding',
            'Content-Language',
            'Content-Length',
            'Content-MD5',
            'Content-Type',
            'Last-Modified',
        );

        foreach ($headers as $header) {
            $this->headers->clear($header);
        }

        return $this;
    }
}

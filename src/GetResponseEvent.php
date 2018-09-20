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
 * Allows to set response for a request.
 *
 * Call setResponse() to set the response that will be returned for the current request.
 * The propagation of this event is stopped as soon as a response is set.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class GetResponseEvent extends Event
{
    /**
     * Status code.
     *
     * @var int
     */
    private $code;

    /**
     * Response content.
     *
     * @var string
     */
    private $response;

    /**
     * Response kbps.
     *
     * @var int
     */
    private $kbps;

    /**
     * Response headers.
     *
     * @var array
     */
    private $headers;

    /**
     * Class constructor.
     *
     * @param int        $code
     * @param string     $response
     * @param int        $kbps
     * @param array|null $headers
     */
    public function __construct(int $code = 200, string $response = '', int $kbps = 0, array $headers = null)
    {
        $this->code = $code;
        $this->response = $response;
        $this->kbps = $kbps;
        $this->headers = (array) $headers;
    }

    /**
     * Returns status code.
     *
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * Sets status code.
     *
     * @param int $code
     *
     * @return GetResponseEvent
     */
    public function setCode(int $code): GetResponseEvent
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Returns response headers.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Sets response headers.
     *
     * @param array $headers
     *
     * @return GetResponseEvent
     */
    public function setHeaders(array $headers): GetResponseEvent
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Returns kbps.
     *
     * @return int
     */
    public function getKbps(): int
    {
        return $this->kbps;
    }

    /**
     * Sets kbps.
     *
     * @param int $kbps
     *
     * @return GetResponseEvent
     */
    public function setKbps(int $kbps): GetResponseEvent
    {
        $this->kbps = $kbps;

        return $this;
    }

    /**
     * Returns response content.
     *
     * @return string
     */
    public function getResponse(): string
    {
        return $this->response;
    }

    /**
     * Sets response content.
     *
     * @param string $response
     *
     * @return GetResponseEvent
     */
    public function setResponse(string $response): GetResponseEvent
    {
        $this->response = $response;
        $this->stopPropagation();

        return $this;
    }
}

<?php

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
    public function __construct($code = 200, $response = '', $kbps = 0, array $headers = null)
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
    public function getCode()
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
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Returns response headers.
     *
     * @return array
     */
    public function getHeaders()
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
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Returns kbps.
     *
     * @return int
     */
    public function getKbps()
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
    public function setKbps($kbps)
    {
        $this->kbps = $kbps;

        return $this;
    }

    /**
     * Returns response content.
     *
     * @return string
     */
    public function getResponse()
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
    public function setResponse($response)
    {
        $this->response = $response;
        $this->stopPropagation();

        return $this;
    }
}

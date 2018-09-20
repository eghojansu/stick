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
 * Allows to set response for an error.
 *
 * Call setResponse() to set the response that will be returned for the current request.
 * The propagation of this event is stopped as soon as a response is set.
 *
 * Call setHeaders() to set additional headers to sent.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class GetResponseForErrorEvent extends GetResponseEvent
{
    /**
     * Status text.
     *
     * @var string
     */
    private $status;

    /**
     * Error message.
     *
     * @var string|null
     */
    private $message;

    /**
     * Error trace.
     *
     * @var string
     */
    private $trace;

    /**
     * Class constructor.
     *
     * @param int         $code
     * @param string      $status
     * @param string|null $message
     * @param string|null $trace
     */
    public function __construct(int $code, string $status, string $message = null, string $trace = null)
    {
        parent::__construct($code);

        $this->status = $status;
        $this->message = $message;
        $this->trace = $trace;
    }

    /**
     * Returns status text.
     *
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Returns error message.
     *
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Returns error trace.
     *
     * @return string|null
     */
    public function getTrace(): ?string
    {
        return $this->trace;
    }
}

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
 * Stream response.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class StreamedResponse extends Response
{
    /**
     * @var callable
     */
    protected $callback;

    /**
     * @var bool
     */
    protected $streamed;

    /**
     * @var bool
     */
    protected $headersSent;

    /**
     * Class constructor.
     *
     * @param callable|null $callback A valid PHP callback or null to set it later
     * @param int           $status   The response status code
     * @param array         $headers  An array of response headers
     */
    public function __construct(callable $callback = null, int $status = null, array $headers = null)
    {
        parent::__construct(null, $status, $headers);

        if ($callback) {
            $this->setCallback($callback);
        }

        $this->streamed = false;
        $this->headersSent = false;
    }

    /**
     * Sets the PHP callback associated with this Response.
     *
     * @param callable $callback A valid PHP callback
     *
     * @return StreamedResponse
     */
    public function setCallback(callable $callback): StreamedResponse
    {
        $this->callback = $callback;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function sendHeaders(): Response
    {
        if ($this->headersSent) {
            return $this;
        }

        $this->headersSent = true;

        return parent::sendHeaders();
    }

    /**
     * {@inheritdoc}
     *
     * This method only sends the content once.
     *
     * @throws LogicException if callback is null
     */
    public function sendContent(): Response
    {
        if ($this->streamed) {
            return $this;
        }

        $this->streamed = true;

        if (null === $this->callback) {
            throw new \LogicException('The Response callback must not be null.');
        }

        ($this->callback)();

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \LogicException when the content is not null
     */
    public function setContent($content): Response
    {
        if (null !== $content) {
            throw new \LogicException('The content cannot be set on a StreamedResponse instance.');
        }

        $this->streamed = true;

        return $this;
    }
}

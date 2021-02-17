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

namespace Ekok\Stick\Event;

class RequestErrorEvent extends ResponseEvent
{
    private $code;
    private $text;
    private $message;
    private $headers;
    private $error;

    public function __construct(int $code, string $text, string $message, array $headers = null, \Throwable $error = null)
    {
        $this->code = $code;
        $this->text = $text;
        $this->message = $message;
        $this->headers = $headers;
        $this->error = $error;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getHeaders(): ?array
    {
        return $this->headers;
    }

    public function getError(): ?\Throwable
    {
        return $this->error;
    }
}

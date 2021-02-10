<?php

declare(strict_types=1);

namespace Ekok\Stick;

class HttpException extends \RuntimeException
{
    private $httpCode;
    private $httpHeaders;

    public static function notFound(string $message, array $httpHeaders = null): self
    {
        return new static(404, $message, $httpHeaders);
    }

    public static function forbidden(string $message, array $httpHeaders = null): self
    {
        return new static(403, $message, $httpHeaders);
    }

    public function __construct(
        int $httpCode,
        string $message = '',
        array $httpHeaders = null,
        int $code = 0,
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->httpCode = $httpCode;
        $this->httpHeaders = $httpHeaders;
    }

    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    public function getHttpHeaders(): ?array
    {
        return $this->httpHeaders;
    }
}

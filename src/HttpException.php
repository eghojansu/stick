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

namespace Ekok\Stick;

class HttpException extends \RuntimeException
{
    private $httpCode;
    private $httpHeaders;

    public static function forbidden(string $message = null, array $httpHeaders = null): static
    {
        return new static(403, $message ?? 'The action you are trying to perform is forbidden.', $httpHeaders);
    }

    public static function notFound(string $message = null, array $httpHeaders = null): static
    {
        return new static(404, $message ?? 'The page you are looking for is not found.', $httpHeaders);
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

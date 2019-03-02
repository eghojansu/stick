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

namespace Fal\Stick\Web\Exception;

/**
 * Base class for http exception.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class HttpException extends \Exception
{
    /**
     * @var int
     */
    protected $statusCode;

    /**
     * Class constructor.
     *
     * @param string|null    $message
     * @param int|null       $statusCode
     * @param int|null       $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = null, int $statusCode = null, int $code = null, \Throwable $previous = null)
    {
        parent::__construct($message ?? '', $code ?? 0, $previous);

        $this->statusCode = $statusCode ?? 500;
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
}

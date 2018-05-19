<?php

declare(strict_types=1);

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
 * Base class for Response Error.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class ResponseErrorException extends \Exception
{
    /**
     * Class constructor.
     *
     * @param string|null    $message
     * @param int            $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = null, int $code = 500, \Throwable $previous = null)
    {
        parent::__construct($message ?? '', $code, $previous);
    }
}

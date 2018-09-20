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
 * Response exception.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class ResponseException extends \LogicException
{
    /**
     * Class constructor.
     *
     * @param int       $code
     * @param string    $message
     * @param Throwable $previous
     */
    public function __construct(int $code, string $message = null, \Throwable $previous = null)
    {
        parent::__construct((string) $message, $code, $previous);
    }
}

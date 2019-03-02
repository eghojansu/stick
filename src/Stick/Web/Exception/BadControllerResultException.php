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
 * Bad controller result.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class BadControllerResultException extends HttpException
{
    /**
     * Class constructor.
     *
     * @param string|null    $message
     * @param Throwable|null $previous
     */
    public function __construct(string $message = null, \Throwable $previous = null)
    {
        parent::__construct($message, 500, null, $previous);
    }
}

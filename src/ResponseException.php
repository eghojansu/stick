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
 * Response exception.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class ResponseException extends \LogicException
{
    /**
     * Class constructor.
     *
     * @param int    $code
     * @param string $message
     * @param mixed  $previous
     */
    public function __construct($code, $message = null, $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

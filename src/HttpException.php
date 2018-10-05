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
 * Http exception.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class HttpException extends \Exception
{
    /**
     * @var array
     */
    private $headers;

    /**
     * Class constructor.
     *
     * @param string|null $message
     * @param int         $code
     * @param array|null  $headers
     * @param Exception   $prev
     */
    public function __construct(string $message = null, int $code = 500, array $headers = null, \Exception $prev = null)
    {
        parent::__construct((string) $message, $code, $prev);

        $this->headers = (array) $headers;
    }

    /**
     * Returns headers.
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
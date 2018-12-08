<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Nov 25, 2018 12:56
 */

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
     * @param Throwable   $previos
     */
    public function __construct(string $message = null, int $code = 500, array $headers = null, \Throwable $previos = null)
    {
        parent::__construct($message ?? '', $code, $previos);

        $this->headers = $headers ?? array();
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

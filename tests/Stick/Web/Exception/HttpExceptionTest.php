<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 30, 2019 11:34
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web\Exception;

use Fal\Stick\Web\Exception\HttpException;
use PHPUnit\Framework\TestCase;

class HttpExceptionTest extends TestCase
{
    public function testGetStatusCode()
    {
        $exception = new HttpException();

        $this->assertEquals(500, $exception->getStatusCode());
    }
}

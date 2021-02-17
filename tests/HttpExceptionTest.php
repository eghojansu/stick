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

namespace Ekok\Stick\Tests;

use Ekok\Stick\HttpException;
use PHPUnit\Framework\TestCase;

class HttpExceptionTest extends TestCase
{
    public function testIsUsable()
    {
        $exception = new HttpException(500);

        $this->assertSame(500, $exception->getHttpCode());
        $this->assertNull($exception->getHttpHeaders());
    }

    public function testForbiddenCreation()
    {
        $exception = HttpException::forbidden();

        $this->assertSame(403, $exception->getHttpCode());
        $this->assertEquals('The action you are trying to perform is forbidden.', $exception->getMessage());
    }

    public function testNotFoundCreation()
    {
        $exception = HttpException::notFound();

        $this->assertSame(404, $exception->getHttpCode());
        $this->assertEquals('The page you are looking for is not found.', $exception->getMessage());
    }
}

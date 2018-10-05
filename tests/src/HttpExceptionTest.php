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

namespace Fal\Stick\Test;

use Fal\Stick\HttpException;
use PHPUnit\Framework\TestCase;

class HttpExceptionTest extends TestCase
{
    public function testGetHeaders()
    {
        $e = new HttpException('foo');

        $this->assertEquals('foo', $e->getMessage());
        $this->assertEquals(array(), $e->getHeaders());
    }
}

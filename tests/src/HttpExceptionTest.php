<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Dec 05, 2018 09:59
 */

declare(strict_types=1);

namespace Fal\Stick\Test;

use Fal\Stick\HttpException;
use PHPUnit\Framework\TestCase;

class HttpExceptionTest extends TestCase
{
    private $exception;

    public function setUp()
    {
        $this->exception = new HttpException('foo');
    }

    public function testGetHeaders()
    {
        $this->assertEquals(array(), $this->exception->getHeaders());
    }
}

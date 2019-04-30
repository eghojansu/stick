<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Feb 09, 2019 18:47
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Security;

use Fal\Stick\TestSuite\MyTestCase;

class PlainPasswordEncoderTest extends MyTestCase
{
    public function testHash()
    {
        $this->assertEquals('foo', $this->plainPasswordEncoder->hash('foo'));
    }

    public function testVerify()
    {
        $this->assertTrue($this->plainPasswordEncoder->verify('foo', 'foo'));
        $this->assertFalse($this->plainPasswordEncoder->verify('bar', 'foo'));
    }
}

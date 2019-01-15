<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 14, 2019 08:54
 */

namespace Fal\Stick\Test\Security;

use PHPUnit\Framework\TestCase;
use Fal\Stick\Security\PlainPasswordEncoder;

class PlainPasswordEncoderTest extends TestCase
{
    private $encoder;

    public function setup()
    {
        $this->encoder = new PlainPasswordEncoder();
    }

    public function testHash()
    {
        $this->assertEquals('foo', $this->encoder->hash('foo'));
    }

    public function testVerify()
    {
        $this->assertTrue($this->encoder->verify('foo', 'foo'));
    }
}

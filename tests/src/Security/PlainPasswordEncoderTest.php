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

namespace Fal\Stick\Test\Security;

use Fal\Stick\Security\PlainPasswordEncoder;
use PHPUnit\Framework\TestCase;

class PlainPasswordEncoderTest extends TestCase
{
    private $encoder;

    public function setUp()
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
        $this->assertFalse($this->encoder->verify('bar', 'foo'));
    }
}

<?php

declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        $plain = 'foo';
        $hash = $plain;
        $hash2 = $this->encoder->hash($plain);

        $this->assertTrue($this->encoder->verify($plain, $hash));
        $this->assertTrue($this->encoder->verify($plain, $hash2));
        $this->assertEquals($hash, $hash2);
    }

    public function testVerify()
    {
        $plain = 'foo';
        $hash = $plain;

        $this->assertTrue($this->encoder->verify($plain, $hash));
    }
}

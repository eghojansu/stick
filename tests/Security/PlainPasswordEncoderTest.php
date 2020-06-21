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

namespace Ekok\Stick\Tests\Security;

use Ekok\Stick\Security\PlainPasswordEncoder;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Ekok\Stick\Security\PlainPasswordEncoder
 */
final class PlainPasswordEncoderTest extends TestCase
{
    private $encoder;

    protected function setUp(): void
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

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

use Ekok\Stick\Security\PhpPasswordEncoder;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Ekok\Stick\Security\PhpPasswordEncoder
 */
final class PhpPasswordEncoderTest extends TestCase
{
    private $encoder;

    protected function setUp(): void
    {
        $this->encoder = new PhpPasswordEncoder();
    }

    public function testHash()
    {
        $this->assertEquals(60, strlen($this->encoder->hash('foobar')));
        $this->assertEquals(60, strlen($this->encoder->hash('foo')));
        $this->assertEquals(60, strlen($this->encoder->hash('bar')));
    }

    public function testVerify()
    {
        $hash = $this->encoder->hash('foo');

        $this->assertTrue($this->encoder->verify('foo', $hash));
        $this->assertFalse($this->encoder->verify('bar', $hash));
    }
}

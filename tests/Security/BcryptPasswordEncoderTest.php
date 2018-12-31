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

use Fal\Stick\Security\BcryptPasswordEncoder;
use PHPUnit\Framework\TestCase;

class BcryptPasswordEncoderTest extends TestCase
{
    private $encoder;

    public function setUp()
    {
        $this->encoder = new BcryptPasswordEncoder();
    }

    /**
     * @dataProvider hashProvider
     */
    public function testHash($plain)
    {
        $hash = $this->encoder->hash($plain);

        $this->assertEquals(60, strlen($hash));
    }

    public function testVerify()
    {
        $hash = $this->encoder->hash('foo');

        $this->assertTrue($this->encoder->verify('foo', $hash));
        $this->assertFalse($this->encoder->verify('bar', $hash));
    }

    public function hashProvider()
    {
        return array(
            array('foo'),
            array('foobar'),
            array('foobarbaz'),
        );
    }
}

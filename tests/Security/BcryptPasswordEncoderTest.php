<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Security;

use Fal\Stick\Security\BcryptPasswordEncoder;
use PHPUnit\Framework\TestCase;

class BcryptPasswordEncoderTest extends TestCase
{
    private $encoder;

    public function setup()
    {
        $this->encoder = new BcryptPasswordEncoder();
    }

    public function testHash()
    {
        $hash = $this->encoder->hash('foo');

        $this->assertTrue($this->encoder->verify('foo', $hash));
    }

    public function testVerify()
    {
        $hash = '$2y$10$7iP2VBKRsEzRuUzQf0maauXiw3rrYaGeFdi2fDEw9OLNnXUA6HYXW';

        $this->assertTrue($this->encoder->verify('foo', $hash));
    }
}

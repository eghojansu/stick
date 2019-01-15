<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 14, 2019 08:46
 */

namespace Fal\Stick\Test\Security;

use PHPUnit\Framework\TestCase;
use Fal\Stick\Security\BcryptPasswordEncoder;

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

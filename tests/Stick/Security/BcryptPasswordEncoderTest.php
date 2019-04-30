<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Feb 09, 2019 18:48
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Security;

use Fal\Stick\TestSuite\MyTestCase;

class BcryptPasswordEncoderTest extends MyTestCase
{
    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Security\BcryptPasswordEncoderProvider::hash
     */
    public function testHash($plain)
    {
        $hash = $this->bcryptPasswordEncoder->hash($plain);

        $this->assertEquals(60, strlen($hash));
    }

    public function testVerify()
    {
        $hash = $this->bcryptPasswordEncoder->hash('foo');

        $this->assertTrue($this->bcryptPasswordEncoder->verify('foo', $hash));
        $this->assertFalse($this->bcryptPasswordEncoder->verify('bar', $hash));
    }
}

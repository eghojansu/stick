<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Unit\Security;

use Fal\Stick\Security\BcryptPasswordEncoder;
use PHPUnit\Framework\TestCase;

class BcryptPasswordEncoderTest extends TestCase
{
    private $encoder;

    public function setUp()
    {
        $this->encoder = new BcryptPasswordEncoder;
    }

    public function testHash()
    {
        $plain = 'foo';
        $hash = '$2y$10$aUK89Pqc3T8AJbpTBlBLB.zPS3wOG3gKAPO4zk9GZQv9Cs1Vrwnjm';
        $hash2 = $this->encoder->hash($plain);

        $this->assertTrue($this->encoder->verify($plain, $hash));
        $this->assertTrue($this->encoder->verify($plain, $hash2));
        $this->assertNotEquals($hash, $hash2);
    }

    public function testVerify()
    {
        $plain = 'foo';
        $hash = '$2y$10$aUK89Pqc3T8AJbpTBlBLB.zPS3wOG3gKAPO4zk9GZQv9Cs1Vrwnjm';

        $this->assertTrue($this->encoder->verify($plain, $hash));
    }
}

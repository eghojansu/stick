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

use Fal\Stick\Security\SimpleUser;
use PHPUnit\Framework\TestCase;

class SimpleUserTest extends TestCase
{
    private $user;

    public function setUp()
    {
        $this->user = new SimpleUser('1','foo','bar');
    }

    public function tearDown()
    {
        error_clear_last();
    }

    public function testSetId()
    {
        $this->assertEquals('2', $this->user->setId('2')->getId());
    }

    public function testGetId()
    {
        $this->assertEquals('1', $this->user->getId());
    }

    public function testSetUsername()
    {
        $this->assertEquals('bar', $this->user->setUsername('bar')->getUsername());
    }

    public function testGetUsername()
    {
        $this->assertEquals('foo', $this->user->getUsername());
    }

    public function testSetPassword()
    {
        $this->assertEquals('foo', $this->user->setPassword('foo')->getPassword());
    }

    public function testGetPassword()
    {
        $this->assertEquals('bar', $this->user->getPassword());
    }

    public function testSetRoles()
    {
        $this->assertEquals(['foo'], $this->user->setRoles(['foo'])->getRoles());
    }

    public function testGetRoles()
    {
        $this->assertEquals(['ROLE_ANONYMOUS'], $this->user->getRoles());
    }

    public function testSetExpired()
    {
        $this->assertTrue($this->user->setExpired(true)->isExpired());
    }

    public function testIsExpired()
    {
        $this->assertFalse($this->user->isExpired());
    }
}

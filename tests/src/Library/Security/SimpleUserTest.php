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

namespace Fal\Stick\Test\Library\Security;

use Fal\Stick\Library\Security\SimpleUser;
use PHPUnit\Framework\TestCase;

class SimpleUserTest extends TestCase
{
    private $user;

    public function setUp()
    {
        $this->user = new SimpleUser('1', 'foo', 'bar');
    }

    public function testGetId()
    {
        $this->assertEquals('1', $this->user->getId());
    }

    public function testGetUsername()
    {
        $this->assertEquals('foo', $this->user->getUsername());
    }

    public function testGetPassword()
    {
        $this->assertEquals('bar', $this->user->getPassword());
    }

    public function testGetRoles()
    {
        $this->assertEquals(array('ROLE_ANONYMOUS'), $this->user->getRoles());
    }

    public function testIsCredentialsExpired()
    {
        $this->assertFalse($this->user->isCredentialsExpired());
    }

    public function testSetId()
    {
        $this->assertEquals('foo', $this->user->setId('foo')->getId());
    }

    public function testSetUsername()
    {
        $this->assertEquals('foo', $this->user->setUsername('foo')->getUsername());
    }

    public function testSetPassword()
    {
        $this->assertEquals('foo', $this->user->setPassword('foo')->getPassword());
    }

    public function testSetRoles()
    {
        $this->assertEquals(array('FOO'), $this->user->setRoles(array('FOO'))->getRoles());
    }

    public function testSetCredentialsExpired()
    {
        $this->assertTrue($this->user->setCredentialsExpired(true)->isCredentialsExpired());
    }
}

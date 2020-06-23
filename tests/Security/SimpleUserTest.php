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

use Ekok\Stick\Security\SimpleUser;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Ekok\Stick\Security\SimpleUser
 */
final class SimpleUserTest extends TestCase
{
    private $user;

    protected function setUp(): void
    {
        $this->user = new SimpleUser('1', 'foobar', 'foo', 'foo,bar', true, true);
    }

    public function testFromArray()
    {
        $user = SimpleUser::fromArray(array(
            'id' => '1',
            'username' => 'foobar',
            'password' => 'foo',
            'expired' => true,
            'disabled' => true,
            'roles' => 'foo,bar',
        ));

        $this->assertEquals($this->user, $user);
    }

    public function testToArray()
    {
        $expected = array(
            'id' => '1',
            'username' => 'foobar',
            'password' => 'foo',
            'expired' => true,
            'disabled' => true,
            'roles' => array('foo', 'bar'),
            'info' => array(),
        );

        $this->assertEquals($expected, $this->user->toArray(true));
    }

    public function testGetId()
    {
        $this->assertEquals('1', $this->user->getId());
    }

    public function testGetUsername()
    {
        $this->assertEquals('foobar', $this->user->getUsername());
    }

    public function testGetPassword()
    {
        $this->assertEquals('foo', $this->user->getPassword());
    }

    public function testGetRoles()
    {
        $this->assertEquals(array('foo', 'bar'), $this->user->getRoles());
    }

    public function testIsExpired()
    {
        $this->assertTrue($this->user->isExpired());
    }

    public function testIsDisabled()
    {
        $this->assertTrue($this->user->isDisabled());
    }

    public function testMagicExists()
    {
        $this->assertFalse(isset($this->user->foo));
    }

    public function testMagicGet()
    {
        $this->assertNull($this->user->foo);
    }

    public function testMagicSet()
    {
        $this->user->foo = 'bar';
        $this->user->username = 'foobar';

        $this->assertEquals('bar', $this->user->foo);
        $this->assertEquals('foobar', $this->user->getUsername());
    }

    public function testMagicUnset()
    {
        $this->user->foo = 'bar';
        unset($this->user->foo);

        $this->assertNull($this->user->foo);
    }

    public function testOffsetExists()
    {
        $this->assertFalse(isset($this->user['foo']));
    }

    public function testOffsetGet()
    {
        $this->assertNull($this->user['foo']);
    }

    public function testOffsetSet()
    {
        $this->user['foo'] = 'bar';

        $this->assertEquals('bar', $this->user['foo']);
    }

    public function testOffsetUnset()
    {
        $this->user['foo'] = 'bar';
        unset($this->user['foo']);

        $this->assertNull($this->user['foo']);
    }

    public function testGetInfo()
    {
        $this->assertEquals(array(), $this->user->getInfo());
    }

    public function testInfo()
    {
        $this->assertNull($this->user->info('foo'));
    }

    public function testAddInfo()
    {
        $this->assertEquals('bar', $this->user->addInfo('foo', 'bar')->info('foo'));
    }

    public function testRemInfo()
    {
        $this->assertNull($this->user->addInfo('foo', 'bar')->remInfo('foo')->info('foo'));
    }

    public function testSetUsername()
    {
        $this->assertEquals('foobar', $this->user->setUsername('foobar')->getUsername());
    }

    public function testSetPassword()
    {
        $this->assertEquals('foobar', $this->user->setPassword('foobar')->getPassword());
    }

    public function testSetRoles()
    {
        $this->assertEquals(array('foo'), $this->user->setRoles(array('foo'))->getRoles());
    }

    public function testSetExpired()
    {
        $this->assertTrue($this->user->setExpired(true)->isExpired());
    }

    public function testSetDisabled()
    {
        $this->assertTrue($this->user->setDisabled(true)->isDisabled());
    }
}

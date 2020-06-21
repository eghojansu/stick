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
        );

        $this->assertEquals($expected, $this->user->toArray());
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
}

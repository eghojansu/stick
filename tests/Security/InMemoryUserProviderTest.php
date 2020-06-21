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

use Ekok\Stick\Security\InMemoryUserProvider;
use Ekok\Stick\Security\SimpleUser;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Ekok\Stick\Security\InMemoryUserProvider
 */
final class InMemoryUserProviderTest extends TestCase
{
    private $provider;

    protected function setUp(): void
    {
        $this->provider = new InMemoryUserProvider(function ($user) {
            return SimpleUser::fromArray($user);
        });
    }

    public function testAddUser()
    {
        $this->assertSame($this->provider, $this->provider->addUser(new SimpleUser('1', 'foobar')));
    }

    public function testFindByUsername()
    {
        $this->provider->addUser(new SimpleUser('1', 'foobar'));

        $this->assertNull($this->provider->findByUsername('bar'));
        $this->assertEquals('foobar', $this->provider->findByUsername('foobar')->getUsername());
    }

    public function testFindById()
    {
        $this->provider->addUser(new SimpleUser('1', 'foobar'));

        $this->assertEquals('foobar', $this->provider->findById('1')->getUsername());
    }

    public function testFromArray()
    {
        $this->assertEquals('foo', $this->provider->fromArray(array('username' => 'foo'))->getUsername());
    }
}

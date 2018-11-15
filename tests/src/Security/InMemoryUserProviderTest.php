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

use Fal\Stick\Security\InMemoryUserProvider;
use Fixture\SimpleUser;
use PHPUnit\Framework\TestCase;

class InMemoryUserProviderTest extends TestCase
{
    private $provider;

    public function setUp()
    {
        $this->provider = new InMemoryUserProvider();
    }

    public function testAddUser()
    {
        $this->assertSame($this->provider, $this->provider->addUser(new SimpleUser('1', 'foo', 'bar')));
    }

    public function testFindByUsername()
    {
        $this->provider->addUser(new SimpleUser('1', 'foo', 'bar'));

        $this->assertNull($this->provider->findByUsername('bar'));
        $this->assertEquals('foo', $this->provider->findByUsername('foo')->getUsername());
    }

    public function testFindById()
    {
        $this->provider->addUser(new SimpleUser('1', 'foo', 'bar'));

        $this->assertEquals('foo', $this->provider->findById('1')->getUsername());
    }
}

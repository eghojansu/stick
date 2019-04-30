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

use Fal\Stick\TestSuite\MyTestCase;
use Fal\Stick\TestSuite\Classes\SimpleUser;

class InMemoryUserProviderTest extends MyTestCase
{
    public function testAddUser()
    {
        $this->assertSame($this->inMemoryUserProvider, $this->inMemoryUserProvider->addUser(new SimpleUser('1', 'foo', 'bar')));
    }

    public function testFindByUsername()
    {
        $this->inMemoryUserProvider->addUser(new SimpleUser('1', 'foo', 'bar'));

        $this->assertNull($this->inMemoryUserProvider->findByUsername('bar'));
        $this->assertEquals('foo', $this->inMemoryUserProvider->findByUsername('foo')->getUsername());
    }

    public function testFindById()
    {
        $this->inMemoryUserProvider->addUser(new SimpleUser('1', 'foo', 'bar'));

        $this->assertEquals('foo', $this->inMemoryUserProvider->findById('1')->getUsername());
    }
}

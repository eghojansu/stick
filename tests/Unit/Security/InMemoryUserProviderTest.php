<?php

declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Unit\Security;

use Fal\Stick\Security\InMemoryUserProvider;
use Fal\Stick\Security\SimpleUser;
use Fal\Stick\Security\SimpleUserTransformer;
use PHPUnit\Framework\TestCase;

class InMemoryUserProviderTest extends TestCase
{
    private $provider;

    public function setUp()
    {
        $this->provider = new InMemoryUserProvider(['foo' => 'bar', 'baz' => 'qux'], new SimpleUserTransformer());
    }

    public function testAddUser()
    {
        $this->provider->addUser('quux', 'bleh');
        $user = new SimpleUser('quux', 'quux', 'bleh');

        $this->assertEquals($user, $this->provider->findByUsername('quux'));
    }

    public function testFindByUsername()
    {
        $user = new SimpleUser('foo', 'foo', 'bar');

        $this->assertEquals($user, $this->provider->findByUsername('foo'));
    }

    public function testFindById()
    {
        $user = new SimpleUser('foo', 'foo', 'bar');

        $this->assertEquals($user, $this->provider->findById('foo'));
    }
}

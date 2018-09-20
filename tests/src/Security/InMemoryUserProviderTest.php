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
use Fal\Stick\Security\SimpleUserTransformer;
use PHPUnit\Framework\TestCase;

class InMemoryUserProviderTest extends TestCase
{
    private $provider;

    public function setUp()
    {
        $this->provider = new InMemoryUserProvider(new SimpleUserTransformer());
    }

    public function testAddUser()
    {
        $this->assertSame($this->provider, $this->provider->addUser('foo', 'bar'));
    }

    public function testFindByUsername()
    {
        $this->provider->addUser('foo', 'bar');

        $this->assertNull($this->provider->findByUsername('bar'));
        $this->assertEquals('foo', $this->provider->findByUsername('foo')->getUsername());
    }

    public function testFindById()
    {
        $this->provider->addUser('foo', 'bar');
        $this->provider->addUser('bar', 'baz', array('id' => '1'));

        $this->assertEquals('foo', $this->provider->findById('foo')->getUsername());
        $this->assertEquals('bar', $this->provider->findById('1')->getUsername());
    }
}

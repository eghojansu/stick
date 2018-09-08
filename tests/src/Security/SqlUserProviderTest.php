<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Security;

use Fal\Stick\App;
use Fal\Stick\Security\SimpleUserTransformer;
use Fal\Stick\Security\SqlUserProvider;
use Fal\Stick\Sql\Connection;
use PHPUnit\Framework\TestCase;

class SqlUserProviderTest extends TestCase
{
    private $provider;

    public function setUp()
    {
        $app = new App();
        $conn = new Connection($app, array(
            'dsn' => 'sqlite::memory:',
            'commands' => file_get_contents(FIXTURE.'files/schema.sql'),
        ));
        $conn->pdo()->exec('insert into user (username, password) values ("foo", "foo"), ("bar", "bar"), ("baz", "baz")');

        $this->provider = new SqlUserProvider($conn, new SimpleUserTransformer());
    }

    public function testGetOptions()
    {
        $this->assertEquals(array(
            'table' => 'user',
            'username' => 'username',
            'id' => 'id',
        ), $this->provider->getOptions());
    }

    public function testSetOptions()
    {
        $this->assertEquals(array(
            'table' => 'foo',
            'username' => 'username',
            'id' => 'id',
        ), $this->provider->setOptions(array('table' => 'foo'))->getOptions());
    }

    public function testFindByUsername()
    {
        $user = $this->provider->findByUsername('foo');
        $user0 = $this->provider->findByUsername('qux');

        $this->assertEquals('1', $user->getId());
        $this->assertNull($user0);
    }

    public function testFindById()
    {
        $user = $this->provider->findById('1');
        $user0 = $this->provider->findById('qux');

        $this->assertEquals('foo', $user->getUsername());
        $this->assertNull($user0);
    }
}

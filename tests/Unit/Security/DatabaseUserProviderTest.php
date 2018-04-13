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

use Fal\Stick\Cache;
use Fal\Stick\Database\Sql;
use Fal\Stick\Security\DatabaseUserProvider;
use Fal\Stick\Security\SimpleUser;
use Fal\Stick\Security\SimpleUserTransformer;
use PHPUnit\Framework\TestCase;

class DatabaseUserProviderTest extends TestCase
{
    private $provider;

    public function setUp()
    {
        $this->provider = new DatabaseUserProvider(
            $this->db(),
            new SimpleUserTransformer()
        );
    }

    protected function db()
    {
        $cache = new Cache('', 'test', TEMP . 'cache/');
        $cache->reset();

        return new Sql($cache, [
            'driver' => 'sqlite',
            'location' => ':memory:',
            'commands' => [
                <<<SQL1
CREATE TABLE `user` (
    `id` INTEGER NOT null PRIMARY KEY AUTOINCREMENT,
    `username` TEXT NOT null,
    `password` TEXT null DEFAULT null
);
insert into user (username,password) values ("foo","bar")
SQL1
,
            ],
        ]);
    }

    public function testGetOption()
    {
        $expected = [
            'table' => 'user',
            'username' => 'username',
            'id' => 'id',
        ];

        $this->assertEquals($expected, $this->provider->getOption());
    }

    public function testSetOption()
    {
        $expected = [
            'table' => 'foo',
            'username' => 'username',
            'id' => 'id',
        ];

        $this->assertEquals($expected, $this->provider->setOption(['table'=>'foo'])->getOption());
    }

    public function testFindByUsername()
    {
        $user = new SimpleUser('1', 'foo', 'bar');

        $this->assertEquals($user, $this->provider->findByUsername('foo'));
    }

    public function testFindById()
    {
        $user = new SimpleUser('1', 'foo', 'bar');

        $this->assertEquals($user, $this->provider->findById('1'));
    }
}

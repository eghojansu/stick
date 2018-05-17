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

namespace Fal\Stick\Test\Unit\Sql;

use Fal\Stick\Cache;
use Fal\Stick\Sql\Connection;
use Fal\Stick\Sql\Mapper;
use Fal\Stick\Sql\Relation;
use PHPUnit\Framework\TestCase;

class RelationTest extends TestCase
{
    private $mapper;
    private $relation;

    public function setUp()
    {
        $cache = new Cache('', 'test', TEMP.'cache/');
        $cache->reset();

        $conn = new Connection($cache, [
            'driver' => 'sqlite',
            'location' => ':memory:',
            'commands' => <<<SQL1
CREATE TABLE `user` (
    `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    `name` TEXT NOT NULL,
    `soulmate` INTEGER NULL DEFAULT 0
);
CREATE TABLE `group` (
    `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    `name` TEXT NOT NULL
);
CREATE TABLE `user_group` (
    `user_id` INTEGER NOT NULL,
    `group_id` INTEGER NOT NULL
);
insert into user (name, soulmate) values ("foo", 0), ("bar", 1), ("baz", 1), ("qux", 2);
insert into `group` (name) values ("g1"), ("g2"), ("g3");
insert into user_group (user_id, group_id) values (1, 1), (1, 2), (2, 3), (3, 1);
SQL1
        ]);

        $this->mapper = new Mapper($conn, 'user');
        $this->relation = new Relation($this->mapper, null, 'soulmate', null, null, false);
    }

    public function loadManyProvider()
    {
        return [
            [0, 0],
            [1, 2],
            [2, 1],
            [3, 0],
            [4, 0],
        ];
    }

    /**
     * @dataProvider loadManyProvider
     */
    public function testLoadMany($id, $expected)
    {
        $this->mapper->find($id);

        $this->assertCount($expected, $this->relation);
    }

    public function testLoadManyAccess()
    {
        $this->mapper->find(1);

        foreach ($this->relation as $map) {
            $this->assertEquals(1, $map->get('soulmate'));
        }

        $this->assertEquals(2, $this->relation->first()->get('id'));
        $this->assertEquals(3, $this->relation->last()->get('id'));
        $this->assertEquals(2, $this->relation->prev()->get('id'));
        $this->assertEquals(3, $this->relation->next()->get('id'));

        $this->assertEquals(1, $this->relation->key());
    }

    public function testArrayAccess()
    {
        $this->mapper->find(1);

        $this->relation['name'] = 'not bar';
        $this->assertEquals('not bar', $this->relation['name']);
        $this->assertTrue(isset($this->relation['name']));
        unset($this->relation['name']);
        $this->assertEquals('bar', $this->relation->get('name'));
    }

    public function testLoadManyToMany()
    {
        $relation = new Relation($this->mapper, $this->mapper->withTable('group'), null, null, 'user_group');

        $this->mapper->find(1);

        $this->assertCount(2, $relation);
    }
}

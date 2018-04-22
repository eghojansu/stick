<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Unit\Database\Sql;

use Fal\Stick\Cache;
use Fal\Stick\Database\Sql\Mapper;
use Fal\Stick\Database\Sql\Relation;
use Fal\Stick\Database\Sql\Sql;
use PHPUnit\Framework\TestCase;

class RelationTest extends TestCase
{
    private $ref;
    private $relation;

    public function setUp()
    {
        $cache = new Cache('', 'test', TEMP . 'cache/');
        $cache->reset();

        $database = new Sql($cache, [
            'driver' => 'sqlite',
            'location' => ':memory:',
            'commands' => [
                <<<SQL1
CREATE TABLE `user` (
    `id` INTEGER NOT NULL PRIMARY KEY,
    `name` TEXT NOT NULL,
    `friend_id` INTEGER NULL DEFAULT 0
);
CREATE TABLE `friends` (
    `user_id` INTEGER NOT NULL,
    `friend_id` INTEGER NOT NULL
);
INSERT INTO `user` (`id`,`name`,`friend_id`) values
    (1,'foo',0),
    (2,'bar',1),
    (3,'baz',2),
    (4,'qux',2);
INSERT INTO `friends` (user_id,friend_id) VALUES
    (1,2),
    (1,3),
    (1,4),
    (2,3),
    (2,4),
    (3,4);
SQL1
,
            ],
        ]);

        $this->ref = new Mapper($database, 'user');
        $this->ref->loadId(1);
        $this->relation = new Relation($this->ref, 'id', null, 'friend_id');
    }

    public function tearDown()
    {
        error_clear_last();
    }

    public function testLoad()
    {
        $this->assertEquals($this->relation, $this->relation->load());
        $this->assertEquals($this->relation, $this->relation->load());
        $this->assertEquals($this->relation, $this->relation->load(true));

        $this->ref->reset();
        $this->assertEquals($this->relation, $this->relation->load(true));
    }

    public function testManyToMany()
    {
        $option = [
            'lookup' => 'friends',
            'targetId'=>'friend_id',
            'refId'=>'user_id',
        ];
        $relation = new Relation($this->ref, 'id', null, 'id', $option);
        $relation->load();
        $friends = ['bar','baz','qux'];

        $this->assertEquals(count($friends), count($relation));
        foreach ($relation as $key => $item) {
            $this->assertEquals($friends[$key], $item['name']);
        }

        $this->assertEquals('bar', $relation->first()->name);
        $this->assertEquals('qux', $relation->last()->name);
        $this->assertEquals('baz', $relation->prev()->name);
    }

    public function testInvalid()
    {
        $this->assertFalse($this->relation->invalid());

        $this->ref->reset();
        $this->assertTrue($this->relation->load(true)->invalid());
    }

    public function testCount()
    {
        $this->assertEquals(1, $this->relation->count());
    }

    public function testArrayAccess()
    {
        $this->relation['name'] = 'not bar';
        $this->assertEquals('not bar', $this->relation['name']);
        $this->assertTrue(isset($this->relation['name']));
        unset($this->relation['name']);
        $this->assertEquals('bar', $this->relation['name']);
    }

    public function testMagicAccess()
    {
        $this->relation->name = 'not bar';
        $this->assertEquals('not bar', $this->relation->name);
        $this->assertTrue(isset($this->relation->name));
        unset($this->relation->name);
        $this->assertEquals('bar', $this->relation->name);
    }

    public function testMagicCall()
    {
        $this->assertEquals('user', $this->relation->getTable());

        // call to target object
        $this->ref->reset();
        $this->assertEquals('user', $this->relation->getTable());
        $this->assertEquals(1, $this->relation->get('friend_id'));
    }
}

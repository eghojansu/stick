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

namespace Fal\Stick\Test\Sql;

use Fal\Stick\App;
use Fal\Stick\Sql\Connection;
use Fal\Stick\Sql\Mapper;
use Fal\Stick\Test\fixture\mapper\LogMapper;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
    private $mapper;

    public function setUp()
    {
        $app = App::create()->mset([
            'TEMP' => TEMP,
        ])->logClear();
        $cache = $app->get('cache');
        $cache->reset();

        $conn = new Connection($app, $cache, [
            'driver' => 'sqlite',
            'location' => ':memory:',
            'commands' => <<<SQL1
CREATE TABLE `user` (
    `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    `username` TEXT NOT NULL,
    `password` TEXT NULL DEFAULT NULL,
    `active` INTEGER NOT NULL DEFAULT 1
);
CREATE TABLE `profile` (
    `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    `fullname` TEXT NOT NULL,
    `user_id` INTEGER NOT NULL
);
CREATE TABLE `log` (
    `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    `content` TEXT NOT NULL,
    `user_id` INTEGER NOT NULL
);
insert into user (username) values ("foo"), ("bar"), ("baz");
insert into profile (fullname, user_id) values ("fullfoo", 1), ("fullbar", 2), ("fullbaz", 3);
insert into log (content, user_id) values ("logfoo", 1), ("logbar", 1), ("logbaz", 2);
SQL1
        ]);

        $this->mapper = new Mapper($conn, 'user');
    }

    public function testGetTable()
    {
        $this->assertEquals('user', $this->mapper->getTable());
    }

    public function testSetTable()
    {
        $this->assertEquals('profile', $this->mapper->setTable('profile')->getTable());
    }

    public function testWithTable()
    {
        $this->assertEquals('profile', $this->mapper->withTable('profile')->getTable());
    }

    public function testGetFields()
    {
        $this->assertEquals(['id', 'username', 'password', 'active'], $this->mapper->getFields());
    }

    public function testGetSchema()
    {
        $schema = $this->mapper->getSchema();
        $this->assertEquals(['id', 'username', 'password', 'active'], array_keys($schema));
    }

    public function testGetConnection()
    {
        $this->assertInstanceof(Connection::class, $this->mapper->getConnection());
    }

    public function testOne()
    {
        $this->assertEquals($this->mapper, $this->mapper->one('foo', 'is_string'));
    }

    public function testOn()
    {
        $this->assertEquals($this->mapper, $this->mapper->on('foo', 'is_string'));
        $this->assertEquals($this->mapper, $this->mapper->on('bar,baz', 'is_string'));
        $this->assertEquals($this->mapper, $this->mapper->on(['qux', 'quux'], 'is_string'));
    }

    public function triggerProvider()
    {
        return [
            [
                [], [[false, 'foo']],
            ],
            [
                ['foo', function () {
                }], [[true, 'foo']],
            ],
            [
                ['foo,bar', function () {
                }], [[true, 'bar']],
            ],
            [
                ['foo', function () {
                    return true;
                }], [[false, 'foo']],
            ],
            [
                ['foo', function () {
                }], [[true, 'foo']], true,
            ],
        ];
    }

    /**
     * @dataProvider triggerProvider
     */
    public function testTrigger($sets, $triggers, $once = false)
    {
        if ($sets) {
            if ($once) {
                $this->mapper->one(...$sets);
            } else {
                $this->mapper->on(...$sets);
            }
        }

        foreach ($triggers as $trigger) {
            $this->assertEquals($trigger[0], $this->mapper->trigger($trigger[1], $trigger[2] ?? []));
        }

        if ($once) {
            foreach ($triggers as $trigger) {
                $this->assertEquals(false, $this->mapper->trigger($trigger[1], $trigger[2] ?? []));
            }
        }
    }

    public function testHasOne()
    {
        $relation = $this->mapper->hasOne('profile');
        $this->mapper->find(1);

        $this->assertEquals('fullfoo', $relation->get('fullname'));
    }

    public function testHasMany()
    {
        $relation = $this->mapper->find(1)->hasMany(LogMapper::class);

        $this->assertCount(2, $relation);
        $this->assertEquals('logfoo', $relation->first()->get('content'));
        $this->assertEquals('logbar', $relation->last()->get('content'));
    }

    public function testCreateRelation()
    {
        $relation = $this->mapper->find(1)->createRelation($this->mapper->withTable('profile'), 'user_id', 'id');

        $this->assertEquals('fullfoo', $relation->get('fullname'));
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Target must be instance of Fal\Stick\Sql\Mapper or a name of valid table, given DateTime
     */
    public function testCreateRelationException()
    {
        $this->mapper->createRelation(\DateTime::class);
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Target must be instance of Fal\Stick\Sql\Mapper or a name of valid table, given DateTime
     */
    public function testCreateRelationException2()
    {
        $this->mapper->createRelation(new \DateTime());
    }

    public function testExists()
    {
        $this->assertTrue($this->mapper->exists('id'));
        $this->assertTrue($this->mapper->exists('username'));
        $this->assertFalse($this->mapper->exists('foo'));
    }

    public function testGet()
    {
        $this->mapper->set('adhoc', '2');
        $this->mapper->set('prop', 3);

        $this->mapper->load();
        $this->assertEquals(1, $this->mapper->get('id'));
        $this->assertEquals('2', $this->mapper->get('adhoc'));
        $this->assertEquals('3', $this->mapper->get('prop'));
        $this->assertEquals(['id' => 1], $this->mapper->get('keys'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Undefined field foo
     */
    public function testGetException()
    {
        $this->mapper->get('foo');
    }

    public function testSet()
    {
        $this->mapper->set('adhoc', '2');
        $this->mapper->set('prop', 3);

        $this->mapper->load();
        $this->assertEquals(1, $this->mapper->get('id'));
        $this->assertEquals(2, $this->mapper->set('id', 2)->get('id'));
        $this->assertEquals('2', $this->mapper->get('adhoc'));
        $this->assertEquals('4', $this->mapper->set('adhoc', '4')->get('adhoc'));
        $this->assertEquals('3', $this->mapper->get('prop'));
    }

    public function testClear()
    {
        $this->mapper->set('adhoc', '2');
        $this->mapper->set('prop', 3);

        $this->mapper->load();
        $this->mapper->clear('id')->clear('adhoc')->clear('prop');
        $this->assertEquals(1, $this->mapper->get('id'));
    }

    public function testReset()
    {
        $this->mapper->load()->reset();

        $this->assertEquals(null, $this->mapper->get('id'));
        $this->assertEquals(1, $this->mapper->get('active'));
    }

    public function testRequired()
    {
        $this->assertTrue($this->mapper->required('id'));
        $this->assertTrue($this->mapper->required('username'));
        $this->assertFalse($this->mapper->required('password'));
        $this->assertFalse($this->mapper->required('foo'));
    }

    public function testChanged()
    {
        $this->mapper->load();

        $this->assertFalse($this->mapper->changed('id'));
        $this->assertFalse($this->mapper->changed());

        $this->mapper->set('id', 2);
        $this->assertTrue($this->mapper->changed('id'));
        $this->assertTrue($this->mapper->changed());
    }

    public function testKeys()
    {
        $this->assertEquals(['id' => null], $this->mapper->keys());
        $this->assertEquals(['id' => 1], $this->mapper->load()->keys());
    }

    public function testGetKeys()
    {
        $this->assertEquals(['id'], $this->mapper->getKeys());
    }

    public function testSetKeys()
    {
        $this->assertEquals(['username'], $this->mapper->setKeys(['username'])->getKeys());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Invalid key foo
     */
    public function testSetKeysException()
    {
        $this->mapper->setKeys(['foo']);
    }

    public function testFromArray()
    {
        $this->assertEquals('foo', $this->mapper->fromArray(['username' => 'foo'])->get('username'));
        $this->assertEquals('foobar', $this->mapper->fromArray(['username' => 'foo'], function ($u) {
            $u['username'] = 'foobar';

            return $u;
        })->get('username'));
    }

    public function testToArray()
    {
        $this->assertEquals(['id' => null, 'username' => null, 'password' => null, 'active' => 1], $this->mapper->toArray());
        $this->assertEquals(['id' => null, 'username' => 'foo', 'password' => null, 'active' => 1], $this->mapper->toArray(function ($u) {
            $u['username'] = 'foo';

            return $u;
        }));
    }

    public function testValid()
    {
        $this->assertFalse($this->mapper->valid());
        $this->assertTrue($this->mapper->load()->valid());
    }

    public function testDry()
    {
        $this->assertTrue($this->mapper->dry());
        $this->assertFalse($this->mapper->load()->dry());
    }

    public function paginateProvider()
    {
        $all = ['qux', 'quux', 'corge', 'grault', 'garply', 'waldo', 'fred', 'plugh', 'xyzzy', 'thud'];
        $len = 3 + count($all);

        return [
            [
                $all,
                [1, null, ['perpage' => 2]],
                [
                    'total' => $len,
                    'count' => 2,
                    'pages' => 7,
                    'page' => 1,
                    'start' => 1,
                    'end' => 2,
                ],
            ],
            [
                $all,
                [2, null, ['perpage' => 2]],
                [
                    'total' => $len,
                    'count' => 2,
                    'pages' => 7,
                    'page' => 2,
                    'start' => 3,
                    'end' => 4,
                ],
            ],
            [
                $all,
                [2, null, ['perpage' => 3]],
                [
                    'total' => $len,
                    'count' => 3,
                    'pages' => 5,
                    'page' => 2,
                    'start' => 4,
                    'end' => 6,
                ],
            ],
            [
                $all,
                [5, null, ['perpage' => 3]],
                [
                    'total' => $len,
                    'count' => 1,
                    'pages' => 5,
                    'page' => 5,
                    'start' => 13,
                    'end' => 13,
                ],
            ],
            [
                $all,
                [0, null, ['perpage' => 2]],
                [
                    'total' => $len,
                    'count' => 0,
                    'pages' => 7,
                    'page' => 0,
                    'start' => 0,
                    'end' => 0,
                ],
            ],
            [
                [],
                [0, null, ['perpage' => 2]],
                [
                    'total' => 3,
                    'count' => 0,
                    'pages' => 2,
                    'page' => 0,
                    'start' => 0,
                    'end' => 0,
                ],
            ],
        ];
    }

    /** @dataProvider paginateProvider */
    public function testPaginate($data, $args, $expected)
    {
        foreach ($data as $s) {
            $this->mapper->fromArray(['username' => $s])->insert()->reset();
        }

        $res = $this->mapper->paginate(...$args);
        $subset = array_shift($res);
        $this->assertEquals($expected, $res);
    }

    public function testCount()
    {
        $this->assertEquals(3, $this->mapper->count());
        $this->assertEquals(2, $this->mapper->count('id < 3'));
        $this->assertEquals(1, $this->mapper->count(['id' => 1]));
        $this->assertEquals(0, $this->mapper->count('id = 4'));
    }

    public function testLoad()
    {
        $this->assertEquals($this->mapper, $this->mapper->load());
        $this->assertTrue($this->mapper->valid());
        $this->assertEquals($this->mapper, $this->mapper->load('id=4'));
        $this->assertTrue($this->mapper->dry());
    }

    public function testFind()
    {
        $this->assertEquals($this->mapper, $this->mapper->find(1));
        $this->assertEquals(1, $this->mapper->get('id'));
    }

    /**
     * @expectedException \ArgumentCountError
     * @expectedExceptionMessageRegExp /expect exactly 1 arguments, 0 given$/
     */
    public function testFindException()
    {
        $this->mapper->find();
    }

    public function testFindAll()
    {
        $res = $this->mapper->findAll();

        $this->assertCount(3, $res);
        $this->assertEquals([], $this->mapper->findAll('id=4'));

        $res = $this->mapper->findAll(null, ['group' => 'id', 'having' => ['id []' => [1, 2]], 'order' => 'username desc']);
        $this->assertCount(2, $res);
        $this->assertEquals('foo', $res[0]->get('username'));
        $this->assertEquals('bar', $res[1]->get('username'));
    }

    public function testSave()
    {
        $this->mapper->fromArray(['username' => 'bleh'])->save();

        $this->assertEquals('bleh', $this->mapper->get('username'));
        $this->assertEquals(4, $this->mapper->get('id'));

        $this->mapper->fromArray(['username' => 'update'])->save();

        $this->assertEquals('update', $this->mapper->get('username'));
        $this->assertEquals(4, $this->mapper->get('id'));
    }

    public function insertProvider()
    {
        return [
            [
                [], null,
            ],
            [
                ['foo' => 'bar'], null,
            ],
            [
                ['username' => 'bleh'],
            ],
            [
                ['username' => 'bleh', 'foo' => 'bar'],
            ],
            [
                ['username' => 'bleh', 'password' => 'bleh', 'active' => 0, 'id' => 5], 5,
            ],
        ];
    }

    /**
     * @dataProvider insertProvider
     */
    public function testInsert($sets, $id = 4)
    {
        $this->mapper->fromArray($sets);
        $this->mapper->insert();

        $this->assertEquals((bool) $id, $this->mapper->valid());
        $this->assertEquals($id, $this->mapper->get('id'));
    }

    public function testInsertInterception()
    {
        $this->mapper->on(Mapper::EVENT_BEFOREINSERT, function () {
        });
        $this->mapper->fromArray(['username' => 'bleh']);
        $this->mapper->insert();

        $this->assertFalse($this->mapper->valid());
    }

    public function testInsertModify()
    {
        $this->mapper->on(Mapper::EVENT_INSERT, function ($mapper) {
            $mapper->set('username', 'notbleh');
        });
        $this->mapper->fromArray(['username' => 'bleh']);
        $this->mapper->insert();

        $this->assertEquals('notbleh', $this->mapper->get('username'));
        $this->assertTrue($this->mapper->changed());
    }

    public function updateProvider()
    {
        $one = ['id' => 1, 'username' => 'foo', 'password' => null, 'active' => 1];
        $foo = ['foo' => 'bar'];
        $bleh = ['username' => 'bleh'];
        $fullbleh = ['id' => 4, 'username' => 'bleh', 'password' => 'bleh', 'active' => 0];

        return [
            [
                1, [], $one,
            ],
            [
                1, $foo, $one,
            ],
            [
                1, $bleh, $bleh + $one,
            ],
            [
                1, $bleh + $foo, $bleh + $one,
            ],
            [
                1, $fullbleh, $fullbleh,
            ],
        ];
    }

    /**
     * @dataProvider updateProvider
     */
    public function testUpdate($id, $sets, $expected)
    {
        $this->mapper->find($id);
        $this->mapper->fromArray($sets);
        $this->mapper->update();

        $this->assertEquals($expected, $this->mapper->toArray());
    }

    public function testUpdateInterception()
    {
        $this->mapper->on(Mapper::EVENT_BEFOREUPDATE, function () {
        });
        $this->mapper->find(1);
        $this->mapper->fromArray(['username' => 'bleh']);
        $this->mapper->update();

        $this->mapper->find(1);
        $this->assertEquals('foo', $this->mapper->get('username'));
    }

    public function testUpdateModify()
    {
        $this->mapper->on(Mapper::EVENT_UPDATE, function ($mapper) {
            $mapper->set('username', 'notbleh');
        });
        $this->mapper->find(1);
        $this->mapper->fromArray(['username' => 'bleh']);
        $this->mapper->update();

        $this->assertEquals('notbleh', $this->mapper->get('username'));
        $this->assertTrue($this->mapper->changed());
    }

    public function deleteProvider()
    {
        $one = ['id' => 1, 'username' => 'foo', 'password' => null, 'active' => 1];
        $foo = ['foo' => 'bar'];
        $bleh = ['username' => 'bleh'];
        $fullbleh = ['id' => 4, 'username' => 'bleh', 'password' => 'bleh', 'active' => 0];

        return [
            [1, 1],
            [4, 0],
        ];
    }

    /**
     * @dataProvider deleteProvider
     */
    public function testDelete($id, $expected)
    {
        $this->mapper->find($id);
        $deleted = $this->mapper->delete();

        $this->assertEquals($expected, $deleted);
        $this->assertTrue($this->mapper->dry());
    }

    public function testDeleteInterception()
    {
        $this->mapper->on(Mapper::EVENT_BEFOREDELETE, function () {
        });
        $this->mapper->find(1);
        $deleted = $this->mapper->delete();

        $this->assertEquals(0, $deleted);
        $this->assertTrue($this->mapper->valid());

        $this->mapper->find(1);
        $this->assertTrue($this->mapper->valid());
    }

    public function testDeleteModify()
    {
        $this->mapper->on(Mapper::EVENT_DELETE, function ($mapper) {
            $mapper->set('username', 'notbleh');
        });
        $this->mapper->find(1);
        $deleted = $this->mapper->delete();

        $this->assertEquals(1, $deleted);
        $this->assertNull($this->mapper->get('username'));
        $this->assertTrue($this->mapper->dry());
    }

    public function deleteBatchProvider()
    {
        return [
            ['id > 1', true, 2, 1],
            [['id' => 1], true, 1, 2],
            [['id' => 1], false, 1, 2],
            ['id <> 0', false, 3, 0],
        ];
    }

    /**
     * @dataProvider deleteBatchProvider
     */
    public function testDeleteBatch($filter, $hayati, $expected, $rest)
    {
        $deleted = $this->mapper->delete($filter, $hayati);

        $this->assertEquals($expected, $deleted);
        $this->assertEquals($rest, $this->mapper->count());
    }

    public function testArrayAccess()
    {
        $this->mapper['foo'] = 1;
        $this->assertEquals(1, $this->mapper['foo']);
        unset($this->mapper['foo']);
        $this->assertFalse(isset($this->mapper['foo']));
    }

    public function testMagicCall()
    {
        $this->assertNull($this->mapper->getId());
        $this->assertCount(1, $this->mapper->findById(1));
        $this->assertEquals('foo', $this->mapper->loadByUsername('foo')->getUsername());
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessageRegExp /^Call to undefined method [^:]+::foo$/
     */
    public function testMagicCallException()
    {
        $this->mapper->foo();
    }
}

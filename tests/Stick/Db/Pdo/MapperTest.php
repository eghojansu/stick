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

namespace Fal\Stick\Test\Db\Pdo;

use Fal\Stick\Fw;
use Fal\Stick\Db\Pdo\Db;
use Fal\Stick\Db\Pdo\Mapper;
use Fal\Stick\TestSuite\MyTestCase;
use Fal\Stick\Db\Pdo\Driver\SqliteDriver;

class MapperTest extends MyTestCase
{
    private $fw;
    private $db;
    private $mapper;

    public function setup(): void
    {
        $this->fw = new Fw();
        $this->db = new Db(
            $this->fw,
            new SqliteDriver(),
            'sqlite::memory:',
            null,
            null,
            array(
                $this->read('/files/schema_sqlite.sql'),
                'insert into user (username) values ("foo"), ("bar"), ("baz")',
                'insert into friends (user_id, friend_id, level) values '.
                    '(1, 2, 3), (2, 3, 4)',
                'insert into nokey (name, info) values '.
                    '("foo", "bar"), ("bar", "baz")',
                'insert into profile (fullname, user_id) values '.
                    '("fooname", 1), ("barname", 2), ("bazname", 3)',
                'insert into phone (phonename, user_id) values '.
                    '("foo1", 1), ("foo2", 1), ("bar1", 2), ("baz1", 3)',
            )
        );
        $this->mapper = new Mapper($this->db, 'user');
    }

    public function testMagicCall()
    {
        $this->assertCount(1, $this->mapper->findAllById(1));
        $this->assertCount(1, $this->mapper->findById(2));
        $this->assertEquals('bar', $this->mapper->getUsername());

        $this->expectException('BadMethodCallException');
        $this->expectExceptionMessage(
            'Call to undefined method Fal\\Stick\\Db\\Pdo\\Mapper::foo.'
        );
        $this->mapper->foo();
    }

    public function testMagicExists()
    {
        $this->assertTrue(isset($this->mapper->username));
        $this->assertFalse(isset($this->mapper->foo));
    }

    public function testMagicGet()
    {
        $this->assertNull($this->mapper->username);
    }

    public function testMagicSet()
    {
        $this->mapper->username = 'foo';

        $this->assertEquals('foo', $this->mapper->username);
    }

    public function testMagicUnset()
    {
        $this->mapper->username = 'foo';
        unset($this->mapper->username);

        $this->assertNull($this->mapper->username);
    }

    public function testOffsetExists()
    {
        $this->assertTrue(isset($this->mapper['username']));
        $this->assertFalse(isset($this->mapper['foo']));
    }

    public function testOffsetGet()
    {
        $this->assertNull($this->mapper['username']);
    }

    public function testOffsetSet()
    {
        $this->mapper['username'] = 'foo';

        $this->assertEquals('foo', $this->mapper['username']);
    }

    public function testOffsetUnset()
    {
        $this->mapper['username'] = 'foo';
        unset($this->mapper['username']);

        $this->assertNull($this->mapper['username']);
    }

    public function testCurrent()
    {
        $this->assertCount(3, $this->mapper->findAll());
        $this->assertEquals(array(
            'id' => 1,
            'username' => 'foo',
            'password' => null,
            'active' => 1,
        ), $this->mapper->toArray());
    }

    public function testKey()
    {
        $this->assertEquals(0, $this->mapper->key());
    }

    public function testNext()
    {
        $this->mapper->next();

        $this->assertEquals(1, $this->mapper->key());
    }

    public function testRewind()
    {
        $this->mapper->rewind();

        $this->assertEquals(0, $this->mapper->key());
    }

    public function testValid()
    {
        $this->assertFalse($this->mapper->valid());
    }

    public function testCount()
    {
        $this->assertCount(0, $this->mapper);
    }

    public function testMoveTo()
    {
        $this->mapper->findAll();
        $this->mapper->moveTo(2);

        $this->assertEquals(2, $this->mapper->key());
        $this->assertEquals('baz', $this->mapper->get('username'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Invalid pointer: 3.');

        $this->mapper->moveTo(3);
    }

    public function testPrev()
    {
        $this->mapper->prev();

        $this->assertEquals(-1, $this->mapper->key());
    }

    public function testDry()
    {
        $this->assertTrue($this->mapper->dry());
    }

    public function testTable()
    {
        $this->assertEquals('user', $this->mapper->table());
    }

    public function testAlias()
    {
        $this->assertNull($this->mapper->alias());
    }

    public function testSwitchTable()
    {
        $this->mapper->switchTable('profile.foo');

        $this->assertEquals('profile', $this->mapper->table());
        $this->assertEquals('foo', $this->mapper->alias());
    }

    public function testFactory()
    {
        $this->assertEquals(array(
            'id' => null,
            'username' => 'manual',
            'password' => null,
            'active' => 1,
        ), $this->mapper->factory(array('username' => 'manual'))->toArray());
    }

    public function testLock()
    {
        $this->mapper->lock('username', 'foo');
        $this->assertEquals('foo', $this->mapper->get('username'));
        $this->assertTrue($this->mapper->locked('username'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Cannot lock adhoc field: foo.');

        $this->mapper->lock('foo', 'bar');
    }

    public function testUnlock()
    {
        $this->mapper->lock('username', 'foo')->unlock('username');

        $this->assertEquals('foo', $this->mapper->get('username'));
        $this->assertFalse($this->mapper->locked('username'));
    }

    public function testHas()
    {
        $this->assertTrue($this->mapper->has('username'));
        $this->assertFalse($this->mapper->has('foo'));
    }

    public function testGet()
    {
        // get field value
        $this->assertNull($this->mapper->get('username'));

        // get call result of dry
        $this->assertTrue($this->mapper->get('dry'));
        // second call
        $this->assertTrue($this->mapper->get('dry'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Field not exists: baz.');

        $this->mapper->get('baz');
    }

    public function testSet()
    {
        // assign username
        $this->mapper->set('username', 'manual');
        // assign expr adhoc
        $this->mapper->set('foo', 'bar');
        // update expr adhoc
        $this->mapper->set('foo', 'baz');
        // update value adhoc
        $this->mapper->set('bar', 1);
        // assign callable adhoc
        $this->mapper->set('baz', function () {
            return 'baz';
        });
        // locked field
        $this->mapper->lock('password', 'initial');
        $this->mapper->set('password', 'update');

        $this->assertEquals('manual', $this->mapper->get('username'));
        $this->assertEquals('baz', $this->mapper->get('foo'));
        $this->assertEquals(1, $this->mapper->get('bar'));
        $this->assertEquals('baz', $this->mapper->get('baz'));
        $this->assertEquals('initial', $this->mapper->get('password'));
    }

    public function testRem()
    {
        // update field with initial value
        $this->mapper->set('active', 0);
        // assign adhoc
        $this->mapper->set('foo', function () {
            return 'foo';
        });

        // remove
        $this->mapper->rem('active');
        $this->mapper->rem('foo');

        $this->assertEquals(1, $this->mapper->get('active'));
        $this->assertEquals('foo', $this->mapper->get('foo'));
    }

    public function testChanged()
    {
        $this->assertFalse($this->mapper->changed());

        // change it
        $this->mapper->set('username', 'foo');
        $this->assertTrue($this->mapper->changed());
        $this->assertTrue($this->mapper->changed('username'));
        $this->assertFalse($this->mapper->changed('password'));
    }

    public function testLocked()
    {
        $this->assertFalse($this->mapper->locked('username'));
    }

    public function testReset()
    {
        $this->mapper->set('active', 0);
        $this->mapper->reset();

        $this->assertEquals(1, $this->mapper->get('active'));
    }

    public function testToArray()
    {
        // add adhoc
        $this->mapper->set('next_id', 'id + 1');
        $this->mapper->findAll();

        $this->assertEquals(array(
            'id' => '1',
            'username' => 'foo',
            'password' => null,
            'active' => '1',
            'next_id' => '2',
        ), $this->mapper->toArray(true));
    }

    public function testFromArray()
    {
        // add adhoc
        $this->mapper->set('next_id', 'id + 1');
        $this->mapper->fromArray(array(
            'username' => 'manual',
            'next_id' => 'foo',
        ), true);
        $this->assertEquals(array(
            'id' => null,
            'username' => 'manual',
            'password' => null,
            'active' => '1',
            'next_id' => 'foo',
        ), $this->mapper->toArray(true));

        // no adhoc
        $this->mapper->fromArray(array(
            'username' => 'manual',
            'next_id' => 'bar',
        ));
        $this->assertEquals(array(
            'id' => null,
            'username' => 'manual',
            'password' => null,
            'active' => '1',
            'next_id' => 'foo',
        ), $this->mapper->toArray(true));
    }

    public function testInitial()
    {
        $this->assertEquals(array(
            'id' => null,
            'username' => null,
            'password' => null,
            'active' => '1',
        ), $this->mapper->initial());
    }

    public function testValues()
    {
        $this->assertEquals(array(
            'id' => null,
            'username' => null,
            'password' => null,
            'active' => '1',
        ), $this->mapper->values());
    }

    public function testChanges()
    {
        $this->assertEquals(array(), $this->mapper->changes());
    }

    public function testKeys()
    {
        $this->assertEquals(array(
            'id' => null,
        ), $this->mapper->keys());
    }

    public function testConcatFields()
    {
        $expected = '`id`, `username`, `password`, `active`';

        $this->assertEquals($expected, $this->mapper->concatFields());
    }

    public function testConcatAdhoc()
    {
        $this->mapper->set('adhoc', 'foo');
        $expected = ', (foo) as `adhoc`';

        $this->assertEquals($expected, $this->mapper->concatAdhoc());
    }

    public function testRows()
    {
        $this->assertCount(0, $this->mapper->rows());
    }

    public function testAdhoc()
    {
        $this->assertCount(0, $this->mapper->adhoc());
    }

    public function testRules()
    {
        $this->mapper->switchTable('types_check');

        $ref = new \ReflectionProperty($this->mapper, 'rules');
        $ref->setAccessible(true);
        $ref->setValue($this->mapper, array(
            'foo' => 'bar',
            'bar' => array('baz', 'group2'),
            'name' => array('foo', 'group2'),
        ));

        $this->assertEquals(array(
            'last_name' => 'lenmax:32',
            'last_check' => 'required|DATE',
            'name' => 'foo',
            'bar' => 'baz',
        ), $this->mapper->rules('group2'));
    }

    public function testFindAll()
    {
        // set adhoc
        $this->mapper->set('adhoc', '1+1');

        $this->assertCount(3, $this->mapper->findAll());
        $this->assertCount(1, $this->mapper->findAll('id = 1'));
        $this->assertEquals(2, $this->mapper->get('adhoc'));
    }

    public function testFindOne()
    {
        $this->assertCount(1, $this->mapper->findOne());
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Db\Pdo\MapperProvider::find
     */
    public function testFind($expected, $keys, $switch = null, $exception = null)
    {
        if ($switch) {
            $this->mapper->switchTable($switch);
        }

        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            $this->mapper->find($keys);

            return;
        }

        $this->assertEquals($expected, $this->mapper->find($keys)->toArray());
    }

    public function testCountRow()
    {
        $this->assertEquals(3, $this->mapper->countRow());
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Db\Pdo\MapperProvider::paginate
     */
    public function testPaginate($expected, $page, $filter = null, $options = null)
    {
        $actual = $this->mapper->paginate($page, $filter, $options);

        $this->assertInstanceOf(Mapper::class, $actual['subset']);

        $actual['subset'] = count($actual['subset']);

        $this->assertEquals($expected, $actual);
    }

    public function testSave()
    {
        // assign from array
        $this->mapper->fromArray(array(
            'username' => 'qux',
            'password' => 'qux',
            'active' => 0,
        ));

        $this->assertTrue($this->mapper->dry());
        $this->assertEquals(array(
            'id' => null,
            'username' => 'qux',
            'password' => 'qux',
            'active' => 0,
        ), $this->mapper->toArray());
        $this->assertTrue($this->mapper->save());
        $this->assertTrue($this->mapper->valid());
        $this->assertFalse($this->mapper->dry());
        $this->assertEquals(array(
            'id' => 4,
            'username' => 'qux',
            'password' => 'qux',
            'active' => 0,
        ), $this->mapper->toArray());

        // assign with id
        $this->mapper->reset()->fromArray(array(
            'id' => null,
            'username' => 'quux',
            'password' => 'quux',
            'active' => 0,
        ));
        $this->assertTrue($this->mapper->save());
        $this->assertEquals(array(
            'id' => 5,
            'username' => 'quux',
            'password' => 'quux',
            'active' => 0,
        ), $this->mapper->toArray());

        // update
        $this->mapper->set('username', 'quux quux');
        $this->assertTrue($this->mapper->save());
        $this->assertEquals(array(
            'id' => 5,
            'username' => 'quux quux',
            'password' => 'quux',
            'active' => 0,
        ), $this->mapper->toArray());

        // before save
        $this->fw->on('mapper.save', function () {
            return false;
        });
        $this->assertFalse($this->mapper->save());

        // no key
        $this->fw->off('mapper.save');
        $this->mapper->switchTable('nokey');

        $this->assertCount(0, $this->mapper);
        $this->assertTrue($this->mapper->fromArray(array('name' => 'nokey'))->save());
        $this->assertCount(1, $this->mapper);
        $this->assertEquals('nokey', $this->mapper->get('name'));
    }

    public function testSaveNothing()
    {
        $this->assertFalse($this->mapper->save());
    }

    public function testDelete()
    {
        // no key
        $this->mapper->switchTable('nokey');
        $this->assertFalse($this->mapper->findAll()->delete());
        $this->assertCount(2, $this->mapper);

        // switch table
        $this->mapper->switchTable('user');
        $this->assertTrue($this->mapper->findAll()->delete());
        $this->assertCount(2, $this->mapper);
        $this->assertEquals(2, $this->mapper->get('id'));

        // add event
        $this->fw->on('mapper.delete', function () {
            return false;
        });
        $this->assertFalse($this->mapper->delete());
    }

    public function testDeleteAll()
    {
        // event doing nothing
        $this->fw->on('mapper.delete', function () {
            // do nothing
        });

        $this->assertEquals(1, $this->mapper->deleteAll('id = 1', true));
        $this->assertEquals(1, $this->mapper->deleteAll('id > 2'));
        $this->assertCount(1, $this->mapper->findAll());
    }

    public function testHasOne()
    {
        // new mapper without profile
        $this->mapper->fromArray(array(
            'username' => 'myfoo',
        ))->save();

        // wants to insert new profile
        $profile = $this->mapper->hasOne('profile');

        $this->assertCount(0, $profile);
        $this->assertEquals(4, $profile->get('user_id'));

        // assign and save profile
        $profile->fromArray(array(
            'fullname' => 'myfoo name',
        ));

        $this->assertEquals(array(
            'id' => null,
            'fullname' => 'myfoo name',
            'user_id' => '4',
        ), $profile->toArray());

        $this->assertTrue($profile->save());
        $this->assertCount(1, $profile);
        $this->assertEquals('myfoo name', $profile->get('fullname'));

        // confirm
        $this->mapper->reset();
        $this->mapper->findById(4);

        $profile = $this->mapper->hasOne('profile');

        $this->assertCount(1, $profile);
        $this->assertEquals('myfoo name', $profile->get('fullname'));

        // load profile with extra filter
        $profile = $this->mapper->hasOne('profile', null, 'fullname = "none"');
        $this->assertCount(0, $profile);
    }

    public function testHasMany()
    {
        $this->mapper->findOne();

        $phone = $this->mapper->hasMany('phone');

        $this->assertCount(2, $phone);
        $this->assertEquals('foo1', $phone->get('phonename'));
    }

    public function testBelongsTo()
    {
        $this->mapper->switchTable('profile');
        $this->mapper->find(1);

        $user = $this->mapper->belongsTo('user');

        $this->assertCount(1, $user);
        $this->assertEquals('foo', $user->get('username'));
    }

    public function testBelongsToMany()
    {
        $this->db->mexec(array(
            'insert into ta (id, vcol) values (1, "ta1"), (2, "ta2")',
            'insert into tb (id, vcol) values (1, "tb1"), (2, "tb2")',
            'insert into tc (ta_id, tb_id) values (1, 1), (1, 2), (2, 1)',
        ));
        $this->mapper->switchTable('ta');
        $this->mapper->findOne();

        $tc = $this->mapper->belongsToMany('tb', 'tc', null, 'id > 0');

        $this->assertCount(2, $tc);

        $this->assertCount(1, $tc->pivot);
        $this->assertEquals(array(
            'id' => 1,
            'vcol' => 'tb1',
        ), $tc->toArray());
        $this->assertEquals(array(
            'ta_id' => 1,
            'tb_id' => 1,
        ), $tc->pivot->toArray());

        // next
        $tc->next();
        $tc->current();
        $this->assertCount(1, $tc->pivot);
        $this->assertEquals(array(
            'id' => 2,
            'vcol' => 'tb2',
        ), $tc->toArray());
        $this->assertEquals(array(
            'ta_id' => 1,
            'tb_id' => 2,
        ), $tc->pivot->toArray());
    }

    public function testBelongsToMany2()
    {
        $this->db->mexec(array(
            'insert into ta2 (id, vcol) values (1, "ta1"), (2, "ta2")',
            'insert into tb2 (id, vcol, id2) values (1, "tb11", 1), (1, "tb12", 2), (1, "tb13", 3), (2, "tb21", 1)',
            'insert into tc2 (ta2_id, tb2_id, tb2_id2) values (1, 1, 1), (1, 1, 2), (1, 1, 3), (2, 1, 1)',
        ));
        $this->mapper->switchTable('ta2');
        $this->mapper->findOne();

        $tc = $this->mapper->belongsToMany('tb2', 'tc2', 'tb2_id = id, tb2_id2 = id2');

        $this->assertCount(3, $tc);

        $this->assertCount(1, $tc->pivot);
        $this->assertEquals(array(
            'id' => 1,
            'vcol' => 'tb11',
            'id2' => 1,
        ), $tc->toArray());
        $this->assertEquals(array(
            'ta2_id' => 1,
            'tb2_id' => 1,
            'tb2_id2' => 1,
        ), $tc->pivot->toArray());

        // next
        $tc->next();
        $tc->current();
        $this->assertCount(1, $tc->pivot);
        $this->assertEquals(array(
            'id' => 1,
            'vcol' => 'tb12',
            'id2' => 2,
        ), $tc->toArray());
        $this->assertEquals(array(
            'ta2_id' => 1,
            'tb2_id' => 1,
            'tb2_id2' => 2,
        ), $tc->pivot->toArray());

        // next
        $tc->next();
        $tc->current();
        $this->assertCount(1, $tc->pivot);
        $this->assertEquals(array(
            'id' => 1,
            'vcol' => 'tb13',
            'id2' => 3,
        ), $tc->toArray());
        $this->assertEquals(array(
            'ta2_id' => 1,
            'tb2_id' => 1,
            'tb2_id2' => 3,
        ), $tc->pivot->toArray());
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Db\Pdo\MapperProvider::prepareRelation
     */
    public function testPrepareRelation($expected, $relations, $exception = null, $construct = false, $switch = null)
    {
        list($mapper) = $relations;

        if ($switch) {
            $this->mapper->switchTable($switch);
        }

        $this->mapper->findOne();

        if ($exception) {
            $this->expectException('LogicException');
            $this->expectExceptionMessage($expected);

            $this->mapper->hasOne(...$relations);

            return;
        }

        if ($construct) {
            $relations[0] = new $mapper($this->db);
        }

        $mapper = $this->mapper->hasOne(...$relations);

        $this->assertEquals($expected, $mapper->toArray());
    }
}

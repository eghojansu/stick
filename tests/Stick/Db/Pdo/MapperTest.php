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
    private $table = 'user';

    public function setup(): void
    {
        $this->fw = new Fw();
        $this->db = new Db($this->fw, new SqliteDriver(), 'sqlite::memory:', null, null, array(
            $this->read('/files/schema_sqlite.sql'),
            'insert into user (username) values ("foo"), ("bar"), ("baz")',
            'insert into friends (user_id, friend_id, level) values (1, 2, 3), (2, 3, 4)',
            'insert into nokey (name, info) values ("foo", "bar"), ("bar", "baz")',
        ));
    }

    protected function createInstance()
    {
        return new Mapper($this->db, $this->table);
    }

    public function testCurrent()
    {
        $this->assertSame($this->mapper, $this->mapper->current());
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
        $this->mapper->next();
        $this->mapper->rewind();

        $this->assertEquals(0, $this->mapper->key());
    }

    public function testValid()
    {
        $this->assertFalse($this->mapper->valid());
    }

    public function testCount()
    {
        $this->assertEquals(0, $this->mapper->count());
    }

    public function testFound()
    {
        $this->assertFalse($this->mapper->found());
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
        $this->assertEquals('profile', $this->mapper->switchTable('profile.alias')->table());
    }

    public function testHas()
    {
        $this->assertTrue($this->mapper->has('id'));
        $this->assertTrue($this->mapper->has('username'));
        $this->assertFalse($this->mapper->has('foo'));
    }

    public function testGet()
    {
        // set id
        $this->mapper->set('id', 1);
        // set adhoc
        $this->mapper->set('adhoc', function () {
            return 'foo';
        });

        // get previously set id
        $this->assertEquals(1, $this->mapper->get('id'));
        // get default value
        $this->assertEquals(1, $this->mapper->get('active'));
        // previously set adhoc
        $this->assertEquals('foo', $this->mapper->get('adhoc'));
        // call mapper method
        $this->assertTrue($this->mapper->get('dry'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Field not exists: baz.');
        $this->mapper->get('baz');
    }

    public function testSet()
    {
        // set id
        $this->mapper->set('id', 1);
        // update id
        $this->mapper->set('id', 2);
        // set adhoc
        $this->mapper->set('adhoc', function () {
            return 'foo';
        });
        $this->mapper->set('adhoc2', 'expr');

        // get previously set id
        $this->assertEquals(2, $this->mapper->get('id'));
        // get default value
        $this->assertEquals(1, $this->mapper->get('active'));
        // previously set adhoc
        $this->assertEquals('foo', $this->mapper->get('adhoc'));

        // modify adhoc
        $this->mapper->set('adhoc', function () {
            return 'bar';
        });
        $this->mapper->set('adhoc2', 'expr2');
        $this->assertEquals('bar', $this->mapper->get('adhoc'));
    }

    public function testRem()
    {
        // set active
        $this->mapper->set('active', 0);
        $this->mapper->set('adhoc', function () {
            return 'foo';
        });

        // get mapper self adhoc, then update its value, confirm value
        $dry = $this->mapper->get('dry');
        $this->mapper->set('dry', false);
        $this->assertFalse($this->mapper->get('dry'));

        // remove active, reset to initial
        $this->assertEquals(1, $this->mapper->rem('active')->get('active'));
        // remove self adhoc, which always be executed back after remove
        $this->assertTrue($this->mapper->rem('dry')->get('dry'));

        // remove adhoc, confirm
        $this->mapper->rem('adhoc');
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Field not exists: adhoc.');
        $this->mapper->get('adhoc');
    }

    public function testToArray()
    {
        // set id
        $this->mapper->set('id', 1);
        // set adhoc
        $this->mapper->set('adhoc', function () {
            return 'foo';
        });

        $this->assertEquals(array(
            'id' => 1,
            'username' => null,
            'password' => null,
            'active' => 1,
        ), $this->mapper->toArray());
        $this->assertEquals(array(
            'id' => 1,
            'username' => null,
            'password' => null,
            'active' => 1,
            'adhoc' => 'foo',
        ), $this->mapper->toArray(true));
    }

    public function testFromArray()
    {
        // set adhoc
        $this->mapper->set('adhoc', function () {
            return 'foo';
        });

        $this->assertEquals(array(
            'id' => 1,
            'username' => null,
            'password' => null,
            'active' => 1,
            'adhoc' => 'foo',
        ), $this->mapper->fromArray(array(
            'id' => 1,
            'adhoc' => 'bar',
        ))->toArray(true));
    }

    public function testKeys()
    {
        $this->mapper->findOne();
        $this->assertEquals(array('id' => 1), $this->mapper->keys());

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Mapper empty!');
        $this->mapper->reset()->keys();
    }

    public function testReset()
    {
        $this->mapper->set('id', 1);

        $this->assertCount(0, $this->mapper);
        $this->assertEquals(1, $this->mapper->get('id'));
        $this->assertCount(0, $this->mapper->reset());
        $this->assertNull($this->mapper->get('id'));
    }

    public function testFields()
    {
        $this->assertEquals('`id`, `username`, `password`, `active`', $this->mapper->fields());
    }

    public function testAdhocs()
    {
        // set adhoc
        $this->mapper->set('adhoc', 'foo');

        $this->assertEquals(', (foo) as `adhoc`', $this->mapper->adhocs());
    }

    public function testFind()
    {
        // set adhoc
        $this->mapper->set('adhoc', '1+1');

        $this->assertCount(3, $this->mapper->find());
        $this->assertCount(1, $this->mapper->find('id = 1'));
        $this->assertEquals(2, $this->mapper->get('adhoc'));
    }

    public function testFindOne()
    {
        $this->assertCount(1, $this->mapper->findOne());
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Db\Pdo\MapperProvider::findKey
     */
    public function testFindKey($expected, $values, $switch = null, $exception = null)
    {
        if ($switch) {
            $this->mapper->switchTable($switch);
        }

        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);
            $this->mapper->findKey($values);

            return;
        }

        $this->assertEquals($expected, $this->mapper->findKey($values)->toArray());
    }

    public function testRecordCount()
    {
        $this->assertEquals(3, $this->mapper->recordCount());
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Db\Pdo\MapperProvider::paginate
     */
    public function testPaginate($expected, $page, $filter = null, $options = null)
    {
        $actual = $this->mapper->paginate($page, $filter, $options);
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
        // this will make active skipped to insert
        $this->mapper->set('active', 1);

        $this->assertTrue($this->mapper->dry());
        $this->assertEquals(array(
            'id' => null,
            'username' => 'qux',
            'password' => 'qux',
            'active' => 1,
        ), $this->mapper->toArray());
        $this->assertTrue($this->mapper->save());
        $this->assertTrue($this->mapper->valid());
        $this->assertFalse($this->mapper->dry());
        $this->assertEquals(array(
            'id' => 4,
            'username' => 'qux',
            'password' => 'qux',
            'active' => 1,
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
    }

    public function testDelete()
    {
        $this->assertTrue($this->mapper->find()->delete());
        $this->assertCount(2, $this->mapper);
        $this->assertEquals(2, $this->mapper->get('id'));

        // no key
        $this->mapper->switchTable('nokey');
        $this->assertFalse($this->mapper->find()->delete());

        // add event
        $this->fw->on('mapper.delete', function () {
            return false;
        });
        $this->assertFalse($this->mapper->delete());

        // throw event
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Empty mapper!');
        $this->mapper->switchTable('user');
        $this->mapper->delete();
    }

    public function testDeleteAll()
    {
        // event doing nothing
        $this->fw->on('mapper.delete', function () {
            // do nothing
        });

        $this->assertEquals(1, $this->mapper->deleteAll('id = 1', true));
        $this->assertEquals(1, $this->mapper->deleteAll('id > 2'));
        $this->assertCount(1, $this->mapper->find());
    }

    public function testCallMagic()
    {
        $this->assertCount(1, $this->mapper->findById(1));
        $this->assertCount(1, $this->mapper->findOneById(2));
        $this->assertEquals('bar', $this->mapper->getUsername());

        $this->expectException('BadMethodCallException');
        $this->expectExceptionMessage('Call to undefined method Fal\\Stick\\Db\\Pdo\\Mapper::foo.');
        $this->mapper->foo();
    }

    public function testClone()
    {
        $mapper = clone $this->mapper;

        $this->assertNotSame($this->mapper->schema, $mapper->schema);
    }

    public function testHive()
    {
        $this->mapper->set('adhoc', '1+2');
        $this->mapper->findOne();
        // update username
        $this->mapper->set('username', 'bar');

        $expected = array(
            'id' => 1,
            'username' => 'bar',
            'password' => null,
            'active' => 1,
            'adhoc' => 3,
        );

        $this->assertEquals(array($expected), $this->mapper->hive());
    }

    public function testRows()
    {
        $this->mapper->set('adhoc', '1+2');
        $this->mapper->findOne();
        // update username
        $this->mapper->set('username', 'bar');

        $expected = array(
            'id' => 1,
            'username' => 'foo',
            'password' => null,
            'active' => 1,
            'adhoc' => 3,
        );

        $this->assertEquals(array($expected), $this->mapper->rows());
    }
}

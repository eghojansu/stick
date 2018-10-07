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

namespace Fal\Stick\Test\Library\Sql;

use Fal\Stick\App;
use Fal\Stick\Library\Sql\Connection;
use Fal\Stick\Library\Sql\Mapper;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
    private $mapper;
    private $app;

    public function setUp()
    {
        $this->prepare('user');
    }

    private function prepare($source, $mapper = false)
    {
        $this->app = new App();
        $conn = new Connection($this->app, array(
            'dsn' => 'sqlite::memory:',
            'commands' => file_get_contents(FIXTURE.'files/schema.sql'),
        ));

        if ($mapper) {
            $this->mapper = new $source($this->app, $conn);
        } else {
            $this->mapper = new Mapper($this->app, $conn, $source);
        }
    }

    private function filldb()
    {
        $this->mapper->db()->pdo()->exec('insert into user (username) values ("foo"), ("bar"), ("baz")');
    }

    public function testTable()
    {
        $this->assertEquals('user', $this->mapper->table());
    }

    public function testCreate()
    {
        $profile = $this->mapper->create('profile');

        $this->assertEquals('profile', $profile->table());
        $this->assertNotSame($this->mapper, $profile);
    }

    public function testFields()
    {
        $fields = $this->mapper->fields();

        $this->assertEquals(array('id', 'username', 'password', 'active'), $fields);
    }

    public function testSchema()
    {
        $fields = $this->mapper->schema();

        $this->assertEquals(array('id', 'username', 'password', 'active'), array_keys($fields));
    }

    public function testDb()
    {
        $this->assertInstanceOf(Connection::class, $this->mapper->db());
    }

    public function testRequired()
    {
        $this->assertTrue($this->mapper->required('id'));
        $this->assertFalse($this->mapper->required('password'));
        $this->assertFalse($this->mapper->required('foo'));
    }

    public function testChanged()
    {
        $this->assertFalse($this->mapper->changed('id'));
        $this->assertFalse($this->mapper->changed('foo'));
        $this->assertFalse($this->mapper->changed());
    }

    public function testKeys()
    {
        $this->assertEquals(array('id' => null), $this->mapper->keys());
        $this->assertEquals(array('user_id' => null, 'friend_id' => null), $this->mapper->create('friends')->keys());
        $this->assertEquals(array(), $this->mapper->create('nokey')->keys());
    }

    public function testExists()
    {
        $this->assertTrue($this->mapper->exists('id'));
        $this->assertFalse($this->mapper->exists('foo'));
    }

    public function testGet()
    {
        $this->assertNull($this->mapper->get('id'));

        // Get once call to mapper method
        $this->prepare('Fixture\\Mapper\\User', true);
        $dt = $this->mapper->get('datetime');
        $this->assertInstanceOf('DateTime', $dt);
        $this->assertSame($dt, $this->mapper->get('datetime'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Undefined field "foo".
     */
    public function testGetException()
    {
        $this->mapper->get('foo');
    }

    public function setProvider()
    {
        $dt = new \DateTime();

        return array(
            array('username', 'foo'),
            array('username', 'xfoo', 'username = "foo"'),
            array('foo', 'bar', null, array('foo' => '"bar"')),
            array('foo', $dt, null, array('foo' => $dt)),
        );
    }

    /**
     * @dataProvider setProvider
     */
    public function testSet($key, $val, $load = null, array $sets = array())
    {
        foreach ($sets as $key => $value) {
            $this->mapper->set($key, $value);
        }

        if ($load) {
            $this->filldb();
            $this->mapper->load($load);
        }

        $this->assertEquals($val, $this->mapper->set($key, $val)->get($key));
        $this->assertEquals($val, $this->mapper->set($key, $val)->get($key));
    }

    public function testClear()
    {
        $this->filldb();

        $key = 'username';
        $this->mapper->load('username = "foo"');
        $this->assertEquals(1, $this->mapper->loaded());

        $this->mapper->set($key, 'xfoo');
        $this->assertEquals('foo', $this->mapper->clear($key)->get($key));
    }

    public function testReset()
    {
        $this->filldb();
        $this->mapper->load();

        $this->assertEquals(3, $this->mapper->loaded());
        $this->assertEquals(0, $this->mapper->reset()->loaded());
    }

    public function testFromArray()
    {
        $this->assertEquals(1, $this->mapper->fromArray(array('id' => 1))->get('id'));
    }

    public function testToArray()
    {
        $this->assertEquals(array(
            'id' => null,
            'username' => null,
            'password' => null,
            'active' => 1,
        ), $this->mapper->toArray());
    }

    public function testValid()
    {
        $this->assertFalse($this->mapper->valid());

        $this->filldb();
        $this->mapper->load();
        $this->assertTrue($this->mapper->valid());
    }

    public function testDry()
    {
        $this->assertTrue($this->mapper->dry());

        $this->filldb();
        $this->mapper->load();
        $this->assertFalse($this->mapper->dry());
    }

    public function testCount()
    {
        $this->filldb();

        $this->assertEquals(3, $this->mapper->count());
        $this->assertEquals(1, $this->mapper->count('username = "foo"'));
    }

    public function testFind()
    {
        $this->filldb();

        $this->assertCount(3, $this->mapper->find());
    }

    public function testFindOne()
    {
        $this->filldb();

        $this->assertEquals('foo', $this->mapper->findOne()->getUsername());
    }

    public function testLoad()
    {
        $this->filldb();

        $this->assertEquals(1, $this->mapper->load()->get('id'));
    }

    public function testLoaded()
    {
        $this->filldb();

        $this->assertEquals(3, $this->mapper->load()->loaded());
    }

    public function testWithId()
    {
        $this->filldb();

        $this->assertEquals('foo', $this->mapper->withId(1)->get('username'));
        $this->assertEquals('bar', $this->mapper->withId(array(2))->get('username'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Insufficient primary keys value. Expect exactly 1 parameters, 0 given.
     */
    public function testWithIdException()
    {
        $this->mapper->withId('');
    }

    public function testOffsetExists()
    {
        $this->assertTrue($this->mapper->offsetExists('id'));
        $this->assertFalse($this->mapper->offsetExists('foo'));
    }

    public function testOffsetGet()
    {
        $this->assertNull($this->mapper->offsetGet('id'));
    }

    public function testOffsetSet()
    {
        $this->mapper->offsetSet('username', 'bar');
        $this->assertEquals('bar', $this->mapper->offsetGet('username'));
    }

    public function testOffsetUnset()
    {
        $this->mapper->offsetSet('username', 'bar');
        $this->mapper->offsetUnset('username');
        $this->assertNull($this->mapper->offsetGet('username'));
    }

    public function testMagicIsset()
    {
        $this->assertTrue(isset($this->mapper->id));
        $this->assertFalse(isset($this->mapper->foo));
    }

    public function testMagicGet()
    {
        $this->assertNull($this->mapper->id);
    }

    public function testMagicSet()
    {
        $this->mapper->username = 'bar';
        $this->assertEquals('bar', $this->mapper->username);
    }

    public function testMagicUnset()
    {
        $this->mapper->username = 'bar';
        unset($this->mapper->username);
        $this->assertNull($this->mapper->username);
    }

    public function testStringifyFullOptions()
    {
        $this->filldb();

        $this->mapper->set('foo', '"bar"');
        $this->mapper->load('username is not null', array(
            'limit' => 1,
            'offset' => 1,
            'group' => 'id',
            'having' => 'id is not null',
            'order' => 'id DESC',
        ));

        $this->assertEquals(2, $this->mapper->get('id'));
    }

    public function testSkip()
    {
        $this->filldb();
        $this->mapper->load();

        $this->assertEquals(1, $this->mapper->skip(0)->get('id'));
        $this->assertEquals('foo', $this->mapper->get('username'));
        $this->assertNull($this->mapper->skip(10)->get('id'));
    }

    public function testFirst()
    {
        $this->filldb();
        $this->mapper->load();

        $this->assertEquals(1, $this->mapper->first()->get('id'));
        $this->assertEquals('foo', $this->mapper->get('username'));
    }

    public function testLast()
    {
        $this->filldb();
        $this->mapper->load();

        $this->assertEquals(3, $this->mapper->last()->get('id'));
        $this->assertEquals('baz', $this->mapper->get('username'));
    }

    public function testNext()
    {
        $this->filldb();
        $this->mapper->load();

        $this->assertEquals(2, $this->mapper->next()->get('id'));
        $this->assertEquals('bar', $this->mapper->get('username'));
    }

    public function testPrev()
    {
        $this->filldb();
        $this->mapper->load();

        $this->mapper->last();
        $this->assertEquals(3, $this->mapper->get('id'));
        $this->assertEquals(2, $this->mapper->prev()->get('id'));
        $this->assertEquals('bar', $this->mapper->get('username'));
    }

    public function testCall()
    {
        $this->filldb();
        $this->mapper->load();

        $this->assertEquals(1, $this->mapper->getId());
        $this->assertEquals('bar', $this->mapper->findOneById(2)->getUsername());
        $this->assertEquals('baz', $this->mapper->loadById(3)->getUsername());

        $users = $this->mapper->findById(2);
        $this->assertEquals('bar', $users[0]->getUsername());
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Call to undefined method Fal\Stick\Library\Sql\Mapper::undef.
     */
    public function testCallException()
    {
        $this->mapper->undef();
    }

    public function testDelete()
    {
        $this->filldb();
        $this->app->one('sql_mapper_before_delete', function () {
            return true;
        });

        $this->assertEquals(0, $this->mapper->delete());

        $this->mapper->load();
        $this->assertEquals(0, $this->mapper->delete());

        // event has been removed
        $this->assertEquals(1, $this->mapper->delete());
        $this->assertEquals(1, $this->mapper->delete('id = 2'));
        $this->assertEquals(1, $this->mapper->delete('id = 3', true));
    }

    public function testUpdate()
    {
        $this->filldb();

        $this->app->one('sql_mapper_before_update', function () {
            return true;
        });

        $this->mapper->load();
        $this->mapper->set('username', 'xfoo');
        $this->mapper->update();

        $first = $this->mapper->findOne();
        $this->assertEquals('foo', $first->get('username'));

        // event has been removed
        $this->mapper->update();
        $first = $this->mapper->findOne();
        $this->assertEquals($first['username'], $this->mapper->get('username'));
    }

    public function testInsert()
    {
        $this->app->one('sql_mapper_before_insert', function () {
            return true;
        });

        $this->mapper->set('username', 'foo');
        $this->mapper->insert();

        $first = $this->mapper->findOne();
        $this->assertNull($first);

        // event has been removed
        $this->mapper->insert();
        $first = $this->mapper->findOne();
        $this->assertEquals(1, $this->mapper->get('id'));
        $this->assertEquals($first['username'], $this->mapper->get('username'));

        // no data to insert
        $this->mapper->reset();
        $this->mapper->insert();
        $this->assertNull($this->mapper->get('username'));

        // change table
        $mapper = $this->mapper->create('friends');
        $mapper->set('user_id', 1)->set('friend_id', 2);
        $mapper->insert();
        $this->assertEquals(1, $mapper->get('level'));
    }

    public function testSave()
    {
        $mapper = $this->mapper->create('friends');
        $mapper->set('user_id', 1)->set('friend_id', 2);
        $mapper->save();

        $this->assertEquals(1, $mapper->get('level'));

        $mapper->set('level', 100);
        $mapper->save();

        $this->assertEquals(100, $mapper->get('level'));
    }

    public function paginateProvider()
    {
        return array(
            array(array(
                'total' => 3,
                'count' => 3,
                'pages' => 1,
                'page' => 1,
                'start' => 1,
                'end' => 3,
            ), 1),
            array(array(
                'total' => 1,
                'count' => 1,
                'pages' => 1,
                'page' => 1,
                'start' => 1,
                'end' => 1,
            ), 1, 'id = 1'),
            array(array(
                'total' => 3,
                'count' => 1,
                'pages' => 3,
                'page' => 1,
                'start' => 1,
                'end' => 1,
            ), 1, null, array('perpage' => 1)),
            array(array(
                'total' => 3,
                'count' => 1,
                'pages' => 3,
                'page' => 2,
                'start' => 2,
                'end' => 2,
            ), 2, null, array('perpage' => 1)),
            array(array(
                'total' => 3,
                'count' => 0,
                'pages' => 3,
                'page' => 4,
                'start' => 4,
                'end' => 3,
            ), 4, null, array('perpage' => 1)),
            array(array(
                'total' => 3,
                'count' => 0,
                'pages' => 1,
                'page' => -1,
                'start' => 0,
                'end' => 0,
            ), -1),
        );
    }

    /**
     * @dataProvider paginateProvider
     */
    public function testPaginate($expected, $page, $filter = null, $options = null)
    {
        $this->filldb();

        $page = $this->mapper->paginate($page, $filter, $options);

        $this->assertEquals($expected, array_slice($page, 1));
    }
}

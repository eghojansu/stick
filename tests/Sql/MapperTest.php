<?php

/**
 * This file is part of the eghojansu/stick-test library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Sql;

use Fal\Stick\Core;
use Fal\Stick\Sql\Connection;
use Fal\Stick\Sql\Mapper;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
    private $fw;
    private $mapper;

    public function setUp()
    {
        $this->build();
    }

    public function testCreate()
    {
        $this->assertInstanceOf('Fal\\Stick\\Sql\\Mapper', $this->mapper->create('profile'));
        $this->assertInstanceOf('Fal\\Stick\\Sql\\Mapper', $this->mapper->create('Fixture\\Mapper\\TUser'));
    }

    public function testKeys()
    {
        $this->assertEquals(array('id' => null), $this->mapper->keys());
        $this->assertEquals(array('id'), $this->mapper->keys(false));
    }

    public function testTable()
    {
        $this->assertEquals('user', $this->mapper->table());
    }

    public function testMap()
    {
        $this->assertEquals('`user`', $this->mapper->map());
    }

    public function testFields()
    {
        $this->assertEquals(array('id', 'username', 'password', 'active'), $this->mapper->fields());
    }

    public function testAdhoc()
    {
        $this->assertEquals(array(), $this->mapper->adhoc());
    }

    public function testSchema()
    {
        $schema = $this->mapper->schema();

        $this->assertEquals(array('id', 'username', 'password', 'active'), array_keys($schema));
    }

    public function testDb()
    {
        $this->assertInstanceOf('Fal\\Stick\\Sql\\Connection', $this->mapper->db());
    }

    public function testExists()
    {
        $this->assertTrue($this->mapper->exists('username'));
        $this->assertFalse($this->mapper->exists('foo'));
    }

    public function testGet()
    {
        $this->build(null, null, true);

        $this->assertNull($this->mapper->get('id'));
        $this->assertNull($this->mapper->get('one_plus_one'));
        $this->assertEquals(1, $this->mapper->get('one'));
        $this->assertEquals('foo', $this->mapper->get('obj')->name);
    }

    public function testGetException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Undefined field "foo".');

        $this->mapper->get('foo');
    }

    public function testSet()
    {
        $this->build(null, null, true);

        $obj = new \StdClass();
        $obj->name = 'bar';

        $this->mapper->set('id', 1);
        $this->mapper->set('password', null);
        $this->mapper->set('adhoc', 1);
        $this->mapper->set('one_plus_one', 1);
        $this->mapper->set('obj', $obj);

        $this->assertEquals(1, $this->mapper->get('id'));
        $this->assertNull($this->mapper->get('adhoc'));
        $this->assertNull($this->mapper->get('password'));
        $this->assertEquals(1, $this->mapper->get('one_plus_one'));
        $this->assertEquals(1, $this->mapper->get('one'));
        $this->assertEquals('bar', $this->mapper->get('obj')->name);
    }

    public function testClear()
    {
        $this->mapper->set('id', 1);
        $this->mapper->clear('id');

        $this->assertNull($this->mapper->clear('id')->get('id'));
    }

    public function testReset()
    {
        $this->build(null, null, true);

        $this->mapper->set('id', $this->mapper->get('obj')->name);
        $this->mapper->reset();

        $this->assertNull($this->mapper->get('id'));
    }

    public function testOffsetExists()
    {
        $this->assertTrue(isset($this->mapper['id']));
        $this->assertFalse(isset($this->mapper['foo']));
    }

    public function testOffsetGet()
    {
        $this->assertNull($this->mapper['id']);
    }

    public function testOffsetSet()
    {
        $this->mapper['id'] = 1;

        $this->assertEquals(1, $this->mapper['id']);
    }

    public function testOffsetUnset()
    {
        $this->mapper['id'] = 1;
        unset($this->mapper['id']);

        $this->assertNull($this->mapper['id']);
    }

    public function testFromArray()
    {
        $this->mapper->fromArray(array(
            'id' => 1,
        ));

        $this->assertEquals(1, $this->mapper->get('id'));
    }

    public function testToArray()
    {
        $expected = array(
            'id' => null,
            'username' => null,
            'password' => null,
            'active' => 1,
        );

        $this->assertEquals($expected, $this->mapper->toArray());
    }

    public function testCount()
    {
        $this->assertEquals(3, $this->mapper->count());
        $this->assertEquals(0, $this->mapper->count('id = 4'));
    }

    public function testFindAll()
    {
        $this->assertCount(3, $this->mapper->findAll());
        $this->assertCount(0, $this->mapper->findAll('id = 4'));
    }

    public function testFindOne()
    {
        $found = $this->mapper->findOne('id = 4');
        $this->assertNull($found);

        $found = $this->mapper->findOne('id = 1');
        $this->assertEquals(1, $found->get('id'));
    }

    public function testLoad()
    {
        $this->mapper->load('id = 4');
        $this->assertNull($this->mapper->get('id'));

        $this->mapper->load();
        $this->assertEquals(1, $this->mapper->get('id'));
    }

    public function testFind()
    {
        $found = $this->mapper->find(4);
        $this->assertNull($found);

        $found = $this->mapper->find(1);
        $this->assertEquals(1, $found->get('id'));
    }

    public function testFindException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Insufficient primary keys value. Expected exactly 1 parameters, 0 given.');

        $this->mapper->find();
    }

    /**
     * @dataProvider paginateProvider
     */
    public function testPaginate($expected, $page, $filter = null, $options = null)
    {
        $page = $this->mapper->paginate($page, $filter, $options);

        $this->assertEquals($expected, array_slice($page, 1));
    }

    public function testValid()
    {
        $this->assertFalse($this->mapper->valid());
        $this->assertTrue($this->mapper->find(1)->valid());
    }

    public function testDry()
    {
        $this->assertTrue($this->mapper->dry());
        $this->assertFalse($this->mapper->find(1)->dry());
    }

    public function testLoaded()
    {
        $this->assertEquals(0, $this->mapper->loaded());
        $this->assertEquals(3, $this->mapper->load()->loaded());
    }

    public function testRows()
    {
        $this->assertEquals(array(), $this->mapper->rows());
        $this->assertCount(3, $this->mapper->load()->rows());
    }

    public function testSkip()
    {
        $this->mapper->load();

        $this->assertEquals(1, $this->mapper->skip(0)->get('id'));
        $this->assertEquals(2, $this->mapper->skip(1)->get('id'));
        $this->assertNull($this->mapper->skip(4)->get('id'));
    }

    public function testFirst()
    {
        $this->mapper->load();

        $this->assertEquals(1, $this->mapper->first()->get('id'));
    }

    public function testLast()
    {
        $this->mapper->load();

        $this->assertEquals(3, $this->mapper->last()->get('id'));
    }

    public function testNext()
    {
        $this->mapper->load();

        $this->assertEquals(2, $this->mapper->next()->get('id'));
    }

    public function testPrev()
    {
        $this->mapper->load();

        $this->assertNull($this->mapper->prev()->get('id'));
    }

    public function testRequired()
    {
        $this->assertTrue($this->mapper->required('id'));
        $this->assertTrue($this->mapper->required('username'));
        $this->assertFalse($this->mapper->required('password'));
        $this->assertTrue($this->mapper->required('active'));
    }

    public function testChanged()
    {
        $this->assertFalse($this->mapper->changed());
        $this->assertFalse($this->mapper->changed('id'));

        $this->mapper->set('id', 'foo');
        $this->assertTrue($this->mapper->changed('id'));
        $this->assertTrue($this->mapper->changed());
    }

    /**
     * @dataProvider deleteProvider
     */
    public function testDelete($id, $filter, $hayati, $expected)
    {
        if ($id) {
            $this->mapper = $this->mapper->load(array('id' => $id));
        }

        $this->assertEquals($expected, $this->mapper->delete($filter, $hayati));
    }

    public function testDeleteInterception()
    {
        $this->fw->on('mapper_delete', function () {
            return true;
        });

        $this->assertEquals(0, $this->mapper->load(array('id' => 1))->delete());
    }

    /**
     * @dataProvider insertProvider
     */
    public function testInsert($data, $valid, $expected, $key = 'id', $table = 'user')
    {
        $this->build($table);
        $this->mapper->fromArray($data);
        $this->mapper->insert();

        $this->assertEquals($valid, $this->mapper->valid());
        $this->assertEquals($expected, $this->mapper->get($key));
    }

    public function testInsertInterception()
    {
        $this->fw->on('mapper_insert', function () {
            return true;
        });
        $this->mapper->fromArray(array('username' => 'foo'))->insert();

        $this->assertFalse($this->mapper->valid());
        $this->assertEquals(0, $this->mapper->get('id'));
    }

    /**
     * @dataProvider updateProvider
     */
    public function testUpdate($id, $data, $expected, $key)
    {
        if ($id) {
            $this->mapper->load(array('id' => $id));
        }

        $this->mapper->fromArray($data)->update();

        $this->assertEquals($expected, $this->mapper->get($key));
    }

    public function testUpdateInterception()
    {
        $this->fw->on('mapper_update', function () {
            return true;
        });
        $this->mapper->load(array('id' => 1));
        $this->mapper->fromArray(array('username' => 'qux'))->update();
        $this->mapper->load(array('id' => 1));

        $this->assertEquals('foo', $this->mapper->get('username'));
    }

    public function testSave()
    {
        $this->mapper
            ->fromArray(array('username' => 'qux'))
            ->save()
            ->fromArray(array('username' => 'qux - bar'))
            ->save()
        ;

        $this->assertEquals('qux - bar', $this->mapper->get('username'));
    }

    public function testHasMany()
    {
        $this->fillprofile();

        $this->mapper->load(array('id' => 1));
        $relation = $this->mapper->hasMany('profile');

        $this->assertEquals('profile', $relation->table());
        $this->assertEquals(2, $relation->loaded());

        $this->mapper->load(array('id' => 2));
        $relation = $this->mapper->hasMany('Fixture\\Mapper\\TProfile', 'user_id=id');

        $this->assertEquals('profile', $relation->table());
        $this->assertEquals(1, $relation->loaded());
    }

    public function testHasOne()
    {
        $this->fillprofile();

        $this->mapper->load(array('id' => 1));
        $relation = $this->mapper->hasOne('profile');

        $this->assertEquals('profile', $relation->table());
        $this->assertEquals(1, $relation->loaded());
    }

    public function testBelongsTo()
    {
        $this->build('profile');
        $this->fillprofile();

        $this->mapper->load(array('id' => 1));
        $relation = $this->mapper->belongsTo('user');

        $this->assertEquals('user', $relation->table());
        $this->assertEquals(1, $relation->loaded());

        $this->mapper->load(array('id' => 2));
        $relation2 = $this->mapper->belongsTo('Fixture\\Mapper\\TUser', 'user_id=id');

        $this->assertEquals($relation->table(), $relation2->table());
        $this->assertEquals($relation->loaded(), $relation2->loaded());
    }

    public function testBelongsToMany()
    {
        $this->build('ta');
        $this->fillta();

        $this->mapper->load(array('id' => 1));
        $relation = $this->mapper->belongsToMany('tb', 'tc');

        $this->assertEquals('tb', $relation->table());
        $this->assertEquals(2, $relation->loaded());

        $this->mapper->load(array('id' => 2));
        $relation = $this->mapper->belongsToMany('Fixture\\Mapper\\TTb', 'tc', 'tb_id=id', 'ta_id=id');

        $this->assertEquals('tb', $relation->table());
        $this->assertEquals(1, $relation->loaded());
        $this->assertEquals(2, $relation['id']);
        $this->assertEquals(3, $relation['inc_id']);
    }

    public function testCall()
    {
        $this->assertEquals(1, $this->mapper->load()->getId());
        $this->assertEquals('bar', $this->mapper->findOneById(2)->getUsername());
        $this->assertEquals('baz', $this->mapper->loadById(3)->getUsername());

        $users = $this->mapper->findById(2);
        $this->assertEquals('bar', $users[0]->getUsername());
    }

    public function testCallException()
    {
        $this->expectException('BadMethodCallException');
        $this->expectExceptionMessage('Call to undefined method Fal\Stick\Sql\Mapper::undef.');

        $this->mapper->undef();
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

    public function deleteProvider()
    {
        return array(
            array(null, null, false, 0),
            array(1, null, false, 1),
            array(null, 'id is not null', false, 3),
            array(null, 'id is not null', true, 3),
            array(null, 'id = 1', false, 1),
        );
    }

    public function insertProvider()
    {
        return array(
            array(array(), false, 0),
            array(array('username' => 'qux'), true, 4),
            array(array('name' => 'foo'), true, 'foo', 'name', 'nokey'),
        );
    }

    public function updateProvider()
    {
        return array(
            array(null, array(), null, 'username'),
            array(1, array('username' => 'qux'), 'qux', 'username'),
        );
    }

    private function build(string $table = null, string $mapper = null, bool $anon = false)
    {
        $fw = new Core('phpunit-test');
        $db = new Connection($fw, 'sqlite::memory:', null, null, array(
            file_get_contents(TEST_FIXTURE.'files/schema.sql'),
            'insert into user (username) values ("foo"), ("bar"), ("baz")',
        ));

        if ($anon) {
            $this->mapper = new class($fw, $db, $table ?? 'user') extends Mapper {
                protected $props = array('one' => array('value' => 1, 'self' => false));
                protected $adhoc = array('one_plus_one' => array('value' => null, 'expr' => '(1 + 1)'));

                public function obj()
                {
                    $obj = new \StdClass();
                    $obj->name = 'foo';

                    return $obj;
                }
            };
        } elseif ($mapper) {
            $this->mapper = new $mapper($fw, $db);
        } else {
            $this->mapper = new Mapper($fw, $db, $table ?? 'user');
        }

        $fw->rule(Connection::class, $db);

        $this->fw = $fw;
    }

    private function fillprofile()
    {
        $sql = 'insert into profile (fullname, user_id) values ("foo name", 1), ("foo second name", 1), ("bar name", 2)';

        $this->mapper->db()->getPdo()->exec($sql);
    }

    private function fillta()
    {
        $sql = 'insert into ta (id, vcol) values (1, "aa"), (2, "ab");'.
                'insert into tb (id, vcol) values (1, "ba"), (2, "bb");'.
                'insert into tc (ta_id, tb_id) values (1, 1), (1, 2), (2, 2);';

        $this->mapper->db()->getPdo()->exec($sql);
    }
}

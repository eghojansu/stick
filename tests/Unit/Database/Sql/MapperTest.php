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
use Fal\Stick\Database\MapperInterface;
use Fal\Stick\Database\Sql\Mapper;
use Fal\Stick\Database\Sql\Relation;
use Fal\Stick\Database\Sql\Sql;
use Fal\Stick\Test\fixture\classes\UserMapper;
use PHPUnit\Framework\TestCase;

class MapperTest extends TestCase
{
    private $expected = [
        'r1' => ['id'=>'1','first_name'=>'foo','last_name'=>null,'active'=>'1'],
        'r2' => ['id'=>'2','first_name'=>'bar','last_name'=>null,'active'=>'1'],
        'r3' => ['id'=>'3','first_name'=>'baz','last_name'=>null,'active'=>'1'],
        'schema' => [
            'id' => [
                'type' => 'INTEGER',
                'pdo_type' => \PDO::PARAM_INT,
                'default' => null,
                'nullable' => false,
                'pkey' => true,
                'value' => null,
                'initial' => null,
                'changed' => false,
            ],
            'first_name' => [
                'type' => 'TEXT',
                'pdo_type' => \PDO::PARAM_STR,
                'default' => null,
                'nullable' => false,
                'pkey' => false,
                'value' => null,
                'initial' => null,
                'changed' => false,
            ],
            'last_name' => [
                'type' => 'TEXT',
                'pdo_type' => \PDO::PARAM_STR,
                'default' => 'NULL',
                'nullable' => true,
                'pkey' => false,
                'value' => null,
                'initial' => null,
                'changed' => false,
            ],
            'active' => [
                'type' => 'INTEGER',
                'pdo_type' => \PDO::PARAM_INT,
                'default' => '1',
                'nullable' => false,
                'pkey' => false,
                'value' => null,
                'initial' => null,
                'changed' => false,
            ],
        ],
    ];
    private $mapper;

    public function setUp()
    {
        $this->build();
    }

    public function tearDown()
    {
        error_clear_last();
    }

    private function build(string $dsn = null, string $option = null, string $mapper = null)
    {
        $cache = new Cache($dsn ?? '', 'test', TEMP . 'cache/');
        $cache->reset();

        $database = new Sql($cache, ($option ?? []) + [
            'driver' => 'sqlite',
            'location' => ':memory:',
            'commands' => [
                <<<SQL1
CREATE TABLE `user_mapper` (
    `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    `first_name` TEXT NOT NULL,
    `last_name` TEXT NULL DEFAULT NULL,
    `active` INTEGER NOT NULL DEFAULT 1
);
insert into `user_mapper` (first_name) values ("foo"), ("bar"), ("baz");
CREATE TABLE `nokey` (
    `id` INTEGER NOT NULL,
    `name` TEXT NOT NULL
);
insert into `nokey` (id,name) values(1,"foo"), (2,"bar")
SQL1
,
            ],
        ]);

        $use = $mapper ?? Mapper::class;

        $this->mapper = new $use($database, $mapper ? null : 'user_mapper');
    }

    public function testWithTable()
    {
        $this->assertEquals($this->mapper, $this->mapper->withTable('user_mapper'));
    }

    public function testGetDb()
    {
        $this->assertInstanceOf(Sql::class, $this->mapper->getDb());
    }

    public function testGetDbType()
    {
        $this->assertEquals('SQL', $this->mapper->getDbType());
    }

    public function testGetFields()
    {
        $this->assertEquals(array_keys($this->expected['r1']), $this->mapper->getFields());
    }

    public function testGetSchema()
    {
        $this->assertEquals($this->expected['schema'], $this->mapper->getSchema());
    }

    public function testSetSchema()
    {
        $schema = $this->expected['schema'];
        $schema['first_name']['pdo_type'] = \PDO::PARAM_INT;

        $this->assertEquals($schema, $this->mapper->setSchema($schema)->getSchema());
    }

    public function testSetTable()
    {
        $this->assertEquals($this->mapper, $this->mapper->setTable('user_mapper'));
        $this->assertEquals($this->mapper, $this->mapper->setTable(null));
    }

    public function testCount()
    {
        $this->assertEquals(3, $this->mapper->count());

        // with adhoc
        $this->mapper->set('foo', 'id + 1');
        $this->assertEquals(3, $this->mapper->count());
    }

    public function testLoad()
    {
        $this->mapper->load('id=1');
        $this->assertEquals($this->expected['r1']['first_name'], $this->mapper->get('first_name'));
    }

    public function testLoadId()
    {
        $this->mapper->loadId(1);
        $this->assertEquals($this->expected['r1']['first_name'], $this->mapper->get('first_name'));
    }

    public function testFindId()
    {
        $mapper = $this->mapper->findId(1);
        $this->assertEquals($this->expected['r1']['first_name'], $mapper->get('first_name'));
    }

    /**
     * @expectedException ArgumentCountError
     * @expectedExceptionRegex /expect exactly 1 arguments, 0 given$/
     */
    public function testFindIdException()
    {
        $this->mapper->findId(null);
    }

    public function testFindOne()
    {
        $mapper = $this->mapper->findOne();
        $this->assertEquals($this->expected['r1']['first_name'], $mapper->get('first_name'));
    }

    public function testFind()
    {
        $res = $this->mapper->find();
        $this->assertEquals(3, count($res));
        $this->assertEquals($this->expected['r1']['first_name'], $res[0]->get('first_name'));

        // with adhoc
        $this->mapper->set('foo', 'id + 1');
        $res = $this->mapper->find();
        $this->assertEquals(3, count($res));
        $this->assertEquals(2, $res[0]->get('foo'));
    }

    public function testFindOption()
    {
        // with option set
        $option = [
            'limit' => 3,
            'offset' => 1,
            'group' => 'id',
            'having' => 'id > 0',
            'order' => 'id',
        ];
        $res = $this->mapper->find('id > 0', $option);
        $this->assertEquals(2, count($res));
        $this->assertEquals(2, $res[0]->get('id'));
    }

    public function testInsert()
    {
        // insert nothing
        $this->mapper->insert();
        $this->assertEquals(3, $this->mapper->count());

        $this->mapper->set('first_name', 'quux');
        $this->assertEquals('quux', $this->mapper->insert()->get('first_name'));
        $this->assertEquals(4, $this->mapper->count());
    }

    public function testInsertBeforeEvent()
    {
        $this->mapper->beforeinsert(function($mapper) {
            // canceled
            $mapper->set('first_name', 'not ' . $mapper->get('first_name'));
        });
        $this->mapper->set('first_name', 'quux');
        $this->assertEquals('not quux', $this->mapper->insert()->get('first_name'));
        $this->assertEquals(3, $this->mapper->count());

        $this->mapper->beforeinsert(function($mapper) {
            // continue
            return true;
        }, true);
        $this->assertEquals('not quux', $this->mapper->insert()->get('first_name'));
        $this->assertEquals(4, $this->mapper->count());
    }

    public function testInsertAfterEvent()
    {
        $this->mapper->oninsert(function($mapper) {
            // canceled, but already inserted to db
            $mapper->set('first_name', 'not ' . $mapper->get('first_name'));
        });
        $this->mapper->set('first_name', 'quux');
        $this->assertEquals('not quux', $this->mapper->insert()->get('first_name'));
        $this->assertEquals(4, $this->mapper->count());
    }

    public function testInsertNoReload()
    {
        $this->mapper->setTable('nokey');
        $this->mapper->set('id', 3);
        $this->mapper->set('name', 'nokey');
        $this->mapper->insert();
        $this->assertEquals(3, $this->mapper->get('id'));
        $this->assertEquals('nokey', $this->mapper->get('name'));
    }

    public function testUpdate()
    {
        $this->mapper->loadId(1);
        $this->mapper->set('first_name', 'updated ' . $this->mapper->get('first_name'));
        $this->mapper->update();
        $newload = $this->mapper->findId(1);

        $this->assertEquals('updated foo', $this->mapper->get('first_name'));
        $this->assertEquals($newload, $this->mapper);
    }

    public function testUpdateBeforeEvent()
    {
        $this->mapper->loadId(1);
        $this->mapper->beforeupdate(function($mapper) {
            // canceled
            $mapper->set('first_name', 'not ' . $mapper->get('first_name'));
        });
        $this->mapper->set('first_name', 'quux');
        $this->assertEquals('not quux', $this->mapper->update()->get('first_name'));

        // check in db
        $updated = $this->mapper->findId(1);
        $this->assertEquals('foo', $updated->get('first_name'));

        // new test
        $this->mapper->loadId(2);
        $this->mapper->beforeupdate(function($mapper) {
            // continue
            return true;
        }, true);
        $this->mapper->set('first_name', 'quux');
        $this->assertEquals('quux', $this->mapper->update()->get('first_name'));

        // check in db
        $updated = $this->mapper->findId(2);
        $this->assertEquals('quux', $updated->get('first_name'));
    }

    public function testUpdateAfterEvent()
    {
        $this->mapper->loadId(1);
        $this->mapper->onupdate(function($mapper) {
            // canceled, but already updated to db
            $mapper->set('first_name', 'not ' . $mapper->get('first_name'));
        });
        $this->mapper->set('first_name', 'quux');
        $this->assertEquals('not quux', $this->mapper->update()->get('first_name'));

        // check in db
        $updated = $this->mapper->findId(1);
        $this->assertEquals('quux', $updated->get('first_name'));
    }

    public function testDelete()
    {
        $this->mapper->loadId(1);
        $this->assertEquals(1, $this->mapper->delete());

        // delete with adhoc
        $this->mapper->set('foo','id + 1');
        $this->mapper->loadId(2);
        $this->assertEquals(1, $this->mapper->delete());
    }

    public function testDeleteFilter()
    {
        $this->assertEquals(2, $this->mapper->delete('id < 3'));
    }

    public function testDeleteFilterNotQuick()
    {
        $this->assertEquals(2, $this->mapper->delete('id < 3', false));
    }

    public function testDeleteBeforeEvent()
    {
        $this->mapper->loadId(1);
        $this->mapper->beforedelete(function($mapper) {
            // canceled
        });
        $this->assertEquals(0, $this->mapper->delete());

        // check in db
        $deleted = $this->mapper->findId(1);
        $this->assertEquals('foo', $deleted->get('first_name'));

        // new test
        $this->mapper->loadId(2);
        $this->mapper->beforedelete(function($mapper) {
            // continue
            return true;
        });
        $this->assertEquals(1, $this->mapper->delete());

        // check in db
        $deleted = $this->mapper->findId(2);
        $this->assertNull($deleted);
    }

    public function testDeleteAfterEvent()
    {
        $this->mapper->loadId(1);
        $this->mapper->ondelete(function($mapper) {
            // canceled, but already deleted in db
        });
        $this->assertEquals(1, $this->mapper->delete());

        // check in db
        $deleted = $this->mapper->findId(1);
        $this->assertNull($deleted);
    }

    public function testReset()
    {
        $this->mapper->loadId(1);
        $this->assertEquals(1, $this->mapper->get('id'));
        $this->mapper->reset();
        $this->assertNull($this->mapper->get('id'));
    }

    public function testToArray()
    {
        $this->mapper->loadId(1);
        $this->assertEquals($this->expected['r1'], $this->mapper->toArray());
    }

    public function testFromArray()
    {
        $this->mapper->fromArray(['first_name'=>'bleh']);
        $this->assertEquals('bleh', $this->mapper->get('first_name'));
    }

    public function testRequired()
    {
        $this->assertTrue($this->mapper->required('first_name'));
        $this->assertFalse($this->mapper->required('last_name'));
    }

    public function testChanged()
    {
        $this->assertFalse($this->mapper->changed());
        $this->assertFalse($this->mapper->changed('first_name'));
        $this->mapper->set('first_name', 'change');
        $this->assertTrue($this->mapper->changed('first_name'));
    }

    public function testExists()
    {
        $this->assertTrue($this->mapper->exists('id'));
        $this->assertFalse($this->mapper->exists('foo'));
    }

    public function testGet()
    {
        $this->assertNull($this->mapper->get('id'));
        $this->mapper->set('id',1);
        $this->assertEquals(1, $this->mapper->get('id'));

        // adhoc
        $this->mapper->set('foo', 'expr');
        $this->assertNull($this->mapper->get('foo'));

        // prop
        $obj = new \StdClass;
        $obj->name = 'foo';
        $this->mapper->set('obj', $obj);
        $this->assertEquals($obj, $this->mapper->get('obj'));
        $this->assertEquals('foo', $this->mapper->get('obj')->name);
    }

    /**
     * @expectedException LogicException
     * @expectedExceptionMessage Undefined field foo
     */
    public function testGetException()
    {
        $this->mapper->get('foo');
    }

    public function testSet()
    {
        // set same as initial
        $this->mapper->set('last_name', null);
        $this->assertNull($this->mapper->get('last_name'));

        // set same as default value
        $this->mapper->set('last_name', 'NULL');
        $this->assertEquals('NULL', $this->mapper->get('last_name'));

        // set adhoc
        $this->mapper->set('foo', 'bar');
        $this->assertNull($this->mapper->get('foo'));
        // set adhoc value
        $this->mapper->set('foo', 'baz');
        $this->assertEquals('baz', $this->mapper->get('foo'));

        // set prop
        $obj = new \StdClass;
        $this->mapper->set('bar', $obj);
        $this->assertEquals($obj, $this->mapper->get('bar'));
    }

    public function testClear()
    {
        $this->mapper->set('id', 1);
        $this->assertEquals(1, $this->mapper->get('id'));
        $this->mapper->clear('id');
        $this->assertNull($this->mapper->get('id'));

        // clear adhoc
        $this->mapper->set('foo', 'bar');
        $this->assertTrue($this->mapper->exists('foo'));
        $this->mapper->clear('foo');
        $this->assertFalse($this->mapper->exists('foo'));

        $obj = new \StdClass;
        $this->mapper->set('bar', $obj);
        $this->assertEquals($obj, $this->mapper->get('bar'));
        $this->mapper->clear('bar');
    }

    public function testCustomMapper()
    {
        $this->build(null, null, UserMapper::class);
        $this->assertEquals(3, $this->mapper->count());
        $this->assertEquals(1, $this->mapper->get('ctr'));
        $this->assertEquals(1, $this->mapper->get('ctr'));

        // reset
        $this->mapper->reset();
        $this->assertEquals(2, $this->mapper->get('ctr'));
    }

    public function testCreateRelation()
    {
        $this->assertInstanceOf(Relation::class, $this->mapper->createRelation());
    }
}

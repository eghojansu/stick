<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Feb 06, 2019 10:35
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Database;

use Fal\Stick\TestSuite\TestCase;

class MapperTest extends TestCase
{
    private $mapper;

    public function setup()
    {
        $this->mapper = $this->mapper('user');
    }

    public function testCurrent()
    {
        $this->assertTrue(true, 'Not to be used by hand.');
    }

    public function testKey()
    {
        $this->assertTrue(true, 'Not to be used by hand.');
    }

    public function testNext()
    {
        $this->assertTrue(true, 'Not to be used by hand.');
    }

    public function testRewind()
    {
        $this->assertTrue(true, 'Not to be used by hand.');
    }

    public function testValid()
    {
        $this->assertTrue(true, 'Not to be used by hand.');
    }

    public function testCount()
    {
        $this->assertEquals(0, $this->mapper->count());
    }

    public function testGetName()
    {
        $this->assertEquals('user', $this->mapper->getName());
    }

    public function testSetName()
    {
        $this->assertEquals('nokey', $this->mapper->setName('nokey')->getName());
    }

    public function testGetSchema()
    {
        $this->assertInstanceOf('Fal\\Stick\\Database\\Row', $this->mapper->getSchema());
    }

    public function testExists()
    {
        $this->assertTrue($this->mapper->exists('username'));
        $this->assertFalse($this->mapper->exists('foo'));
    }

    public function testGet()
    {
        $this->assertNull($this->mapper->get('username'));

        // call mapper method
        $this->assertFalse($this->mapper->get('valid'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Field not exists: foo.');

        $this->mapper->get('foo');
    }

    public function testSet()
    {
        $this->assertEquals('bar', $this->mapper->set('username', 'bar')->get('username'));
    }

    public function testClear()
    {
        $this->assertNull($this->mapper->set('username', 'bar')->clear('username')->get('username'));
    }

    public function testRemove()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Field not exists: username.');

        $this->mapper->remove('username')->get('username');
    }

    public function testFromArray()
    {
        $this->assertEquals('baz', $this->mapper->fromArray(array('username' => 'baz'))->get('username'));
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

    public function testReset()
    {
        $this->mapper->set('username', 'bar');

        $this->assertNull($this->mapper->reset()->get('username'));
    }

    public function testLoad()
    {
        $this->buildSchema()->initUser();

        $this->assertCount(3, $this->mapper->load());

        $expected = array(
            array(
                'id' => 1,
                'username' => 'foo',
                'password' => '',
                'active' => 1,
            ),
            array(
                'id' => 2,
                'username' => 'bar',
                'password' => '',
                'active' => 1,
            ),
            array(
                'id' => 3,
                'username' => 'baz',
                'password' => '',
                'active' => 1,
            ),
        );
        $actual = array();

        foreach ($this->mapper as $key => $mapper) {
            $actual[] = $mapper->toArray();
        }

        $this->assertEquals($expected, $actual);
    }

    public function testFirst()
    {
        $this->buildSchema()->initUser();

        $this->assertCount(1, $this->mapper->first());
        $this->assertCount(0, $this->mapper->first(array('id' => 4)));
    }

    public function testCountRows()
    {
        $this->buildSchema()->initUser();

        $this->assertEquals(3, $this->mapper->countRows());
    }

    /**
     * @dataProvider paginateProvider
     */
    public function testPaginate($expected, $page, $clause = null, $options = null)
    {
        $this->buildSchema()->initUser();

        $actual = $this->mapper->paginate($page, $clause, $options);
        $actualExpected = array_intersect_key($actual, $expected);
        $actualExpected['mapper'] = count($actual['mapper']);

        $this->assertEquals($expected, $actualExpected);
    }

    public function testDelete()
    {
        // just once
        $this->container->get('eventDispatcher')->one('mapper.beforedelete', function ($event) {
            $event->stopPropagation();
        });

        $this->buildSchema()->initUser();

        $this->mapper->load();

        $this->assertCount(3, $this->mapper);

        // not deleted
        $this->assertEquals(0, $this->mapper->delete());
        $this->assertCount(3, $this->mapper);
        $this->assertEquals(3, $this->mapper->countRows());

        // normally deleted
        $this->assertEquals(1, $this->mapper->delete());
        $this->assertCount(2, $this->mapper);
        $this->assertEquals(2, $this->mapper->countRows());

        // moved to next row
        $this->assertEquals('bar', $this->mapper->get('username'));

        // delete batch
        $this->assertEquals(2, $this->mapper->delete(array('id > 1')));
        $this->assertCount(0, $this->mapper);
        $this->assertEquals(0, $this->mapper->countRows());

        // throws exception
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Unable to delete unloaded mapper.');
        $this->mapper->delete();
    }

    public function testSave()
    {
        $this->buildSchema();

        // not inserted, mapper is empty
        $this->assertFalse($this->mapper->save());

        // just once
        $this->container->get('eventDispatcher')->one('mapper.beforesave', function ($event) {
            $event->stopPropagation();
        });

        $this->mapper->set('username', 'foo');

        // not inserted, prevented by event listener
        $this->assertFalse($this->mapper->save());
        $this->assertEquals(0, $this->mapper->countRows());

        // normally inserted
        $this->assertEquals(true, $this->mapper->save());
        $this->assertEquals(1, $this->mapper->countRows());
        $this->assertCount(1, $this->mapper);
        $this->assertEquals('foo', $this->mapper->get('username'));

        // update
        $this->mapper->set('username', 'bar');
        $this->assertEquals(true, $this->mapper->save());
        $this->assertEquals(1, $this->mapper->countRows());
        $this->assertCount(1, $this->mapper);
        $this->assertEquals('bar', $this->mapper->get('username'));
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
        $this->mapper['foo'] = 'bar';

        $this->assertEquals('bar', $this->mapper['foo']);
    }

    public function testOffsetUnset()
    {
        $this->mapper['username'] = 'bar';
        unset($this->mapper['username']);

        $this->assertNull($this->mapper['username']);
    }

    public function testFind()
    {
        $this->buildSchema()->initUser();

        $this->assertNull($this->mapper->get('username'));

        $this->assertCount(1, $this->mapper->find(2));
        $this->assertEquals('bar', $this->mapper->get('username'));

        $this->assertCount(0, $this->mapper->find(4));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Insufficient keys value. Expected exactly 1 parameters, 0 given.');
        $this->mapper->find();
    }

    public function testFindException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Mapper has no key.');

        $this->mapper('nokey')->find();
    }

    public function testClone()
    {
        $this->buildSchema()->initUser();

        $clone = clone $this->mapper;

        $this->assertNotSame($clone->getSchema(), $this->mapper->getSchema());

        $this->mapper->load();
        $this->assertCount(3, $this->mapper);

        $clone2 = clone $this->mapper;
        $this->assertCount(3, $clone2);
    }

    public function paginateProvider()
    {
        return array(
            array(array(
                'mapper' => 3,
                'total' => 3,
                'count' => 3,
                'pages' => 1,
                'page' => 1,
                'start' => 1,
                'end' => 3,
            ), 1),
            array(array(
                'mapper' => 1,
                'total' => 1,
                'count' => 1,
                'pages' => 1,
                'page' => 1,
                'start' => 1,
                'end' => 1,
            ), 1, array('id' => 1)),
            array(array(
                'mapper' => 1,
                'total' => 3,
                'count' => 1,
                'pages' => 3,
                'page' => 1,
                'start' => 1,
                'end' => 1,
            ), 1, null, array('perpage' => 1)),
            array(array(
                'mapper' => 1,
                'total' => 3,
                'count' => 1,
                'pages' => 3,
                'page' => 2,
                'start' => 2,
                'end' => 2,
            ), 2, null, array('perpage' => 1)),
        );
    }
}

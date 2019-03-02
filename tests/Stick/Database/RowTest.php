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

namespace Fal\Stick\Test\Database;

use Fal\Stick\Database\Adhoc;
use Fal\Stick\Database\Field;
use Fal\Stick\Database\Row;
use PHPUnit\Framework\TestCase;

class RowTest extends TestCase
{
    private $row;

    public function setup()
    {
        $this->row = new Row('foo');
    }

    public function testOffsetExists()
    {
        $this->assertFalse(isset($this->row['foo']));
    }

    public function testOffsetGet()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Field not exists: foo.');

        $this->row['foo'];
    }

    public function testOffsetSet()
    {
        $this->row['foo'] = 'bar';

        $this->assertEquals('bar', $this->row['foo']);
    }

    public function testOffsetUnset()
    {
        $this->row['foo'] = 'bar';
        $this->row['foo'] = 'baz';
        unset($this->row['foo']);

        $this->assertEquals('bar', $this->row['foo']);
    }

    public function testGetIterator()
    {
        $this->assertInstanceOf('ArrayIterator', $this->row->getIterator());
    }

    public function testTable()
    {
        $this->assertEquals('foo', $this->row->table());
    }

    public function testGetField()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Field not exists: foo.');

        $this->row->getField('foo');
    }

    public function testSetField()
    {
        $field = new Field('foo', 'bar');
        $adhoc = new Adhoc('bar', 'baz');

        $this->assertSame($field, $this->row->setField($field)->getField('foo'));
        $this->assertSame($adhoc, $this->row->setField($adhoc)->getField('bar'));
    }

    public function testExists()
    {
        $this->assertFalse($this->row->exists('foo'));
    }

    public function testGet()
    {
        $this->assertEquals('bar', $this->row->setField(new Field('foo', 'bar'))->get('foo'));
    }

    public function testSet()
    {
        $this->row->setField(new Field('foo', 'bar'));
        $this->row->setField(new Adhoc('bar', 'baz'));

        $this->assertEquals('baz', $this->row->set('foo', 'baz')->get('foo'));
        $this->assertEquals('qux', $this->row->set('bar', 'qux')->get('bar'));
        $this->assertEquals('quux', $this->row->set('baz', 'quux')->get('baz'));
    }

    public function testClear()
    {
        $this->row->setField(new Field('foo', 'bar'));
        $this->row->setField(new Adhoc('bar', 'baz'));

        $this->row->set('foo', 'baz')->clear('foo');
        $this->row->set('bar', 'qux')->clear('bar');

        $this->assertEquals('bar', $this->row->get('foo'));
        $this->assertEquals('baz', $this->row->get('bar'));
    }

    public function testRemove()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Field not exists: foo.');

        $this->row->set('foo', 'bar')->remove('foo')->get('foo');
    }

    public function testGetFields()
    {
        $this->assertEquals(array(), $this->row->getFields());
    }

    public function testGetAdhocs()
    {
        $this->assertEquals(array(), $this->row->getAdhocs());
    }

    public function testIsChanged()
    {
        $this->assertFalse($this->row->isChanged());

        $this->row->setField(new Field('foo', 'bar'));

        $this->row->set('foo', 'bar');
        $this->assertFalse($this->row->isChanged());

        $this->row->set('foo', 'baz');
        $this->assertTrue($this->row->isChanged());
    }

    public function testGetKeys()
    {
        $field = new Field('foo', 'bar');
        $field->pkey = true;

        $this->row->setField($field);

        $this->assertEquals(array('foo'), $this->row->getKeys());
    }

    public function testFromArray()
    {
        $this->assertEquals('baz', $this->row->set('foo', 'bar')->fromArray(array('foo' => 'baz'))->get('foo'));
    }

    public function testToArray()
    {
        $this->assertEquals(array('foo' => 'bar'), $this->row->set('foo', 'bar')->toArray());
    }

    public function testCommit()
    {
        $this->row->setField(new Field('foo', 'bar'));
        $this->row->set('foo', 'baz');

        $this->assertTrue($this->row->isChanged());
        $this->assertFalse($this->row->commit()->isChanged());
    }

    public function testReset()
    {
        $this->row->setField(new Field('foo', 'bar'));
        $this->row->set('foo', 'baz');

        $this->assertTrue($this->row->isChanged());
        $this->assertFalse($this->row->reset()->isChanged());
    }

    public function testClone()
    {
        $this->row->setField(new Field('foo', 'bar'));
        $this->row->setField(new Adhoc('bar', 'baz'));

        $clone = clone $this->row;

        $this->assertNotSame($clone->getField('foo'), $this->row->getField('foo'));
        $this->assertNotSame($clone->getField('bar'), $this->row->getField('bar'));
    }
}

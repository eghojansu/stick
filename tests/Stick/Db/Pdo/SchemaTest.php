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

use Fal\Stick\TestSuite\MyTestCase;

class SchemaTest extends MyTestCase
{
    public function testOffsetExists()
    {
        $this->assertFalse(isset($this->schema['foo']));
    }

    public function testOffsetGet()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Field not exists: foo.');

        $this->schema['foo'];
    }

    public function testOffsetSet()
    {
        $this->schema['foo'] = null;

        $this->assertEquals(array(
            'constraint' => null,
            'data_type' => null,
            'default' => null,
            'name' => 'foo',
            'nullable' => true,
            'pdo_type' => \PDO::PARAM_STR,
            'pkey' => false,
            'type' => 'string',
        ), $this->schema['foo']);
    }

    public function testOffsetUnset()
    {
        $this->schema['foo'] = null;
        unset($this->schema['foo']);

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Field not exists: foo.');

        $this->schema['foo'];
    }

    public function testCount()
    {
        $this->assertCount(0, $this->schema);
    }

    public function testGetIterator()
    {
        $this->assertInstanceOf('ArrayIterator', $this->schema->getIterator());
    }

    public function testGetSchema()
    {
        $this->assertCount(0, $this->schema->getSchema());
    }

    public function testGetFields()
    {
        $this->assertCount(0, $this->schema->getFields());
    }

    public function testGetKeys()
    {
        $this->assertCount(0, $this->schema->getKeys());
    }

    public function testAdd()
    {
        $this->schema->add('foo');
        $this->schema->add('bar', 'baz', false, true, 'int', \PDO::PARAM_INT);

        $this->assertEquals(array(
            'constraint' => null,
            'data_type' => null,
            'default' => null,
            'name' => 'foo',
            'nullable' => true,
            'pdo_type' => \PDO::PARAM_STR,
            'pkey' => false,
            'type' => 'string',
        ), $this->schema['foo']);
        $this->assertEquals(array(
            'constraint' => null,
            'data_type' => null,
            'default' => 'baz',
            'name' => 'bar',
            'nullable' => false,
            'pdo_type' => \PDO::PARAM_INT,
            'pkey' => true,
            'type' => 'int',
        ), $this->schema['bar']);

        $this->assertEquals(array('bar'), $this->schema->getKeys());
    }
}

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
    public function testCount()
    {
        $this->assertEquals(0, $this->schema->count());
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

    public function testGet()
    {
        $this->schema->add('foo');

        $this->assertEquals(array(
            'default' => null,
            'nullable' => true,
            'pkey' => false,
            'type' => 'string',
            'pdo_type' => \PDO::PARAM_STR,
            'data_type' => null,
            'constraint' => null,
        ), $this->schema->get('foo'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Field not exists: bar.');
        $this->schema->get('bar');
    }

    public function testSet()
    {
        $this->assertEquals(array(
            'default' => null,
            'nullable' => true,
            'pkey' => false,
            'type' => 'string',
            'pdo_type' => \PDO::PARAM_STR,
            'data_type' => null,
            'constraint' => null,
        ), $this->schema->set('foo', null)->get('foo'));
    }

    public function testRem()
    {
        $this->schema->add('foo', 'baz', false, true);

        $this->assertCount(1, $this->schema->getSchema());
        $this->assertEquals(array('foo'), $this->schema->getKeys());

        $this->schema->rem('foo');
        $this->assertCount(0, $this->schema->getSchema());
        $this->assertCount(0, $this->schema->getKeys());
    }

    public function testAdd()
    {
        $this->schema->add('foo');
        $this->schema->add('bar', 'baz', false, true, 'int', \PDO::PARAM_INT);

        $this->assertEquals(array(
            'default' => null,
            'nullable' => true,
            'pkey' => false,
            'type' => 'string',
            'pdo_type' => \PDO::PARAM_STR,
            'data_type' => null,
            'constraint' => null,
        ), $this->schema->get('foo'));
        $this->assertEquals(array(
            'default' => 'baz',
            'nullable' => false,
            'pkey' => true,
            'type' => 'int',
            'pdo_type' => \PDO::PARAM_INT,
            'data_type' => null,
            'constraint' => null,
        ), $this->schema->get('bar'));
        $this->assertEquals(array('bar'), $this->schema->getKeys());
    }
}

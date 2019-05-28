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
    public function testHas()
    {
        $this->assertFalse($this->schema->has('foo'));
    }

    public function testGet()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Field not exists: foo.');

        $this->schema->get('foo');
    }

    public function testSet()
    {
        $this->schema->set('foo', null);

        $this->assertEquals(array(
            'constraint' => null,
            'data_type' => null,
            'default' => null,
            'name' => 'foo',
            'nullable' => true,
            'pdo_type' => \PDO::PARAM_STR,
            'pkey' => false,
            'type' => 'string',
        ), $this->schema->get('foo'));
    }

    public function testRem()
    {
        $this->schema->set('foo', null);
        $this->schema->rem('foo');

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Field not exists: foo.');

        $this->schema->get('foo');
    }

    public function testCount()
    {
        $this->assertCount(0, $this->schema);
    }

    public function testGetIterator()
    {
        $this->assertInstanceOf('ArrayIterator', $this->schema->getIterator());
    }

    public function testGetFields()
    {
        $this->assertCount(0, $this->schema->getFields());
    }

    public function testGetKeys()
    {
        $this->assertCount(0, $this->schema->getKeys());
    }
}

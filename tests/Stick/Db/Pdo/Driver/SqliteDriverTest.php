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

namespace Fal\Stick\Test\Db\Pdo\Driver;

use Fal\Stick\TestSuite\MyTestCase;

class SqliteDriverTest extends MyTestCase
{
    public function testResolveDbName()
    {
        $this->assertEquals(':memory:', $this->sqliteDriver->resolveDbName('sqlite::memory:'));
        $this->assertEquals('foo.db', $this->sqliteDriver->resolveDbName('sqlite3:foo.db'));
    }

    public function testSqlSchema()
    {
        $this->assertEquals('PRAGMA table_info(`bar`)', $this->sqliteDriver->sqlSchema('foo', 'bar'));
    }

    public function testBuildSchema()
    {
        $rows = array(
            array(
                'name' => 'foo',
                'dflt_value' => 'NULL',
                'pk' => '1',
                'type' => 'TEXT',
                'notnull' => '0',
            ),
            array(
                'name' => 'bar',
                'dflt_value' => '1',
                'pk' => '0',
                'type' => 'INTEGER',
                'notnull' => '1',
            ),
        );
        $schema = $this->sqliteDriver->buildSchema($rows);
        $expected = array(
            'foo' => array(
                'constraint' => null,
                'data_type' => 'TEXT',
                'default' => null,
                'name' => 'foo',
                'nullable' => true,
                'pdo_type' => \PDO::PARAM_STR,
                'pkey' => true,
                'type' => 'TEXT',
            ),
            'bar' => array(
                'constraint' => null,
                'data_type' => 'INTEGER',
                'default' => 1,
                'name' => 'bar',
                'nullable' => false,
                'pdo_type' => \PDO::PARAM_INT,
                'pkey' => false,
                'type' => 'INTEGER',
            ),
        );

        $this->assertEquals($expected, $schema->getSchema());
    }
}

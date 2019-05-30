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

class MysqlDriverTest extends MyTestCase
{
    public function testResolveDbName()
    {
        $this->assertEquals('foo', $this->mysqlDriver->resolveDbName('dbname=foo'));
        $this->assertEquals('foo', $this->mysqlDriver->resolveDbName('mysql:host=localhost;dbname=foo'));
        $this->assertEquals('foo', $this->mysqlDriver->resolveDbName('mysql:host=localhost;dbname=foo;port=3306'));
        $this->assertEquals('', $this->mysqlDriver->resolveDbName('mysql:host=localhost'));
    }

    public function testQuote()
    {
        $this->assertEquals('`foo`.`bar`', $this->mysqlDriver->quote('foo.bar'));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Db\Pdo\MysqlDriverProvider::sqlSelect
     */
    public function testSqlSelect($expected, $fields, $table, $alias = null, $filters = null, $options = null)
    {
        $this->assertEquals($expected, $this->mysqlDriver->sqlSelect($fields, $table, $alias, $filters, $options));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Db\Pdo\MysqlDriverProvider::sqlCount
     */
    public function testSqlCount($expected, $fields, $table, $alias = null, $filters = null, $options = null)
    {
        $this->assertEquals($expected, $this->mysqlDriver->sqlCount($fields, $table, $alias, $filters, $options));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Db\Pdo\MysqlDriverProvider::sqlInsert
     */
    public function testSqlInsert($expected, $table, $schema, $data, $exception = null)
    {
        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);
            $this->mysqlDriver->sqlInsert($table, $schema, $data);

            return;
        }

        $this->assertEquals($expected, $this->mysqlDriver->sqlInsert($table, $schema, $data));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Db\Pdo\MysqlDriverProvider::sqlUpdate
     */
    public function testSqlUpdate($expected, $table, $schema, $data, $keys, $exception = null)
    {
        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);
            $this->mysqlDriver->sqlUpdate($table, $schema, $data, $keys);

            return;
        }

        $this->assertEquals($expected, $this->mysqlDriver->sqlUpdate($table, $schema, $data, $keys));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Db\Pdo\MysqlDriverProvider::sqlDelete
     */
    public function testSqlDelete($expected, $table, $schema, $keys)
    {
        $this->assertEquals($expected, $this->mysqlDriver->sqlDelete($table, $schema, $keys));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Db\Pdo\MysqlDriverProvider::sqlDeleteBatch
     */
    public function testSqlDeleteBatch($expected, $table, $filters)
    {
        $this->assertEquals($expected, $this->mysqlDriver->sqlDeleteBatch($table, $filters));
    }

    public function testSqlSchema()
    {
        $this->assertEquals('SHOW COLUMNS FROM `foo`.`bar`', $this->mysqlDriver->sqlSchema('foo', 'bar'));
    }

    public function testBuildSchema()
    {
        $rows = array(
            array(
                'Field' => 'foo',
                'Default' => 'NULL',
                'Key' => 'PRI',
                'Type' => 'VARCHAR(20)',
                'Null' => 'YES',
            ),
            array(
                'Field' => 'bar',
                'Default' => '1',
                'Key' => '',
                'Type' => 'INT(11)',
                'Null' => 'NO',
            ),
        );
        $schema = $this->mysqlDriver->buildSchema($rows);
        $expected = array(
            'foo' => array(
                'constraint' => '20',
                'data_type' => 'VARCHAR',
                'default' => null,
                'nullable' => true,
                'pdo_type' => \PDO::PARAM_STR,
                'pkey' => true,
                'type' => 'VARCHAR(20)',
            ),
            'bar' => array(
                'constraint' => '11',
                'data_type' => 'INT',
                'default' => 1,
                'nullable' => false,
                'pdo_type' => \PDO::PARAM_INT,
                'pkey' => false,
                'type' => 'INT(11)',
            ),
        );

        $this->assertEquals($expected, $schema->getFields(true));
    }

    public function testFixOrder()
    {
        $expected = '`foo` ASC, `bar` desc, `baz`';
        $expression = 'foo ASC,bar desc, baz';

        $this->assertEquals($expected, $this->mysqlDriver->fixOrder($expression));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Db\Pdo\MysqlDriverProvider::filter
     */
    public function testFilter($expected, $filter, $exception = null)
    {
        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);
            $this->mysqlDriver->filter($filter);

            return;
        }

        $this->assertEquals($expected, $this->mysqlDriver->filter($filter));
    }
}

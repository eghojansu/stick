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

namespace Ekok\Stick\Tests\Database\QueryBuilder;

use Ekok\Stick\Database\QueryBuilder\MsSqlQueryBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Ekok\Stick\Database\QueryBuilder\MsSqlQueryBuilder
 */
final class MsSqlQueryBuilderTest extends TestCase
{
    private $builder;

    protected function setUp(): void
    {
        $this->builder = new MsSqlQueryBuilder(array(
            'dbname' => 'test',
        ));
    }

    public function testGetDsn()
    {
        $this->assertEquals('sqlsrv:Server=localhost,1433;Database=test', $this->builder->getDsn());
    }

    public function testGetUser()
    {
        $this->assertEquals('sa', $this->builder->getUser());
    }

    public function testGetPassword()
    {
        $this->assertEquals(null, $this->builder->getPassword());
    }

    public function testGetOptions()
    {
        $this->assertEquals(null, $this->builder->getOptions());
    }

    public function testGetCommands()
    {
        $this->assertEquals(null, $this->builder->getCommands());
    }

    public function testSupportTransaction()
    {
        $this->assertEquals(true, $this->builder->supportTransaction());
    }

    public function testQuote()
    {
        $this->assertEquals('"foo"', $this->builder->quote('foo'));
        $this->assertEquals('"foo"."bar"', $this->builder->quote('foo.bar'));
    }

    /** @dataProvider stringifyProvider */
    public function testStringify($expected, string $source, $filter = null, array $options = null)
    {
        if ($expected instanceof \Exception) {
            $this->expectException(get_class($expected));
            $this->expectExceptionMessage($expected->getMessage());

            $this->builder->stringify($source, $filter, $options);
        } else {
            $actual = $this->builder->stringify($source, $filter, $options);

            if (is_string($expected)) {
                $this->assertEquals($expected, $actual[0]);
            } else {
                $this->assertEquals($expected, $actual);
            }
        }
    }

    public function testSelect()
    {
        $expected = array('select * from "foo"', array());

        $this->assertEquals($expected, $this->builder->select('foo'));
    }

    public function testCount()
    {
        $expected = array('select count(*) _count from (select * from "foo") _source', array());

        $this->assertEquals($expected, $this->builder->count('foo'));
    }

    public function testInsert()
    {
        $expected = array('insert into "foo" ("foo", "bar") values (?, ?)', array(1, 2));
        $table = 'foo';
        $data = array(
            'foo' => 1,
            'bar' => 2,
        );

        $this->assertEquals($expected, $this->builder->insert($table, $data));
    }

    public function testInsertBatch()
    {
        $expected = array('insert into "foo" ("foo", "bar") values (?, ?), (?, ?)', array(1, 2, 3, 4));
        $table = 'foo';
        $data = array(
            array(
                'foo' => 1,
                'bar' => 2,
            ),
            array(
                'foo' => 3,
                'bar' => 4,
            ),
        );

        $this->assertEquals($expected, $this->builder->insertBatch($table, $data));
    }

    /** @dataProvider insertBatchExceptionProvider */
    public function testInsertBatchException(\Exception $expected, string $table, array $data)
    {
        $this->expectException(get_class($expected));
        $this->expectExceptionMessage($expected->getMessage());

        $this->builder->insertBatch($table, $data);
    }

    public function testUpdate()
    {
        $expected = array('update "foo" set "foo" = ?, "bar" = ? where foo = ?', array(1, 2, 3));
        $table = 'foo';
        $data = array(
            'foo' => 1,
            'bar' => 2,
        );
        $filter = array('foo = ?', 3);

        $this->assertEquals($expected, $this->builder->update($table, $data, $filter));

        $expected = array(
            'update "foo" set "foo" = :_set_foo, "bar" = :_set_bar where foo = :foo',
            array(
                ':foo' => 3,
                ':_set_foo' => 1,
                ':_set_bar' => 2,
            ),
        );
        $table = 'foo';
        $data = array(
            'foo' => 1,
            'bar' => 2,
        );
        $filter = array('foo = :foo', ':foo' => 3);

        $this->assertEquals($expected, $this->builder->update($table, $data, $filter));
    }

    public function testDelete()
    {
        $expected = array('delete from "foo" where foo = 1', array());
        $table = 'foo';
        $filter = 'foo = 1';

        $this->assertEquals($expected, $this->builder->delete($table, $filter));
    }

    public function stringifyProvider()
    {
        return array(
            'simple' => array(
                'select * from "foo"',
                'foo',
            ),
            'with alias' => array(
                'select * from "foo" T',
                'foo',
                null,
                array(
                    'alias' => 'T',
                ),
            ),
            'select top columns' => array(
                'select top 5 "foo" "foo", "bar" "Baz" from "foo"',
                'foo',
                null,
                array(
                    'limit' => 5,
                    'select' => array(
                        'foo',
                        'Baz' => 'bar',
                    ),
                ),
            ),
            'invalid limit offset' => array(
                new \LogicException('Unable to perform limit-offset without order clause.'),
                'foo',
                null,
                array(
                    'limit' => 5,
                    'offset' => 5,
                ),
            ),
            'raw clause' => array(
                'select * from "foo" where foo = 10',
                'foo',
                'foo = 10',
            ),
            'with argument' => array(
                array('select * from "foo" where foo = ?', array('bar')),
                'foo',
                array('foo = ?', 'bar'),
            ),
            'complete' => array(
                array(
                    'select * from "foo" where foo = :foo group by foo having foo = :having order by "foo", "bar" desc, "baz" asc offset 10 rows fetch next 5 rows only',
                    array(':foo' => 'bar', ':having' => 'baz'),
                ),
                'foo',
                array('foo = :foo', ':foo' => 'bar'),
                array(
                    'having' => array('foo = :having', ':having' => 'baz'),
                    'group' => 'foo',
                    'order' => array(
                        'foo',
                        'bar' => 'desc',
                        'baz' => 'asc',
                    ),
                    'limit' => 5,
                    'offset' => 10,
                ),
            ),
        );
    }

    public function insertBatchExceptionProvider()
    {
        return array(
            'invalid structure' => array(
                new \LogicException('Data structure should be array of array.'),
                'foo',
                array(
                    'foo' => 1,
                    'bar' => 2,
                ),
            ),
            'invalid second data' => array(
                new \LogicException('Invalid data count at row 1.'),
                'foo',
                array(
                    array(
                        'foo' => 1,
                        'bar' => 2,
                    ),
                    array(
                        'foo' => 3,
                    ),
                ),
            ),
        );
    }
}

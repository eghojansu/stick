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

namespace Ekok\Stick\Tests\Database;

use Ekok\Stick\Database\QueryBuilder\SqliteQueryBuilder;
use Ekok\Stick\Database\Sql;
use Ekok\Stick\Fw;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Ekok\Stick\Database\Sql
 */
final class SqlTest extends TestCase
{
    private $sql;

    protected function setUp(): void
    {
        $options = array(
            'commands' => array(
                'create table foo (id integer not null, name text not null)',
                'insert into foo values (1, "foo"), (2, "bar")',
            ),
        );

        $fw = new Fw();
        $fw->set('LOG.directory', TEST_TEMP.'/logs_query/');

        $this->sql = new Sql($fw, new SqliteQueryBuilder($options));

        testRemoveTemp('logs_query');
    }

    public function testMagicGet()
    {
        $this->assertInstanceOf('PDO', $this->sql->pdo);
    }

    public function testPdoException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Error establishing a database connection.');

        $sql = new Sql(new Fw(), new SqliteQueryBuilder(array(
            'commands' => array(
                'insert into foo values (1, "foo"), (2, "bar")',
            ),
        )));
        $sql->pdo;
    }

    public function testGetOptions()
    {
        $expected = array(
            'log_query' => false,
        );

        $this->assertEquals($expected, $this->sql->getOptions());
    }

    public function testSetOptions()
    {
        $set = array(
            'log_query' => true,
        );

        $this->assertEquals($set, $this->sql->setOptions($set)->getOptions());
    }

    public function testExec()
    {
        $this->sql->setOptions(array('log_query' => true));

        $result = $this->sql->exec('select * from foo where id > ?', array(0));
        $this->assertCount(2, $result);

        $result = $this->sql->exec('select * from bar');
        $this->assertCount(0, $result);
    }

    public function testExecAll()
    {
        $result = $this->sql->execAll(array(
            'insert into foo values (3, "baz")',
            'select * from foo',
        ));

        $this->assertCount(2, $result);
        $this->assertEquals(1, $result[0]->rowCount);
        $this->assertEquals(3, $result[1]->rowCount);
    }

    public function testPaginate()
    {
        $result = $this->sql->paginate('foo');

        $this->assertEquals(false, $result['empty']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(1, $result['max']);
        $this->assertEquals(10, $result['size']);
        $this->assertEquals(2, $result['total']);
        $this->assertEquals(2, $result['count']);
        $this->assertCount(2, $result['subset']);
        $this->assertEquals(1, $result['from']);
        $this->assertEquals(2, $result['to']);
    }

    public function testFindOne()
    {
        $expected = array(
            'id' => 1,
            'name' => 'foo',
        );

        $this->assertEquals($expected, $this->sql->findOne('foo'));
    }

    public function testFind()
    {
        $result = $this->sql->find('foo');

        $this->assertCount(2, $result);
    }

    public function testCount()
    {
        $this->assertEquals(2, $this->sql->count('foo'));
    }

    public function testInsert()
    {
        $this->assertEquals(1, $this->sql->insert('foo', array('id' => 3, 'name' => 'baz')));
    }

    public function testInsertBatch()
    {
        $this->assertEquals(2, $this->sql->insertBatch('foo', array(
            array('id' => 4, 'name' => 'qux'),
            array('id' => 5, 'name' => 'quux'),
        ), true));
    }

    public function testUpdate()
    {
        $this->assertEquals(1, $this->sql->update('foo', array('name' => 'foobar'), array('name = ?', 'foo')));
    }

    public function testDelete()
    {
        $this->assertEquals(1, $this->sql->delete('foo', array('name = ?', 'foo')));
        $this->assertEquals(0, $this->sql->delete('foo', array('name = ?', 'foo')));
    }

    public function testExists()
    {
        $this->sql->setOptions(array('log_query' => true));

        $this->assertTrue($this->sql->exists('foo'));
        $this->assertFalse($this->sql->exists('bar'));
    }

    public function testValueType()
    {
        $this->assertEquals(\PDO::PARAM_INT, $this->sql->valueType(1));
        $this->assertEquals(\PDO::PARAM_STR, $this->sql->valueType('str'));
        $this->assertEquals(\PDO::PARAM_NULL, $this->sql->valueType(null));
        $this->assertEquals(\PDO::PARAM_BOOL, $this->sql->valueType(true));
        $this->assertEquals(Sql::PARAM_FLOAT, $this->sql->valueType(0.5));
    }

    public function testValueCast()
    {
        $this->assertSame(0.5, $this->sql->valueCast('0.5', Sql::PARAM_FLOAT));
        $this->assertSame(0.5, $this->sql->valueCast(0.5, Sql::PARAM_FLOAT));
        $this->assertSame(null, $this->sql->valueCast('', \PDO::PARAM_NULL));
        $this->assertSame(3, $this->sql->valueCast('3', \PDO::PARAM_INT));
        $this->assertSame(true, $this->sql->valueCast('1', \PDO::PARAM_BOOL));
        $this->assertSame('str', $this->sql->valueCast('str', \PDO::PARAM_STR));
        $this->assertSame('foo', $this->sql->valueCast('foo', \PDO::PARAM_LOB));
    }
}

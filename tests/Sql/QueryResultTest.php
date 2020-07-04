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

namespace Ekok\Stick\Tests\Sql;

use Ekok\Stick\Sql\QueryResult;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Ekok\Stick\Sql\QueryResult
 */
final class QueryResultTest extends TestCase
{
    private $queryResult;
    private $pdo;

    protected function setUp(): void
    {
        $schema = <<<'SCHEMA'
create table foo (
    id integer not null,
    name text not null
);
insert into foo values (1, 'foo'), (2, 'bar');
SCHEMA;

        $this->pdo = new \PDO('sqlite::memory:');
        $this->pdo->exec($schema);

        $this->queryResult = $this->createQueryResult('select * from foo');
    }

    public function testMagicGet()
    {
        $this->assertInstanceOf('PDOStatement', $this->queryResult->query);
        $this->assertEquals('select * from foo', $this->queryResult->sql);
        $this->assertEquals(true, $this->queryResult->isFetchable);
        $this->assertEquals(true, $this->queryResult->isCountable);
        $this->assertEquals(2, $this->queryResult->rowCount);
        $this->assertEquals(2, $this->queryResult->columnCount);
        $this->assertEquals(array(
            array('id' => 1, 'name' => 'foo'),
            array('id' => 2, 'name' => 'bar'),
        ), $this->queryResult->rows);
        $this->assertEquals(array('id' => 1, 'name' => 'foo'), $this->queryResult->row);
    }

    public function testGetRow()
    {
        $this->assertEquals(array('id' => 1, 'name' => 'foo'), $this->queryResult->row);
    }

    public function testRowCount()
    {
        $query = $this->createQueryResult("insert into foo values (3, 'baz')");

        $this->assertEquals(1, $query->rowCount);
    }

    public function testGetIterator()
    {
        $this->assertInstanceOf('PDOStatement', $this->queryResult->getIterator());
    }

    public function testCount()
    {
        $this->assertEquals(2, $this->queryResult->count());
    }

    public function testSetFetchMode()
    {
        $this->assertSame($this->queryResult, $this->queryResult->setFetchMode('num'));
    }

    public function testSetFetchModeException()
    {
        $this->expectException('UnexpectedValueException');
        $this->expectExceptionMessage("Unsupported PDO Fetch mode: 'foo'.");

        $this->queryResult->setFetchMode('foo');
    }

    public function testResetCachedResult()
    {
        $this->assertSame($this->queryResult, $this->queryResult->resetCachedResult());
    }

    public function testColumn()
    {
        $this->assertEquals(1, $this->queryResult->column(0));
        $this->assertEquals('bar', $this->queryResult->column(1));
    }

    private function createQueryResult(string $sql, array $arguments = null): QueryResult
    {
        $query = $this->pdo->query($sql);
        $query->execute($arguments);

        return new QueryResult($query);
    }
}

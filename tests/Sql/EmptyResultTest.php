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

use Ekok\Stick\Sql\EmptyResult;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Ekok\Stick\Sql\EmptyResult
 */
final class EmptyResultTest extends TestCase
{
    private $queryResult;

    protected function setUp(): void
    {
        $this->queryResult = new EmptyResult('select * from foo');
    }

    public function testMagicGet()
    {
        $this->assertEquals('select * from foo', $this->queryResult->sql);
        $this->assertEquals(true, $this->queryResult->isFetchable);
        $this->assertEquals(false, $this->queryResult->isCountable);
        $this->assertEquals(0, $this->queryResult->rowCount);
        $this->assertEquals(0, $this->queryResult->columnCount);
        $this->assertEquals(null, $this->queryResult->row);
        $this->assertEquals(array(), $this->queryResult->rows);
    }

    public function testRowCount()
    {
        $this->assertEquals(0, $this->queryResult->rowCount);
    }

    public function testGetIterator()
    {
        $this->assertInstanceOf('EmptyIterator', $this->queryResult->getIterator());
    }

    public function testCount()
    {
        $this->assertEquals(0, $this->queryResult->count());
    }

    public function testSetFetchMode()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Cannot set fetch mode in empty result.');

        $this->queryResult->setFetchMode('foo');
    }

    public function testResetCachedResult()
    {
        $this->assertSame($this->queryResult, $this->queryResult->resetCachedResult());
    }

    public function testColumn()
    {
        $this->assertEquals(0, $this->queryResult->column());
    }
}

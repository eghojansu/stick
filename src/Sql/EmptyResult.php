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

namespace Ekok\Stick\Sql;

/**
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class EmptyResult extends QueryResult
{
    public function __construct(string $sql)
    {
        $this->hive['sql'] = $sql;
    }

    public function getIterator()
    {
        return new \EmptyIterator();
    }

    public function count()
    {
        return 0;
    }

    public function setFetchMode($mode): QueryResult
    {
        throw new \LogicException('Cannot set fetch mode in empty result.');
    }

    public function resetCachedResult(): QueryResult
    {
        return $this;
    }

    public function column(int $no = 0)
    {
        return null;
    }

    protected function _row()
    {
        return null;
    }

    protected function _rows(): array
    {
        return array();
    }

    protected function _rowCount(): int
    {
        return 0;
    }

    protected function _columnCount(): int
    {
        return 0;
    }
}

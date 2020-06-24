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
 * Query result.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class QueryResult implements \IteratorAggregate, \Countable
{
    protected $hive = array();

    public function __construct(\PDOStatement $query)
    {
        $query->setFetchMode(\PDO::FETCH_ASSOC);

        $this->hive['query'] = $query;
    }

    public function __get($key)
    {
        if (
            !array_key_exists($ukey = $key, $this->hive)
            && !array_key_exists($ukey = strtolower($key), $this->hive)) {
            $this->hive[$ukey] = method_exists($this, $method = '_'.$key) ? $this->{$method}() : null;
        }

        return $this->hive[$ukey];
    }

    public function getIterator()
    {
        return $this->hive['query'];
    }

    public function count()
    {
        return $this->isCountable ? count($this->rows) : 0;
    }

    public function setFetchMode($mode): QueryResult
    {
        if (is_string($mode) && !defined($name = 'PDO::FETCH_'.strtoupper($mode))) {
            throw new \UnexpectedValueException("Unsupported PDO Fetch mode: '{$mode}'.");
        }

        $this->hive['query']->setFetchMode(isset($name) ? constant($name) : $mode);

        return $this;
    }

    public function resetCachedResult(): QueryResult
    {
        $this->hive = array(
            'query' => $this->hive['query'],
        );

        return $this;
    }

    public function column(int $no = 0)
    {
        return $this->hive['query']->fetchColumn($no);
    }

    protected function _sql(): string
    {
        return $this->hive['query']->queryString;
    }

    protected function _isFetchable(): bool
    {
        return preg_match('/(?:^[\s\(]*(?:WITH|EXPLAIN|SELECT|PRAGMA|SHOW)|RETURNING)\b/is', $this->sql)
            || preg_match('/^\s*(?:CALL|EXEC)\b/is', $this->sql);
    }

    protected function _isCountable(): bool
    {
        return $this->isFetchable && $this->columnCount > 0;
    }

    protected function _row()
    {
        $row = $this->hive['query']->fetch();

        return false === $row ? $this->rows[0] ?? null : $row;
    }

    protected function _rows(): array
    {
        return $this->hive['query']->fetchAll();
    }

    protected function _rowCount(): int
    {
        if ($this->isCountable) {
            return count($this->rows);
        }

        return $this->hive['query']->rowCount();
    }

    protected function _columnCount(): int
    {
        return $this->hive['query']->columnCount();
    }
}

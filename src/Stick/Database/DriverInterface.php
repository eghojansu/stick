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

namespace Fal\Stick\Database;

/**
 * Database driver interface.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface DriverInterface
{
    /**
     * Returns true if table exists.
     *
     * @param string $table
     *
     * @return bool
     */
    public function exists(string $table): bool;

    /**
     * Returns table row.
     *
     * @param string     $table
     * @param array|null $options
     * @param int        $ttl
     *
     * @return Row
     */
    public function schema(string $table, array $options = null, int $ttl = 0): Row;

    /**
     * Returns records match clause.
     *
     * @param Row        $row
     * @param array|null $clause
     * @param array|null $options
     * @param int        $ttl
     *
     * @return Row[]
     */
    public function find(Row $row, array $clause = null, array $options = null, int $ttl = 0): array;

    /**
     * Returns first record match clause.
     *
     * @param Row        $row
     * @param array|null $clause
     * @param array|null $options
     * @param int        $ttl
     *
     * @return Row|null
     */
    public function first(Row $row, array $clause = null, array $options = null, int $ttl = 0): ?Row;

    /**
     * Returns records count match given clause.
     *
     * @param Row        $row
     * @param array|null $clause
     * @param array|null $options
     * @param int        $ttl
     *
     * @return int
     */
    public function count(Row $row, array $clause = null, array $options = null, int $ttl = 0): int;

    /**
     * Returns pagination.
     *
     * @param Row        $row
     * @param int        $page
     * @param int        $limit
     * @param array|null $clause
     * @param array|null $options
     * @param int        $ttl
     *
     * @return array
     */
    public function paginate(Row $row, int $page, int $limit = 10, array $clause = null, array $options = null, int $ttl = 0): array;

    /**
     * Returns clause if insert successfull otherwise null.
     *
     * @param Row $row
     *
     * @return Row|null
     */
    public function insert(Row $row): ?Row;

    /**
     * Returns true if update success.
     *
     * @param Row $row
     *
     * @return bool
     */
    public function update(Row $row): bool;

    /**
     * Returns true if delete success.
     *
     * @param Row $row
     *
     * @return bool
     */
    public function delete(Row $row): bool;

    /**
     * Returns number of affected records.
     *
     * @param Row        $row
     * @param array|null $clause
     *
     * @return int
     */
    public function deleteByClause(Row $row, array $clause = null): int;

    /**
     * Returns true if driver supports transaction.
     *
     * @return bool
     */
    public function isSupportTransaction(): bool;

    /**
     * Returns true if in transaction.
     *
     * @return bool
     */
    public function inTransaction(): bool;

    /**
     * Returns true if begin transaction success.
     *
     * @return bool
     */
    public function begin(): bool;

    /**
     * Returns true if commit transaction success.
     *
     * @return bool
     */
    public function commit(): bool;

    /**
     * Returns true if rollback transaction success.
     *
     * @return bool
     */
    public function rollback(): bool;
}

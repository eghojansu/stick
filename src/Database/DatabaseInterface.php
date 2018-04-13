<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Database;

interface DatabaseInterface
{
    /**
     * Get database driver name
     *
     * @return string
     */
    public function getDriverName(): string;

    /**
     * Get database driver version
     *
     * @return string
     */
    public function getDriverVersion(): string;

    /**
     * Get logs
     *
     * @return array
     */
    public function getLogs(): array;

    /**
     * Get errors logs
     *
     * @return array
     */
    public function getErrors(): array;

    /**
     * Get table schema
     *
     * @param  string      $table
     * @param  array|null  $fields
     * @param  int|integer $ttl
     *
     * @return array       ['field'=>['type'=>string,'default'=>mixed,'nullable'=>bool,'pkey'=>bool]]
     */
    public function getSchema(string $table, array $fields = null, int $ttl = 0): array;

    /**
     * Get quote char (open and close)
     *
     * @return array
     */
    public function getQuote(): array;

    /**
     * Return quoted identifier name
     *
     * @param  string  $key
     * @param  boolean $split
     *
     * @return string
     */
    public function quotekey(string $key, bool $split = true): string;

    /**
     * Open transaction
     *
     * @return bool
     */
    public function begin(): bool;

    /**
     * Commit and close transaction
     *
     * @return bool
     */
    public function commit(): bool;

    /**
     * Roolback and close transaction
     *
     * @return bool
     */
    public function rollback(): bool;

    /**
     * Get transaction status
     *
     * @return bool
     */
    public function isTrans(): bool;

    /**
     * Run sql
     *
     * @param  string $sql
     * @param  array  $params
     * @param  int    $ttl
     *
     * @return array  ['success'=>bool,'error'=>array,'data'=>array]
     */
    public function run(string $sql, array $params = null, int $ttl = 0): array;

    /**
     * Select table content
     *
     * @param  string $table
     * @param  mixed  $filter
     * @param  array  $option
     * @param  int    $ttl
     *
     * @return array
     */
    public function select(string $table, $filter = null, array $option = null, int $ttl = 0): array;

    /**
     * Select table content (limit 1 record)
     *
     * @param  string $table
     * @param  mixed  $filter
     * @param  array  $option
     * @param  int    $ttl
     *
     * @return array
     */
    public function selectOne(string $table, $filter = null, array $option = null, int $ttl = 0): array;

    /**
     * Insert data to table
     *
     * @param  string $table
     * @param  array  $data
     *
     * @return string inserted id
     */
    public function insert(string $table, array $data): string;

    /**
     * Update data by filter
     *
     * @param  string $table
     * @param  array  $data
     * @param  mixed  $filter
     *
     * @return bool
     */
    public function update(string $table, array $data, $filter): bool;

    /**
     * Delete data by filter
     *
     * @param  string $table
     * @param  mixed  $filter
     *
     * @return bool
     */
    public function delete(string $table, $filter): bool;

    /**
     * Count table records
     *
     * @param  string $table
     * @param  mixed  $filter
     * @param  array  $option
     * @param  int    $ttl
     *
     * @return int
     */
    public function count(string $table, $filter = null, array $option = null, int $ttl = 0): int;

    /**
     * Paginate records
     *
     * @param  string     $table
     * @param  int        $page
     * @param  mixed      $filter
     * @param  array|null $option
     * @param  int        $ttl
     *
     * @return array
     */
    public function paginate(
        string $table,
        int $page = 1,
        $filter = null,
        array $option = null,
        int $ttl = 0
    ): array;
}

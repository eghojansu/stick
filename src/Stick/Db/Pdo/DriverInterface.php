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

namespace Fal\Stick\Db\Pdo;

/**
 * Database driver interface.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface DriverInterface
{
    /**
     * Returns database name from dsn expression.
     *
     * @param string $dsn
     *
     * @return string
     */
    public function resolveDbName(string $dsn): string;

    /**
     * Returns quoted key.
     *
     * @param string $key
     *
     * @return string
     */
    public function quote(string $key): string;

    /**
     * Returns sql select.
     *
     * @param string            $fields
     * @param string            $table
     * @param string|null       $alias
     * @param string|array|null $filters
     * @param array|null        $options
     *
     * @return array
     */
    public function sqlSelect(string $fields, string $table, string $alias = null, $filters = null, array $options = null): array;

    /**
     * Returns sql count.
     *
     * @param string|null       $fields
     * @param string            $table
     * @param string|null       $alias
     * @param string|array|null $filters
     * @param array|null        $options
     *
     * @return array
     */
    public function sqlCount(?string $fields, string $table, string $alias = null, $filters = null, array $options = null): array;

    /**
     * Returns sql insert.
     *
     * @param string $table
     * @param Schema $schema
     * @param array  $data
     *
     * @return array
     */
    public function sqlInsert(string $table, Schema $schema, array $data): array;

    /**
     * Returns sql update.
     *
     * @param string $table
     * @param Schema $schema
     * @param array  $data
     * @param array  $keys
     *
     * @return array
     */
    public function sqlUpdate(string $table, Schema $schema, array $data, array $keys): array;

    /**
     * Returns sql delete.
     *
     * @param string $table
     * @param Schema $schema
     * @param array  $keys
     *
     * @return array
     */
    public function sqlDelete(string $table, Schema $schema, array $keys): array;

    /**
     * Returns sql delete batch (by filter).
     *
     * @param string            $table
     * @param string|array|null $filters
     *
     * @return array
     */
    public function sqlDeleteBatch(string $table, $filters): array;

    /**
     * Returns sql query for getting table schema.
     *
     * @param string $db
     * @param string $table
     *
     * @return string
     */
    public function sqlSchema(string $db, string $table): string;

    /**
     * Returns schema from sql result.
     *
     * @param array $rows
     *
     * @return Schema
     */
    public function buildSchema(array $rows): Schema;
}

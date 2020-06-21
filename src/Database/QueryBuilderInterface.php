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

namespace Ekok\Stick\Database;

/**
 * Query builder interface.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface QueryBuilderInterface
{
    public function getDsn(): string;

    public function getUser(): string;

    public function getPassword(): ?string;

    public function getOptions(): ?array;

    public function getCommands(): ?array;

    /**
     * Returns true if query driver support transactions.
     */
    public function supportTransaction(): bool;

    public function quote(string $key): string;

    public function select(string $table, $filter = null, array $options = null): array;

    public function count(string $table, $filter = null, array $options = null): array;

    public function insert(string $table, array $data): array;

    public function insertBatch(string $table, array $data): array;

    public function update(string $table, array $data, $filter = null): array;

    public function delete(string $table, $filter = null): array;
}

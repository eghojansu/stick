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

interface MapperInterface extends \ArrayAccess
{
    /** Event names */
    const EVENT_LOAD = 'load';
    const EVENT_BEFOREINSERT = 'beforeinsert';
    const EVENT_AFTERINSERT = 'afterinsert';
    const EVENT_INSERT = self::EVENT_AFTERINSERT;
    const EVENT_BEFOREUPDATE = 'beforeupdate';
    const EVENT_AFTERUPDATE = 'afterupdate';
    const EVENT_UPDATE = self::EVENT_AFTERUPDATE;
    const EVENT_BEFOREDELETE = 'beforedelete';
    const EVENT_AFTERDELETE = 'afterdelete';
    const EVENT_DELETE = self::EVENT_AFTERDELETE;

    /**
     * Get database type
     *
     * @return string
     */
    public function getDbType(): string;

    /**
     * Get field names
     *
     * @return array
     */
    public function getFields(): array;

    /**
     * Get schema
     *
     * @return array
     */
    public function getSchema(): array;

    /**
     * Set schema
     *
     * @param  array $schema
     *
     * @return MapperInterface
     */
    public function setSchema(array $schema): MapperInterface;

    /**
     * Create new instance with table
     *
     * @param  string     $table
     * @param  array|null $option
     *
     * @return MapperInterface
     */
    public function withTable(string $table, array $option = null): MapperInterface;

    /**
     * Get mapped table
     *
     * @return string
     */
    public function getTable(): string;

    /**
     * Set mapped table
     *
     * @param  string|null $table
     *
     * @return MapperInterface
     */
    public function setTable(string $table = null): MapperInterface;

    /**
     * Count records that match criteria
     *
     * @param  mixed       $filter
     * @param  array|null  $option
     * @param  int         $ttl
     *
     * @return int
     */
    public function count($filter = null, array $option = null, int $ttl = 0): int;

    /**
     * Load single record that match criteria into this instance
     *
     * @param  mixed       $filter
     * @param  array|null  $option
     * @param  int         $ttl
     *
     * @return MapperInterface
     */
    public function load($filter = null, array $option = null, int $ttl = 0): MapperInterface;

    /**
     * Return record that match primary keys into this instance
     *
     * @param  string|array $id
     * @param  int          $ttl
     *
     * @return MapperInterface
     */
    public function loadId($id, int $ttl = 0): MapperInterface;

    /**
     * Return record that match primary keys
     *
     * @param  string|array $id
     * @param  int          $ttl
     *
     * @return MapperInterface|null
     */
    public function findId($id, int $ttl = 0): ?MapperInterface;

    /**
     * Return single record that match criteria
     *
     * @param  mixed       $filter
     * @param  array|null  $option
     * @param  int         $ttl
     *
     * @return MapperInterface|null
     */
    public function findOne($filter = null, array $option = null, int $ttl = 0): ?MapperInterface;

    /**
     * Return records (array of mapper objects) that match criteria
     *
     * @param  mixed       $filter
     * @param  array|null  $option
     * @param  int         $ttl
     *
     * @return array
     */
    public function find($filter = null, array $option = null, int $ttl = 0): array;

    /**
     * Paginate records
     *
     * @param  int        $page
     * @param  mixed      $filter
     * @param  array|null $option
     * @param  int        $ttl
     *
     * @return array
     */
    public function paginate(int $page = 1, $filter = null, array $option = null, int $ttl = 0): array;

    /**
     * Insert new record
     *
     * @return MapperInterface
     */
    public function insert(): MapperInterface;

    /**
     * Update current record
     *
     * @return MapperInterface
     */
    public function update(): MapperInterface;

    /**
     * Delete current record
     *
     * @param  mixed $filter
     * @param  bool  $quick
     *
     * @return int
     */
    public function delete($filter = null, bool $quick = true): int;

    /**
     * Save current record
     *
     * @return MapperInterface
     */
    public function save(): MapperInterface;

    /**
     * Return fields of mapper object as an associative array
     *
     * @param  callable $func
     *
     * @return array
     */
    public function toArray(callable $func = null): array;

    /**
     * Set mapper values from array source, customized by func
     *
     * @param  array    $source
     * @param  callable $func
     *
     * @return MapperInterface
     */
    public function fromArray(array $source, callable $func = null): MapperInterface;

    /**
     * Return TRUE if field is not nullable
     *
     * @param  string $key
     *
     * @return bool
     */
    public function required(string $key): bool;

    /**
     * Return TRUE if any/specified field value has changed
     *
     * @param  string|null $key
     *
     * @return bool
     */
    public function changed(string $key = null): bool;

    /**
     * Return TRUE if field is defined
     *
     * @param  string $key
     *
     * @return bool
     */
    public function exists(string $key): bool;

    /**
     * Retrieve value of field
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function &get(string $key);

    /**
     * Assign value to field
     *
     * @param string $key
     * @param mixed  $val
     *
     * @return MapperInterface
     */
    public function set(string $key, $val): MapperInterface;

    /**
     * Clear value of field
     *
     * @param  string $key
     *
     * @return MapperInterface
     */
    public function clear(string $key): MapperInterface;

    /**
     * Return TRUE if current cursor position is not mapped to any record
     *
     * @return bool
     */
    public function unloaded(): bool;

    /**
     * Dry complement
     *
     * @return bool
     */
    public function loaded(): bool;

    /**
     * Reset cursor
     *
     * @return MapperInterface
     */
    public function reset(): MapperInterface;

    /**
     * Add trigger
     *
     * @param string   $name
     * @param callable $func
     * @param bool     $first
     *
     * @return MapperInterface
     */
    public function addTrigger(string $name, callable $func, bool $first = false): MapperInterface;

    /**
     * Define onload trigger
     *
     * @param  callable $func
     * @param  bool     $first
     *
     * @return MapperInterface
     */
    public function onload(callable $func, bool $first = false): MapperInterface;

    /**
     * Define beforeinsert trigger
     *
     * @param  callable $func
     * @param  bool     $first
     *
     * @return MapperInterface
     */
    public function beforeinsert(callable $func, bool $first = false): MapperInterface;

    /**
     * Define afterinsert trigger
     *
     * @param  callable $func
     * @param  bool     $first
     *
     * @return MapperInterface
     */
    public function afterinsert(callable $func, bool $first = false): MapperInterface;

    /**
     * Define oninsert trigger
     *
     * @param  callable $func
     * @param  bool     $first
     *
     * @return MapperInterface
     */
    public function oninsert(callable $func, bool $first = false): MapperInterface;

    /**
     * Define beforeupdate trigger
     *
     * @param  callable $func
     * @param  bool     $first
     *
     * @return MapperInterface
     */
    public function beforeupdate(callable $func, bool $first = false): MapperInterface;

    /**
     * Define afterupdate trigger
     *
     * @param  callable $func
     * @param  bool     $first
     *
     * @return MapperInterface
     */
    public function afterupdate(callable $func, bool $first = false): MapperInterface;

    /**
     * Define onupdate trigger
     *
     * @param  callable $func
     * @param  bool     $first
     *
     * @return MapperInterface
     */
    public function onupdate(callable $func, bool $first = false): MapperInterface;

    /**
     * Define beforeinsert and beforeupdate trigger
     *
     * @param  callable $func
     * @param  bool     $first
     *
     * @return MapperInterface
     */
    public function beforesave(callable $func, bool $first = false): MapperInterface;

    /**
     * Define beforeinsert and beforeupdate trigger
     *
     * @param  callable $func
     * @param  bool     $first
     *
     * @return MapperInterface
     */
    public function aftersave(callable $func, bool $first = false): MapperInterface;

    /**
     * Define oninsert and onupdate trigger
     *
     * @param  callable $func
     * @param  bool     $first
     *
     * @return MapperInterface
     */
    public function onsave(callable $func, bool $first = false): MapperInterface;

    /**
     * Define beforedelete trigger
     *
     * @param  callable $func
     * @param  bool     $first
     *
     * @return MapperInterface
     */
    public function beforedelete(callable $func, bool $first = false): MapperInterface;

    /**
     * Define afterdelete trigger
     *
     * @param  callable $func
     * @param  bool     $first
     *
     * @return MapperInterface
     */
    public function afterdelete(callable $func, bool $first = false): MapperInterface;

    /**
     * Define ondelete trigger
     *
     * @param  callable $func
     * @param  bool     $first
     *
     * @return MapperInterface
     */
    public function ondelete(callable $func, bool $first = false): MapperInterface;
}

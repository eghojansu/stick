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

namespace Fal\Stick\Sql;

use Fal\Stick\Fw;

/**
 * Sql record mapper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Mapper implements \ArrayAccess
{
    // Paginate perpage
    const PAGINATION = 10;

    // Supported events
    const EVENT_LOAD = 'mapper.load';
    const EVENT_INSERT = 'mapper.insert';
    const EVENT_AFTER_INSERT = 'mapper.after_insert';
    const EVENT_UPDATE = 'mapper.update';
    const EVENT_AFTER_UPDATE = 'mapper.after_update';
    const EVENT_DELETE = 'mapper.delete';
    const EVENT_AFTER_DELETE = 'mapper.after_delete';

    /**
     * Fw instance.
     *
     * @var Fw
     */
    protected $_fw;

    /**
     * Connection instance.
     *
     * @var Connection
     */
    protected $_db;

    /**
     * Raw table name.
     *
     * @var string
     */
    protected $_table;

    /**
     * Quoted table name.
     *
     * @var string
     */
    protected $_map;

    /**
     * Table schema.
     *
     * @var array
     */
    protected $_schema;

    /**
     * Primary keys.
     *
     * @var array
     */
    protected $_keys;

    /**
     * @var array
     */
    protected $_adhoc = array();

    /**
     * @var array
     */
    protected $_props = array();

    /**
     * Loaded rows.
     *
     * @var array
     */
    protected $_rows = array();

    /**
     * Row position.
     *
     * @var array
     */
    protected $_ptr = 0;

    /**
     * Class constructor.
     *
     * @param Fw          $fw
     * @param Connection  $db
     * @param string|null $table
     * @param array|null  $fields
     * @param int         $ttl
     */
    public function __construct(Fw $fw, Connection $db, string $table = null, array $fields = null, int $ttl = 60)
    {
        $mTable = $table ?? $this->_table ?? $fw->snakeCase($fw->className($this));
        $map = Connection::DB_OCI === $db->getDriverName() ? strtoupper($mTable) : $mTable;
        $schema = $db->getTableSchema($map, $fields, $ttl);

        $this->_fw = $fw;
        $this->_db = $db;
        $this->_table = $mTable;
        $this->_schema = $schema;
        $this->_map = $db->key($map);
        $this->_keys = array_keys(array_filter(array_column($schema, 'pkey', 'name')));
        $this->reset();
    }

    /**
     * Create mapper with another table.
     *
     * @param string     $table
     * @param array|null $fields
     * @param int        $ttl
     *
     * @return Mapper
     */
    public function create(string $table, array $fields = null, int $ttl = 60): Mapper
    {
        if (is_subclass_of($table, self::class)) {
            return $this->_fw->instance($table);
        }

        return $this->_fw->instance(static::class, compact('table', 'fields', 'ttl'));
    }

    /**
     * One to one relation.
     *
     * @param string      $target
     * @param string|null $relation
     * @param array|null  $filter
     * @param array|null  $options
     *
     * @return Mapper
     */
    public function hasOne(string $target, string $relation = null, array $filter = null, array $options = null): Mapper
    {
        return $this->hasMany(...array(
            $target,
            $relation,
            $filter,
            array('limit' => 1) + (array) $options,
        ));
    }

    /**
     * One to many relation.
     *
     * @param string      $target
     * @param string|null $relation
     * @param array|null  $filter
     * @param array|null  $options
     *
     * @return Mapper
     */
    public function hasMany(string $target, string $relation = null, array $filter = null, array $options = null): Mapper
    {
        list($fk, $pk) = $this->_fw->split($relation, '=') + array($this->_table.'_id', 'id');

        $filter[$fk] = $this->get($pk);

        return $this->create($target)->load($filter, $options);
    }

    /**
     * One to one relation (inversed).
     *
     * @param string      $target
     * @param string|null $relation
     * @param array|null  $filter
     * @param array|null  $options
     *
     * @return Mapper
     */
    public function belongsTo(string $target, string $relation = null, array $filter = null, array $options = null): Mapper
    {
        $mapper = $this->create($target);

        list($fk, $pk) = $this->_fw->split($relation, '=') + array($mapper->_table.'_id', 'id');

        $filter[$pk] = $this->get($fk);

        return $mapper->load($filter, $options);
    }

    /**
     * Many to many relation, via brigde mapper.
     *
     * @param string      $target
     * @param string      $bridge
     * @param string|null $targetRelation
     * @param string|null $bridgeRelation
     * @param array|null  $filter
     * @param array|null  $options
     *
     * @return Mapper
     */
    public function belongsToMany(string $target, string $bridge, string $targetRelation = null, string $bridgeRelation = null, array $filter = null, array $options = null): Mapper
    {
        $targetMapper = $this->create($target);
        $bridgeMapper = $this->create($bridge);

        list($targetFK, $targetPK) = $this->_fw->split($targetRelation, '=') + array($targetMapper->_table.'_id', 'id');
        list($bridgeFK, $bridgePK) = $this->_fw->split($bridgeRelation, '=') + array($this->_table.'_id', 'id');

        $filter[$bridgeMapper->_table.'.'.$bridgeFK] = $this->get($targetPK);

        $qb = QueryBuilder::create($targetMapper);
        list($criteria, $args) = $qb->resolveCriteria($filter, $options);
        $sql = 'SELECT '.$qb->fields().' FROM '.$targetMapper->_map.
            ' JOIN '.$bridgeMapper->_map.' ON '.
                $bridgeMapper->_map.'.'.$this->_db->key($targetFK).'='.
                $targetMapper->_map.'.'.$this->_db->key($bridgePK).
            ' JOIN '.$this->_map.' ON '.
                $bridgeMapper->_map.'.'.$this->_db->key($bridgeFK).'='.
                $this->_map.'.'.$this->_db->key($targetPK).
            $criteria;

        $targetMapper->_rows = array_map(array($this, 'factory'), $this->_db->exec($sql, $args));
        $targetMapper->skip(0);

        return $targetMapper;
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    public function table(): string
    {
        return $this->_table;
    }

    /**
     * Returns normalized and quoted table.
     *
     * @return string
     */
    public function map(): string
    {
        return $this->_map;
    }

    /**
     * Returns schema fields and adhoc name.
     *
     * @return array
     */
    public function fields(): array
    {
        return array_keys($this->_schema + $this->_adhoc);
    }

    /**
     * Returns adhoc definitions.
     *
     * @return array
     */
    public function adhoc(): array
    {
        return $this->_adhoc;
    }

    /**
     * Returns table schema.
     *
     * @return array
     */
    public function schema(): array
    {
        return $this->_schema;
    }

    /**
     * Returns connection instance.
     *
     * @return Connection
     */
    public function db(): Connection
    {
        return $this->_db;
    }

    /**
     * Check field existance.
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->_schema) || array_key_exists($key, $this->_adhoc);
    }

    /**
     * Returns value of a field.
     *
     * @param string $key
     *
     * @return mixed
     *
     * @throws LogicException If given key is undefined
     */
    public function &get(string $key)
    {
        if (array_key_exists($key, $this->_schema)) {
            return $this->_schema[$key]['value'];
        } elseif (array_key_exists($key, $this->_adhoc)) {
            return $this->_adhoc[$key]['value'];
        } elseif (array_key_exists($key, $this->_props)) {
            return $this->_props[$key]['value'];
        } elseif (method_exists($this, $key)) {
            $res = $this->$key();
            $this->_props[$key]['self'] = true;
            $this->_props[$key]['value'] = &$res;

            return $this->_props[$key]['value'];
        }

        throw new \LogicException(sprintf('Undefined field "%s".', $key));
    }

    /**
     * Sets field value.
     *
     * @param string $key
     * @param mixed  $val
     *
     * @return Mapper
     */
    public function set(string $key, $val): Mapper
    {
        if (array_key_exists($key, $this->_schema)) {
            $field = &$this->_schema[$key];

            if (null === $val && ($field['nullable'] || null === $field['default'])) {
                $mVal = null;
            } else {
                $mVal = $this->_db->phpValue($val, $field['pdo_type']);
            }

            $field['changed'] = $field['initial'] !== $mVal || $field['default'] !== $mVal;
            $field['value'] = $mVal;

            unset($field);
        } elseif (array_key_exists($key, $this->_adhoc)) {
            // Adjust result on existing expressions
            $this->_adhoc[$key]['value'] = $val;
        } elseif (is_scalar($val)) {
            // Parenthesize expression in case it's a subquery
            $this->_adhoc[$key] = array('expr' => '('.$val.')', 'value' => null, 'name' => $key);
        } else {
            $this->_props[$key] = array('self' => false, 'value' => $val, 'name' => $key);
        }

        return $this;
    }

    /**
     * Clear field value.
     *
     * @param string $key
     *
     * @return Mapper
     */
    public function clear(string $key): Mapper
    {
        unset($this->_adhoc[$key], $this->_props[$key]);

        if (array_key_exists($key, $this->_schema)) {
            $this->_schema[$key]['value'] = $this->_schema[$key]['initial'];
            $this->_schema[$key]['changed'] = false;
        }

        return $this;
    }

    /**
     * Reset mapper values.
     *
     * @return Mapper
     */
    public function reset(): Mapper
    {
        foreach ($this->_schema as &$field) {
            $field['value'] = $field['initial'] = $field['default'];
            $field['changed'] = false;
            unset($field);
        }

        foreach ($this->_adhoc as &$field) {
            $field['value'] = null;
            unset($field);
        }

        foreach (array_keys($this->_props) as $field) {
            if ($this->_props[$field]['self']) {
                unset($this->_props[$field]);
            }
        }

        $this->_rows = array();
        $this->_ptr = 0;

        return $this;
    }

    /**
     * Check if field is required.
     *
     * @param string $key
     *
     * @return bool
     */
    public function required(string $key): bool
    {
        return isset($this->_schema[$key]) && !$this->_schema[$key]['nullable'];
    }

    /**
     * Check if field or mapper is changed.
     *
     * @param string|null $key
     *
     * @return bool
     */
    public function changed(string $key = null): bool
    {
        if ($key) {
            return isset($this->_schema[$key]) && $this->_schema[$key]['changed'];
        }

        return (bool) array_filter(array_column($this->_schema, 'changed'));
    }

    /**
     * Get primary keys value.
     *
     * @param bool $withValue
     *
     * @return array
     */
    public function keys(bool $withValue = true): array
    {
        if ($withValue) {
            $result = array();

            foreach ($this->_keys as $key) {
                $result[$key] = $this->_schema[$key]['initial'];
            }

            return $result;
        }

        return $this->_keys;
    }

    /**
     * Sets values from array.
     *
     * @param array         $source
     * @param callable|null $cb
     *
     * @return Mapper
     */
    public function fromArray(array $source, callable $cb = null): Mapper
    {
        $use = $cb ? (array) $cb($source) : $source;
        $fix = array_intersect_key($use, $this->_schema);

        foreach ($fix as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    /**
     * Cast mapper to array.
     *
     * @param string        $key
     * @param callable|null $cb
     *
     * @return array
     */
    public function toArray(string $key = 'value', callable $cb = null): array
    {
        $result = array_column($this->_schema + $this->_adhoc + $this->_props, $key, 'name');

        return $cb ? (array) $cb($result) : $result;
    }

    /**
     * Paginate records.
     *
     * @param int               $page
     * @param string|array|null $filter
     * @param array|null        $options
     * @param int               $ttl
     *
     * @return array
     */
    public function paginate(int $page = 1, $filter = null, array $options = null, int $ttl = 0): array
    {
        $use = (array) $options;
        $limit = $use['perpage'] ?? static::PAGINATION;
        $total = $this->count($filter, $options, $ttl);
        $pages = (int) ceil($total / $limit);
        $subset = array();
        $count = 0;
        $start = 0;
        $end = 0;

        if ($page > 0) {
            $offset = ($page - 1) * $limit;
            $subset = $this->findAll($filter, compact('limit', 'offset') + $use, $ttl);
            $count = count($subset);
            $start = $offset + 1;
            $end = $offset + $count;
        }

        return compact('subset', 'total', 'count', 'pages', 'page', 'start', 'end');
    }

    /**
     * Count table records.
     *
     * @param string|array|null $filter
     * @param array|null        $options
     * @param int               $ttl
     *
     * @return int
     */
    public function count($filter = null, array $options = null, int $ttl = 0): int
    {
        list($sql, $args) = QueryBuilder::create($this)->count($filter, $options);

        $res = $this->_db->exec($sql, $args, $ttl);

        return (int) $res[0]['_rows'];
    }

    /**
     * Load mapper filtered by given primary keys value.
     *
     * @param mixed ...$ids
     *
     * @return Mapper|null
     *
     * @throws LogicException If given argument count is not match with primary keys count
     */
    public function find(...$ids): ?Mapper
    {
        $vcount = count($ids);
        $pcount = count($this->_keys);

        if ($vcount !== $pcount) {
            throw new \LogicException(sprintf('Insufficient primary keys value. Expected exactly %d parameters, %d given.', $pcount, $vcount));
        }

        return $this->findOne(array_combine($this->_keys, $ids));
    }

    /**
     * Map to first record that matches criteria.
     *
     * @param string|array|null $filter
     * @param array|null        $options
     * @param int               $ttl
     *
     * @return Mapper
     */
    public function load($filter = null, array $options = null, int $ttl = 0): Mapper
    {
        $this->reset();
        $this->_rows = $this->findAll($filter, $options, $ttl);
        $this->skip(0);

        return $this;
    }

    /**
     * Return record that match criteria.
     *
     * @param string|array|null $filter
     * @param array|null        $options
     * @param int               $ttl
     *
     * @return Mapper|null
     */
    public function findOne($filter = null, array $options = null, int $ttl = 0): ?Mapper
    {
        $rows = $this->findAll($filter, array('limit' => 1) + (array) $options, $ttl);

        return array_shift($rows);
    }

    /**
     * Return records that match criteria.
     *
     * @param string|array|null $filter
     * @param array|null        $options
     * @param int               $ttl
     *
     * @return array
     */
    public function findAll($filter = null, array $options = null, int $ttl = 0): array
    {
        list($sql, $args) = QueryBuilder::create($this)->select(null, $filter, $options);

        return array_map(array($this, 'factory'), $this->_db->exec($sql, $args, $ttl));
    }

    /**
     * Decide insert or update.
     *
     * @return Mapper
     */
    public function save(): Mapper
    {
        return $this->_rows ? $this->update() : $this->insert();
    }

    /**
     * Insert record to database.
     *
     * @return Mapper
     */
    public function insert(): Mapper
    {
        if ($this->_fw->trigger(static::EVENT_INSERT, array($this))) {
            return $this;
        }

        list($sql, $args, $inc, $changes) = QueryBuilder::create($this)->insert();

        if (!$sql) {
            return $this;
        }

        $lID = $this->_db->exec($sql, $args);

        if ($inc) {
            // Reload to obtain default and auto-increment field values
            $pgSqlId = Connection::DB_PGSQL === $this->_db->getDriverName() && $lID;
            $id = $pgSqlId ? $lID[0][reset($this->_keys)] : $this->_db->getPdo()->lastInsertId();

            $this->load(array($inc => $this->_db->phpValue($id, $this->_schema[$inc]['pdo_type'])));
        } else {
            // Load manually
            $this->_schema = $changes;
            $this->_rows = array(clone $this);
            $this->skip(0);
        }

        $this->_fw->trigger(static::EVENT_AFTER_INSERT, array($this));

        return $this;
    }

    /**
     * Update record changes to database.
     *
     * @return Mapper
     */
    public function update(): Mapper
    {
        if ($this->_fw->trigger(static::EVENT_UPDATE, array($this))) {
            return $this;
        }

        list($sql, $args, $changes) = QueryBuilder::create($this)->update();

        if ($sql) {
            $this->_db->exec($sql, $args);
        }

        // reset changed flag after calling afterupdate
        $this->_schema = $changes;
        $this->_fw->trigger(static::EVENT_AFTER_UPDATE, array($this));

        return $this;
    }

    /**
     * Delete record to database.
     *
     * @param string|array|null $filter @see Connection::filter
     * @param bool              $hayati delete slowly
     *
     * @return int
     */
    public function delete($filter = null, bool $hayati = false): int
    {
        if ($filter) {
            if ($hayati) {
                $out = 0;

                foreach ($this->findAll($filter) as $mapper) {
                    $out += $mapper->delete();
                }

                return $out;
            }

            list($sql, $args) = QueryBuilder::create($this)->deleteBatch($filter);

            return (int) $this->_db->exec($sql, $args);
        } elseif (!$this->_keys || $this->dry()) {
            return 0;
        }

        if ($this->_fw->trigger(static::EVENT_DELETE, array($this))) {
            return 0;
        }

        list($sql, $args) = QueryBuilder::create($this)->delete();
        $out = (int) $this->_db->exec($sql, $args);

        $front = array_slice($this->_rows, 0, $this->_ptr, true);
        $rear = array_slice($this->_rows, $this->_ptr, null, true);
        $this->_rows = $front + $rear;

        $this->_fw->trigger(static::EVENT_AFTER_DELETE, array($this));
        $this->first();

        return $out;
    }

    /**
     * Check if mapper is loaded.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return (bool) $this->_rows;
    }

    /**
     * Valid complement.
     *
     * @return bool
     */
    public function dry(): bool
    {
        return !$this->valid();
    }

    /**
     * Return the count of records loaded.
     *
     * @return int
     */
    public function loaded(): int
    {
        return count($this->_rows);
    }

    /**
     * Returns loaded row.
     *
     * @return array
     */
    public function rows(): array
    {
        return $this->_rows;
    }

    /**
     * Map to nth record relative to current cursor position.
     *
     * @param int $offset
     *
     * @return Mapper
     */
    public function skip(int $offset = 1): Mapper
    {
        $this->_ptr += $offset;

        if (isset($this->_rows[$this->_ptr])) {
            $this->_schema = &$this->_rows[$this->_ptr]->_schema;
            $this->_adhoc = &$this->_rows[$this->_ptr]->_adhoc;
            $this->_props = &$this->_rows[$this->_ptr]->_props;
        } else {
            $this->reset();
        }

        return $this;
    }

    /**
     * Map to first record in cursor.
     *
     * @return Mapper
     */
    public function first(): Mapper
    {
        return $this->skip(-$this->_ptr);
    }

    /**
     * Map to last record in cursor.
     *
     * @return Mapper
     */
    public function last(): Mapper
    {
        return $this->skip($this->loaded() - $this->_ptr - 1);
    }

    /**
     * Map next record.
     *
     * @return Mapper
     */
    public function next(): Mapper
    {
        return $this->skip();
    }

    /**
     * Map previous record.
     *
     * @return Mapper
     */
    public function prev(): Mapper
    {
        return $this->skip(-1);
    }

    /**
     * Build mapper from record.
     *
     * @param array $row
     *
     * @return Mapper
     */
    protected function factory(array $row): Mapper
    {
        $mapper = clone $this;
        $mapper->reset();

        foreach ($row as $key => $val) {
            if (array_key_exists($key, $this->_schema)) {
                $field = $this->_schema[$key];
                $nullable = null === $val && $field['nullable'];
                $value = $nullable ? null : $this->_db->phpValue($val, $field['pdo_type']);
                $mapper->_schema[$key]['initial'] = $value;
                $mapper->_schema[$key]['value'] = $value;
            } else {
                $mapper->_adhoc[$key]['value'] = $val;
            }
        }

        $mapper->_rows = array(clone $mapper);

        $this->_fw->trigger(static::EVENT_LOAD, array($mapper));

        return $mapper;
    }

    /**
     * Convenience method for checking field.
     *
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->exists($offset);
    }

    /**
     * Convenience method for retrieving field value.
     *
     * @param string $offset
     *
     * @return mixed
     */
    public function &offsetGet($offset)
    {
        $var = &$this->get($offset);

        return $var;
    }

    /**
     * Convenience method for assigning field value.
     *
     * @param string $offset
     * @param mixed  $value
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Convenience method for removing field value.
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        $this->clear($offset);
    }

    /**
     * @see offsetExists
     */
    public function __isset($offset)
    {
        return $this->exists($offset);
    }

    /**
     * @see offsetGet
     */
    public function &__get($offset)
    {
        $var = &$this->get($offset);

        return $var;
    }

    /**
     * @see offsetSet
     */
    public function __set($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * @see offsetUnset
     */
    public function __unset($offset)
    {
        $this->clear($offset);
    }

    /**
     * Magic method proxy.
     *
     * Example:
     *
     *     getName           => get('name')
     *     findByName('foo') => find(['name'=>'foo'])
     *     loadByName('foo') => load(['name'=>'foo'])
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     *
     * @throws BadMethodCallException If method call cannot be resolved
     */
    public function __call($method, $args)
    {
        $call = null;
        $first = null;
        $mArgs = $args;
        $lmethod = strtolower($method);

        if ('get' === substr($lmethod, 0, 3)) {
            $arg = $this->_fw->snakeCase(substr($method, 3));
            $call = 'get';
        } elseif ('loadby' === substr($lmethod, 0, 6)) {
            $arg = array($this->_fw->snakeCase(substr($method, 6)) => array_shift($mArgs));
            $call = 'load';
        } elseif ('findby' === substr($lmethod, 0, 6)) {
            $arg = array($this->_fw->snakeCase(substr($method, 6)) => array_shift($mArgs));
            $call = 'findall';
        } elseif ('findoneby' === substr($lmethod, 0, 9)) {
            $arg = array($this->_fw->snakeCase(substr($method, 9)) => array_shift($mArgs));
            $call = 'findone';
        } else {
            throw new \BadMethodCallException(sprintf('Call to undefined method %s::%s.', static::class, $method));
        }

        return $this->$call($arg, ...$mArgs);
    }
}

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

namespace Fal\Stick\Library\Sql;

use Fal\Stick\Fw;
use Fal\Stick\Library\Str;
use Fal\Stick\Magic;

/**
 * Sql record mapper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Mapper extends Magic implements \IteratorAggregate
{
    // Paginate perpage
    const PAGINATE_LIMIT = 10;

    // Supported events
    const EVENT_LOAD = 'sql_mapper_load';
    const EVENT_BEFORE_INSERT = 'sql_mapper_before_insert';
    const EVENT_INSERT = 'sql_mapper_insert';
    const EVENT_BEFORE_UPDATE = 'sql_mapper_before_update';
    const EVENT_UPDATE = 'sql_mapper_update';
    const EVENT_BEFORE_DELETE = 'sql_mapper_before_delete';
    const EVENT_DELETE = 'sql_mapper_delete';

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
     * Database driver name.
     *
     * @var string
     */
    protected $_driver;

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
    protected $_fields;

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
    protected $_query = array();

    /**
     * Row position.
     *
     * @var array
     */
    protected $_ptr = 0;

    /**
     * Raw table name.
     *
     * @var string
     */
    protected $_table;

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
        $driver = $db->driver();
        $use = $table ?? $this->_table ?? Str::snakecase(Str::classname($this));
        $fix = Connection::DB_OCI === $driver ? strtoupper($use) : $use;
        $schema = $db->schema($fix, $fields, $ttl);

        $this->_fw = $fw;
        $this->_db = $db;
        $this->_driver = $driver;
        $this->_table = $use;
        $this->_fields = $schema;
        $this->_map = $db->quotekey($fix);
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
     * Retrieve external iterator for fields.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->toArray());
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
        $mapper = $this->create($target);
        $default = array($this->_table.'_id', 'id');
        list($foreignKey, $primaryKey) = array_filter(array_map('trim', explode('=', (string) $relation))) + $default;

        $filter[$foreignKey] = $this->get($primaryKey);

        return $mapper->load($filter, $options);
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
        $default = array($mapper->_table.'_id', 'id');
        list($foreignKey, $primaryKey) = array_filter(array_map('trim', explode('=', (string) $relation))) + $default;

        $filter[$primaryKey] = $this->get($foreignKey);

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

        $targetRelationDefault = array($targetMapper->_table.'_id', 'id');
        list($targetFK, $targetPK) = array_filter(array_map('trim', explode('=', (string) $targetRelation))) + $targetRelationDefault;

        $bridgeRelationDefault = array($this->_table.'_id', 'id');
        list($bridgeFK, $bridgePK) = array_filter(array_map('trim', explode('=', (string) $bridgeRelation))) + $bridgeRelationDefault;

        $filter[$bridgeMapper->_table.'.'.$bridgeFK] = $this->get($targetPK);

        $qb = QueryBuilder::create($targetMapper);
        list($criteria, $args) = $qb->filterOptions($filter, $options);
        $sql = 'SELECT '.$qb->fields().' FROM '.$targetMapper->_map.
            ' JOIN '.$bridgeMapper->_map.' ON '.
                $bridgeMapper->_map.'.'.$this->_db->quotekey($targetFK).'='.
                $targetMapper->_map.'.'.$this->_db->quotekey($bridgePK).
            ' JOIN '.$this->_map.' ON '.
                $bridgeMapper->_map.'.'.$this->_db->quotekey($bridgeFK).'='.
                $this->_map.'.'.$this->_db->quotekey($targetPK).
            $criteria;

        $targetMapper->_query = $targetMapper->factories($this->_db->exec($sql, $args));
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
        return array_keys($this->_fields + $this->_adhoc);
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
        return $this->_fields;
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
        return array_key_exists($key, $this->_fields + $this->_adhoc);
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
        if (array_key_exists($key, $this->_fields)) {
            return $this->_fields[$key]['value'];
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

        throw new \LogicException('Undefined field "'.$key.'".');
    }

    /**
     * Sets field value.
     *
     * @param string $key
     * @param mixed  $val
     *
     * @return Magic
     */
    public function set(string $key, $val): Magic
    {
        if (array_key_exists($key, $this->_fields)) {
            $val = null === $val && $this->_fields[$key]['nullable'] ? null : $this->_db->phpValue($this->_fields[$key]['pdo_type'], $val);
            $this->_fields[$key]['changed'] = $this->_fields[$key]['initial'] !== $val || $this->_fields[$key]['default'] !== $val;
            $this->_fields[$key]['value'] = $val;
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
     * @return Magic
     */
    public function clear(string $key): Magic
    {
        unset($this->_adhoc[$key], $this->_props[$key]);

        if (array_key_exists($key, $this->_fields)) {
            $this->_fields[$key]['value'] = $this->_fields[$key]['initial'];
            $this->_fields[$key]['changed'] = false;
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
        foreach ($this->_fields as &$field) {
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

        $this->_query = array();
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
        return isset($this->_fields[$key]) && !$this->_fields[$key]['nullable'];
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
            return isset($this->_fields[$key]) && $this->_fields[$key]['changed'];
        }

        return (bool) array_filter(array_column($this->_fields, 'changed'));
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
                $result[$key] = $this->_fields[$key]['initial'];
            }

            return $result;
        }

        return $this->_keys;
    }

    /**
     * Sets values from array.
     *
     * @param array         $source
     * @param callable|null $transformer
     *
     * @return Mapper
     */
    public function fromArray(array $source, callable $transformer = null): Mapper
    {
        $use = $transformer ? $transformer($source) : $source;
        $fix = array_intersect_key($use, $this->_fields);

        foreach ($fix as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    /**
     * Cast mapper to array.
     *
     * @param callable|null $func
     *
     * @return array
     */
    public function toArray(callable $transformer = null): array
    {
        $result = array_column($this->_fields + $this->_adhoc + $this->_props, 'value', 'name');

        return $transformer ? $transformer($result) : $result;
    }

    /**
     * Check if mapper is loaded.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return (bool) $this->_query;
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
        $limit = $use['perpage'] ?? static::PAGINATE_LIMIT;
        $total = $this->count($filter, $options, $ttl);
        $pages = (int) ceil($total / $limit);
        $subset = array();
        $count = 0;
        $start = 0;
        $end = 0;

        if ($page > 0) {
            $offset = ($page - 1) * $limit;
            $subset = $this->find($filter, compact('limit', 'offset') + $use, $ttl);
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
        $shouldHack = in_array($this->_driver, array(Connection::DB_MSSQL, Connection::DB_DBLIB, Connection::DB_SQLSRV));
        $fields = substr('TOP 100 PERCENT *', 16 * ((bool) !$shouldHack));
        list($sql, $args) = QueryBuilder::create($this)->select($fields, $filter, $options);

        $sql = 'SELECT COUNT(*) AS '.$this->_db->quotekey('_rows').
               ' FROM ('.$sql.') AS '.$this->_db->quotekey('_temp');
        $res = $this->_db->exec($sql, $args, $ttl);

        return (int) $res[0]['_rows'];
    }

    /**
     * Load mapper filtered by given primary keys value.
     *
     * @param mixed ...$ids
     *
     * @return Mapper
     *
     * @throws LogicException If given argument count is not match with primary keys count
     */
    public function withId(...$ids): Mapper
    {
        $vcount = count($ids);
        $pcount = count($this->_keys);

        if ($vcount !== $pcount) {
            throw new \LogicException('Insufficient primary keys value. Expect exactly '.$pcount.' parameters, '.$vcount.' given.');
        }

        return $this->load(array_combine($this->_keys, $ids));
    }

    /**
     * Return the count of records loaded.
     *
     * @return int
     */
    public function loaded(): int
    {
        return count($this->_query);
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
        $this->_query = $this->find($filter, $options, $ttl);
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
        $rows = $this->find($filter, array('limit' => 1) + (array) $options, $ttl);

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
    public function find($filter = null, array $options = null, int $ttl = 0): array
    {
        $qb = QueryBuilder::create($this);
        list($sql, $args) = $qb->select($qb->fields(), $filter, $options);

        return $this->factories($this->_db->exec($sql, $args, $ttl));
    }

    /**
     * Decide insert or update.
     *
     * @return Mapper
     */
    public function save(): Mapper
    {
        return $this->_query ? $this->update() : $this->insert();
    }

    /**
     * Insert record to database.
     *
     * @return Mapper
     */
    public function insert(): Mapper
    {
        if ($this->_fw->trigger(static::EVENT_BEFORE_INSERT, array($this))) {
            return $this;
        }

        list($sql, $args, $inc, $changes) = QueryBuilder::create($this)->insert();

        if (!$sql) {
            return $this;
        }

        $lID = $this->_db->exec($sql, $args);

        if ($inc) {
            // Reload to obtain default and auto-increment field values
            $pgSqlId = Connection::DB_PGSQL === $this->_db->driver() && $lID;
            $id = $pgSqlId ? $lID[0][reset($this->_keys)] : $this->_db->pdo()->lastinsertid();

            $this->load(array($inc => $this->_db->phpValue($this->_fields[$inc]['pdo_type'], $id)));
        } else {
            // Load manually
            $this->_fields = $changes;
            $this->_query = array(clone $this);
            $this->skip(0);
        }

        $this->_fw->trigger(static::EVENT_INSERT, array($this));

        return $this;
    }

    /**
     * Update record changes to database.
     *
     * @return Mapper
     */
    public function update(): Mapper
    {
        if ($this->_fw->trigger(static::EVENT_BEFORE_UPDATE, array($this))) {
            return $this;
        }

        list($sql, $args, $changes) = QueryBuilder::create($this)->update();

        if ($sql) {
            $this->_db->exec($sql, $args);
        }

        // reset changed flag after calling afterupdate
        $this->_fields = $changes;
        $this->_fw->trigger(static::EVENT_UPDATE, array($this));

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

                foreach ($this->find($filter) as $mapper) {
                    $out += $mapper->delete();
                }

                return $out;
            }

            $sql = 'DELETE FROM '.$this->_map;
            $args = QueryBuilder::create($this)->filter($filter);
            $criteria = $args ? ' WHERE '.array_shift($args) : '';

            return (int) $this->_db->exec($sql.$criteria, $args);
        } elseif (!$this->_keys || $this->dry()) {
            return 0;
        }

        if ($this->_fw->trigger(static::EVENT_BEFORE_DELETE, array($this))) {
            return 0;
        }

        list($sql, $args) = QueryBuilder::create($this)->delete();
        $out = (int) $this->_db->exec($sql, $args);

        $front = array_slice($this->_query, 0, $this->_ptr, true);
        $rear = array_slice($this->_query, $this->_ptr, null, true);
        $this->_query = $front + $rear;

        $this->_fw->trigger(static::EVENT_DELETE, array($this));
        $this->first();

        return $out;
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

        if (isset($this->_query[$this->_ptr])) {
            $this->_fields = &$this->_query[$this->_ptr]->_fields;
            $this->_adhoc = &$this->_query[$this->_ptr]->_adhoc;
            $this->_props = &$this->_query[$this->_ptr]->_props;
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
     * Construct mapper from records.
     *
     * @param array $rows
     *
     * @return array
     */
    protected function factories(array $rows): array
    {
        return array_map(array($this, 'factory'), $rows);
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
            if (array_key_exists($key, $this->_fields)) {
                $field = $this->_fields[$key];
                $nullable = null === $val && $field['nullable'];
                $value = $nullable ? null : $this->_db->phpValue($field['pdo_type'], $val);
                $mapper->_fields[$key]['initial'] = $value;
                $mapper->_fields[$key]['value'] = $value;
            } else {
                $mapper->_adhoc[$key]['value'] = $val;
            }
        }

        $mapper->_query = array(clone $mapper);

        $this->_fw->trigger(static::EVENT_LOAD, array($mapper));

        return $mapper;
    }

    /**
     * Returns normalized field.
     *
     * @param string $str
     * @param int    $start
     *
     * @return string
     */
    protected function fieldFix(string $str, int $start): string
    {
        $field = substr($str, $start);

        return isset($this->_fields[$field]) ? $field : Str::snakecase($field);
    }

    /**
     * Return modified field arg.
     *
     * @param string $field
     * @param array  $args
     *
     * @return array
     */
    protected function fieldArgs(string $field, array $args): array
    {
        if ($args) {
            $first = array_shift($args);
            array_unshift($args, array($field => $first));
        }

        return $args;
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
        $lmethod = strtolower($method);
        $call = $method;
        $mArgs = $args;

        if ('get' === substr($lmethod, 0, 3)) {
            array_unshift($mArgs, $this->fieldFix($method, 3));
            $call = 'get';
        } elseif ('findby' === substr($lmethod, 0, 6)) {
            $mArgs = $this->fieldArgs($this->fieldFix($method, 6), $args);
            $call = 'find';
        } elseif ('findoneby' === substr($lmethod, 0, 9)) {
            $mArgs = $this->fieldArgs($this->fieldFix($method, 9), $args);
            $call = 'findone';
        } elseif ('loadby' === substr($lmethod, 0, 6)) {
            $mArgs = $this->fieldArgs($this->fieldFix($method, 6), $args);
            $call = 'load';
        } else {
            throw new \BadMethodCallException('Call to undefined method '.static::class.'::'.$method.'.');
        }

        return $this->$call(...$mArgs);
    }
}

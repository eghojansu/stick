<?php

declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Sql;

use Fal\Stick\Helper;

/**
 * Sql record mapper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Mapper implements \ArrayAccess
{
    // Paginate perpage
    const PERPAGE = 10;

    // Supported events
    const EVENT_LOAD = 'load';
    const EVENT_BEFOREINSERT = 'beforeinsert';
    const EVENT_INSERT = 'insert';
    const EVENT_BEFOREUPDATE = 'beforeupdate';
    const EVENT_UPDATE = 'update';
    const EVENT_BEFOREDELETE = 'beforedelete';
    const EVENT_DELETE = 'delete';

    /**
     * @var Connection
     */
    protected $db;

    /**
     * @var string
     */
    protected $driver;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $map;

    /**
     * @var array
     */
    protected $fields;

    /**
     * Primary keys.
     *
     * @var array
     */
    protected $keys;

    /**
     * @var array
     */
    protected $hive;

    /**
     * @var array
     */
    protected $adhoc = [];

    /**
     * @var array
     */
    protected $props = [];

    /**
     * @var array
     */
    protected $triggers = [];

    /**
     * Class constructor.
     *
     * @param Connection  $db
     * @param string|null $table
     * @param array|null  $fields
     * @param int         $ttl
     */
    public function __construct(Connection $db, string $table = null, array $fields = null, int $ttl = 60)
    {
        $this->db = $db;
        $this->hive = [
            'fields' => $fields,
            'ttl' => $ttl,
            'loaded' => false,
        ];
        $this->driver = $db->getDriver();
        $this->setTable($table ?? $this->table ?? Helper::snakecase(Helper::classname($this)));
    }

    /**
     * Get table name.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Set table name and load its schema.
     *
     * @param string $table
     *
     * @return Mapper
     */
    public function setTable(string $table): Mapper
    {
        $use = Connection::DB_OCI === $this->driver ? strtoupper($table) : $table;
        $prev = $this->table;

        $this->table = $use;
        $this->map = $this->db->quotekey($use);
        $this->fields = $this->db->schema($use, $this->hive['fields'], $this->hive['ttl']);
        $this->reset();

        if (!$this->keys || ($prev && $this->table !== $prev)) {
            $this->keys = [];

            foreach ($this->fields as $key => $value) {
                if ($value['pkey']) {
                    $this->keys[] = $key;
                }
            }
        }

        return $this;
    }

    /**
     * Create mapper with other table.
     *
     * @param string     $table
     * @param array|null $fields
     * @param int        $ttl
     *
     * @return Mapper
     */
    public function withTable(string $table, array $fields = null, int $ttl = 60): Mapper
    {
        return new self($this->db, $table, $fields, $ttl);
    }

    /**
     * Get fields name.
     *
     * @return array
     */
    public function getFields(): array
    {
        return array_keys($this->fields + $this->adhoc);
    }

    /**
     * Get table schema.
     *
     * @return array
     */
    public function getSchema(): array
    {
        return $this->fields;
    }

    /**
     * Get connection instance.
     *
     * @return Connection
     */
    public function getConnection(): Connection
    {
        return $this->db;
    }

    /**
     * Set event listener.
     *
     * Listener should return true to give control back to the caller
     *
     * @param string|array $events
     * @param callable     $trigger
     *
     * @return Mapper
     */
    public function on($events, callable $trigger): Mapper
    {
        foreach (Helper::reqarr($events) as $event) {
            $this->triggers[$event][] = $trigger;
        }

        return $this;
    }

    /**
     * Trigger event.
     *
     * @param string $event
     * @param array  $args
     *
     * @return bool
     */
    public function trigger(string $event, array $args = []): bool
    {
        if (!isset($this->triggers[$event])) {
            return false;
        }

        foreach ($this->triggers[$event] as $func) {
            if (true === call_user_func_array($func, $args)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Create has one Relation.
     *
     * @param string|Mapper $target   table name, Mapper instance or names
     * @param string|null   $targetId
     * @param string|null   $refId
     * @param array|null    $options
     *
     * @return Relation
     */
    public function hasOne($target = null, string $targetId = null, string $refId = null, array $options = null): Relation
    {
        return $this->createRelation($target, $targetId, $refId, null, null, $options);
    }

    /**
     * Create has many Relation.
     *
     * @param string|Mapper $target
     * @param string|null   $targetId
     * @param string|null   $refId
     * @param string|array  $pivot
     * @param array|null    $options
     *
     * @return Relation
     */
    public function hasMany($target = null, string $targetId = null, string $refId = null, $pivot = null, array $options = null): Relation
    {
        return $this->createRelation($target, $targetId, $refId, $pivot, false, $options);
    }

    /**
     * Create Relation.
     *
     * @param string|Mapper $target
     * @param string|null   $targetId
     * @param string|null   $refId
     * @param [type]        $pivot
     * @param bool|null     $one
     * @param array|null    $options
     *
     * @return Relation
     *
     * @throws UnexpectedValueException If target is not instance of Mapper
     */
    public function createRelation($target = null, string $targetId = null, string $refId = null, $pivot = null, bool $one = null, array $options = null): Relation
    {
        $use = $target;

        if (is_string($target)) {
            if (is_a($target, self::class, true) || is_subclass_of($target, self::class)) {
                $use = new $target($this->db);
            } elseif (class_exists($target)) {
                throw new \UnexpectedValueException('Target must be instance of '.self::class.' or a name of valid table, given '.$target);
            } else {
                $use = $this->withTable($target);
            }
        } elseif ($target && !$target instanceof $this) {
            throw new \UnexpectedValueException('Target must be instance of '.self::class.' or a name of valid table, given '.get_class($target));
        }

        return new Relation($this, $use, $targetId, $refId, $pivot, $one, $options);
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
        return array_key_exists($key, $this->fields + $this->adhoc);
    }

    /**
     * Get value of a field.
     *
     * @param string $key
     *
     * @return mixed
     *
     * @throws LogicException If given key is undefined
     */
    public function &get(string $key)
    {
        if (array_key_exists($key, $this->fields)) {
            return $this->fields[$key]['value'];
        } elseif (array_key_exists($key, $this->adhoc)) {
            return $this->adhoc[$key]['value'];
        } elseif (array_key_exists($key, $this->props)) {
            return $this->props[$key]['value'];
        } elseif (method_exists($this, $key)) {
            $res = $this->$key();
            $this->props[$key]['self'] = true;
            $this->props[$key]['value'] = &$res;

            return $this->props[$key]['value'];
        }

        throw new \LogicException('Undefined field '.$key);
    }

    /**
     * Set field value.
     *
     * @param string $key
     * @param mixed  $val
     *
     * @return Mapper
     */
    public function set(string $key, $val): Mapper
    {
        if (array_key_exists($key, $this->fields)) {
            $val = is_null($val) && $this->fields[$key]['nullable'] ? $val : $this->db->phpValue($this->fields[$key]['pdo_type'], $val);
            $this->fields[$key]['changed'] = $this->fields[$key]['initial'] !== $val || $this->fields[$key]['default'] !== $val;
            $this->fields[$key]['value'] = $val;
        } elseif (isset($this->adhoc[$key])) {
            // Adjust result on existing expressions
            $this->adhoc[$key]['value'] = $val;
        } elseif (is_string($val)) {
            // Parenthesize expression in case it's a subquery
            $this->adhoc[$key] = ['expr' => '('.$val.')', 'value' => null];
        } else {
            $this->props[$key] = ['self' => false, 'value' => $val];
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
        if (array_key_exists($key, $this->fields)) {
            $this->fields[$key]['value'] = $this->fields[$key]['initial'];
            $this->fields[$key]['changed'] = false;
        } elseif (array_key_exists($key, $this->adhoc)) {
            unset($this->adhoc[$key]);
        } else {
            unset($this->props[$key]);
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
        foreach ($this->fields as &$field) {
            $field['value'] = $field['initial'] = $field['default'];
            $field['changed'] = false;
            unset($field);
        }

        foreach ($this->adhoc as &$field) {
            $field['value'] = null;
            unset($field);
        }

        foreach ($this->props as $key => $field) {
            if ($field['self']) {
                unset($this->props[$key]);
            }
        }

        $this->hive['loaded'] = false;

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
        return !($this->fields[$key]['nullable'] ?? true);
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
            return $this->fields[$key]['changed'] ?? false;
        }

        foreach ($this->fields as $key => $value) {
            if ($value['changed']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get primary keys value.
     *
     * @return array
     */
    public function keys(): array
    {
        $res = [];

        foreach ($this->keys as $key) {
            $res[$key] = $this->fields[$key]['initial'];
        }

        return $res;
    }

    /**
     * Get primary keys fields.
     *
     * @return array
     */
    public function getKeys(): array
    {
        return $this->keys;
    }

    /**
     * Set primary keys fields.
     *
     * @param array $keys
     *
     * @return Mapper
     *
     * @throws LogicException If given key is not valid field
     */
    public function setKeys(array $keys): Mapper
    {
        foreach ($keys as $key) {
            if (!isset($this->fields[$key])) {
                throw new \LogicException('Invalid key '.$key);
            }
        }

        $this->keys = $keys;

        return $this;
    }

    /**
     * Set values from array.
     *
     * @param array         $source
     * @param callable|null $func
     *
     * @return Mapper
     */
    public function fromArray(array $source, callable $func = null): Mapper
    {
        foreach ($func ? call_user_func_array($func, [$source]) : $source as $key => $value) {
            if (isset($this->fields[$key])) {
                $this->set($key, $value);
            }
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
    public function toArray(callable $func = null): array
    {
        $result = [];
        foreach ($this->fields + $this->adhoc + $this->props as $key => $value) {
            $result[$key] = $value['value'];
        }

        return $func ? call_user_func_array($func, [$result]) : $result;
    }

    /**
     * Check if mapper is loaded.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return $this->hive['loaded'];
    }

    /**
     * Valid complement.
     *
     * @return bool
     */
    public function dry(): bool
    {
        return !$this->hive['loaded'];
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
        $limit = $use['perpage'] ?? static::PERPAGE;
        $total = $this->count($filter, $options, $ttl);
        $pages = (int) ceil($total / $limit);
        $subset = [];
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
        $fields = (in_array($this->driver, [Connection::DB_MSSQL, Connection::DB_DBLIB, Connection::DB_SQLSRV]) ? 'TOP 100 PERCENT ' : '').'*'.$this->stringifyAdhoc();
        list($sql, $args) = $this->stringify($fields, $filter, $options);

        $sql = 'SELECT COUNT(*) AS '.$this->db->quotekey('_rows').
            ' FROM ('.$sql.') AS '.$this->db->quotekey('_temp');
        $res = $this->db->exec($sql, $args, $ttl);

        return (int) $res[0]['_rows'];
    }

    /**
     * Load mapper.
     *
     * @param string|array|null $filter
     * @param array|null        $options
     * @param int               $ttl
     *
     * @return Mapper
     */
    public function load($filter = null, array $options = null, int $ttl = 0): Mapper
    {
        $found = $this->reset()->findAll($filter, ['limit' => 1] + (array) $options, $ttl);

        if ($found) {
            $this->fields = $found[0]->fields;
            $this->adhoc = $found[0]->adhoc;
            $this->props = $found[0]->props;
            $this->hive = $found[0]->hive;
        }

        return $this;
    }

    /**
     * Load mapper filtered by given primary keys value.
     *
     * @param mixed $ids
     *
     * @return Mapper
     *
     * @throws ArgumentCountError If given argument count is not match with primary keys count
     */
    public function find(...$ids): Mapper
    {
        $vcount = count($ids);
        $pcount = count($this->keys);

        if ($vcount !== $pcount) {
            throw new \ArgumentCountError(__METHOD__.' expect exactly '.$pcount.' arguments, '.$vcount.' given');
        }

        return $this->load(array_combine($this->keys, $ids));
    }

    /**
     * Find records.
     *
     * @param string|array|null $filter
     * @param array|null        $options
     * @param int               $ttl
     *
     * @return array
     */
    public function findAll($filter = null, array $options = null, int $ttl = 0): array
    {
        $fields = isset($options['group']) && !in_array($this->driver, [Connection::DB_MYSQL, Connection::DB_SQLITE]) ? $options['group'] : implode(',', array_map([$this->db, 'quotekey'], array_keys($this->fields)));
        list($sql, $args) = $this->stringify($fields.$this->stringifyAdhoc(), $filter, $options);

        return array_map([$this, 'factory'], $this->db->exec($sql, $args, $ttl));
    }

    /**
     * Decide insert or update.
     *
     * @return Mapper
     */
    public function save(): Mapper
    {
        return $this->hive['loaded'] ? $this->update() : $this->insert();
    }

    /**
     * Insert record to database.
     *
     * @return Mapper
     */
    public function insert(): Mapper
    {
        $args = [];
        $ctr = 0;
        $fields = '';
        $values = '';
        $filter = [];
        $ckeys = [];
        $inc = null;

        if ($this->trigger(self::EVENT_BEFOREINSERT, [$this, $this->keys()])) {
            return $this;
        }

        foreach ($this->fields as $key => $field) {
            if ($field['pkey']) {
                if (!$inc && \PDO::PARAM_INT == $field['pdo_type'] && empty($field['value']) && !$field['nullable']) {
                    $inc = $key;
                }

                $filter[$key] = $this->db->phpValue($field['pdo_type'], $field['value']);
            }

            if ($field['changed'] && $key !== $inc) {
                $fields .= ','.$this->db->quotekey($key);
                $values .= ','.'?';
                $args[++$ctr] = [$field['value'], $field['pdo_type']];
                $ckeys[] = $key;
            }
        }

        if (!$fields) {
            return $this;
        }

        $sql = 'INSERT INTO '.$this->map.' ('.ltrim($fields, ',').') '.'VALUES ('.ltrim($values, ',').')';
        $prefix = in_array($this->driver, [Connection::DB_MSSQL, Connection::DB_DBLIB, Connection::DB_SQLSRV]) && array_intersect($this->keys, $ckeys) ? 'SET IDENTITY_INSERT '.$this->map.' ON;' : '';
        $suffix = Connection::DB_PGSQL === $this->driver ? ' RETURNING '.$this->db->quotekey(reset($this->keys)) : '';

        $lID = $this->db->exec($prefix.$sql.$suffix, $args);
        $id = Connection::DB_PGSQL === $this->driver && $lID ? $lID[0][reset($this->keys)] : $this->db->pdo()->lastinsertid();

        // Reload to obtain default and auto-increment field values
        if ($inc || $filter) {
            $this->load($inc ? [$inc => $this->db->phpValue($this->fields[$inc]['pdo_type'], $id)] : $filter);
        }

        $this->trigger(self::EVENT_INSERT, [$this, $this->keys()]);

        return $this;
    }

    /**
     * Update record changes to database.
     *
     * @return Mapper
     */
    public function update(): Mapper
    {
        $args = [];
        $ctr = 0;
        $pairs = '';
        $filter = '';

        if ($this->trigger(self::EVENT_BEFOREUPDATE, [$this, $this->keys()])) {
            return $this;
        }

        foreach ($this->fields as $key => $field) {
            if ($field['changed']) {
                $pairs .= ($pairs ? ',' : '').$this->db->quotekey($key).'=?';
                $args[++$ctr] = [$field['value'], $field['pdo_type']];
            }
        }

        foreach ($this->keys as $key) {
            $filter .= ($filter ? ' AND ' : ' WHERE ').$this->db->quotekey($key).'=?';
            $args[++$ctr] = [$this->fields[$key]['initial'], $this->fields[$key]['pdo_type']];
        }

        if ($pairs) {
            $sql = 'UPDATE '.$this->map.' SET '.$pairs.$filter;

            $this->db->exec($sql, $args);
        }

        if ($this->trigger(self::EVENT_UPDATE, [$this, $this->keys()])) {
            return $this;
        }

        // reset changed flag after calling afterupdate
        foreach ($this->fields as $key => &$field) {
            $field['initial'] = $field['value'];
            $field['changed'] = false;
            unset($field);
        }

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

            $args = $this->db->buildFilter($filter);
            $sql = 'DELETE FROM '.$this->map.($args ? ' WHERE '.array_shift($args) : '');

            return (int) $this->db->exec($sql, $args);
        }

        $args = [];
        $ctr = 0;
        $out = 0;

        foreach ($this->keys as $key) {
            $filter .= ($filter ? ' AND ' : '').$this->db->quotekey($key).'=?';
            $args[++$ctr] = [$this->fields[$key]['initial'], $this->fields[$key]['pdo_type']];
        }

        if ($this->trigger(self::EVENT_BEFOREDELETE, [$this, $this->keys()])) {
            return 0;
        }

        if ($filter) {
            $out = (int) $this->db->exec('DELETE FROM '.$this->map.' WHERE '.$filter, $args);
        }

        $this->trigger(self::EVENT_DELETE, [$this, $this->keys()]);
        $this->reset();

        return $out;
    }

    /**
     * Build select query.
     *
     * @param string            $fields
     * @param string|array|null $filter
     * @param array|null        $options
     *
     * @return array
     */
    protected function stringify(string $fields, $filter = null, array $options = null): array
    {
        $use = ($options ?? []) + [
            'group' => null,
            'having' => null,
            'order' => null,
            'limit' => 0,
            'offset' => 0,
        ];

        $args = [];
        $sql = 'SELECT '.$fields.' FROM '.$this->map;

        $f = $this->db->buildFilter($filter);
        if ($f) {
            $sql .= ' WHERE '.array_shift($f);
            $args = array_merge($args, $f);
        }

        if ($use['group']) {
            $sql .= ' GROUP BY '.$use['group'];
        }

        $f = $this->db->buildFilter($use['having']);
        if ($f) {
            $sql .= ' HAVING '.array_shift($f);
            $args = array_merge($args, $f);
        }

        if ($use['order']) {
            $order = ' ORDER BY '.$use['order'];
        }

        // SQL Server fixes
        // We skip this part to test
        // @codeCoverageIgnoreStart
        if (in_array($this->driver, [Connection::DB_MSSQL, Connection::DB_SQLSRV, Connection::DB_ODBC]) && ($use['limit'] || $use['offset'])) {
            // order by pkey when no ordering option was given
            if (!$use['order'] && $this->keys) {
                $order = ' ORDER BY '.implode(',', array_map([$this->db, 'quotekey'], $this->keys));
            }

            $ofs = (int) $use['offset'];
            $lmt = (int) $use['limit'];

            if (strncmp($this->db->getVersion(), '11', 2) >= 0) {
                // SQL Server >= 2012
                $sql .= $order.' OFFSET '.$ofs.' ROWS';

                if ($lmt) {
                    $sql .= ' FETCH NEXT '.$lmt.' ROWS ONLY';
                }
            } else {
                // Require primary keys or order clause
                // SQL Server 2008
                $sql = preg_replace(
                    '/SELECT/',
                    'SELECT '.
                    ($lmt > 0 ? 'TOP '.($ofs + $lmt) : '').' ROW_NUMBER() '.
                    'OVER ('.$order.') AS rnum,',
                    $sql.$order,
                    1
                );
                $sql = 'SELECT * FROM ('.$sql.') x WHERE rnum > '.$ofs;
            }
        } else {
            $sql .= ($order ?? '');

            if ($use['limit']) {
                $sql .= ' LIMIT '.(int) $use['limit'];
            }

            if ($use['offset']) {
                $sql .= ' OFFSET '.(int) $use['offset'];
            }
        }
        // @codeCoverageIgnoreEnd

        return [$sql, $args];
    }

    /**
     * Convert adhoc as select column.
     *
     * @return string
     */
    protected function stringifyAdhoc(): string
    {
        $res = '';

        foreach ($this->adhoc as $key => $field) {
            $res .= ','.$field['expr'].' AS '.$this->db->quotekey($key);
        }

        return $res;
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
        $mapper->hive['loaded'] = true;

        foreach ($row as $key => $val) {
            if (array_key_exists($key, $this->fields)) {
                $mapper->fields[$key]['value'] = $mapper->fields[$key]['initial'] = is_null($val) && $this->fields[$key]['nullable'] ? $val : $this->db->phpValue($this->fields[$key]['pdo_type'], $val);
            } else {
                $mapper->adhoc[$key]['value'] = $val;
            }
        }

        $this->trigger(self::EVENT_LOAD, [$mapper]);

        return $mapper;
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
            array_unshift($args, [$field => $first]);
        }

        return $args;
    }

    /**
     * Convenience method to check field existance.
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
     * Convenience method to get field value.
     *
     * @param string $offset
     *
     * @return mixed
     */
    public function &offsetGet($offset)
    {
        $ref = &$this->get($offset);

        return $ref;
    }

    /**
     * Convenience method to set field value.
     *
     * @param string $offset
     * @param mixed  $value
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Convenience method to clear field value.
     *
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        $this->clear($offset);
    }

    /**
     * Magic method proxy.
     *
     * Example:
     *
     *     getName      => get('name')
     *     findByName   => findAll(['name'=>'value'])
     *     loadByName   => load(['name'=>'value'])
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     *
     * @throws BadMethodCallException If method is undefined
     */
    public function __call($method, array $args)
    {
        if (Helper::istartswith($method, 'get')) {
            $field = Helper::snakecase(Helper::icutafter($method, 'get'));
            array_unshift($args, $field);

            return $this->get(...$args);
        } elseif (Helper::istartswith($method, 'findby')) {
            $field = Helper::snakecase(Helper::icutafter($method, 'findby'));
            $args = $this->fieldArgs($field, $args);

            return $this->findAll(...$args);
        } elseif (Helper::istartswith($method, 'loadby')) {
            $field = Helper::snakecase(Helper::icutafter($method, 'loadby'));
            $args = $this->fieldArgs($field, $args);

            return $this->load(...$args);
        }

        throw new \BadMethodCallException('Call to undefined method '.static::class.'::'.$method);
    }
}

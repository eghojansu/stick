<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Sql;

use Fal\Stick\App;

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
    const EVENT_LOAD = 'sql_mapper_load';
    const EVENT_BEFOREINSERT = 'sql_mapper_before_insert';
    const EVENT_INSERT = 'sql_mapper_insert';
    const EVENT_BEFOREUPDATE = 'sql_mapper_before_update';
    const EVENT_UPDATE = 'sql_mapper_update';
    const EVENT_BEFOREDELETE = 'sql_mapper_before_delete';
    const EVENT_DELETE = 'sql_mapper_delete';

    /**
     * @var Connection
     */
    protected $db;

    /**
     * Raw table name.
     *
     * @var string
     */
    protected $table;

    /**
     * Quoted table name.
     *
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
    protected $adhoc = array();

    /**
     * @var array
     */
    protected $props = array();

    /**
     * Query results.
     *
     * @var array
     */
    protected $query = array();

    /**
     * Current position.
     *
     * @var int
     */
    protected $ptr = 0;

    /**
     * Class constructor.
     *
     * @param Connection  $db
     * @param string|null $table
     * @param mixed       $fields
     * @param int         $ttl
     */
    public function __construct(Connection $db, $table = null, $fields = null, $ttl = 60)
    {
        $useTable = $this->table ?: ($table ?: App::snakecase(App::classname($this)));
        $this->db = $db;
        $this->setTable($useTable, $fields, $ttl);
    }

    /**
     * Get table name.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Sets table name and load its schema.
     *
     * @param string $table
     * @param mixed  $fields
     * @param int    $ttl
     *
     * @return Mapper
     */
    public function setTable($table, $fields = null, $ttl = 60)
    {
        $fix = Connection::DB_OCI === $this->db->getDriver() ? strtoupper($table) : $table;
        $prev = $this->table;

        $this->table = $fix;
        $this->map = $this->db->quotekey($fix);
        $this->fields = $this->db->schema($fix, $fields, $ttl);
        $this->keys = array_keys(array_filter(App::column($this->fields, 'pkey')));
        $this->reset();

        return $this;
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
    public function create($table, array $fields = null, $ttl = 60)
    {
        return new self($this->db, $table, $fields, $ttl);
    }

    /**
     * Get fields name.
     *
     * @return array
     */
    public function getFields()
    {
        return array_keys($this->fields + $this->adhoc);
    }

    /**
     * Get table schema.
     *
     * @return array
     */
    public function getSchema()
    {
        return $this->fields;
    }

    /**
     * Get connection instance.
     *
     * @return Connection
     */
    public function db()
    {
        return $this->db;
    }

    /**
     * Check field existance.
     *
     * @param string $key
     *
     * @return bool
     */
    public function exists($key)
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
    public function &get($key)
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

        throw new \LogicException('Undefined field "'.$key.'".');
    }

    /**
     * Sets field value.
     *
     * @param string $key
     * @param mixed  $val
     *
     * @return Mapper
     */
    public function set($key, $val)
    {
        if (array_key_exists($key, $this->fields)) {
            $val = null === $val && $this->fields[$key]['nullable'] ? null : $this->db->phpValue($this->fields[$key]['pdo_type'], $val);
            $this->fields[$key]['changed'] = $this->fields[$key]['initial'] !== $val || $this->fields[$key]['default'] !== $val;
            $this->fields[$key]['value'] = $val;
        } elseif (array_key_exists($key, $this->adhoc)) {
            // Adjust result on existing expressions
            $this->adhoc[$key]['value'] = $val;
        } elseif (is_scalar($val)) {
            // Parenthesize expression in case it's a subquery
            $this->adhoc[$key] = array('expr' => '('.$val.')', 'value' => null);
        } else {
            $this->props[$key] = array('self' => false, 'value' => $val);
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
    public function clear($key)
    {
        unset($this->adhoc[$key], $this->props[$key]);

        if (array_key_exists($key, $this->fields)) {
            $this->fields[$key]['value'] = $this->fields[$key]['initial'];
            $this->fields[$key]['changed'] = false;
        }

        return $this;
    }

    /**
     * Reset mapper values.
     *
     * @return Mapper
     */
    public function reset()
    {
        foreach ($this->fields as &$field) {
            $field['value'] = $field['initial'] = $field['default'];
            $field['changed'] = false;
            unset($field);
        }

        $nullAdhoc = array_fill_keys(array_keys($this->adhoc), array('value' => null));
        $selfProps = array_filter(App::column($this->props, 'self'));
        $this->adhoc = array_replace_recursive($this->adhoc, $nullAdhoc);
        $this->props = array_intersect_key($this->props, $selfProps);
        $this->query = array();
        $this->ptr = 0;

        return $this;
    }

    /**
     * Check if field is required.
     *
     * @param string $key
     *
     * @return bool
     */
    public function required($key)
    {
        return isset($this->fields[$key]) && !$this->fields[$key]['nullable'];
    }

    /**
     * Check if field or mapper is changed.
     *
     * @param string|null $key
     *
     * @return bool
     */
    public function changed($key = null)
    {
        if ($key) {
            return isset($this->fields[$key]) && $this->fields[$key]['changed'];
        }

        return (bool) array_filter(array_column($this->fields, 'changed'));
    }

    /**
     * Get primary keys value.
     *
     * @return array
     */
    public function keys()
    {
        $keys = array_flip($this->keys);

        return App::column(array_merge($keys, array_intersect_key($this->fields, $keys)), 'initial');
    }

    /**
     * Sets values from array.
     *
     * @param array         $source
     * @param callable|null $transformer
     *
     * @return Mapper
     */
    public function fromArray(array $source, $transformer = null)
    {
        $use = $transformer ? call_user_func_array($transformer, array($source)) : $source;
        $fix = array_intersect_key($use, $this->fields);

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
    public function toArray($transformer = null)
    {
        $result = App::column($this->fields + $this->adhoc + $this->props, 'value');

        return $transformer ? call_user_func_array($transformer, array($result)) : $result;
    }

    /**
     * Check if mapper is loaded.
     *
     * @return bool
     */
    public function valid()
    {
        return (bool) $this->query;
    }

    /**
     * Valid complement.
     *
     * @return bool
     */
    public function dry()
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
    public function paginate($page = 1, $filter = null, array $options = null, $ttl = 0)
    {
        $use = (array) $options;
        $limit = App::pick($use, 'perpage', static::PERPAGE);
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
    public function count($filter = null, array $options = null, $ttl = 0)
    {
        $shouldHack = in_array($this->db->getDriver(), array(Connection::DB_MSSQL, Connection::DB_DBLIB, Connection::DB_SQLSRV));
        $fields = substr('TOP 100 PERCENT *'.$this->stringifyAdhoc(), 16 * ((bool) !$shouldHack));
        list($sql, $args) = $this->stringify($fields, $filter, $options);

        $sql = 'SELECT COUNT(*) AS '.$this->db->quotekey('_rows').
               ' FROM ('.$sql.') AS '.$this->db->quotekey('_temp');
        $res = $this->db->exec($sql, $args, $ttl);

        return (int) $res[0]['_rows'];
    }

    /**
     * Load mapper filtered by given primary keys value.
     *
     * @param string|array $ids
     *
     * @return Mapper
     *
     * @throws LogicException If given argument count is not match with primary keys count
     */
    public function withId($ids)
    {
        $fix = App::arr($ids);
        $vcount = count($fix);
        $pcount = count($this->keys);
        $throw = $vcount !== $pcount;
        $message = 'Find by key expect exactly '.$pcount.' key values, '.$vcount.' given.';

        App::throws($throw, $message);

        return $this->load(array_combine($this->keys, $fix));
    }

    /**
     * Return the count of records loaded.
     *
     * @return int
     */
    public function loaded()
    {
        return count($this->query);
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
    public function load($filter = null, array $options = null, $ttl = 0)
    {
        $this->reset();
        $this->query = $this->find($filter, $options, $ttl);
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
    public function findOne($filter = null, array $options = null, $ttl = 0)
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
    public function find($filter = null, array $options = null, $ttl = 0)
    {
        $useGroup = isset($options['group']) && !in_array($this->db->getDriver(), array(Connection::DB_MYSQL, Connection::DB_SQLITE));
        $fields = $useGroup ? $options['group'] : implode(',', array_map(array($this->db, 'quotekey'), array_keys($this->fields)));
        list($sql, $args) = $this->stringify($fields.$this->stringifyAdhoc(), $filter, $options);

        return array_map(array($this, 'factory'), $this->db->exec($sql, $args, $ttl));
    }

    /**
     * Decide insert or update.
     *
     * @return Mapper
     */
    public function save()
    {
        return $this->query ? $this->update() : $this->insert();
    }

    /**
     * Insert record to database.
     *
     * @return Mapper
     */
    public function insert()
    {
        $args = array();
        $ctr = 0;
        $fields = '';
        $values = '';
        $filter = array();
        $ckeys = array();
        $inc = null;
        $driver = $this->db->getDriver();

        $event = new MapperEvent($this);
        $this->db->getApp()->trigger(self::EVENT_BEFOREINSERT, $event);

        if ($event->isPropagationStopped()) {
            return $this;
        }

        foreach ($this->fields as $key => $field) {
            if (!$inc && $field['pkey']) {
                $incKey = \PDO::PARAM_INT == $field['pdo_type'] && empty($field['value']) && !$field['nullable'];

                if ($incKey) {
                    $inc = $key;
                } else {
                    $filter[$key] = $this->db->phpValue($field['pdo_type'], $field['value']);
                }
            }

            $changed = $field['changed'] && $key !== $inc;

            if ($changed) {
                $fields .= ','.$this->db->quotekey($key);
                $values .= ',?';
                $args[++$ctr] = array($field['value'], $field['pdo_type']);
                $ckeys[] = $key;
            }
        }

        if (!$fields) {
            return $this;
        }

        $needPrefix = in_array($driver, array(Connection::DB_MSSQL, Connection::DB_DBLIB, Connection::DB_SQLSRV)) && array_intersect($this->keys, $ckeys);
        $needSuffix = Connection::DB_PGSQL === $driver;
        $prefix = $needPrefix ? 'SET IDENTITY_INSERT '.$this->map.' ON;' : '';
        $suffix = $needSuffix ? ' RETURNING '.$this->db->quotekey(reset($this->keys)) : '';
        $sql = 'INSERT INTO '.$this->map.' ('.ltrim($fields, ',').') '.'VALUES ('.ltrim($values, ',').')';
        $lID = $this->db->exec($prefix.$sql.$suffix, $args);

        // Reload to obtain default and auto-increment field values
        if ($inc) {
            $pgSqlId = Connection::DB_PGSQL === $driver && $lID;
            $id = $pgSqlId ? $lID[0][reset($this->keys)] : $this->db->pdo()->lastinsertid();

            $this->load(array($inc => $this->db->phpValue($this->fields[$inc]['pdo_type'], $id)));
        } elseif ($filter) {
            $this->load($filter);
        }

        $event = new MapperEvent($this);
        $this->db->getApp()->trigger(self::EVENT_INSERT, $event);

        return $this;
    }

    /**
     * Update record changes to database.
     *
     * @return Mapper
     */
    public function update()
    {
        $args = array();
        $ctr = 0;
        $pairs = '';
        $filter = '';
        $changes = array();

        $event = new MapperEvent($this);
        $this->db->getApp()->trigger(self::EVENT_BEFOREUPDATE, $event);

        if ($event->isPropagationStopped()) {
            return $this;
        }

        foreach ($this->fields as $key => $field) {
            if ($field['changed']) {
                $pairs .= ','.$this->db->quotekey($key).'=?';
                $args[++$ctr] = array($field['value'], $field['pdo_type']);
            }

            $changes[$key] = $field;
            $changes[$key]['initial'] = $field['value'];
            $changes[$key]['changed'] = false;
        }

        foreach ($this->fields as $key => $field) {
            if ($field['pkey'] || !$this->keys) {
                $filter .= ' AND '.$this->db->quotekey($key).'=?';
                $args[++$ctr] = array($field['initial'], $field['pdo_type']);
            }
        }

        if ($pairs) {
            $sql = 'UPDATE '.$this->map.' SET '.ltrim($pairs, ',');

            if ($filter) {
                $sql .= ' WHERE'.substr($filter, 4);
            }

            $this->db->exec($sql, $args);
        }

        // reset changed flag after calling afterupdate
        $this->fields = $changes;

        $event = new MapperEvent($this);
        $this->db->getApp()->trigger(self::EVENT_UPDATE, $event);

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
    public function delete($filter = null, $hayati = false)
    {
        $sql = 'DELETE FROM '.$this->map;

        if ($filter) {
            if ($hayati) {
                $out = 0;

                foreach ($this->find($filter) as $mapper) {
                    $out += $mapper->delete();
                }

                return $out;
            }

            $args = $this->db->buildFilter($filter);
            $criteria = $args ? ' WHERE '.array_shift($args) : '';

            return (int) $this->db->exec($sql.$criteria, $args);
        } elseif (!$this->keys || $this->dry()) {
            return 0;
        }

        $args = array();
        $ctr = 0;
        $out = 0;

        foreach ($this->keys as $key) {
            $field = $this->fields[$key];

            $filter .= ' AND '.$this->db->quotekey($key).'=?';
            $args[++$ctr] = array($field['initial'], $field['pdo_type']);
        }

        $event = new MapperEvent($this);
        $this->db->getApp()->trigger(self::EVENT_BEFOREDELETE, $event);

        if ($event->isPropagationStopped()) {
            return 0;
        }

        $sql .= ' WHERE'.substr($filter, 4);
        $out = (int) $this->db->exec($sql, $args);

        $this->query = array_slice($this->query, 0, $this->ptr, true) +
                       array_slice($this->query, $this->ptr, null, true);

        $event = new MapperEvent($this);
        $this->db->getApp()->trigger(self::EVENT_DELETE, $event);
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
    public function skip($offset = 1)
    {
        $this->ptr += $offset;

        if (isset($this->query[$this->ptr])) {
            $row = $this->query[$this->ptr];

            $this->fields = $row->fields;
            $this->adhoc = $row->adhoc;
            $this->props = $row->props;
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
    public function first()
    {
        return $this->skip(-$this->ptr);
    }

    /**
     * Map to last record in cursor.
     *
     * @return Mapper
     */
    public function last()
    {
        return $this->skip($this->loaded() - $this->ptr - 1);
    }

    /**
     * Map next record.
     *
     * @return Mapper
     */
    public function next()
    {
        return $this->skip();
    }

    /**
     * Map previous record.
     *
     * @return Mapper
     */
    public function prev()
    {
        return $this->skip(-1);
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
    protected function stringify($fields, $filter = null, array $options = null)
    {
        $default = array(
            'group' => null,
            'having' => null,
            'order' => null,
            'limit' => 0,
            'offset' => 0,
        );
        $use = ((array) $options) + $default;
        $driver = $this->db->getDriver();
        $order = '';

        $args = array();
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
        if (in_array($driver, array(Connection::DB_MSSQL, Connection::DB_SQLSRV, Connection::DB_ODBC)) && ($use['limit'] || $use['offset'])) {
            // order by pkey when no ordering option was given
            if (!$use['order'] && $this->keys) {
                $order = ' ORDER BY '.implode(',', array_map(array($this->db, 'quotekey'), $this->keys));
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
            $sql .= $order;

            if ($use['limit']) {
                $sql .= ' LIMIT '.(int) $use['limit'];
            }

            if ($use['offset']) {
                $sql .= ' OFFSET '.(int) $use['offset'];
            }
        }
        // @codeCoverageIgnoreEnd

        return array($sql, $args);
    }

    /**
     * Convert adhoc as select column.
     *
     * @return string
     */
    protected function stringifyAdhoc()
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
    protected function factory(array $row)
    {
        $mapper = clone $this;
        $mapper->reset();

        foreach ($row as $key => $val) {
            if (array_key_exists($key, $this->fields)) {
                $field = $this->fields[$key];
                $nullable = null === $val && $field['nullable'];
                $mapper->fields[$key]['initial'] = $nullable ? null : $this->db->phpValue($field['pdo_type'], $val);
                $mapper->fields[$key]['value'] = $mapper->fields[$key]['initial'];
            } else {
                $mapper->adhoc[$key]['value'] = $val;
            }
        }

        $mapper->query = array(clone $mapper);

        $event = new MapperEvent($mapper);
        $this->db->getApp()->trigger(self::EVENT_LOAD, $event);

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
    protected function fieldArgs($field, array $args)
    {
        if ($args) {
            $first = array_shift($args);
            array_unshift($args, array($field => $first));
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
     *     getName           => get('name')
     *     findByName('foo') => find(['name'=>'foo'])
     *     loadByName('foo') => load(['name'=>'foo'])
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     *
     * @throws BadMethodCallException If method is undefined
     */
    public function __call($method, $args)
    {
        $lmethod = strtolower($method);

        if (App::startswith($lmethod, 'get')) {
            $field = App::snakecase(App::cutprefix($lmethod, 'get'));
            array_unshift($args, $field);
            $call = 'get';
        } elseif (App::startswith($lmethod, 'findby')) {
            $field = App::snakecase(App::cutprefix($lmethod, 'findby'));
            $args = $this->fieldArgs($field, $args);
            $call = 'find';
        } elseif (App::startswith($lmethod, 'findoneby')) {
            $field = App::snakecase(App::cutprefix($lmethod, 'findoneby'));
            $args = $this->fieldArgs($field, $args);
            $call = 'findone';
        } elseif (App::startswith($lmethod, 'loadby')) {
            $field = App::snakecase(App::cutprefix($lmethod, 'loadby'));
            $args = $this->fieldArgs($field, $args);
            $call = 'load';
        } else {
            throw new \BadMethodCallException('Call to undefined method '.static::class.'::'.$method.'.');
        }

        return call_user_func_array(array($this, $call), $args);
    }
}

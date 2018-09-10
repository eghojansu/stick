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
    const PAGINATE_LIMIT = 10;

    // Supported events
    const EVENT_LOAD = 'sql_mapper_load';
    const EVENT_INSERT = 'sql_mapper_insert';
    const EVENT_AFTER_INSERT = 'sql_mapper_after_insert';
    const EVENT_UPDATE = 'sql_mapper_update';
    const EVENT_AFTER_UPDATE = 'sql_mapper_after_update';
    const EVENT_DELETE = 'sql_mapper_delete';
    const EVENT_AFTER_DELETE = 'sql_mapper_after_delete';

    /**
     * App instance.
     *
     * @var App
     */
    protected $_app;

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
     * @param App         $app
     * @param Connection  $db
     * @param string|null $table
     * @param mixed       $fields
     * @param int         $ttl
     */
    public function __construct(App $app, Connection $db, $table = null, $fields = null, $ttl = 60)
    {
        $fix = $this->_table ?: ($table ?: App::snakecase(App::classname($this)));
        $this->_app = $app;
        $this->_db = $db;
        $this->_driver = $db->getDriver();
        $this->setTable($fix, $fields, $ttl);
    }

    /**
     * Get table name.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->_table;
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
        $fix = Connection::DB_OCI === $this->_driver ? strtoupper($table) : $table;
        $prev = $this->_table;
        $quoted = $this->_db->quotekey($fix);
        $schema = $this->_db->schema($fix, $fields, $ttl);

        $this->_table = $table;
        $this->_map = $quoted;
        $this->_fields = $schema;
        $this->_keys = array_keys(array_filter(App::column($schema, 'pkey')));
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
        return new self($this->_app, $this->_db, $table, $fields, $ttl);
    }

    /**
     * Get fields name.
     *
     * @return array
     */
    public function getFields()
    {
        return array_keys($this->_fields + $this->_adhoc);
    }

    /**
     * Get table schema.
     *
     * @return array
     */
    public function getSchema()
    {
        return $this->_fields;
    }

    /**
     * Get connection instance.
     *
     * @return Connection
     */
    public function db()
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
    public function exists($key)
    {
        return array_key_exists($key, $this->_fields + $this->_adhoc);
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
     * @return Mapper
     */
    public function set($key, $val)
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
            $this->_adhoc[$key] = array('expr' => '('.$val.')', 'value' => null);
        } else {
            $this->_props[$key] = array('self' => false, 'value' => $val);
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
    public function reset()
    {
        foreach ($this->_fields as &$field) {
            $field['value'] = $field['initial'] = $field['default'];
            $field['changed'] = false;
            unset($field);
        }

        $nullAdhoc = array_fill_keys(array_keys($this->_adhoc), array('value' => null));
        $selfProps = array_filter(App::column($this->_props, 'self'));
        $this->_adhoc = array_replace_recursive($this->_adhoc, $nullAdhoc);
        $this->_props = array_intersect_key($this->_props, $selfProps);
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
    public function required($key)
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
    public function changed($key = null)
    {
        if ($key) {
            return isset($this->_fields[$key]) && $this->_fields[$key]['changed'];
        }

        return (bool) array_filter(array_column($this->_fields, 'changed'));
    }

    /**
     * Get primary keys value.
     *
     * @return array
     */
    public function keys()
    {
        $keys = array_flip($this->_keys);

        return App::column(array_merge($keys, array_intersect_key($this->_fields, $keys)), 'initial');
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
    public function toArray($transformer = null)
    {
        $result = App::column($this->_fields + $this->_adhoc + $this->_props, 'value');

        return $transformer ? call_user_func_array($transformer, array($result)) : $result;
    }

    /**
     * Check if mapper is loaded.
     *
     * @return bool
     */
    public function valid()
    {
        return (bool) $this->_query;
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
        $limit = App::pick($use, 'perpage', static::PAGINATE_LIMIT);
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
        $shouldHack = in_array($this->_driver, array(Connection::DB_MSSQL, Connection::DB_DBLIB, Connection::DB_SQLSRV));
        $fields = substr('TOP 100 PERCENT *'.$this->stringifyAdhoc(), 16 * ((bool) !$shouldHack));
        list($sql, $args) = $this->stringify($fields, $filter, $options);

        $sql = 'SELECT COUNT(*) AS '.$this->_db->quotekey('_rows').
               ' FROM ('.$sql.') AS '.$this->_db->quotekey('_temp');
        $res = $this->_db->exec($sql, $args, $ttl);

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
        $pcount = count($this->_keys);
        $throw = $vcount !== $pcount;
        $message = 'Find by key expect exactly '.$pcount.' key values, '.$vcount.' given.';

        App::throws($throw, $message);

        return $this->load(array_combine($this->_keys, $fix));
    }

    /**
     * Return the count of records loaded.
     *
     * @return int
     */
    public function loaded()
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
    public function load($filter = null, array $options = null, $ttl = 0)
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
        $useGroup = isset($options['group']) && !in_array($this->_driver, array(Connection::DB_MYSQL, Connection::DB_SQLITE));
        $fields = $useGroup ? $options['group'] : implode(',', array_map(array($this->_db, 'quotekey'), array_keys($this->_fields)));
        list($sql, $args) = $this->stringify($fields.$this->stringifyAdhoc(), $filter, $options);

        return array_map(array($this, 'factory'), $this->_db->exec($sql, $args, $ttl));
    }

    /**
     * Decide insert or update.
     *
     * @return Mapper
     */
    public function save()
    {
        return $this->_query ? $this->update() : $this->insert();
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
        $driver = $this->_driver;

        $event = new MapperEvent($this);
        $this->_app->trigger(self::EVENT_INSERT, $event);

        if ($event->isPropagationStopped()) {
            return $this;
        }

        foreach ($this->_fields as $key => $field) {
            if (!$inc && $field['pkey']) {
                $incKey = \PDO::PARAM_INT == $field['pdo_type'] && empty($field['value']) && !$field['nullable'];

                if ($incKey) {
                    $inc = $key;
                } else {
                    $filter[$key] = $this->_db->phpValue($field['pdo_type'], $field['value']);
                }
            }

            $changed = $field['changed'] && $key !== $inc;

            if ($changed) {
                $fields .= ','.$this->_db->quotekey($key);
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
        $prefix = $needPrefix ? 'SET IDENTITY_INSERT '.$this->_map.' ON;' : '';
        $suffix = $needSuffix ? ' RETURNING '.$this->_db->quotekey(reset($this->_keys)) : '';
        $sql = 'INSERT INTO '.$this->_map.' ('.ltrim($fields, ',').') '.'VALUES ('.ltrim($values, ',').')';
        $lID = $this->_db->exec($prefix.$sql.$suffix, $args);

        // Reload to obtain default and auto-increment field values
        if ($inc) {
            $pgSqlId = Connection::DB_PGSQL === $driver && $lID;
            $id = $pgSqlId ? $lID[0][reset($this->_keys)] : $this->_db->pdo()->lastinsertid();

            $this->load(array($inc => $this->_db->phpValue($this->_fields[$inc]['pdo_type'], $id)));
        } elseif ($filter) {
            $this->load($filter);
        }

        $event = new MapperEvent($this);
        $this->_app->trigger(self::EVENT_AFTER_INSERT, $event);

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
        $this->_app->trigger(self::EVENT_UPDATE, $event);

        if ($event->isPropagationStopped()) {
            return $this;
        }

        foreach ($this->_fields as $key => $field) {
            if ($field['changed']) {
                $pairs .= ','.$this->_db->quotekey($key).'=?';
                $args[++$ctr] = array($field['value'], $field['pdo_type']);
            }

            $changes[$key] = $field;
            $changes[$key]['initial'] = $field['value'];
            $changes[$key]['changed'] = false;
        }

        foreach ($this->_fields as $key => $field) {
            if ($field['pkey'] || !$this->_keys) {
                $filter .= ' AND '.$this->_db->quotekey($key).'=?';
                $args[++$ctr] = array($field['initial'], $field['pdo_type']);
            }
        }

        if ($pairs) {
            $sql = 'UPDATE '.$this->_map.' SET '.ltrim($pairs, ',');

            if ($filter) {
                $sql .= ' WHERE'.substr($filter, 4);
            }

            $this->_db->exec($sql, $args);
        }

        // reset changed flag after calling afterupdate
        $this->_fields = $changes;

        $event = new MapperEvent($this);
        $this->_app->trigger(self::EVENT_AFTER_UPDATE, $event);

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
        $sql = 'DELETE FROM '.$this->_map;

        if ($filter) {
            if ($hayati) {
                $out = 0;

                foreach ($this->find($filter) as $mapper) {
                    $out += $mapper->delete();
                }

                return $out;
            }

            $args = $this->_db->buildFilter($filter);
            $criteria = $args ? ' WHERE '.array_shift($args) : '';

            return (int) $this->_db->exec($sql.$criteria, $args);
        } elseif (!$this->_keys || $this->dry()) {
            return 0;
        }

        $args = array();
        $ctr = 0;
        $out = 0;

        foreach ($this->_keys as $key) {
            $field = $this->_fields[$key];

            $filter .= ' AND '.$this->_db->quotekey($key).'=?';
            $args[++$ctr] = array($field['initial'], $field['pdo_type']);
        }

        $event = new MapperEvent($this);
        $this->_app->trigger(self::EVENT_DELETE, $event);

        if ($event->isPropagationStopped()) {
            return 0;
        }

        $sql .= ' WHERE'.substr($filter, 4);
        $out = (int) $this->_db->exec($sql, $args);

        $this->_query = array_slice($this->_query, 0, $this->_ptr, true) +
                       array_slice($this->_query, $this->_ptr, null, true);

        $event = new MapperEvent($this);
        $this->_app->trigger(self::EVENT_AFTER_DELETE, $event);
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
        $this->_ptr += $offset;

        if (isset($this->_query[$this->_ptr])) {
            $row = $this->_query[$this->_ptr];

            $this->_fields = $row->_fields;
            $this->_adhoc = $row->_adhoc;
            $this->_props = $row->_props;
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
        return $this->skip(-$this->_ptr);
    }

    /**
     * Map to last record in cursor.
     *
     * @return Mapper
     */
    public function last()
    {
        return $this->skip($this->loaded() - $this->_ptr - 1);
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
        $driver = $this->_driver;
        $order = '';

        $args = array();
        $sql = 'SELECT '.$fields.' FROM '.$this->_map;

        $f = $this->_db->buildFilter($filter);
        if ($f) {
            $sql .= ' WHERE '.array_shift($f);
            $args = array_merge($args, $f);
        }

        if ($use['group']) {
            $sql .= ' GROUP BY '.$use['group'];
        }

        $f = $this->_db->buildFilter($use['having']);
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
                $order = ' ORDER BY '.implode(',', array_map(array($this->_db, 'quotekey'), $this->keys));
            }

            $ofs = (int) $use['offset'];
            $lmt = (int) $use['limit'];

            if (strncmp($this->_db->getVersion(), '11', 2) >= 0) {
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

        foreach ($this->_adhoc as $key => $field) {
            $res .= ','.$field['expr'].' AS '.$this->_db->quotekey($key);
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

        $event = new MapperEvent($mapper);
        $this->_app->trigger(self::EVENT_LOAD, $event);

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
     * Convenience method to check field existance.
     *
     * @param string $offset
     *
     * @return bool
     */
    public function __isset($offset)
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
    public function &__get($offset)
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
    public function __set($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Convenience method to clear field value.
     *
     * @param string $offset
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

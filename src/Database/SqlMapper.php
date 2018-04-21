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

use function Fal\Stick\classname;
use function Fal\Stick\istartswith;
use function Fal\Stick\icutafter;
use function Fal\Stick\snakecase;

class SqlMapper extends Mapper
{
    /** @var Sql */
    protected $db;

    /** @var int */
    protected $ttl;

    /** @var array Original fields */
    protected $original;

    /** @var array Schema */
    protected $fields;

    /** @var string Quoted table */
    protected $map;

    /** @var string Driver name */
    protected $driver;

    /** @var bool */
    protected $one = false;

    /** @var array Primary keys */
    protected $pkeys = [];

    /** @var array */
    protected $adhoc = [];

    /** @var array */
    protected $props = [];

    /**
     * Class constructor
     *
     * @param Sql         $db
     * @param string|null $table
     * @param array       $fields
     * @param int         $ttl
     */
    public function __construct(Sql $db, string $table = null, array $fields = null, int $ttl = 60)
    {
        $this->db = $db;
        $this->ttl = $ttl;
        $this->original = $fields;
        $this->driver = $db->getDriver();
        $this->setTable($table ?? $this->table ?? snakecase(classname($this)));
    }

    /**
     * Get db
     *
     * @return Sql
     */
    public function getDb(): Sql
    {
        return $this->db;
    }

    /**
     * {@inheritdoc}
     */
    public function getDbType(): string
    {
        return 'SQL';
    }

    /**
     * {@inheritdoc}
     */
    public function getFields(): array
    {
        return array_keys($this->fields + $this->adhoc);
    }

    /**
     * {@inheritdoc}
     */
    public function getSchema(): array
    {
        return $this->fields;
    }

    /**
     * {@inheritdoc}
     */
    public function setSchema(array $schema): MapperInterface
    {
        $this->fields = $schema;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withTable(string $table, array $option = null): MapperInterface
    {
        return new static($this->db, $table, $option['fields'] ?? null, $option['ttl'] ?? 60);
    }

    /**
     * {@inheritdoc}
     */
    public function setTable(string $table = null): MapperInterface
    {
        if (!$table) {
            return $this;
        }

        $use = $this->driver === Sql::DB_OCI ? strtoupper($table) : $table;
        $prev = $this->table;

        parent::setTable($use);
        $this->map = $this->db->quotekey($use);
        $this->fields = $this->db->schema($use, $this->original, $this->ttl);
        $this->reset();

        if (!$this->pkeys || $this->table !== $prev) {
            $this->pkeys = [];

            foreach ($this->fields as $key => $value) {
                if ($value['pkey']) {
                    $this->pkeys[] = $key;
                }
            }
        }

        $this->one = count($this->pkeys) === 1;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function count($filter = null, array $option = null, int $ttl = 0): int
    {
        $adhoc = '';
        foreach ($this->adhoc as $key => $field) {
            $adhoc .= ',' . $field['expr'] . ' AS ' . $this->db->quotekey($key);
        }

        $fields = '*' . $adhoc;
        if (in_array($this->driver, [Sql::DB_MSSQL, Sql::DB_DBLIB, Sql::DB_SQLSRV])) {
            $fields = 'TOP 100 PERCENT ' . $fields;
        }

        list($sql, $arg) = $this->stringify($fields, $filter, $option);

        $sql = 'SELECT COUNT(*) AS ' . $this->db->quotekey('_rows') .
            ' FROM (' . $sql . ') AS ' . $this->db->quotekey('_temp');
        $res = $this->db->exec($sql, $arg, $ttl);

        return (int) $res[0]['_rows'];
    }

    /**
     * {@inheritdoc}
     */
    public function load($filter = null, array $option = null, int $ttl = 0): MapperInterface
    {
        $found = $this->findOne($filter, $option, $ttl);

        if ($found) {
            $this->fields = $found->fields;
            $this->loaded = $found->loaded;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function loadId($id, int $ttl = 0): MapperInterface
    {
        $found = $this->findId($id, $ttl);

        if ($found) {
            $this->fields = $found->fields;
            $this->loaded = $found->loaded;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function findId($id, int $ttl = 0): ?MapperInterface
    {
        $pvals = (array) $id;
        $vcount = count($pvals);
        $pcount = count($this->pkeys);

        if ($vcount !== $pcount) {
            throw new \ArgumentCountError(
                __METHOD__ . ' expect exactly ' . $pcount .
                ' arguments, ' . $vcount . ' given'
            );
        }

        return $this->findOne(array_combine($this->pkeys, $pvals), null, $ttl);
    }

    /**
     * {@inheritdoc}
     */
    public function find($filter = null, array $option = null, int $ttl = 0): array
    {
        $use = ((array) $option) + ['group' => null];

        $adhoc = '';
        foreach ($this->adhoc as $key=>$field) {
            $adhoc .= ',' . $field['expr'] . ' AS ' . $this->db->quotekey($key);
        }

        $fields = ($use['group'] && !in_array($this->driver, [Sql::DB_MYSQL, Sql::DB_SQLITE])?
                    $use['group'] : implode(',', array_map([$this->db,'quotekey'], array_keys($this->fields)))) .
                  $adhoc;
        list($sql, $arg) = $this->stringify($fields, $filter, $use);

        $res = $this->db->exec($sql, $arg, $ttl);
        $out = [];

        foreach ($res as &$row) {
            foreach ($row as $field => &$val) {
                if (array_key_exists($field, $this->fields)) {
                    if (!is_null($val) || !$this->fields[$field]['nullable']) {
                        $val = $this->db->value($this->fields[$field]['pdo_type'], $val);
                    }
                }
                unset($val);
            }

            $out[] = $this->factory($row);
            unset($row);
        }

        return $out;
    }

    /**
     * {@inheritdoc}
     */
    public function insert(): MapperInterface
    {
        $args = [];
        $ctr = 0;
        $fields = '';
        $values = '';
        $filter = [];
        $ckeys = [];
        $pkeys = $this->getPkeys();
        $inc = NULL;

        if ($this->trigger(MapperInterface::EVENT_BEFOREINSERT, [$this, $pkeys])) {
            return $this;
        }

        foreach ($this->fields as $key=>&$field) {
            if ($field['pkey']) {
                if (!$inc && $field['pdo_type'] == \PDO::PARAM_INT &&
                    empty($field['value']) && !$field['nullable']) {
                    $inc = $key;
                }

                $filter[$key] = $this->db->value($field['pdo_type'], $field['value']);
            }

            if ($field['changed'] && $key !== $inc) {
                $fields .= ($ctr ? ',' : '') . $this->db->quotekey($key);
                $values .= ($ctr ? ',' : '') . '?';
                $args[$ctr+1] = [$field['value'], $field['pdo_type']];
                $ctr++;
                $ckeys[] = $key;
            }
        }

        if (!$fields) {
            return $this;
        }

        $add = '';
        if ($this->driver === Sql::DB_PGSQL) {
            $aik = end($this->pkeys);
            $add = ' RETURNING ' . $this->db->quotekey($aik);
        }

        $lID = $this->db->exec(
            (in_array($this->driver, [Sql::DB_MSSQL, Sql::DB_DBLIB, Sql::DB_SQLSRV]) &&
            array_intersect($this->pkeys, $ckeys)?
                'SET IDENTITY_INSERT ' . $this->map . ' ON;' : '').
            'INSERT INTO ' . $this->map . ' (' . $fields . ') ' .
            'VALUES (' . $values . ')' . $add, $args
        );

        if ($this->driver === Sql::DB_PGSQL && $lID) {
            $id = $lID[0][$aik];
        } elseif ($this->driver !== Sql::DB_OCI) {
            $id = $this->db->pdo()->lastinsertid();
        }

        // Reload to obtain default and auto-increment field values
        $reload = $inc || $filter;
        if ($reload) {
            $this->load($inc ?
                [$inc => $this->db->value($this->fields[$inc]['pdo_type'], $id)] :
                $filter
            );
            $pkeys = $this->getPkeys();
        }

        if ($this->trigger(MapperInterface::EVENT_AFTERINSERT, [$this, $pkeys])) {
            return $this;
        }

        // reset changed flag after calling afterinsert
        if (!$reload) {
            foreach ($this->fields as $key => &$field) {
                $field['changed'] = FALSE;
                $field['initial'] = $field['value'];
                unset($field);
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function update(): MapperInterface
    {
        $args = [];
        $ctr = 0;
        $pairs = '';
        $filter = '';
        $pkeys = $this->getPkeys('initial');

        if ($this->trigger(MapperInterface::EVENT_BEFOREUPDATE, [$this, $pkeys])) {
            return $this;
        }

        foreach ($this->fields as $key => $field) {
            if ($field['changed']) {
                $pairs .= ($pairs ? ',' : '') . $this->db->quotekey($key) . '=?';
                $args[++$ctr] = [$field['value'], $field['pdo_type']];
            }
        }

        foreach ($this->pkeys as $key) {
            $filter .= ($filter ? ' AND ' : ' WHERE ') . $this->db->quotekey($key) . '=?';
            $args[++$ctr] = [$this->fields[$key]['initial'], $this->fields[$key]['pdo_type']];
        }

        if ($pairs) {
            $sql = 'UPDATE ' . $this->map . ' SET ' . $pairs . $filter;
            $this->db->exec($sql, $args);
            $pkeys = $this->getPkeys();
        }

        if ($this->trigger(MapperInterface::EVENT_AFTERUPDATE, [$this, $pkeys])) {
            return $this;
        }

        // reset changed flag after calling afterupdate
        foreach ($this->fields as $key => &$field) {
            $field['changed'] = FALSE;
            $field['initial'] = $field['value'];
            unset($field);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($filter = null, bool $quick = true): int
    {
        if (isset($filter)) {
            if (!$quick) {
                $out = 0;
                foreach ($this->find($filter) as $mapper) {
                    $out += $mapper->delete();
                }

                return $out;
            }

            $args = $this->db->filter($filter);
            $sql = 'DELETE FROM ' . $this->map;

            if ($args) {
                $sql .= ' WHERE ' . array_shift($args);
            }

            return (int) $this->db->exec($sql, $args);
        }

        $args = [];
        $ctr = 0;
        $filter = '';
        $pkeys = $this->getPkeys('initial');

        foreach ($this->fields as $key => &$field) {
            if ($field['pkey']) {
                $filter .= ($filter ? ' AND ' : '') . $this->db->quotekey($key) . '=?';
                $args[$ctr+1] = [$field['initial'], $field['pdo_type']];
                $ctr++;
            }

            $field['value'] = NULL;
            $field['changed'] = (bool) $field['default'];
            unset($field);
        }

        foreach ($this->adhoc as &$field) {
            $field['value'] = NULL;
            unset($field);
        }

        if ($this->trigger(MapperInterface::EVENT_BEFOREDELETE, [$this, $pkeys])) {
            return 0;
        }

        $out = $this->db->exec('DELETE FROM ' . $this->map . ' WHERE ' . $filter . ';', $args);

        $this->trigger(MapperInterface::EVENT_BEFOREDELETE, [$this, $pkeys]);

        return (int) $out;
    }

    /**
     * {@inheritdoc}
     */
    public function reset(): MapperInterface
    {
        foreach ($this->fields as &$field) {
            $field['value'] = null;
            $field['initial'] = null;
            $field['changed'] = false;
            unset($field);
        }

        foreach ($this->adhoc as &$field) {
            $field['value'] = null;
            unset($field);
        }

        $this->loaded = false;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(callable $func = null): array
    {
        $res = [];
        foreach ($this->fields + $this->adhoc as $key => $value) {
            $res[$key] = $value['value'];
        }

        return $func ? call_user_func_array($func, [$res]) : $res;
    }

    /**
     * {@inheritdoc}
     */
    public function fromArray(array $source, callable $func = null): MapperInterface
    {
        foreach ($func ? call_user_func_array($func, [$source]) : $source as $key => $val) {
            if (array_key_exists($key, $this->fields)) {
                $this->set($key, $val);
            }
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function required(string $key): bool
    {
        return !($this->fields[$key]['nullable'] ?? true);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return array_key_exists($key, $this->fields + $this->adhoc);
    }

    /**
     * {@inheritdoc}
     */
    public function &get(string $key)
    {
        if (array_key_exists($key, $this->fields)) {
            return $this->fields[$key]['value'];
        } elseif (array_key_exists($key, $this->adhoc)) {
            return $this->adhoc[$key]['value'];
        } elseif (array_key_exists($key, $this->props)) {
            if (is_callable($this->props[$key])) {
                $call = $this->props[$key];
                $res = $call($this);
                $ref =& $res;
            } else {
                $ref =& $this->props[$key];
            }

            return $ref;
        }

        throw new \LogicException('Undefined field ' . $key);
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, $val): MapperInterface
    {
        if (array_key_exists($key, $this->fields)) {
            $val = is_null($val) && $this->fields[$key]['nullable'] ? null : $this->db->value($this->fields[$key]['pdo_type'], $val);
            if ($this->fields[$key]['initial'] !== $val ||
                $this->fields[$key]['default'] !== $val &&
                is_null($val)) {
                $this->fields[$key]['changed'] = true;
            }
            $this->fields[$key]['value'] = $val;
        } elseif (isset($this->adhoc[$key])) {
            // Adjust result on existing expressions
            $this->adhoc[$key]['value'] = $val;
        } elseif (is_string($val)) {
            // Parenthesize expression in case it's a subquery
            $this->adhoc[$key] = ['expr'=> '(' . $val . ')', 'value'=>null];
        } else {
            $this->props[$key] = $val;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function clear(string $key): MapperInterface
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
     * Get primary key value
     *
     * @param  string $field
     *
     * @return array
     */
    protected function getPkeys(string $field = 'value'): array
    {
        $res = [];

        foreach ($this->pkeys as $key) {
            $res[$key] = $this->fields[$key][$field];
        }

        return $res;
    }

    /**
     * Convert array to mapper object
     *
     * @param  array $row
     *
     * @return SqlMapper
     */
    protected function factory(array $row): SqlMapper
    {
        $mapper = clone $this;
        $mapper->reset();
        $mapper->loaded = true;

        foreach ($row as $key => $val) {
            if (array_key_exists($key, $this->fields)) {
                $mapper->fields[$key]['value'] = $val;
                $mapper->fields[$key]['initial'] = $val;
            } elseif (array_key_exists($key, $this->adhoc)) {
                $mapper->adhoc[$key]['value'] = $val;
                $mapper->adhoc[$key]['initial'] = $val;
            }
        }

        $this->trigger(MapperInterface::EVENT_LOAD, [$mapper]);

        return $mapper;
    }

    /**
     * Build query string and arguments
     *
     * @param  string      $fields
     * @param  mixed       $filter
     * @param  array|null  $option
     *
     * @return array
     */
    protected function stringify(string $fields, $filter = null, array $option = null): array
    {
        $use = ($option ?? []) + [
            'group' => null,
            'having' => null,
            'order' => null,
            'limit' => 0,
            'offset' => 0,
        ];

        $arg = [];
        $sql = 'SELECT ' . $fields . ' FROM ' . $this->map;

        $f = $this->db->filter($filter);
        if ($f) {
            $sql .= ' WHERE ' . array_shift($f);
            $arg = array_merge($arg, $f);
        }

        if ($use['group']) {
            $sql .= ' GROUP BY ' . $use['group'];
        }

        $f = $this->db->filter($use['having']);
        if ($f) {
            $sql .= ' HAVING ' . array_shift($f);
            $arg = array_merge($arg, $f);
        }

        if ($use['order']) {
            $order = ' ORDER BY ' . $use['order'];
        }

        // @codeCoverageIgnoreStart
        // SQL Server fixes
        if (in_array($this->driver, [Sql::DB_MSSQL, Sql::DB_SQLSRV, Sql::DB_ODBC]) && ($use['limit'] || $use['offset'])) {
            // order by pkey when no ordering option was given
            if (!$use['order'] && $this->pkeys) {
                $order = ' ORDER BY ' . implode(',', array_map([$this->db, 'quotekey'], $this->pkeys));
            }

            $ofs = $use['offset'] ? (int) $use['offset'] : 0;
            $lmt = $use['limit'] ? (int) $use['limit'] : 0;

            if (strncmp($this->db->getVersion(), '11', 2) >= 0) {
                // SQL Server >= 2012
                $sql .= $order . ' OFFSET ' . $ofs . ' ROWS';

                if ($lmt) {
                    $sql .= ' FETCH NEXT ' . $lmt . ' ROWS ONLY';
                }
            } else {
                // Require primary keys or order clause
                // SQL Server 2008
                $sql = preg_replace('/SELECT/',
                    'SELECT '.
                    ($lmt > 0 ? 'TOP ' . ($ofs+$lmt) : '') . ' ROW_NUMBER() '.
                    'OVER (' . $order . ') AS rnum,', $sql . $order, 1
                );
                $sql = 'SELECT * FROM (' . $sql . ') x WHERE rnum > ' . $ofs;
            }
        } else {
            $sql .= ($order ?? '');

            if ($use['limit']) {
                $sql .= ' LIMIT ' . (int) $use['limit'];
            }

            if ($use['offset']) {
                $sql .= ' OFFSET ' . (int) $use['offset'];
            }
        }
        // @codeCoverageIgnoreEnd

        return [$sql, $arg];
    }

    /**
     * Proxy to mapper method
     * Example:
     *   loadByUsername('foo') = load(['username'=>'foo'])
     *
     * @param  string $method
     * @param  array  $args
     *
     * @return mixed
     */
    public function __call($method, array $args)
    {
        if (istartswith('loadby', $method)) {
            $field = snakecase(icutafter('loadby', $method));

            if ($args) {
                $first = array_shift($args);
                array_unshift($args, [$field => $first]);
            }

            return $this->load(...$args);
        }

        return parent::__call($method, $args);
    }
}

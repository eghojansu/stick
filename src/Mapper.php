<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Mar 07, 2018 17:09
 */

namespace Fal\Stick;

class Mapper
{
    /** @var Database */
    protected $db;

    /** @var string Map to class name */
    protected $map;

    /** @var int Default fetch mode */
    protected $fetch = \PDO::FETCH_ASSOC;

    /** @var string */
    protected $source;

    /** @var string Quoted table */
    protected $table;

    /** @var array */
    protected $fields;

    /** @var array */
    protected $pkeys = [];

    /**
     * Class constructor
     *
     * @param Database     $db
     * @param string       $table
     * @param string|array $fields
     * @param int          $ttl
     */
    public function __construct(
        Database $db,
        string $table = null,
        $fields = null,
        int $ttl = 60
    ) {
        $use = $this->source ?? $table ??
               rtrim(cutbefore('mapper', snakecase(classname($this))), '_');

        $this->db = $db;
        $this->setSource($use, $fields, $ttl);
    }

    /**
     * Clone with new source
     *
     * @param  string $source
     *
     * @return Mapper
     */
    public function withSource(string $source): Mapper
    {
        $clone = clone $this;
        $clone->setSource($source);

        return $clone;
    }

    /**
     * Set source (table)
     *
     * @param string       $source
     * @param string|array $fields
     * @param int          $ttl
     *
     * @return Mapper
     */
    public function setSource(string $source, $fields = null, int $ttl = 60): Mapper
    {
        if ($source) {
            $this->source = $source;
            $this->table = $this->db->quotekey($source);
            $this->fields = $this->db->schema($source, $fields, $ttl);
            $this->pkeys = [];

            foreach ($this->fields as $key => $value) {
                if ($value['pkey']) {
                    $this->pkeys[] = $key;
                }
            }
        }

        return $this;
    }

    /**
     * Get source (table)
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->source ?? '';
    }

    /**
     * Expose db
     *
     * @return Database
     */
    public function getDb(): Database
    {
        return $this->db;
    }

    /**
     * Run select query, with count
     * @see  Database::findBy
     *
     * @param  string|array $filter  @see Database::filter
     * @param  array  $options
     * @param  int    $ttl
     *
     * @return int
     *
     * @throws Throwable If error in debug mode
     */
    public function count($filter = null, array $options = null, int $ttl = 0): int
    {
        $res = $this->findBy(
            $filter,
            ['column' => 'count(*) as `cc`'] + (array) $options,
            $ttl
        );

        return $res ? (int) $res[0]['cc'] : 0;
    }

    /**
     * Paginate records
     *
     * @param  int           $page
     * @param  int           $limit
     * @param  string|filter $filter
     * @param  array         $options
     * @param  int           $ttl
     *
     * @return array
     *
     * @throws Throwable If error in debug mode
     */
    public function paginate(
        int $page = 1,
        int $limit = 10,
        $filter = null,
        array $options = null,
        int $ttl = 0
    ): array {
        $total = $this->count($filter, $options, $ttl);
        $pages = (int) ceil($total / $limit);
        $subset = [];
        $start = 0;
        $end = 0;

        if ($page > 0) {
            $offset = ($page - 1) * $limit;
            $subset = $this->findBy(
                $filter,
                compact('limit','offset') + (array) $options,
                $ttl
            );
            $start = $offset + 1;
            $end = $offset + count($subset);
        }

        return compact('subset', 'total', 'pages', 'page', 'start', 'end');
    }

    /**
     * Run select query, set limit to 1 (one)
     * @see  Database::findBy
     *
     * @param  string|array $filter  @see Database::filter
     * @param  array        $options
     * @param  int          $ttl
     *
     * @return mixed
     *
     * @throws Throwable If error in debug mode
     */
    public function findOneBy($filter = null, array $options = null, int $ttl = 0)
    {
        $result = $this->findBy($filter, ['limit' => 1] + (array) $options, $ttl);

        return $result ? $result[0] : null;
    }

    /**
     * Run select query, available options (and its default value):
     *     column = null, fallback to '*'
     *     group  = null
     *     having = null
     *     order  = null
     *     limit  = 0
     *     offset = 0
     *     ttl    = null
     *     raw    = false
     *
     * @param  string|array $filter  @see Database::filter
     * @param  array        $options
     * @param  int          $ttl
     *
     * @return array
     *
     * @throws Throwable If error in debug mode
     */
    public function findBy($filter = null, array $options = null, int $ttl = 0): array
    {
        $options = (array) $options;
        $options += [
            'column' => null,
            'group' => null,
            'having' => null,
            'order' => null,
            'limit' => 0,
            'offset' => 0,
        ];

        $raw = $options['column'] !== null;
        $column = $options['column'] ?? implode(',', quoteall(array_keys($this->fields), $this->db->quotes()));
        $sql = 'SELECT ' . $column . ' FROM ' . $this->table;
        $params = [];

        $f = $this->db->filter($filter);
        if ($f) {
            $sql .= ' WHERE ' . array_shift($f);
            $params = array_merge($params, $f);
        }

        if ($options['group']) {
            $sql .= ' GROUP BY ' . $options['group'];
        }

        $f = $this->db->filter($options['having'], $sql);
        if ($f) {
            $sql .= ' HAVING ' . array_shift($f);
            $params = array_merge($params, $f);
        }

        if ($options['order']) {
            $sql .= ' ORDER BY ' . $options['order'];
        }

        if ($options['limit']) {
            $sql .= ' LIMIT ' . max(0, $options['limit']);

            if ($options['offset']) {
                $sql .= ' OFFSET ' . max(0, $options['offset']);
            }
        }

        if ($ttl && $this->db->getCache()->isCached($hash, $result, 'sql', $sql, $params)) {
            return $result[0];
        }

        $query = $this->db->prepare($sql, $params);

        if (!$query) {
            return [];
        }

        $success = $this->db->run($query, $params);

        if ($raw) {
            $result = $query->fetchAll(\PDO::FETCH_ASSOC) ?? [];
        } elseif ($this->map) {
            $result = $query->fetchAll(\PDO::FETCH_FUNC, $this->factory()) ?? [];
        } else {
            $result = $query->fetchAll($this->fetch) ?? [];
        }

        if ($ttl) {
            $this->db->getCache()->set($hash, $result, $ttl);
        }

        return $result;
    }

    /**
     * Insert record into table
     *
     * @param  array|obj  $data
     *
     * @return int Last inserted id or 0
     *
     * @throws Throwable If error in debug mode or no record provided
     */
    public function insert($data): int
    {
        if (!$data) {
            throw new \LogicException('No data provided to insert');
        }

        $len = -1;
        $columns = '';
        $params = [];
        $record = $this->cast($data);

        foreach ($record as $key => $value) {
            if (!isset($this->fields[$key])) {
                continue;
            }

            $columns .= $this->db->quotekey($key) . ', ';
            $params[] = $value;
            $len++;
        }

        $columns = rtrim($columns, ', ');

        $sql = 'INSERT INTO ' . $this->table .
            ' (' . $columns . ')' .
            ' VALUES' .
            ' (' . str_repeat('?, ', $len) . '?)';

        $query = $this->db->prepare($sql, $params);

        if (!$query) {
            return 0;
        }

        $success = $this->db->run($query, $params);

        return $success ? (int) $this->db->pdo()->lastInsertId() : 0;
    }

    /**
     * Run prepared query multiple times for inserting records
     *
     * @param  array $records
     * @param  bool  $trans
     *
     * @return array of inserted ids
     */
    public function insertBatch(array $records, bool $trans = true): array
    {
        // first record as template
        $template = reset($records);

        if (!$template) {
            throw new \LogicException('No data provided to insert (batch)');
        }

        $len = 0;
        $columns = '';
        $record = $this->cast($template);

        foreach ($record as $key => $value) {
            if (isset($this->fields[$key])) {
                $columns .= $this->db->quotekey($key) . ', ';
                $len++;
            }
        }

        $columns = rtrim($columns, ', ');

        $sql = 'INSERT INTO ' . $this->table .
            ' (' . $columns . ')' .
            ' VALUES' .
            ' (' . str_repeat('?, ', $len - 1) . '?)';

        $query = $this->db->prepare($sql, $records);

        if (!$query) {
            return [];
        }

        if ($trans) {
            $this->db->begin();
        }

        $result = [];
        $success = false;
        $pdo = $this->db->pdo();

        foreach ($records as $key => $record) {
            $params = array_intersect_key($this->cast($record), $this->fields);

            if ($len !== count($params)) {
                throw new \LogicException('Invalid record: #' . $key);
            }

            $success = $this->db->run($query, array_values($params));
            if (!$success) {
                // no more insert
                break;
            }

            $result[] = $pdo->lastInsertId();
        }

        if ($trans) {
            if ($success) {
                $this->db->commit();
            } else {
                $this->db->rollBack();
            }
        }

        return $result;
    }

    /**
     * Update record in table
     *
     * @param  array|obj    $data
     * @param  string|array $filter
     *
     * @return bool
     *
     * @throws Throwable If error in debug mode
     */
    public function update($data, $filter): bool
    {
        if (!$data) {
            throw new \LogicException('No data provided to update');
        }

        $set = '';
        $params = [];
        $record = $this->cast($data);

        foreach ($record as $key => $value) {
            if (is_array($value)) {
                // raw
                $set .= array_shift($value) . ', ';
            } else {
                if (!isset($this->fields[$key])) {
                    continue;
                }

                $k = ":u_{$key}";
                $set .= $this->db->quotekey($key) . " = $k, ";
                $params[$k] = $value;
            }
        }

        $set = rtrim($set, ', ');
        $sql = 'UPDATE ' . $this->table . ' SET ' . $set;

        $f = $this->db->filter($this->objToFilter($filter));
        if ($f) {
            $sql   .= ' WHERE ' . array_shift($f);
            $params = array_merge($params, $f);
        }

        $query = $this->db->prepare($sql, $params);

        if (!$query) {
            return false;
        }

        $success = $this->db->run($query, $params);

        return $success;
    }

    /**
     * Run prepared query multiple times for updating records
     *
     * @param  array $records  Multidimensional array of records with set and filter key
     * @param  bool  $trans
     *
     * @return bool
     *
     * @throws Throwable If error in debug mode
     */
    public function updateBatch(array $records, bool $trans = true): bool
    {
        $template = reset($records);

        if (!$template || empty($template['data'])) {
            throw new \LogicException('No data provided to update (batch)');
        }

        $len = 0;
        $set = '';

        foreach ($this->cast($template['data']) as $column => $val) {
            if (is_array($val)) {
                // raw
                $set .= array_shift($val) . ', ';
            } else {
                if (!isset($this->fields[$column])) {
                    continue;
                }

                $set .= $this->db->quotekey($column) . " = :u_{$column}, ";
                $len++;
            }
        }

        $set = rtrim($set, ', ');
        $sql = 'UPDATE ' . $this->table . ' SET ' . $set;

        $f = $this->db->filter($template['filter'] ?? null);
        if ($f) {
            $sql .= ' WHERE ' . array_shift($f);
            $len += count($f);
        }

        $query = $this->db->prepare($sql, $records);

        if (!$query) {
            return false;
        }

        if ($trans) {
            $this->db->begin();
        }

        $success = false;

        foreach ($records as $key => $record) {
            $data = [];

            if (isset($record['data'])) {
                $data = [];
                $prefix = ':u_';
                foreach ($this->cast($record['data']) as $column => $value) {
                    if (is_array($value) || !isset($this->fields[$column])) {
                        continue;
                    }

                    $data[$prefix . $column] = $value;
                }
            }

            if (isset($record['filter'])) {
                $filter = $this->db->filter($record['filter']);
                array_shift($filter);

                if ($filter) {
                    $data += $filter;
                }
            }

            if ($len !== count($data)) {
                throw new \LogicException('Invalid record: #' . $key);
            }

            $success = $this->db->run($query, $data);

            if (!$success) {
                // no more update
                break;
            }
        }

        if ($trans) {
            if ($success) {
                $this->db->commit();
            } else {
                $this->db->rollBack();
            }
        }

        return  $success;
    }

    /**
     * Delete from table
     *
     * @param  string|array $filter
     *
     * @return bool
     *
     * @throws Throwable If error in debug mode
     */
    public function delete($filter): bool
    {
        $sql = 'DELETE FROM ' . $this->table;
        $params = [];

        $f = $this->db->filter($this->objToFilter($filter));
        if ($f) {
            $sql .= ' WHERE ' . array_shift($f);
            $params = array_merge($params, $f);
        }

        $query = $this->db->prepare($sql, $params);

        if (!$query) {
            return false;
        }

        $success = $this->db->run($query, $params);

        return $success;
    }

    /**
     * Cast to filter
     *
     * @param  mixed $obj
     *
     * @return mixed
     */
    protected function objToFilter($obj)
    {
        if (is_object($obj)) {
            $filter = [];
            $record = $this->cast($obj);

            foreach ($this->pkeys as $key) {
                if (array_key_exists($key, $record)) {
                    $filter[$key] = $record[$key];
                }
            }

            return $filter;
        }

        return $obj;
    }

    /**
     * Cast from object to array
     *
     * @param  object $obj
     *
     * @return array
     */
    protected function cast($obj): array
    {
        if (is_array($obj)) {
            return $obj;
        }

        $record = [];

        foreach ($this->fields as $key => $value) {
            $name = camelcase($key);
            $get = 'get' . $name;
            $is = 'is' . $name;

            if (method_exists($obj, $get)) {
                $record[$key] = $obj->$get();
            } elseif (method_exists($obj, $is)) {
                $record[$key] = $obj->$is();
            }
        }

        return $record;
    }

    /**
     * Closure for construct class
     *
     * @return Closure
     */
    protected function factory(): \Closure
    {
        $class = $this->map;
        $fields = $this->fields;

        return function(...$props) use ($class, $fields) {
            $obj = new $class();

            foreach ($fields as $key => $value) {
                $set = 'set' . camelcase($key);
                $val = array_shift($props);

                if (method_exists($obj, $set)) {
                    $obj->$set($val);
                }
            }

            return $obj;
        };
    }

    /**
     * Proxy to mapped method
     * Example:
     *     findOneByUsername('foo') = findOne(['username'=>'foo'])
     *
     * @param  string $method
     * @param  array  $args
     *
     * @return mixed
     */
    public function __call($method, array $args)
    {
        $findOne = icutafter('findoneby', $method);
        $find = icutafter('findby', $method);

        if ($findOne) {
            if ($args) {
                $first = array_shift($args);
                array_unshift($args, [snakecase($findOne) => $first]);
            }

            return $this->findOneBy(...$args);
        } elseif ($find) {
            if ($args) {
                $first = array_shift($args);
                array_unshift($args, [snakecase($find) => $first]);
            }

            return $this->findBy(...$args);
        } elseif ($method === 'find') {
            $pvals = (array) reset($args);
            $vcount = count($pvals);
            $pcount = count($this->pkeys);

            if ($vcount !== $pcount) {
                throw new \ArgumentCountError(
                    static::class . '::' . $method . ' expect exactly ' . $pcount .
                    ' parameters, given only ' . $vcount . ' parameters'
                );
            }

            return $this->findOneBy(array_combine($this->pkeys, $pvals));
        }

        throw new \BadMethodCallException(
            'Call to undefined method ' . static::class . '::' . $method
        );
    }
}

<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick;

/**
 * PDO wrapper
 */
class Database
{
    /** @var array */
    protected $options;

    /** @var array Map rule */
    protected $maps = [];

    /** @var PDO */
    protected $pdo;

    /** @var Cache */
    protected $cache;

    /** @var PDOStatement Last query */
    protected $query;

    /** @var bool Last query result */
    protected $querySuccess;

    /** @var array */
    protected $logs = [];

    /** @var array Db info cache */
    protected $info = [];

    /**
     * Class constructor
     *
     * @param Cache $cache
     * @param array $options
     * @param array $maps
     */
    public function __construct(Cache $cache, array $options, array $maps = [])
    {
        $this->cache = $cache;
        $this->setOptions($options);
        $this->setMaps($maps);
    }

    /**
     * Get database driver name
     *
     * @return string
     */
    public function getDriver(): string
    {
        if (empty($this->info['driver'])) {
            $this->info['driver'] = $this->pdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        }

        return $this->info['driver'];
    }

    /**
     * Get server version
     *
     * @return string
     */
    public function getVersion(): string
    {
        if (empty($this->info['version'])) {
            $this->info['version'] = $this->pdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
        }

        return $this->info['version'];
    }

    /**
     * Get maps
     *
     * @return array
     */
    public function getMaps(): array
    {
        return $this->maps;
    }

    /**
     * Set maps
     *
     * @param array $maps
     *
     * @return Database
     */
    public function setMaps(array $maps): Database
    {
        foreach ($maps as $id => $rule) {
            $this->setMap($id, $rule);
        }

        return $this;
    }

    /**
     * Get rule
     *
     * @param  string $id
     *
     * @return array
     */
    public function getMap(string $id): array
    {
        static $default = [
            'class' => null,
            'transformer' => null,
            'select' => null,
            'safe' => []
        ];

        return ($this->maps[$id] ?? []) + $default + ['table' => $id];
    }

    /**
     * Set rule
     *
     * @param string $id
     * @param array  $rule
     *
     * @return Database
     */
    public function setMap(string $id, array $rule): Database
    {
        if (isset($rule['class']) && !class_exists($rule['class'])) {
            throw new \LogicException("Class does not exists: $rule[class]");
        }

        if (isset($rule['transformer']) && !is_callable($rule['transformer'])) {
            throw new \LogicException('Transformer is not callable');
        }

        if (isset($rule['safe']) && !is_array($rule['safe'])) {
            throw new \InvalidArgumentException('Safe option is not array');
        }

        $this->maps[$id] = $rule;

        return $this;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set options, available options (and its default value):
     *     debug: bool       = false (enable debug mode)
     *     log: bool         = false (log query)
     *     driver: string    = unknown (database driver name, eg: mysql, sqlite)
     *     dsn: string       = void (valid dsn)
     *     server: string    = void|127.0.0.1 (on mysql)
     *     port: int         = void|3306 (on mysql)
     *     password: string  = void (on mysql)
     *     username: string  = void
     *     dbname: string    = void
     *     location: string  = void (for sqlite driver)
     *     attributes: array = void (map array attribute and its value)
     *     commands: array   = void (commands after pdo creation)
     *     defaults          = array
     *         fetch_style   = \PDO::FETCH_ASSOC,
     *
     * @param array $options
     * @return Database
     *
     * @throws LogicException
     */
    public function setOptions(array $options): Database
    {
        $options += ['debug' => false, 'log' => false, 'driver' => 'unknown'];

        if (empty($options['dsn'])) {
            $driver = strtolower($options['driver']);

            if ($driver === 'mysql') {
                $options += [
                    'server' => '127.0.0.1',
                    'port' => 3306,
                    'password' => null,
                ];

                if (
                    empty($options['server'])
                    || empty($options['username'])
                    || empty($options['dbname'])
                ) {
                    throw new \LogicException('Invalid mysql driver configuration');
                }

                $options['dsn'] = 'mysql:host=' . $options['server'] .
                                  ';port=' . $options['port'] .
                                  ';dbname=' . $options['dbname'];
            } elseif ($driver === 'sqlite') {
                if (empty($options['location'])) {
                    throw new \LogicException('Invalid sqlite driver configuration');
                }

                // location can be full filepath or :memory:
                $options['dsn'] = 'sqlite:' . $options['location'];
            } else {
                $error = 'Currently, there is no logic for ' . $driver .
                         ' DSN creation, please provide a valid one';
                throw new \LogicException($error);
            }
        }

        $this->options = $options;
        $this->pdo = null;
        $this->info = [];
        $this->resetQuery();

        return $this;
    }

    /**
     * Get pdo
     *
     * @return PDO
     *
     * @throws LogicException If construct failed and not in debug mode
     * @throws Throwable   If construct failed and in debug mode
     */
    public function pdo(): \PDO
    {
        if ($this->pdo) {
            return $this->pdo;
        }

        $options = $this->options;

        try {
            $pdo = new \PDO(
                $options['dsn'],
                $options['username'] ?? null,
                $options['password'] ?? null
            );

            foreach ($options['attributes'] ?? [] as $attribute => $value) {
                $pdo->setAttribute($attribute, $value);
            }

            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            foreach ($options['commands'] ?? [] as $command) {
                $pdo->exec($command);
            }

            $this->pdo = $pdo;
        } catch (\Throwable $e) {
            if ($options['debug']) {
                throw $e;
            }

            throw new \LogicException('Invalid database configuration');
        }

        return $this->pdo;
    }

    /**
     * Build filter with rule below:
     *
     * @param  string|array $filter
     *
     * @return array
     */
    public function filter($filter): array
    {
        if (!$filter) {
            return [];
        }

        // operator map
        static $map = [
            '='   => '=',
            '>'   => '>',
            '<'   => '<',
            '>='  => '>=',
            '<='  => '<=',
            '<>'  => '<>',
            '!='  => '!=',
            '&'   => 'AND',
            '|'   => 'OR',
            '^'   => 'XOR',
            '!'   => 'NOT',
            '~'   => 'LIKE',
            '!~'  => 'NOT LIKE',
            '@'   => 'SOUNDS LIKE',
            '[]'  => 'IN',
            '![]' => 'NOT IN',
            '><'  => 'BETWEEN',
            '!><' => 'NOT BETWEEN',
        ];
        static $mapkeys = '=<>&|^!~@[] ';

        $ctr    = 0;
        $str    = '';
        $result = [];

        foreach ((array) $filter as $key => $value) {
            if (is_numeric($key)) {
                if (is_string($value)) {
                    // raw
                    $str   .= ' ' . $value;
                } elseif ($cfilter = $this->filter((array) $value)) {
                    $str   .= ' AND (' . array_shift($cfilter) . ')';
                    $result = array_merge($result, $cfilter);
                }

                continue;
            }

            $raw  = is_string($value) && startswith('```', $value);
            $expr = $raw ? substr($value, 3) : $value;
            // clear column from comments format
            $ccol = (false === ($pos = strpos($key, '#'))) ? $key : substr($key, 0, $pos);
            $col  = trim($ccol, $mapkeys);
            $a1   = substr($ccol, 0, 1);
            $a2   = substr($ccol, 0, 2);
            $a3   = substr($ccol, 0, 3);
            $b1   = substr($ccol, -1);
            $b2   = substr($ccol, -2);
            $b3   = substr($ccol, -3);

            $str .= ' ' . ($map[$a3] ?? $map[$a2] ?? $map[$a1] ?? ($ctr > 0 ? 'AND' : ''));

            if ($col) {
                $str .= ' ' . $this->quotekey($col);
                $str .= ' ' . ($map[$b3] ?? $map[$b2] ?? $map[$b1] ?? '=');
            }

            if ($raw) {
                $str .= ' ' . $expr;
            } else {
                if ($b3 === '!><' || $b2 === '><') {
                    if (!is_array($expr)) {
                        $error = 'BETWEEN operator needs array operand, ' .
                                 gettype($expr) . ' given';

                        throw new \LogicException($error);
                    }

                    $str .= " :{$col}1 AND :{$col}2";
                    $result[":{$col}1"] = array_shift($expr);
                    $result[":{$col}2"] = array_shift($expr);
                } elseif ($b3 === '![]' || $b2 === '[]') {
                    $str .= ' (';
                    $i = 1;

                    foreach ((array) $expr as $val) {
                        $k = ":{$col}{$i}";
                        $str .= "$k, ";
                        $result[$k] = $val;
                        $i++;
                    }

                    $str = rtrim($str, ', ') . ')';
                } elseif (is_array($expr)) {
                    $cfilter = $this->filter((array) $expr);

                    if ($cfilter) {
                        $str .= ' (' . array_shift($cfilter) . ')';
                        $result = array_merge($result, $cfilter);
                    }
                } elseif ($col) {
                    $k = ":{$col}";
                    $str .= " $k";
                    $result[$k] = $expr;
                } else {
                    $str .= ' ' . $expr;
                }
            }

            $ctr++;
        }

        array_unshift($result, trim($str));

        return $result;
    }

    /**
     * Check if table exists
     *
     * @param  string $table
     * @param  int    $ttl
     *
     * @return bool
     */
    public function tableExists(string $table, int $ttl = 0): bool
    {
        $rule = $this->getMap($table);
        $sql = 'SELECT 1 FROM ' . $this->quotekey($rule['table']);

        if ($ttl && $this->isCached($sql, [], $hash, $result)) {
            return $result;
        }

        try {
            $result = $this->prepareQuery($sql)->runQuery();
        } catch (\Exception $e) {
            $result = false;
        }

        if ($ttl) {
            $this->cache->set($hash, $result, $ttl);
        }

        return $result !== false;
    }

    /**
     * Run select query, with count
     * @see  Database::find
     *
     * @param  string $table
     * @param  string|array $filter  @see Database::filter
     * @param  array  $options
     * @param  int    $ttl
     *
     * @return int
     *
     * @throws Throwable If error in debug mode
     */
    public function count(
        string $table,
        $filter = null,
        array $options = null,
        int $ttl = null
    ): int {
        $res = $this->find(
            $table,
            $filter,
            ['column' => 'count(*) as `cc`', 'raw'=>true] + (array) $options,
            $ttl
        );

        return $res ? (int) $res[0]['cc'] : 0;
    }

    /**
     * Paginate records
     *
     * @param  string      $table
     * @param  int $page
     * @param  int $limit
     * @param  string|filter      $filter
     * @param  array       $options
     * @param  int    $ttl
     *
     * @return array
     *
     * @throws Throwable If error in debug mode
     */
    public function paginate(
        string $table,
        int $page = 1,
        int $limit = 10,
        $filter = null,
        array $options = null,
        int $ttl = null
    ): array {
        $total = $this->count($table, $filter, $options, $ttl);
        $pages = (int) ceil($total / $limit);
        $subset = [];
        $start = 0;
        $end = 0;

        if ($page > 0) {
            $offset = ($page - 1) * $limit;
            $subset = $this->find($table, $filter, compact('limit','offset') + (array) $options, $ttl);
            $start = $offset + 1;
            $end = $offset + count($subset);
        }

        return compact('subset', 'total', 'pages', 'page', 'start', 'end');
    }

    /**
     * Run select query, set limit to 1 (one)
     * @see  Database::find
     *
     * @param  string $table
     * @param  string|array $filter  @see Database::filter
     * @param  array  $options
     * @param  int    $ttl
     *
     * @return mixed
     *
     * @throws Throwable If error in debug mode
     */
    public function findOne(
        string $table,
        $filter = null,
        array $options = null,
        int $ttl = null
    ) {
        $result = $this->find($table, $filter, ['limit' => 1] + (array) $options, $ttl);

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
     * @param  string $table
     * @param  string|array $filter  @see Database::filter
     * @param  array  $options
     * @param  int    $ttl
     *
     * @return array
     *
     * @throws Throwable If error in debug mode
     */
    public function find(string $table, $filter = null, array $options = null, int $ttl = null): array
    {
        $options = (array) $options;
        $options += [
            'column' => null,
            'group' => null,
            'having' => null,
            'order' => null,
            'limit' => 0,
            'offset' => 0,
            'raw' => false,
        ];

        $rule = $this->getMap($table);
        $qtable = $this->quotekey($rule['table']);
        $columnRule = ($options['raw'] || !isset($rule['select'])) ? null : $rule['select'];
        $column = $options['column'] ?? $columnRule ?? '*';
        $uttl = $ttl ?? $rule['ttl'] ?? 0;

        $sql = "SELECT $column FROM $qtable";
        $params = [];

        $f = $this->filter($filter, $sql);
        if ($f) {
            $sql .= ' WHERE ' . array_shift($f);
            $params = array_merge($params, $f);
        }

        if ($options['group']) {
            $sql .= ' GROUP BY ' . $options['group'];
        }

        $f = $this->filter($options['having'], $sql);
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

        if ($uttl && $this->isCached($sql, $params, $hash, $result)) {
            return $result;
        }

        $success = $this->prepareQuery($sql, $params)->runQuery($params);

        if (!$success) {
            return [];
        }

        $args = $options['raw'] ? [\PDO::FETCH_ASSOC] : $this->fetchArgs($rule);
        $result = $this->query->fetchAll(...$args) ?? [];

        if ($uttl) {
            $this->cache->set($hash, $result, $uttl);
        }

        return $result;
    }

    /**
     * Insert record into table
     *
     * @param  string $table
     * @param  array  $record
     *
     * @return int Last inserted id or 0
     *
     * @throws Throwable If error in debug mode or no record provided
     */
    public function insert(string $table, array $record): int
    {
        if (!$record) {
            throw new \LogicException('No data provided to insert');
        }

        $len = -1;
        $columns = '';
        $rule = $this->getMap($table);
        $params = [];

        foreach ($record as $key => $value) {
            if ($rule['safe'] && !in_array($key, $rule['safe'])) {
                continue;
            }

            $columns .= $this->quotekey($key) . ', ';
            $params[] = $value;
            $len++;
        }

        $columns = rtrim($columns, ', ');

        $sql = 'INSERT INTO '.$this->quotekey($rule['table']).
            ' (' . $columns . ')' .
            ' VALUES' .
            ' (' . str_repeat('?, ', $len) . '?)';

        $success = $this->prepareQuery($sql, $params)->runQuery($params);

        return $success ? (int) $this->lastInsertId() : 0;
    }

    /**
     * Run prepared query multiple times for inserting records
     *
     * @param  string       $table
     * @param  array        $records
     * @param  bool $trans
     *
     * @return array of inserted ids
     */
    public function insertBatch(string $table, array $records, bool $trans = true): array
    {
        // first record as template
        $template = reset($records);

        if (!$template) {
            throw new \LogicException('No data provided to insert (batch)');
        }

        $len = 0;
        $columns = '';
        $rule = $this->getMap($table);
        $safe = array_flip($rule['safe']);

        foreach ($template as $key => $value) {
            if (!$safe || isset($safe[$key])) {
                $columns .= $this->quotekey($key) . ', ';
                $len++;
            }
        }

        $columns = rtrim($columns, ', ');

        $sql = 'INSERT INTO '.$this->quotekey($rule['table']).
            ' (' . $columns . ')' .
            ' VALUES' .
            ' (' . str_repeat('?, ', $len - 1) . '?)';

        if (!$this->prepareQuery($sql, $records)->query) {
            return [];
        }

        if ($trans) {
            $this->begin();
        }

        $result = [];

        foreach ($records as $key => $record) {
            $params = $safe ? array_intersect_key($record, $safe) : $record;

            if ($len !== count($params)) {
                throw new \LogicException("Invalid record #{$key}");
            }

            if (!$this->runQuery(array_values($params))) {
                // no more insert
                break;
            }

            $result[] = $this->lastInsertId();
        }

        if ($trans) {
            if ($this->querySuccess) {
                $this->commit();
            } else {
                $this->rollBack();
            }
        }

        return $result;
    }

    /**
     * Update record in table
     *
     * @param  string $table
     * @param  array  $record
     * @param  string|array $filter
     *
     * @return bool
     *
     * @throws Throwable If error in debug mode
     */
    public function update(string $table, array $record, $filter): bool
    {
        if (!$record) {
            throw new \LogicException('No data provided to update');
        }

        $set = '';
        $params = [];
        $rule = $this->getMap($table);
        $safe = array_flip($rule['safe']);

        foreach ($record as $key => $value) {
            if (is_array($value)) {
                // raw
                $set .= array_shift($value) . ', ';
            } else {
                if ($safe && !isset($safe[$key])) {
                    continue;
                }

                $k = ":u_{$key}";
                $set .= $this->quotekey($key) . " = $k, ";
                $params[$k] = $value;
            }
        }

        $set = rtrim($set, ', ');
        $sql = 'UPDATE ' . $this->quotekey($rule['table']) . ' SET ' . $set;

        $f = $this->filter($filter);
        if ($f) {
            $sql   .= ' WHERE ' . array_shift($f);
            $params = array_merge($params, $f);
        }

        $success = $this->prepareQuery($sql, $params)->runQuery($params);

        return $success;
    }

    /**
     * Run prepared query multiple times for updating records
     *
     * @param  string       $table
     * @param  array        $template Array containing columns and filter key
     * @param  array        $records  Multidimensional array of records with set and filter key
     * @param  bool $trans
     *
     * @return bool
     *
     * @throws Throwable If error in debug mode
     */
    public function updateBatch(
        string $table,
        array $template,
        array $records,
        bool $trans = true
    ): bool {
        $template += ['set'=>[],'filter'=>null];

        if (!$template || !$template['set'] || !$records) {
            throw new \LogicException('Query template error or No data provided to update (batch)');
        }

        $len = 0;
        $set = '';
        $params = [];
        $rule = $this->getMap($table);
        $safe = array_flip($rule['safe']);

        foreach ($template['set'] as $column) {
            if (is_array($column)) {
                // raw
                $set .= array_shift($column) . ', ';
            } else {
                if ($safe && !isset($safe[$column])) {
                    continue;
                }

                $set .= $this->quotekey($column) . " = :u_{$column}, ";
                $len++;
            }
        }

        $set = rtrim($set, ', ');
        $sql = 'UPDATE ' . $this->quotekey($rule['table']) . ' SET ' . $set;

        $f = $this->filter($template['filter']);
        if ($f) {
            $sql .= ' WHERE ' . array_shift($f);
            $params = $f;
            $len += count($f);
        }

        if (!$this->prepareQuery($sql, $records)->query) {
            return false;
        }

        if ($trans) {
            $this->begin();
        }

        foreach ($records as $key => $record) {
            $rdata = isset($record['data']) ? (array) $record['data'] : [];
            $rfilt = isset($record['filter']) ? (array) $record['filter'] : [];

            $data = array_merge(
                $params,
                quotekey($safe ? array_intersect_key($rdata, $safe) : $rdata, [':u_']),
                quotekey($rfilt, [':'])
            );

            if ($len !== count($data)) {
                throw new \LogicException("Invalid record #{$key}");
            }

            if (!$this->runQuery($data)) {
                // no more update
                break;
            }
        }

        if ($trans) {
            if ($this->querySuccess) {
                $this->commit();
            } else {
                $this->rollBack();
            }
        }

        return  $this->querySuccess;
    }

    /**
     * Delete from table
     *
     * @param  string $table
     * @param  string|array $filter
     *
     * @return bool
     *
     * @throws Throwable If error in debug mode
     */
    public function delete(string $table, $filter): bool
    {
        $rule = $this->getMap($table);
        $sql = 'DELETE FROM ' . $this->quotekey($rule['table']);
        $params = [];

        $f = $f = $this->filter($filter);
        if ($f) {
            $sql .= ' WHERE ' . array_shift($f);
            $params = array_merge($params, $f);
        }

        $success = $this->prepareQuery($sql, $params)->runQuery($params);

        return $success;
    }

    /**
     * Get last query result
     *
     * @return bool
     */
    public function isQuerySuccess(): bool
    {
        return $this->querySuccess;
    }

    /**
     * isQuerySuccess complement
     *
     * @return bool
     */
    public function isQueryFailed(): bool
    {
        return !$this->isQuerySuccess();
    }

    /**
     * Get last query
     *
     * @return PDOStatement|null
     */
    public function getQuery(): ?\PDOStatement
    {
        return $this->query;
    }

    /**
     * Get last error message
     *
     * @return string
     */
    public function getMessage(): string
    {
        if (isset($this->info['error'])) {
            return $this->info['error'];
        }

        $error = $this->query ? $this->query->errorInfo() : [];

        return ($error && '00000' !== $error[0]) ? "[{$error[1]}] $error[2]" : '';
    }

    /**
     * Proxy to PDO::lastInsertId
     *
     * @see  PDO::lastInsertID
     *
     * @throws RuntimeException
     * @throws LogicException
     */
    public function lastInsertId(string $name = null): string
    {
        return $this->pdo()->lastInsertId($name);
    }

    /**
     * Execute callback in transaction
     *
     * @param  callable $callback Callable accept Database instance,
     *                            returning false will rollback trans
     *
     * @return bool
     */
    public function execute(callable $callback): bool
    {
        $this->begin();

        if ($callback($this) !== false) {
            $this->commit();

            return true;
        }

        $this->rollBack();

        return false;
    }

    /**
     * Proxy to PDO::beginTransaction
     */
    public function begin(): bool
    {
        $out = $this->pdo()->beginTransaction();
        $this->info['trans'] = true;

        return $out;
    }

    /**
     * Proxy to PDO::commit
     */
    public function commit(): bool
    {
        $out = $this->pdo()->commit();
        $this->info['trans'] = false;

        return $out;
    }

    /**
     * Proxy to PDO::rollBack
     */
    public function rollBack(): bool
    {
        $out = $this->pdo()->rollBack();
        $this->info['trans'] = false;

        return $out;
    }

    /**
     * Get transaction status
     *
     * @return bool
     */
    public function trans(): bool
    {
        return $this->info['trans'] ?? false;
    }

    /**
     * Return quoted identifier name
     *
     * @param  string  $key
     * @param  boolean $split
     * @param  string  $custom Custom driver
     *
     * @return string
     */
    public function quotekey(string $key, bool $split = true, string $custom = null): ?string
    {
        static $quotes = [
            '``' => ['sqlite', 'mysql'],
            '""' => ['pgsql', 'oci'],
            '[]' => ['mssql','sqlsrv','odbc','sybase','dblib'],
        ];

        $driver = $custom ??  $this->getDriver();

        foreach ($quotes as $use => $drivers) {
            if (in_array($driver, $drivers)) {
                return $use[0] . ($split ? implode($use[1] . '.' . $use[0], explode('.', $key)) : $key) . $use[1];
            }
        }

        return $key;
    }

    /**
     * Get logs
     *
     * @return array
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * Clear logs
     *
     * @return Database
     */
    public function clearLogs(): Database
    {
        $this->logs = [];

        return $this;
    }

    /**
     * Check sql cache and get data
     *
     * @param  string      $sql
     * @param  array       $params
     * @param  string|null &$hash
     * @param  mixed      &$data
     *
     * @return bool
     */
    protected function isCached(
        string $sql,
        array $params,
        string &$hash = null,
        &$data = null
    ): bool {
        $hash = hash($sql . ':params:' . stringify($params)) . '.sql';
        $exists = $this->cache->exists($hash);

        if ($exists) {
            $cached = $this->cache->get($hash);
            $data = $cached[0];
        }

        return $exists;
    }

    /**
     * Get fetch args
     *
     * @param  array  $rule
     *
     * @return array
     */
    protected function fetchArgs(array $rule): array
    {
        if ($rule['class']) {
            $class = $rule['class'];
            $args = [
                \PDO::FETCH_FUNC,
                function(...$props) use ($class) {
                    return new $class(...$props);
                }
            ];
        } elseif ($rule['transformer']) {
            $args = [
                \PDO::FETCH_FUNC,
                $rule['transformer']
            ];
        } else {
            $defaults = $this->options['defaults'] ?? [];
            $args = [$fetchStyle ?? $defaults['fetch_style'] ?? \PDO::FETCH_ASSOC];
        }

        return $args;
    }

    /**
     * Log query
     *
     * @param  string $sql
     * @param  array|null  $params
     *
     * @return void
     */
    protected function log(string $sql, array $params = null): void
    {
        if ($this->options['log']) {
            $this->logs[] = [$sql, $params];
        }
    }

    /**
     * Run query
     *
     * @param  array  $params
     *
     * @return bool
     *
     * @throws Throwable If error in debug mode
     */
    protected function runQuery(array $params = []): bool
    {
        $result = false;

        if ($this->query) {
            try {
                $result = $this->query->execute($params);
            } catch (\Throwable $e) {
                $result = false;
                $this->consumeException($e);
            }
        }

        $this->querySuccess = $result;

        return $result;
    }

    /**
     * Prepare and log sql query
     *
     * @param  string $sql
     * @param  array  $params
     *
     * @return Database
     */
    protected function prepareQuery(string $sql, array $params = []): Database
    {
        $this->resetQuery();
        $this->log($sql, $params);

        try {
            $this->query = $this->pdo()->prepare($sql);
        } catch (\Throwable $e) {
            $this->consumeException($e);
        }

        return $this;
    }

    /**
     * Consume exception
     *
     * @param  \Throwable $e
     * @param  string     $error
     *
     * @return bool
     *
     * @throws RuntimeException In debug mode
     */
    protected function consumeException(\Throwable $e, string $error = 'Invalid query'): ?bool
    {
        $this->info['error'] = $error;

        if ($this->options['debug']) {
            throw $e;
        }

        return true;
    }

    /**
     * Reset query state
     *
     * @return void
     */
    protected function resetQuery(): void
    {
        $this->query = null;
        $this->querySuccess   = false;
        $this->info['error'] = null;
        $this->info['map']   = null;
    }

    /**
     * Call find/findOne magically
     *
     * @param  string $method
     * @param  string $param
     * @param  array  $args
     *
     * @return mixed
     */
    protected function callFind(string $method, string $param, array $args)
    {
        $x = explode('By', $param);
        $table = snakecase(array_shift($x));
        $column = snakecase(implode('', $x));

        if ($column && $args) {
            $first = array_shift($args);
            array_unshift($args, [$column => $first]);
        }

        array_unshift($args, $table);

        return call_user_func_array([$this, $method], $args);
    }

    /**
     * Prohibit cloning
     *
     * @return void
     *
     * @codeCoverageIgnore
     */
    private function __clone()
    {
    }

    /**
     * Proxy to mapped method
     * Example:
     *     findOneUser = findOne('user')
     *
     * @param  string $method
     * @param  array  $args
     *
     * @return mixed
     */
    public function __call($method, array $args)
    {
        $findOne = cutafter('findOne', $method);
        $find = cutafter('find', $method);

        if ($findOne) {
            return $this->callFind('findOne', $findOne, $args);
        } elseif ($find) {
            return $this->callFind('find', $find, $args);
        }

        static $map = [
            'insertBatch' => 11,
            'updateBatch' => 11,
            'paginate' => 8,
            'insert' => 6,
            'update' => 6,
            'delete' => 6,
            'count' => 5,
        ];

        foreach ($map as $m => $cut) {
            if ($m === substr($method, 0, $cut)) {
                $table = snakecase(substr($method, $cut));
                array_unshift($args, $table);

                return call_user_func_array([$this, $m], $args);
            }
        }

        throw new \BadMethodCallException('Invalid method ' . static::class . '::' . $method);
    }
}

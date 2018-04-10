<?php declare(strict_types=1);

namespace Fal\Stick\Database;

use function Fal\Stick\quote;
use function Fal\Stick\quoteAll;
use function Fal\Stick\startswith;
use Fal\Stick\Cache;

class Sql implements DatabaseInterface
{
    /** @var array */
    protected $option;

    /** @var PDO */
    protected $pdo;

    /** @var Cache */
    protected $cache;

    /** @var array */
    protected $logs = [];

    /** @var array */
    protected $errors = [];

    /** @var string */
    protected $driverName;

    /** @var string */
    protected $driverVersion;

    /** @var bool */
    protected $trans;

    /**
     * Class constructor
     *
     * @param Cache $cache
     * @param array $option
     */
    public function __construct(Cache $cache, array $option)
    {
        $this->cache = $cache;
        $this->setOption($option);
    }

    /**
     * Get cache
     *
     * @return Cache
     */
    public function getCache(): Cache
    {
        return $this->cache;
    }

    /**
     * Get option
     *
     * @return array
     */
    public function getOption(): array
    {
        return $this->option;
    }

    /**
     * Set option, available option (and its default value):
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
     *     defaults: array   = void (defaults configuration)
     *
     * @param array $option
     * @return Sql
     *
     * @throws LogicException
     */
    public function setOption(array $option): Sql
    {
        $option += ['debug' => false, 'log' => false, 'driver' => 'unknown'];

        if (empty($option['dsn'])) {
            $driver = strtolower($option['driver']);

            if ($driver === 'mysql') {
                $option += [
                    'server' => '127.0.0.1',
                    'port' => 3306,
                    'password' => null,
                ];

                if (
                    empty($option['server'])
                    || empty($option['username'])
                    || empty($option['dbname'])
                ) {
                    throw new \LogicException('Invalid mysql driver configuration');
                }

                $option['dsn'] = 'mysql:host=' . $option['server'] .
                                  ';port=' . $option['port'] .
                                  ';dbname=' . $option['dbname'];
            } elseif ($driver === 'sqlite') {
                if (empty($option['location'])) {
                    throw new \LogicException('Invalid sqlite driver configuration');
                }

                // location can be full filepath or :memory:
                $option['dsn'] = 'sqlite:' . $option['location'];
            } else {
                throw new \LogicException(
                    'Currently, there is no logic for ' . $driver .
                    ' DSN creation, please provide a valid one'
                );
            }
        } elseif (
            empty($option['dbname'])
            && preg_match('/^.+?(?:dbname|database)=(.+?)(?=;|$)/is', $option['dsn'], $parts)
        ) {
            $option['dbname'] = $parts[1];
        }

        $this->option = $option;
        $this->pdo = null;
        $this->driverName = null;
        $this->driverVersion = null;
        $this->trans = null;

        return $this;
    }

    /**
     * Get pdo
     *
     * @return PDO
     *
     * @throws LogicException If construct failed and not in debug mode
     * @throws PDOException   If construct failed and in debug mode
     */
    public function pdo(): \PDO
    {
        if ($this->pdo) {
            return $this->pdo;
        }

        $o = $this->option;

        try {
            $pdo = new \PDO($o['dsn'], $o['username'] ?? null, $o['password'] ?? null);

            foreach ($o['attributes'] ?? [] as $attribute => $value) {
                $pdo->setAttribute($attribute, $value);
            }

            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            foreach ($o['commands'] ?? [] as $command) {
                $pdo->exec($command);
            }

            $this->pdo = $pdo;
        } catch (\PDOException $e) {
            if ($o['debug']) {
                throw $e;
            }

            throw new \LogicException('Invalid database configuration');
        }

        return $this->pdo;
    }

    /**
     * Check if table exists
     *
     * @param  string $table
     * @param  int    $ttl
     *
     * @return bool
     */
    public function isTableExists(string $table, int $ttl = 0): bool
    {
        $res = $this->run('SELECT 1 FROM ' . $this->quotekey($table), null, $ttl);

        return $res['success'];
    }

    /**
     * Perform self::run in sequence
     *
     * @param  array $queries       [[sql, params, ttl]]
     * @param  bool  $stopOnFailure
     *
     * @return array
     */
    public function runAll(array $queries, bool $stopOnFailure = true): array
    {
        $result = [];

        foreach ($queries as $query) {
            $res = $this->run(...$query);
            $result[] = $res['data'];
            $this->error($res);

            if ($stopOnFailure && !$res['success']) {
                break;
            }
        }

        return $result;
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
        $map = [
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
        $mapkeys = '=<>&|^!~@[] ';

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
                        throw new \LogicException(
                            'BETWEEN operator needs an array operand, ' .
                            gettype($expr) . ' given'
                        );
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
     * {@inheritdoc}
     */
    public function getDriverName(): string
    {
        return $this->driverName ?? $this->pdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function getDriverVersion(): string
    {
        return $this->driverVersion ?? $this->pdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    /**
     * {@inheritdoc}
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * {@inheritdoc}
     */
    public function getSchema(string $table, array $fields = null, int $ttl = 0): array
    {
        if ($ttl && $this->cache->isCached($hash, $data, 'schema', $table, $fields)) {
            return $data[0];
        }

        if (strpos($table, '.')) {
            list($schemaName, $table) = explode('.', $table);
        }

        // Supported engines
        $cmdPattern = <<<PTRN
/^(?:
    (sqlite2?)|
    (mysql)|
    (mssql|sqlsrv|sybase|dblib|pgsql|odbc)|
    (oci)
)$/x
PTRN;
        $driver = $this->getDriverName();
        $qtable = $this->quotekey($table);
        $dbname = $schemaName ?? $this->option['dbname'] ?? '';

        if ($dbname) {
            $dbname = $this->quotekey($dbname);
        }

        preg_match($cmdPattern, $driver, $match);

        // @codeCoverageIgnoreStart
        if (isset($match[1]) && $match[1]) {
            $cmd = [
                'PRAGMA table_info(' . $qtable. ')',
                'name', 'type', 'dflt_value', 'notnull', 0, 'pk', TRUE,
            ];
        } elseif (isset($match[2]) && $match[2]) {
            $cmd = [
                'SHOW columns FROM ' . $dbname . '.' . $qtable,
                'Field', 'Type', 'Default', 'Null', 'YES', 'Key', 'PRI',
            ];
        } elseif (isset($match[3]) && $match[3]) {
            $cmd = [
                'SELECT '.
                    'C.COLUMN_NAME AS field,' .
                    'C.DATA_TYPE AS type,' .
                    'C.COLUMN_DEFAULT AS defval,' .
                    'C.IS_NULLABLE AS nullable,' .
                    'T.CONSTRAINT_TYPE AS pkey ' .
                'FROM INFORMATION_SCHEMA.COLUMNS AS C ' .
                'LEFT OUTER JOIN ' .
                    'INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K ' .
                    'ON ' .
                        'C.TABLE_NAME=K.TABLE_NAME AND ' .
                        'C.COLUMN_NAME=K.COLUMN_NAME AND ' .
                        'C.TABLE_SCHEMA=K.TABLE_SCHEMA ' .
                        ($dbname ? 'AND C.TABLE_CATALOG=K.TABLE_CATALOG ' : '').
                'LEFT OUTER JOIN ' .
                    'INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON ' .
                        'K.TABLE_NAME=T.TABLE_NAME AND ' .
                        'K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND ' .
                        'K.TABLE_SCHEMA=T.TABLE_SCHEMA ' .
                        ($dbname ? 'AND K.TABLE_CATALOG=T.TABLE_CATALOG ' : '') .
                'WHERE ' .
                    'C.TABLE_NAME=' . $qtable .
                    ($dbname ? ' AND C.TABLE_CATALOG=' . $qtable : ''),
                'field', 'type', 'defval', 'nullable', 'YES', 'pkey', 'PRIMARY KEY',
            ];
        } elseif (isset($match[4]) && $match[4]) {
            $cmd = [
                'SELECT c.column_name AS field, ' .
                    'c.data_type AS type, ' .
                    'c.data_default AS defval, ' .
                    'c.nullable AS nullable, ' .
                    '(SELECT t.constraint_type ' .
                        'FROM all_cons_columns acc' .
                        ' LEFT OUTER JOIN all_constraints t' .
                        ' ON acc.constraint_name=t.constraint_name' .
                        ' WHERE acc.table_name=' . $qtable .
                        ' AND acc.column_name=c.column_name' .
                        ' AND constraint_type=' . $this->quote('P') . ') AS pkey '.
                'FROM all_tab_cols c ' .
                'WHERE c.table_name=' . $qtable,
                'FIELD', 'TYPE', 'DEFVAL', 'NULLABLE', 'Y', 'PKEY', 'P',
            ];
        } else {
            throw new \DomainException('Driver ' . $driver . ' is not supported');
        }
        // @codeCoverageIgnoreEnd

        $query = $this->pdo()->query($cmd[0]);
        $schema = $query->fetchAll(\PDO::FETCH_ASSOC);
        $rows = [];

        foreach ($schema as $row) {
            if ($fields && !in_array($row[$cmd[1]], $fields)) {
                continue;
            }

            $rows[$row[$cmd[1]]] = [
                'type' => $row[$cmd[2]],
                'default' => $row[$cmd[3]],
                'nullable' => $row[$cmd[4]] == $cmd[5],
                'pkey' => $row[$cmd[6]] == $cmd[7],
            ];
        }

        if ($ttl) {
            // Save to cache backend
            $this->cache->set($hash, $rows, $ttl);
        }

        return $rows;
    }

    /**
     * {@inheritdoc}
     */
    public function getQuote(): array
    {
        $quotes = [
            '``' => 'sqlite2?|mysql',
            '""' => 'pgsql|oci',
            '[]' => 'mssql|sqlsrv|odbc|sybase|dblib',
        ];

        preg_match(
            '/^(?:(' . implode(')|(', $quotes) . '))$/',
            $this->getDriverName(),
            $match
        );

        foreach ($match as $key => $part) {
            if ($key > 0 && $part) {
                $keys = array_keys($quotes);

                return str_split($keys[$key - 1]);
            }
        }

        return ['',''];
    }

    /**
     * {@inheritdoc}
     */
    public function quotekey(string $key, bool $split = true): string
    {
        return quote($split ? explode('.', $key) : $key, $this->getQuote(), '.');
    }

    /**
     * {@inheritdoc}
     */
    public function begin(): bool
    {
        $out = $this->pdo()->beginTransaction();
        $this->trans = true;

        return $out;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): bool
    {
        $out = $this->pdo()->commit();
        $this->trans = false;

        return $out;
    }

    /**
     * {@inheritdoc}
     */
    public function rollback(): bool
    {
        $out = $this->pdo()->rollBack();
        $this->trans = false;

        return $out;
    }

    /**
     * {@inheritdoc}
     */
    public function isTrans(): bool
    {
        return $this->trans ?? false;
    }

    /**
     * {@inheritdoc}
     */
    public function run(string $sql, array $params = null, int $ttl = 0): array
    {
        if ($ttl && $this->cache->isCached($hash, $data, 'sql', $sql, $params)) {
            return $data[0];
        }

        $this->log($sql, $params);

        $params = (array) $params;
        $one = is_scalar(reset($params));
        $data = [];
        $safe = '00000';

        try {
            $query = $this->pdo()->prepare($sql);
            $fetch = (bool) preg_match(
                '/(?:^[\s\(]*(?:EXPLAIN|SELECT|PRAGMA|SHOW)|RETURNING)\b/is',
                $sql
            );
            $error = [$safe,null,null];
            $use = $error;

            foreach ($one ? [$params] : $params as $param) {
                $res = $query->execute($param);
                $error = $query->errorCode() === $safe ? $use : $query->errorInfo();
                $data[] = $fetch ? $query->fetchAll(\PDO::FETCH_ASSOC) : $res;
            }
        } catch (\PDOException $e) {
            $error = $e->errorInfo;
            $ttl = 0;
        }

        $result = [
            'success' => $error[0] === $safe,
            'error' => $error,
            'data' => $one && $data ? $data[0] : $data,
        ];

        if ($ttl) {
            $this->cache->set($hash, $result, $ttl);
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function select(string $table, $filter = null, array $option = null, int $ttl = 0): array
    {
        $use = ($option ?? []) + [
            'column' => null,
            'group' => null,
            'having' => null,
            'order' => null,
            'limit' => 0,
            'offset' => 0,
        ];

        $param = [];
        $sql = 'SELECT ';

        if (is_array($use['column'])) {
            $sql .= implode(',', array_map([$this, 'quotekey'], $use['column']));
        } else {
            $sql .= $use['column'] ?? '*';
        }

        $sql .= ' FROM ' . $table;

        $f = $this->filter($filter);
        if ($f) {
            $sql .= ' WHERE ' . array_shift($f);
            $param = array_merge($param, $f);
        }

        if ($use['group']) {
            $sql .= ' GROUP BY ' . $use['group'];
        }

        $f = $this->filter($use['having']);
        if ($f) {
            $sql .= ' HAVING ' . array_shift($f);
            $param = array_merge($param, $f);
        }

        if ($use['order']) {
            $sql .= ' ORDER BY ' . $use['order'];
        }

        if ($use['limit']) {
            $sql .= ' LIMIT ' . max(0, $use['limit']);

            if ($use['offset']) {
                $sql .= ' OFFSET ' . max(0, $use['offset']);
            }
        }

        $res = $this->run($sql, $param, $ttl);
        $this->error($res);

        return $res['data'];
    }

    /**
     * {@inheritdoc}
     */
    public function selectOne(string $table, $filter = null, array $option = null, int $ttl = 0): array
    {
        $res = $this->select($table, $filter, ['limit'=>1] + (array) $option, $ttl);

        return $res[0] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function insert(string $table, array $data): string
    {
        $len = -1;
        $param = [];
        $sql = 'INSERT INTO ' . $this->quotekey($table) . '(';

        foreach ($data as $key => $value) {
            $sql .= $this->quotekey($key) . ', ';
            $param[] = $value;
            $len++;
        }

        $sql = rtrim($sql, ', ') . ') VALUES (' . str_repeat('?, ', $len) . '?)';
        $res = $this->run($sql, $param);
        $this->error($res);

        return $res['success'] ? $this->pdo()->lastInsertId() : '0';
    }

    /**
     * {@inheritdoc}
     */
    public function update(string $table, array $data, $filter): bool
    {
        $sql = 'UPDATE ' . $this->quotekey($table) . ' SET ';
        $param = [];

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // raw
                $sql .= reset($value) . ', ';
            } else {
                $k = ':u_' . $key;
                $sql .= $this->quotekey($key) . ' = ' . $k . ', ';
                $param[$k] = $value;
            }
        }

        $sql = rtrim($sql, ', ');

        $f = $this->filter($filter);
        if ($f) {
            $sql .= ' WHERE ' . array_shift($f);
            $param = array_merge($param, $f);
        }

        $res = $this->run($sql, $param);
        $this->error($res);

        return $res['success'];
    }

    /**
     * {@inheritdoc}
     */
    public function delete(string $table, $filter): bool
    {
        $sql = 'DELETE FROM ' . $this->quotekey($table);
        $param = [];

        $f = $this->filter($filter);
        if ($f) {
            $sql .= ' WHERE ' . array_shift($f);
            $param = array_merge($param, $f);
        }

        $res = $this->run($sql, $param);
        $this->error($res);

        return $res['success'];
    }

    /**
     * {@inheritdoc}
     */
    public function count(string $table, $filter = null, array $option = null, int $ttl = 0): int
    {
        $res = $this->select(
            $table,
            $filter,
            ['column' => 'count(*) as ' . $this->quotekey('c')] + (array) $option,
            $ttl
        );

        return (int) ($res[0]['c'] ?? 0);
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(
        string $table,
        int $page = 1,
        $filter = null,
        array $option = null,
        int $ttl = 0
    ): array {
        $use = (array) $option;
        $limit = $use['limit'] ?? $this->option['defaults']['pagination'] ?? 10;
        $total = $this->count($table, $filter, $option, $ttl);
        $pages = (int) ceil($total / $limit);
        $subset = [];
        $start = 0;
        $end = 0;

        if ($page > 0) {
            $offset = ($page - 1) * $limit;
            $subset = $this->select(
                $table,
                $filter,
                compact('limit','offset') + $use,
                $ttl
            );
            $start = $offset + 1;
            $end = $offset + count($subset);
        }

        return compact('subset', 'total', 'pages', 'page', 'start', 'end');
    }

    /**
     * Log error result
     *
     * @param  array $data
     *
     * @return void
     */
    protected function error(array $data): void
    {
        if (!$data['success']) {
            $this->errors[] = $data['error'];
        }
    }

    /**
     * Log sql and param
     *
     * @param  string $sql
     * @param  array  $param
     *
     * @return void
     */
    protected function log(string $sql, array $param = null): void
    {
        if ($this->option['log']) {
            $this->logs[] = [$sql, $param];
        }
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
}

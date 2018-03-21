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

    /** @var PDO */
    protected $pdo;

    /** @var Cache */
    protected $cache;

    /** @var array */
    protected $logs = [];

    /** @var array Db info cache */
    protected $info = [];

    /**
     * Class constructor
     *
     * @param Cache $cache
     * @param array $options
     */
    public function __construct(Cache $cache, array $options)
    {
        $this->cache = $cache;
        $this->setOptions($options);
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
     * Get latest error
     *
     * @return string
     */
    public function getError(): string
    {
        return $this->info['error'] ?? '';
    }

    /**
     * Get info
     *
     * @return array
     */
    public function getInfo(): array
    {
        return $this->info;
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
     * Retrieve schema of SQL table
     *
     * @param  string       $table
     * @param  string|array $fields
     * @param  int          $ttl
     *
     * @return array
     *
     * @throws DomainException
     */
    public function schema(string $table, $fields = NULL, int $ttl = 0): array
    {
        if (
            $ttl
            && $this->cache->isCached(
                $hash,
                $data,
                'schema',
                $this->options['dsn'],
                $table,
                $fields
            )
        ) {
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
        $driver = $this->getDriver();
        $qtable = $this->quotekey($table);
        $dbname = $schemaName ?? $this->options['dbname'] ?? '';

        if ($dbname) {
            $dbname = $this->quotekey($dbname);
        }

        preg_match($cmdPattern, $driver, $match);

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
            throw new \DomainException(
                'Driver ' . $driver . ' is not supported'
            );
        }

        $query = $this->pdo()->query($cmd[0]);
        $schema = $query->fetchAll(\PDO::FETCH_ASSOC);
        $rows = [];

        if ($fields) {
            $fields = reqarr($fields);
        }

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
                throw new \LogicException(
                    'Currently, there is no logic for ' . $driver .
                    ' DSN creation, please provide a valid one'
                );
            }
        } elseif (
            empty($options['dbname'])
            && preg_match('/^.+?(?:dbname|database)=(.+?)(?=;|$)/is', $options['dsn'], $parts)
        ) {
            $options['dbname'] = $parts[1];
        }

        $this->options = $options;
        $this->pdo = null;
        $this->info = [];

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
     * Check if table exists
     *
     * @param  string $table
     * @param  int    $ttl
     *
     * @return bool
     */
    public function tableExists(string $table, int $ttl = 0): bool
    {
        $sql = 'SELECT 1 FROM ' . $this->quotekey($table);

        if ($ttl && $this->cache->isCached($hash, $result, 'sql', $sql)) {
            return $result[0];
        }

        try {
            $query = $this->pdo()->prepare($sql);
            $result = $query->execute();
        } catch (\Exception $e) {
            $result = false;
            $this->consumeException($e);
        }

        if ($ttl) {
            $this->cache->set($hash, $result, $ttl);
        }

        return $result !== false;
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
     * Quote string
     *
     * @param  mixed $val
     * @param  int $type
     *
     * @return mixed
     */
    public function quote($val, $type = \PDO::PARAM_STR)
    {
        if ($this->getDriver() === 'odbc') {
            return is_string($val) ? stringify(str_replace('\'', '\'\'', $val)) : $val;
        }

        return $this->pdo()->quote($val, $type);
    }

    /**
     * Return quoted identifier name
     *
     * @param  string  $key
     * @param  boolean $split
     *
     * @return string
     */
    public function quotekey(string $key, bool $split = true): ?string
    {
        $use = $this->quotes();

        return $use[0] . (
            $split ? implode($use[1] . '.' . $use[0], explode('.', $key)) : $key
        ) . $use[1];
    }

    /**
     * Get quotes
     *
     * @return array
     */
    public function quotes(): array
    {
        $pattern = <<<PTRN
/^(?:
    (sqlite2?|mysql)|
    (pgsql|oci)|
    (mssql|sqlsrv|odbc|sybase|dblib)
)$/x
PTRN;
        preg_match($pattern, $this->getDriver(), $match);

        if (isset($match[1]) && $match[1]) {
            return ['`','`'];
        } elseif (isset($match[2]) && $match[2]) {
            return ['"','"'];
        } elseif (isset($match[3]) && $match[3]) {
            return ['[',']'];
        } else {
            return ['',''];
        }
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
     * Run query
     *
     * @param  PDOStatement  $query
     * @param  array  $params
     *
     * @return bool
     *
     * @throws Throwable If error in debug mode
     */
    public function run(\PDOStatement $query, array $params = []): bool
    {
        $result = false;

        try {
            $result = $query->execute($params);
        } catch (\Throwable $e) {
            $result = false;
            $this->consumeException($e);
        }

        return $result;
    }

    /**
     * Prepare and log sql query
     *
     * @param  string $sql
     * @param  array  $params
     *
     * @return PDOStatement
     */
    public function prepare(string $sql, array $params = null): ?\PDOStatement
    {
        $this->log($sql, $params);

        try {
            $query = $this->pdo()->prepare($sql);
        } catch (\Throwable $e) {
            $this->consumeException($e);
            $query = null;
        }

        return $query;
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

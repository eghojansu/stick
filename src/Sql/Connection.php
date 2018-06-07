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

use Fal\Stick\Cache;
use Fal\Stick\Helper;
use Fal\Stick\Logger;

/**
 * PDO Wrapper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class Connection
{
    // Supported databases
    const DB_SQLITE = 'sqlite';
    const DB_SQLITE2 = 'sqlite2';
    const DB_MYSQL = 'mysql';
    const DB_PGSQL = 'pgsql';
    const DB_OCI = 'oci';
    const DB_MSSQL = 'mssql';
    const DB_SQLSRV = 'sqlsrv';
    const DB_ODBC = 'odbc';
    const DB_SYBASE = 'sybase';
    const DB_DBLIB = 'dblib';

    const PARAM_FLOAT = 'float';

    /**
     * @var array
     */
    private $options;

    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var Cache
     */
    private $cache;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Driver name.
     *
     * @var string
     */
    private $driver;

    /**
     * Driver version.
     *
     * @var string
     */
    private $version;

    /**
     * Log level.
     *
     * @var string
     */
    private $logLevel = Logger::LEVEL_INFO;

    /**
     * Transaction status.
     *
     * @var bool
     */
    private $trans;

    /**
     * @var int
     */
    private $rows = 0;

    /**
     * Class constructor.
     *
     * @param Cache  $cache
     * @param Logger $logger
     * @param array  $options
     */
    public function __construct(Cache $cache, Logger $logger, array $options)
    {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->setOptions($options);
    }

    /**
     * Get database driver name.
     *
     * @return string
     */
    public function getDriver(): string
    {
        return $this->driver ?? $this->driver = $this->pdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    /**
     * Get database driver version.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version ?? $this->version = $this->pdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
    }

    /**
     * Get current database name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->options['dbname'] ?? '';
    }

    /**
     * Get cache.
     *
     * @return Cache
     */
    public function getCache(): Cache
    {
        return $this->cache;
    }

    /**
     * Get logger.
     *
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }

    /**
     * Get logLevel.
     *
     * @return string
     */
    public function getLogLevel(): string
    {
        return $this->logLevel;
    }

    /**
     * Set logLevel.
     *
     * In case you need to change log level higher or lower.
     *
     * @param string $logLevel
     *
     * @return Connection
     */
    public function setLogLevel(string $logLevel): Connection
    {
        $this->logLevel = $logLevel;

        return $this;
    }

    /**
     * Get options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Set options.
     *
     * Available options (and its default value):
     *
     *     debug    bool    false           enable debug mode
     *     encoding string  UTF8
     *     driver   string  void|unknown    database driver name, eg: mysql, sqlite
     *     dsn      string  void            valid dsn
     *     server   string  127.0.0.1
     *     port     int     3306
     *     password string  null
     *     username string  null
     *     dbname   string  void
     *     location string  void            for sqlite driver
     *     options  array   []              A key=>value array of driver-specific connection options
     *     commands array   void            Commands to be executed
     *
     * @param array $options
     *
     * @return Connection
     */
    public function setOptions(array $options): Connection
    {
        $defaults = [
            'debug' => false,
            'encoding' => 'UTF8',
            'server' => '127.0.0.1',
            'port' => 3306,
            'username' => null,
            'password' => null,
            'options' => [],
            'commands' => [],
        ];
        $pattern = '/^.+?(?:dbname|database)=(.+?)(?=;|$)/is';
        $use = $options + $defaults;
        $dsn = $use['dsn'] ?? null;
        $driver = strtolower($use['driver'] ?? 'unknown');
        $driverDefaults = [
            self::DB_MYSQL => ['options' => [
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.strtolower(str_replace('-', '', $use['encoding'])).';',
            ]],
        ];

        if (empty($dsn)) {
            $use['dsn'] = $this->createDsn($driver, $use);
        } elseif (preg_match($pattern, $dsn, $match)) {
            $driver = $use['driver'] ?? strstr($dsn, ':', true);
            $use['dbname'] = $use['dbname'] ?? $match[1];
            $use['driver'] = $driver;
        }

        $this->options = array_replace_recursive($driverDefaults[$driver] ?? [], $use);
        $this->pdo = null;
        $this->driver = null;
        $this->version = null;
        $this->trans = null;

        return $this;
    }

    /**
     * Get pdo.
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

        $o = $this->options;

        try {
            $this->pdo = new \PDO($o['dsn'], $o['username'], $o['password'], $o['options']);

            foreach ((array) $o['commands'] as $cmd) {
                $this->pdo->exec($cmd);
            }
        } catch (\PDOException $e) {
            if ($o['debug']) {
                throw $e;
            }

            throw new \LogicException('Invalid database configuration');
        }

        return $this->pdo;
    }

    /**
     * Map data type of argument to a PDO constant.
     *
     * @param mixed  $val
     * @param string $type
     *
     * @return mixed
     */
    public function pdoType($val = null, string $type = null)
    {
        static $types = [
            'null' => \PDO::PARAM_NULL,
            'resource' => \PDO::PARAM_LOB,
            'int' => \PDO::PARAM_INT,
            'integer' => \PDO::PARAM_INT,
            'bool' => \PDO::PARAM_BOOL,
            'boolean' => \PDO::PARAM_BOOL,
            'blob' => \PDO::PARAM_LOB,
            'bytea' => \PDO::PARAM_LOB,
            'image' => \PDO::PARAM_LOB,
            'binary' => \PDO::PARAM_LOB,
            'float' => self::PARAM_FLOAT,
            'real' => self::PARAM_FLOAT,
            'double' => self::PARAM_FLOAT,
            'decimal' => self::PARAM_FLOAT,
            'numeric' => self::PARAM_FLOAT,
            'default' => \PDO::PARAM_STR,
        ];

        return $types[strtolower($type ?? gettype($val))] ?? $types['default'];
    }

    /**
     * Real PDO type.
     *
     * @param mixed $val
     * @param mixed &$type
     *
     * @return mixed
     */
    public function realPdoType($val, $type = null)
    {
        $use = $type ?? $this->pdoType($val);

        return self::PARAM_FLOAT === $use ? \PDO::PARAM_STR : $use;
    }

    /**
     * Cast value to PHP type.
     *
     * @param mixed $type
     * @param mixed $val
     *
     * @return mixed
     */
    public function phpValue($type, $val)
    {
        if (self::PARAM_FLOAT === $type) {
            return is_string($val) ? $val : str_replace(',', '.', $val);
        } elseif (\PDO::PARAM_NULL === $type) {
            return null;
        } elseif (\PDO::PARAM_INT === $type) {
            return (int) $val;
        } elseif (\PDO::PARAM_BOOL === $type) {
            return (bool) $val;
        } elseif (\PDO::PARAM_STR === $type) {
            return (string) $val;
        } elseif (\PDO::PARAM_LOB === $type) {
            return (binary) $val;
        } else {
            return $val;
        }
    }

    /**
     * Build filter.
     *
     * Available maps:
     *
     *      * =       : =
     *      * >       : >
     *      * <       : <
     *      * >=      : >=
     *      * <=      : <=
     *      * <>      : <>
     *      * !=      : !=
     *      * &       : AND
     *      * |       : OR
     *      * ^       : XOR
     *      * !       : NOT
     *      * ~       : LIKE
     *      * !~      : NOT LIKE
     *
     *      * @       : SOUNDS LIKE
     *      * []      : IN
     *      * ![]     : NOT IN
     *      * ><      : BETWEEN
     *      * !><     : NOT BETWEEN
     *
     * Example:
     *
     *      ['field []' => [1,3]]   = 'field between 1 and 3'
     *      ['field <>' => 2]   = 'field <> 1'
     *      ['field #comment in case duplicate field cannot be avoided' => 2]   = 'field = 2'
     *
     * @param string|array $filter
     *
     * @return array
     */
    public function buildFilter($filter): array
    {
        if (!$filter) {
            return [];
        }

        // operator map
        $map = [
            '=' => '=',
            '>' => '>',
            '<' => '<',
            '>=' => '>=',
            '<=' => '<=',
            '<>' => '<>',
            '!=' => '!=',
            '&' => 'AND',
            '|' => 'OR',
            '^' => 'XOR',
            '!' => 'NOT',
            '~' => 'LIKE',
            '!~' => 'NOT LIKE',
            '@' => 'SOUNDS LIKE',
            '[]' => 'IN',
            '![]' => 'NOT IN',
            '><' => 'BETWEEN',
            '!><' => 'NOT BETWEEN',
        ];
        $mapkeys = '=<>&|^!~@[] ';

        $ctr = 0;
        $str = '';
        $result = [];

        foreach ((array) $filter as $key => $value) {
            if (is_numeric($key)) {
                if (is_string($value)) {
                    // raw
                    $str .= ' '.$value;
                } elseif ($cfilter = $this->buildFilter((array) $value)) {
                    $str .= ' AND ('.array_shift($cfilter).')';
                    $result = array_merge($result, $cfilter);
                }

                continue;
            }

            $raw = is_string($value) && Helper::startswith($value, '```');
            $expr = $raw ? substr($value, 3) : $value;
            // clear column from comments format
            $ccol = (false === ($pos = strpos($key, '#'))) ? $key : substr($key, 0, $pos);
            $col = trim($ccol, $mapkeys);
            $kcol = str_replace('.', '_', $col);
            $a1 = substr($ccol, 0, 1);
            $a2 = substr($ccol, 0, 2);
            $a3 = substr($ccol, 0, 3);
            $b1 = substr($ccol, -1);
            $b2 = substr($ccol, -2);
            $b3 = substr($ccol, -3);

            $str .= ' '.($map[$a3] ?? $map[$a2] ?? $map[$a1] ?? ($ctr > 0 ? 'AND' : ''));

            if ($col) {
                $str .= ' '.$this->quotekey($col);
                $str .= ' '.($map[$b3] ?? $map[$b2] ?? $map[$b1] ?? '=');
            }

            if ($raw) {
                $str .= ' '.$expr;
            } else {
                if ('!><' === $b3 || '><' === $b2) {
                    if (!is_array($expr)) {
                        throw new \LogicException('BETWEEN operator needs an array operand, '.gettype($expr).' given');
                    }

                    $str .= " :{$kcol}1 AND :{$kcol}2";
                    $result[":{$kcol}1"] = array_shift($expr);
                    $result[":{$kcol}2"] = array_shift($expr);
                } elseif ('![]' === $b3 || '[]' === $b2) {
                    $str .= ' (';
                    $i = 1;

                    foreach ((array) $expr as $val) {
                        $k = ":{$kcol}{$i}";
                        $str .= "$k, ";
                        $result[$k] = $val;
                        ++$i;
                    }

                    $str = rtrim($str, ', ').')';
                } elseif (is_array($expr)) {
                    $cfilter = $this->buildFilter($expr);

                    if ($cfilter) {
                        $str .= ' ('.array_shift($cfilter).')';
                        $result = array_merge($result, $cfilter);
                    }
                } elseif ($kcol) {
                    $k = ":{$kcol}";
                    $str .= " $k";
                    $result[$k] = $expr;
                } else {
                    $str .= ' '.$expr;
                }
            }

            ++$ctr;
        }

        array_unshift($result, trim($str));

        return $result;
    }

    /**
     * Get table schema.
     *
     * @param string     $table
     * @param array|null $fields
     * @param int        $ttl
     *
     * @return array
     *
     * @throws LogicException If schema contains nothing
     */
    public function schema(string $table, array $fields = null, int $ttl = 0): array
    {
        $start = microtime(true);
        $message = '(%.1f) %sRetrieving table "%s" schema';

        if ($ttl && $this->cache->isCached($hash, $data, 'schema', $table, $fields)) {
            $this->logger->log($this->logLevel, sprintf('(%.1fms) [CACHED] Retrieving schema of %s table', 1e3 * (microtime(true) - $start), $table));

            return $data[0];
        }

        if (strpos($table, '.')) {
            list($db, $table) = explode('.', $table);
        }

        $cmd = $this->schemaCmd($table, $db ?? $this->options['dbname'] ?? '');
        $query = $this->pdo()->query($cmd[0]);
        $schema = $query->fetchAll(\PDO::FETCH_ASSOC);
        $rows = [];

        foreach ($schema as $row) {
            if ($fields && !in_array($row[$cmd[1]], $fields)) {
                continue;
            }

            $rows[$row[$cmd[1]]] = [
                'type' => $row[$cmd[2]],
                'pdo_type' => $this->pdoType(null, $row[$cmd[2]]),
                'default' => is_string($row[$cmd[3]]) ? $this->schemaDefaultValue($row[$cmd[3]]) : $row[$cmd[3]],
                'nullable' => $row[$cmd[4]] == $cmd[5],
                'pkey' => $row[$cmd[6]] == $cmd[7],
            ];
        }

        if (!$rows) {
            throw new \LogicException('Table "'.$table.'" contains no defined schema');
        }

        if ($ttl) {
            // Save to cache backend
            $this->cache->set($hash, $rows, $ttl);
        }

        $this->logger->log($this->logLevel, sprintf('(%.1fms) Retrieving schema of %s table (%s)', 1e3 * (microtime(true) - $start), $table, $cmd[0]));

        return $rows;
    }

    /**
     * Get quote char (open and close).
     *
     * @return array
     */
    public function getQuote(): array
    {
        $quotes = [
            '``' => [self::DB_SQLITE, self::DB_SQLITE2, self::DB_MYSQL],
            '""' => [self::DB_PGSQL, self::DB_OCI],
            '[]' => [self::DB_MSSQL, self::DB_SQLSRV, self::DB_ODBC, self::DB_SYBASE, self::DB_DBLIB],
        ];
        $driver = $this->getDriver();

        foreach ($quotes as $quote => $engines) {
            if (in_array($driver, $engines)) {
                return str_split($quote);
            }
        }

        return ['', ''];
    }

    /**
     * Quote string.
     *
     * @param string $val
     * @param mixed  $type
     *
     * @return string
     */
    public function quote($val, $type = null): string
    {
        switch ($this->getDriver()) {
            case self::DB_ODBC:
                return is_string($val) ? Helper::stringify(str_replace('\'', '\'\'', $val)) : (string) $val;
            default:
                return $this->pdo()->quote($val, $type ?? \PDO::PARAM_STR);
        }
    }

    /**
     * Return quoted identifier name.
     *
     * @param string $key
     * @param bool   $split
     *
     * @return string
     */
    public function quotekey(string $key, bool $split = true): string
    {
        return Helper::quote($split ? explode('.', $key) : $key, $this->getQuote(), '.');
    }

    /**
     * Open transaction.
     *
     * @return bool
     */
    public function begin(): bool
    {
        $out = $this->pdo()->beginTransaction();
        $this->trans = true;

        return $out;
    }

    /**
     * Commit and close transaction.
     *
     * @return bool
     */
    public function commit(): bool
    {
        $out = $this->pdo()->commit();
        $this->trans = false;

        return $out;
    }

    /**
     * Roolback and close transaction.
     *
     * @return bool
     */
    public function rollback(): bool
    {
        $out = $this->pdo()->rollBack();
        $this->trans = false;

        return $out;
    }

    /**
     * Get transaction status.
     *
     * @return bool
     */
    public function isTrans(): bool
    {
        return $this->trans ?? false;
    }

    /**
     * Exec command(s).
     *
     * @param string|array $cmds
     * @param mixed        $args
     * @param int          $ttl
     *
     * @return mixed
     *
     * @throws LogicException If commands resulting error
     */
    public function exec($cmds, $args = null, int $ttl = 0)
    {
        $res = [];
        $auto = false;
        $count = 1;
        $one = true;
        $driver = $this->getDriver();
        $fetchPattern = [
            '/(?:^[\s\(]*(?:EXPLAIN|SELECT|PRAGMA|SHOW)|RETURNING)\b/is',
            '/^\s*(?:CALL|EXEC)\b/is',
        ];

        if (is_null($args)) {
            $args = [];
        } elseif (is_scalar($args)) {
            $args = [1 => $args];
        }

        if (is_array($cmds)) {
            $count = count($cmds);
            $one = 1 === $count;

            if (count($args) < $count) {
                // Apply arguments to SQL commands
                $args = array_fill(0, $count, $args);
            }

            if (!$this->trans) {
                $this->begin();
                $auto = true;
            }
        } else {
            $cmds = [$cmds];
            $args = [$args];
        }

        $this->pdo();

        for ($i = 0; $i < $count; ++$i) {
            $cmd = $cmds[$i];
            $arg = $args[$i];
            $start = microtime(true);

            if (!preg_replace('/(^\s+|[\s;]+$)/', '', $cmd)) {
                continue;
            }

            // ensure 1-based arguments
            if (array_key_exists(0, $arg)) {
                array_unshift($arg, '');
                unset($arg[0]);
            }

            if ($ttl && $this->cache->isCached($hash, $data, 'sql', $cmd, $arg)) {
                $res[$i] = $data[0];

                $this->logger->log($this->logLevel, $this->buildLog([$cmd, $arg, $start, true]));

                continue;
            }

            $query = $this->pdo->prepare($cmd);
            $error = $this->pdo->errorinfo();

            if (!is_object($query) || ($error && \PDO::ERR_NONE !== $error[0])) {
                // PDO-level error occurred
                if ($this->trans) {
                    $this->rollback();
                }

                throw new \LogicException('PDO: '.$error[2]);
            }

            foreach ($arg as $key => $val) {
                if (is_array($val)) {
                    // User-specified data type
                    $query->bindvalue($key, $val[0], $this->realPdoType(null, $val[1]));
                } else {
                    // Convert to PDO data type
                    $query->bindvalue($key, $val, $this->realPdoType($val));
                }
            }

            $log = $this->buildLog([$cmd, $arg]);
            $query->execute();
            $this->logger->log($this->logLevel, $this->buildLog([], $start, $log));

            $error = $query->errorinfo();

            if ($error && \PDO::ERR_NONE !== $error[0]) {
                // Statement-level error occurred
                if ($this->trans) {
                    $this->rollback();
                }

                throw new \LogicException('PDOStatement: '.$error[2]);
            }

            if (preg_match($fetchPattern[0], $cmd) || (preg_match($fetchPattern[1], $cmd) && $query->columnCount())) {
                $res[$i] = $query->fetchall(\PDO::FETCH_ASSOC);

                // Work around SQLite quote bug
                if (in_array($driver, [self::DB_SQLITE, self::DB_SQLITE2])) {
                    foreach ($res[$i] as $pos => $rec) {
                        unset($res[$i][$pos]);
                        $res[$i][$pos] = [];
                        foreach ($rec as $key => $val) {
                            $res[$i][$pos][trim($key, '\'"[]`')] = $val;
                        }
                    }
                }

                $this->rows = count($res[$i]);

                if ($ttl) {
                    // Save to cache backend
                    $this->cache->set($hash, $res[$i], $ttl);
                }
            } else {
                $this->rows = $res[$i] = $query->rowcount();
            }

            $query->closecursor();
            unset($query);
        }

        if ($this->trans && $auto) {
            $this->commit();
        }

        return $one ? $res[0] ?? null : $res;
    }

    /**
     * Get rows count affected by last query.
     *
     * @return int
     */
    public function getRows(): int
    {
        return $this->rows;
    }

    /**
     * Check if table exists.
     *
     * @param string $table
     *
     * @return bool
     */
    public function exists(string $table): bool
    {
        $mode = $this->pdo()->getAttribute(\PDO::ATTR_ERRMODE);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
        $out = $this->pdo->query('SELECT 1 FROM '.$this->quotekey($table).' LIMIT 1');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, $mode);

        return is_object($out);
    }

    /**
     * Log sql and arg.
     *
     * @param array  $data
     * @param float  $update
     * @param string $prior
     *
     * @return string
     */
    private function buildLog(array $data, float $update = 0, string $prior = null): string
    {
        $use = $data + ['', [], $update, false];
        $time = '('.($use[2] ? sprintf('%.1f', 1e3 * (microtime(true) - $use[2])) : '-0').'ms)';

        if ($prior && $update) {
            return str_replace('(-0ms)', $time, $prior);
        }

        $keys = $vals = [];

        foreach ($use[1] as $key => $val) {
            if (is_array($val)) {
                // User-specified data type
                $vals[] = Helper::stringify($use[3] ? $val[0] : $this->phpValue($val[1], $val[0]));
            } else {
                $vals[] = Helper::stringify($use[3] ? $val : $this->phpValue($this->realPdoType($val), $val));
            }
            $keys[] = '/'.preg_quote(is_numeric($key) ? chr(0).'?' : $key).'/';
        }

        return $time.' '.($use[3] ? '[CACHED] ' : '').preg_replace($keys, $vals, str_replace('?', chr(0).'?', $use[0]), 1);
    }

    /**
     * Create pdo dsn.
     *
     * @param string $driver
     * @param array  $options
     *
     * @return string
     *
     * @throws LogicException If no logic found for creating driver DSN
     */
    private function createDsn(string $driver, array $options): string
    {
        switch ($driver) {
            case self::DB_MYSQL:
                if (isset($options['server'], $options['username'], $options['dbname'])) {
                    return
                        $driver.
                        ':host='.$options['server'].
                        ';port='.$options['port'].
                        ';dbname='.$options['dbname'];
                }

                $error = 'Invalid mysql driver configuration';
                break;

            case self::DB_SQLITE:
            case self::DB_SQLITE2:
                if (isset($options['location'])) {
                    // location can be full filepath or :memory:
                    return $driver.':'.$options['location'];
                }

                $error = 'Invalid sqlite driver configuration';
                break;
        }

        throw new \LogicException($error ?? 'There is no logic for '.$driver.' DSN creation, please provide a valid one');
    }

    /**
     * Get schema command.
     *
     * @param string $table
     * @param string $dbname
     *
     * @return array
     *
     * @throws LogicException
     */
    private function schemaCmd(string $table, string $dbname): array
    {
        // Supported engines
        static $groups = [
            1 => [self::DB_SQLITE, self::DB_SQLITE2],
            [self::DB_MYSQL],
            [self::DB_MSSQL, self::DB_SQLSRV, self::DB_SYBASE, self::DB_DBLIB, self::DB_PGSQL, self::DB_ODBC],
            [self::DB_OCI],
        ];

        $tbl = $this->quotekey($table);
        $db = $dbname ? $this->quotekey($dbname) : '';
        $cmds = [
            1 => [
                'PRAGMA table_info('.$tbl.')',
                'name', 'type', 'dflt_value', 'notnull', 0, 'pk', true,
            ],
            [
                'SHOW columns FROM '.$db.'.'.$tbl,
                'Field', 'Type', 'Default', 'Null', 'YES', 'Key', 'PRI',
            ],
            [
                'SELECT '.
                    'C.COLUMN_NAME AS field,'.
                    'C.DATA_TYPE AS type,'.
                    'C.COLUMN_DEFAULT AS defval,'.
                    'C.IS_NULLABLE AS nullable,'.
                    'T.CONSTRAINT_TYPE AS pkey '.
                'FROM INFORMATION_SCHEMA.COLUMNS AS C '.
                'LEFT OUTER JOIN '.
                    'INFORMATION_SCHEMA.KEY_COLUMN_USAGE AS K '.
                    'ON '.
                        'C.TABLE_NAME=K.TABLE_NAME AND '.
                        'C.COLUMN_NAME=K.COLUMN_NAME AND '.
                        'C.TABLE_SCHEMA=K.TABLE_SCHEMA '.
                        ($db ? 'AND C.TABLE_CATALOG=K.TABLE_CATALOG ' : '').
                'LEFT OUTER JOIN '.
                    'INFORMATION_SCHEMA.TABLE_CONSTRAINTS AS T ON '.
                        'K.TABLE_NAME=T.TABLE_NAME AND '.
                        'K.CONSTRAINT_NAME=T.CONSTRAINT_NAME AND '.
                        'K.TABLE_SCHEMA=T.TABLE_SCHEMA '.
                        ($db ? 'AND K.TABLE_CATALOG=T.TABLE_CATALOG ' : '').
                'WHERE '.
                    'C.TABLE_NAME='.$tbl.
                    ($db ? ' AND C.TABLE_CATALOG='.$tbl : ''),
                'field', 'type', 'defval', 'nullable', 'YES', 'pkey', 'PRIMARY KEY',
            ],
            [
                'SELECT c.column_name AS field, '.
                    'c.data_type AS type, '.
                    'c.data_default AS defval, '.
                    'c.nullable AS nullable, '.
                    '(SELECT t.constraint_type '.
                        'FROM all_cons_columns acc'.
                        ' LEFT OUTER JOIN all_constraints t'.
                        ' ON acc.constraint_name=t.constraint_name'.
                        ' WHERE acc.table_name='.$tbl.
                        ' AND acc.column_name=c.column_name'.
                        ' AND constraint_type='.$this->quote('P').') AS pkey '.
                'FROM all_tab_cols c '.
                'WHERE c.table_name='.$tbl,
                'FIELD', 'TYPE', 'DEFVAL', 'NULLABLE', 'Y', 'PKEY', 'P',
            ],
        ];
        $driver = $this->getDriver();

        foreach ($groups as $id => $members) {
            if (in_array($driver, $members)) {
                return $cmds[$id];
            }
        }

        throw new \DomainException('Driver '.$driver.' is not supported');
    }

    /**
     * Cast SQL-default-value to PHP-value.
     *
     * @param string $value
     *
     * @return mixed
     */
    private function schemaDefaultValue(string $value)
    {
        return Helper::cast(preg_replace('/^\s*([\'"])(.*)\1\s*/', '\2', $value));
    }

    /**
     * Prohibit cloning.
     *
     *
     * @codeCoverageIgnore
     */
    private function __clone()
    {
    }
}

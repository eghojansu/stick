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
 * PDO Wrapper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Connection
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
     * @var App
     */
    private $app;

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
     * Database name.
     *
     * @var string
     */
    private $dbname = '';

    /**
     * Log level.
     *
     * @var string
     */
    private $logLevel = App::LOG_LEVEL_INFO;

    /**
     * Transaction status.
     *
     * @var bool
     */
    private $trans = false;

    /**
     * @var int
     */
    private $rows = 0;

    /**
     * Class constructor.
     *
     * @param App   $app
     * @param array $options
     */
    public function __construct(App $app, array $options)
    {
        $this->app = $app;
        $this->setOptions($options);
    }

    /**
     * Returns database driver name.
     *
     * @return string
     */
    public function getDriver()
    {
        if (null === $this->driver) {
            $this->driver = $this->pdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        }

        return $this->driver;
    }

    /**
     * Returns database driver version.
     *
     * @return string
     */
    public function getVersion()
    {
        if (null === $this->version) {
            $this->version = $this->pdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
        }

        return $this->version;
    }

    /**
     * Returns current database name.
     *
     * @return string
     */
    public function getDbName()
    {
        return $this->dbname;
    }

    /**
     * Returns logLevel.
     *
     * @return string
     */
    public function getLogLevel()
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
    public function setLogLevel($logLevel)
    {
        $this->logLevel = $logLevel;

        return $this;
    }

    /**
     * Returns options.
     *
     * @return array
     */
    public function getOptions()
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
     *     dsn      string  void            valid dsn
     *     password string  null
     *     username string  null
     *     options  array   []              A key=>value array of driver-specific connection options
     *     commands array   void            Commands to be executed
     *
     * @param array $options
     *
     * @return Connection
     */
    public function setOptions(array $options)
    {
        $defaults = array(
            'debug' => false,
            'encoding' => 'UTF8',
            'dsn' => null,
            'username' => null,
            'password' => null,
            'options' => array(),
            'commands' => null,
        );
        $pattern = '/^.+?(?:dbname|database)=(.+?)(?=;|$)/is';
        $fix = $options + $defaults;
        $dsn = $fix['dsn'];
        $driver = 'unknown';
        $driverDefaults = array(
            self::DB_MYSQL => array('options' => array(
                \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES '.strtolower(str_replace('-', '', $fix['encoding'])).';',
            )),
        );

        if ($dsn && preg_match($pattern, $dsn, $match)) {
            $driver = strstr($dsn, ':', true);
            $this->dbname = $match[1];
        }

        $this->options = array_replace_recursive($fix, App::pick($driverDefaults, $driver, array()));
        $this->pdo = null;
        $this->driver = null;
        $this->version = null;
        $this->trans = false;

        return $this;
    }

    /**
     * Returns pdo.
     *
     * @return PDO
     *
     * @throws LogicException If construct failed and not in debug mode
     * @throws PDOException   If construct failed and in debug mode
     */
    public function pdo()
    {
        if (null === $this->pdo) {
            $o = $this->options;

            try {
                $this->pdo = new \PDO($o['dsn'], $o['username'], $o['password'], $o['options']);

                App::walk((array) $o['commands'], array($this->pdo, 'exec'));
            } catch (\PDOException $e) {
                $this->app->log(App::LOG_LEVEL_EMERGENCY, $e->getMessage());

                throw new \LogicException('Invalid database configuration.');
            }
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
    public function pdoType($val = null, $type = null)
    {
        $types = array(
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
        );
        $check = strtolower($type ?: gettype($val));

        return App::pick($types, $check, $types['default']);
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
        $check = $type ?: $this->pdoType($val);

        return self::PARAM_FLOAT === $check ? \PDO::PARAM_STR : $check;
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
        }

        if (\PDO::PARAM_NULL === $type) {
            return null;
        }

        if (\PDO::PARAM_INT === $type) {
            return (int) $val;
        }

        if (\PDO::PARAM_BOOL === $type) {
            return (bool) $val;
        }

        if (\PDO::PARAM_STR === $type) {
            return (string) $val;
        }

        if (\PDO::PARAM_LOB === $type) {
            return (binary) $val;
        }

        return $val;
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
    public function buildFilter($filter)
    {
        if (!$filter) {
            return array();
        }

        // operator map
        $map = array(
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
        );
        $mapkeys = '=<>&|^!~@[] ';

        $ctr = 0;
        $str = '';
        $result = array();

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

            $raw = is_string($value) && '```' === substr($value, 0, 3);
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

            $str .= ' '.App::pickFirst($map, array($a3, $a2, $a1), $ctr ? 'AND' : '');

            if ($col) {
                $str .= ' '.$this->quotekey($col);
                $str .= ' '.App::pickFirst($map, array($b3, $b2, $b1), '=');
            }

            if ($raw) {
                $str .= ' '.$expr;
            } else {
                if ('!><' === $b3 || '><' === $b2) {
                    $throw = !is_array($expr);
                    $message = 'BETWEEN operator needs an array operand, '.gettype($expr).' given.';
                    App::throws($throw, $message);

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
     * Returns table schema.
     *
     * @param string $table
     * @param mixed  $fields
     * @param int    $ttl
     *
     * @return array
     *
     * @throws LogicException If schema contains nothing
     */
    public function schema($table, $fields = null, $ttl = 0)
    {
        $start = microtime(true);
        $message = '(%.1f) %sRetrieving table "%s" schema';
        $hash = App::hash($table.var_export($fields, true)).'.schema';
        $db = $this->getDbName();

        if ($ttl && $this->app->isCached($hash, $data)) {
            $message = sprintf('(%.1fms) [CACHED] Retrieving schema of %s table', 1e3 * (microtime(true) - $start), $table);
            $this->app->log($this->logLevel, $message);

            return $data[0];
        }

        if (strpos($table, '.')) {
            list($db, $table) = explode('.', $table);
        }

        $cmd = $this->schemaCmd($table, $db);
        $query = $this->pdo()->query($cmd[0]);
        $schema = $query->fetchAll(\PDO::FETCH_ASSOC);
        $rows = array();
        $check = App::arr($fields);

        foreach ($schema as $row) {
            if (!$check || in_array($row[$cmd[1]], $check)) {
                $rows[$row[$cmd[1]]] = array(
                    'type' => $row[$cmd[2]],
                    'pdo_type' => $this->pdoType(null, $row[$cmd[2]]),
                    'default' => is_string($row[$cmd[3]]) ? $this->schemaDefaultValue($row[$cmd[3]]) : $row[$cmd[3]],
                    'nullable' => $row[$cmd[4]] == $cmd[5],
                    'pkey' => $row[$cmd[6]] == $cmd[7],
                );
            }
        }

        App::throws(!$rows, 'Table "'.$table.'" contains no defined schema.');

        if ($ttl) {
            // Save to cache backend
            $this->app->cacheSet($hash, $rows, $ttl);
        }

        $message = sprintf('(%.1fms) Retrieving schema of %s table (%s)', 1e3 * (microtime(true) - $start), $table, $cmd[0]);
        $this->app->log($this->logLevel, $message);

        return $rows;
    }

    /**
     * Quote string.
     *
     * @param string $val
     * @param mixed  $type
     *
     * @return string
     */
    public function quote($val, $type = null)
    {
        if (self::DB_ODBC === $this->getDriver()) {
            return is_string($val) ? str_replace('\'', '\'\'', $val) : (string) $val;
        }

        return $this->pdo()->quote($val, $type ?: \PDO::PARAM_STR);
    }

    /**
     * Return quoted identifier name.
     *
     * @param string $key
     * @param bool   $split
     *
     * @return string
     */
    public function quotekey($key, $split = true)
    {
        $driver = $this->getDriver();
        $engines = array(
            self::DB_SQLITE => '``',
            self::DB_SQLITE2 => '``',
            self::DB_MYSQL => '``',
            self::DB_PGSQL => '""',
            self::DB_OCI => '""',
            self::DB_MSSQL => '[]',
            self::DB_SQLSRV => '[]',
            self::DB_ODBC => '[]',
            self::DB_SYBASE => '[]',
            self::DB_DBLIB => '[]',
        );

        if ($key && isset($engines[$driver])) {
            $quote = $engines[$driver];

            return
                $quote[0].
                implode($quote[1].'.'.$quote[0], $split ? explode('.', $key) : array($key)).
                $quote[1];
        }

        return $key;
    }

    /**
     * Open transaction.
     *
     * @return bool
     */
    public function begin()
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
    public function commit()
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
    public function rollback()
    {
        $out = $this->pdo()->rollBack();
        $this->trans = false;

        return $out;
    }

    /**
     * Returns transaction status.
     *
     * @return bool
     */
    public function isTrans()
    {
        return $this->trans;
    }

    /**
     * Begin trans only if transaction mode is not enabled.
     *
     * @return Connection
     */
    public function safeBegin()
    {
        if (!$this->trans) {
            $this->begin();
        }

        return $this;
    }

    /**
     * Rollback only if transaction mode is enabled.
     *
     * @return Connection
     */
    public function safeRollback()
    {
        if ($this->trans) {
            $this->rollback();
        }

        return $this;
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
    public function exec($cmds, $args = null, $ttl = 0)
    {
        $res = array();
        $auto = false;
        $count = 1;
        $one = true;
        $driver = $this->getDriver();
        $fetchPattern = array(
            '/(?:^[\s\(]*(?:EXPLAIN|SELECT|PRAGMA|SHOW)|RETURNING)\b/is',
            '/^\s*(?:CALL|EXEC)\b/is',
        );

        if (is_null($args)) {
            $args = array();
        } elseif (is_scalar($args)) {
            $args = array(1 => $args);
        }

        if (is_array($cmds)) {
            $count = count($cmds);
            $one = 1 === $count;
            $auto = !$this->trans;

            if (count($args) < $count) {
                // Apply arguments to SQL commands
                $args = array_fill(0, $count, $args);
            }

            $this->safeBegin();
        } else {
            $cmds = array($cmds);
            $args = array($args);
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

            $hash = $cmd.var_export($arg, true).'.sql';

            if ($ttl && $this->app->isCached($hash, $data)) {
                $res[$i] = $data[0];

                $this->app->log($this->logLevel, $this->buildLog(array($cmd, $arg, $start, true)));

                continue;
            }

            $query = $this->pdo->prepare($cmd);
            $error = $this->pdo->errorinfo();

            if (!is_object($query) || ($error && \PDO::ERR_NONE !== $error[0])) {
                // PDO-level error occurred
                $this->safeRollback();

                throw new \LogicException('PDO: '.$error[2].'.');
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

            $log = $this->buildLog(array($cmd, $arg));
            $query->execute();
            $this->app->log($this->logLevel, $this->buildLog(array(), $start, $log));

            $error = $query->errorinfo();

            if ($error && \PDO::ERR_NONE !== $error[0]) {
                // Statement-level error occurred
                $this->safeRollback();

                throw new \LogicException('PDOStatement: '.$error[2].'.');
            }

            if (preg_match($fetchPattern[0], $cmd) || (preg_match($fetchPattern[1], $cmd) && $query->columnCount())) {
                $res[$i] = $query->fetchall(\PDO::FETCH_ASSOC);

                // Work around SQLite quote bug
                if (in_array($driver, array(self::DB_SQLITE, self::DB_SQLITE2))) {
                    foreach ($res[$i] as $pos => $rec) {
                        unset($res[$i][$pos]);
                        $res[$i][$pos] = array();
                        foreach ($rec as $key => $val) {
                            $res[$i][$pos][trim($key, '\'"[]`')] = $val;
                        }
                    }
                }

                $this->rows = count($res[$i]);

                if ($ttl) {
                    // Save to cache backend
                    $this->app->cacheSet($hash, $res[$i], $ttl);
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

        return $one ? array_shift($res) : $res;
    }

    /**
     * Returns rows count affected by last query.
     *
     * @return int
     */
    public function getRows()
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
    public function exists($table)
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
    private function buildLog(array $data, $update = 0, $prior = null)
    {
        $use = $data + array('', array(), $update, false);
        $time = '('.($use[2] ? sprintf('%.1f', 1e3 * (microtime(true) - $use[2])) : '-0').'ms)';

        if ($prior && $update) {
            return str_replace('(-0ms)', $time, $prior);
        }

        $keys = $vals = array();

        foreach ($use[1] as $key => $val) {
            if (is_array($val)) {
                // User-specified data type
                $toStr = $use[3] ? $val[0] : $this->phpValue($val[1], $val[0]);
            } else {
                $toStr = $use[3] ? $val : $this->phpValue($this->realPdoType($val), $val);
            }

            $vals[] = var_export($toStr, true);
            $keys[] = '/'.preg_quote(is_numeric($key) ? chr(0).'?' : $key).'/';
        }

        return $time.' '.($use[3] ? '[CACHED] ' : '').preg_replace($keys, $vals, str_replace('?', chr(0).'?', $use[0]), 1);
    }

    /**
     * Returns schema command.
     *
     * @param string $table
     * @param string $dbname
     *
     * @return array
     *
     * @throws LogicException
     */
    private function schemaCmd($table, $dbname)
    {
        $tbl = $this->quotekey($table);
        $db = $this->quotekey($dbname);
        $engines = array(
            self::DB_SQLITE => 1,
            self::DB_SQLITE2 => 1,
            self::DB_MYSQL => 2,
            self::DB_MSSQL => 3,
            self::DB_SQLSRV => 3,
            self::DB_SYBASE => 3,
            self::DB_DBLIB => 3,
            self::DB_PGSQL => 3,
            self::DB_ODBC => 3,
            self::DB_OCI => 4,
        );
        $cmds = array(
            1 => array(
                'PRAGMA table_info('.$tbl.')',
                'name', 'type', 'dflt_value', 'notnull', 0, 'pk', true,
            ),
            array(
                'SHOW columns FROM '.$db.'.'.$tbl,
                'Field', 'Type', 'Default', 'Null', 'YES', 'Key', 'PRI',
            ),
            array(
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
            ),
            array(
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
            ),
        );
        $driver = $this->getDriver();

        if (isset($engines[$driver])) {
            $id = $engines[$driver];

            return $cmds[$id];
        }

        throw new \DomainException('Driver '.$driver.' is not supported.');
    }

    /**
     * Cast SQL-default-value to PHP-value.
     *
     * @param string $value
     *
     * @return mixed
     */
    private function schemaDefaultValue($value)
    {
        return App::cast(preg_replace('/^\s*([\'"])(.*)\1\s*/', '\2', $value));
    }

    /**
     * Prohibit cloning.
     *
     * @codeCoverageIgnore
     */
    private function __clone()
    {
    }
}

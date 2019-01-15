<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Database\Sql;

use Fal\Stick\CacheItem;
use Fal\Stick\Core;

class Sql
{
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

    const PARAM_FLOAT = -1;

    /**
     * @var Core
     */
    protected $core;

    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var string
     */
    protected $dsn;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var array
     */
    protected $commands;

    /**
     * @var string
     */
    protected $dbName;

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var bool
     */
    protected $trans = false;

    /**
     * Class constructor.
     *
     * @param Core        $core
     * @param string      $dsn
     * @param string|null $username
     * @param string|null $password
     * @param array|null  $commands
     * @param array|null  $options
     */
    public function __construct(Core $core, string $dsn, string $username = null, string $password = null, array $commands = null, array $options = null)
    {
        $this->core = $core;
        $this->dsn = $dsn;
        $this->username = (string) $username;
        $this->password = (string) $password;
        $this->options = (array) $options;
        $this->commands = (array) $commands;
    }

    /**
     * Returns pdo instance.
     *
     * @return PDO
     */
    public function getPdo()
    {
        if ($this->pdo) {
            return $this->pdo;
        }

        try {
            $this->pdo = new \PDO($this->dsn, $this->username, $this->password, $this->options);
            $mode = $this->pdo->getAttribute(\PDO::ATTR_ERRMODE);

            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            foreach ($this->commands as $command) {
                $this->pdo->exec($command);
            }

            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, $mode);
        } catch (\Throwable $e) {
            $this->core->log(Core::LOG_EMERGENCY, $e->getMessage());

            throw new \LogicException('Database connection failed!', 0, $e);
        }

        return $this->pdo;
    }

    /**
     * Returns driver name.
     *
     * @return string
     */
    public function getDriverName()
    {
        return (string) $this->getAttribute('driver_name');
    }

    /**
     * Returns server version.
     *
     * @return string
     */
    public function getServerVersion()
    {
        return (string) $this->getAttribute('server_version');
    }

    /**
     * Returns db name.
     *
     * @return string
     */
    public function getDbName()
    {
        if (null === $this->dbName) {
            $pattern = '/^.+?(?:dbname|database)=(.+?)(?=;|$)/is';
            $this->dbName = '';

            if (preg_match($pattern, $this->dsn, $match)) {
                $this->dbName = $match[1];
            }
        }

        return $this->dbName;
    }

    /**
     * Returns attribute.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getAttribute($name)
    {
        $upperName = strtoupper($name);

        if (!array_key_exists($upperName, $this->attributes)) {
            $this->attributes[$upperName] = null;

            if (defined($attribute = 'PDO::ATTR_'.$upperName)) {
                $this->attributes[$upperName] = $this->getPdo()->getAttribute(constant($attribute));
            }
        }

        return $this->attributes[$upperName];
    }

    /**
     * Returns true if table exists.
     *
     * @param string $table
     *
     * @return bool
     */
    public function isTableExists($table)
    {
        $mode = $this->getPdo()->getAttribute(\PDO::ATTR_ERRMODE);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
        $out = $this->pdo->query('SELECT 1 FROM '.$this->key($table).' LIMIT 1');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, $mode);

        return is_object($out);
    }

    /**
     * Returns table schema.
     *
     * @param string      $table
     * @param string|null $fields
     * @param int         $ttl
     *
     * @return array
     */
    public function getTableSchema($table, $fields = null, $ttl = 0)
    {
        $time = microtime(true);
        $hash = $this->core->hash($table.var_export($fields, true)).'.schema';

        if ($ttl && $cache = $this->core->cacheGet($hash)) {
            $this->core->log(Core::LOG_INFO, sprintf('(%fs) [CACHED] Retrieving schema of %s table', microtime(true) - $time, $table));

            return $cache->getValue();
        }

        $schema = [];
        $db = $this->getDbName();
        $mTable = $table;
        $mFields = $this->core->split($fields);

        if (strpos($table, '.')) {
            list($db, $mTable) = explode('.', $table);
        }

        list($command, $fName, $fType, $fDefault, $fNull, $nullCompare, $fKey, $keyCompare) = $this->getSchemaCommand($this->key($db), $this->key($mTable), $this->getDriverName());

        $query = $this->getPdo()->query($command);

        foreach ($query->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $name = $row[$fName];

            if (!$mFields || in_array($name, $mFields)) {
                $schema[$name] = [
                    'type'     => $row[$fType],
                    'pdo_type' => $this->pdoType(null, $row[$fType]),
                    'default'  => $this->resolveSchemaDefaultValue($row[$fDefault]),
                    'nullable' => $row[$fNull] == $nullCompare,
                    'pkey'     => $row[$fKey] == $keyCompare,
                    'name'     => $name,
                ];
            }
        }

        if ($ttl && $schema) {
            // Save to cache backend
            $this->core->cacheSet($hash, new CacheItem($schema, $ttl, $time));
        }

        $this->core->log(Core::LOG_INFO, sprintf('(%fs) Retrieving schema of %s table (%s)', microtime(true) - $time, $table, $command));

        return $schema;
    }

    /**
     * Exec all commands.
     *
     * @param array $cmds
     * @param int   $ttl
     *
     * @return array
     */
    public function execAll(array $cmds, $ttl = 0)
    {
        $result = [];
        $autoCommit = !$this->trans;

        $this->begin();

        foreach ($cmds as $cmd => $args) {
            if (is_numeric($cmd)) {
                $cmd = $args;
                $args = null;
            }

            $result[] = $this->exec($cmd, $args, $ttl);
        }

        if ($autoCommit) {
            $this->commit();
        }

        return $result;
    }

    /**
     * Execute sql.
     *
     * @param string     $cmd
     * @param array|null $args
     * @param in         $ttl
     *
     * @return mixed
     */
    public function exec($cmd, array $args = null, $ttl = 0)
    {
        if ($this->isQueryEmpty($cmd)) {
            $this->rollback();

            throw new \LogicException('Cannot execute an empty query!');
        }

        $time = microtime(true);
        $hash = $this->core->hash($cmd.var_export($args, true)).'.sql';

        if ($ttl && $cache = $this->core->cacheGet($hash)) {
            $this->core->log(Core::LOG_INFO, sprintf('(%fs) [CACHED] %s', microtime(true) - $time, $this->buildQuery($cmd, $args)));

            return $cache->getValue();
        }

        $query = $this->prepare($cmd, $args);

        $this->core->log(Core::LOG_INFO, sprintf('(%fs) %s', microtime(true) - $time, $this->buildQuery($cmd, $args)));

        if (!$query) {
            $this->rollback();

            throw new \LogicException($this->buildPdoErrorMessage($this->pdo));
        }

        $success = $query->execute();

        if (!$success) {
            $this->rollback();

            throw new \LogicException($this->buildPdoErrorMessage($query));
        }

        if ($this->isQueryFetchable($cmd) && $query->columnCount() > 0) {
            $data = $query->fetchAll(\PDO::FETCH_ASSOC);

            if ($ttl) {
                // Save to cache backend
                $this->core->cacheSet($hash, new CacheItem($data, $ttl, $time));
            }

            return $data;
        }

        return $query->rowCount();
    }

    /**
     * Prepare sql statement.
     *
     * @param string     $cmd
     * @param array|null $args
     *
     * @return PDOStatement|null
     */
    public function prepare($cmd, array $args = null)
    {
        $query = $this->getPdo()->prepare($cmd) ?: null;

        if ($query && $args) {
            // ensure 1-based arguments
            if (array_key_exists(0, $args)) {
                array_unshift($args, null);
                unset($args[0]);
            }

            foreach ($args as $key => $arg) {
                $valueType = is_array($arg) ? array_values($arg) : [$arg];
                array_unshift($valueType, $key);

                call_user_func_array([$query, 'bindValue'], $valueType);
            }
        }

        return $query;
    }

    /**
     * Returns transaction status.
     *
     * @return bool
     */
    public function trans()
    {
        return $this->trans;
    }

    /**
     * Start transaction.
     *
     * @return Connection
     */
    public function begin()
    {
        if (!$this->trans) {
            $this->trans = $this->getPdo()->beginTransaction();
        }

        return $this;
    }

    /**
     * Commit transaction.
     *
     * @return Connection
     */
    public function commit()
    {
        if ($this->trans) {
            $this->trans = !$this->getPdo()->commit();
        }

        return $this;
    }

    /**
     * Rollback transaction.
     *
     * @return Connection
     */
    public function rollback()
    {
        if ($this->trans) {
            $this->trans = !$this->getPdo()->rollback();
        }

        return $this;
    }

    /**
     * Quote key.
     *
     * @param string $key
     *
     * @return string
     */
    public function key($key)
    {
        $quotes = [
            self::DB_SQLITE  => '``',
            self::DB_SQLITE2 => '``',
            self::DB_MYSQL   => '``',
            self::DB_PGSQL   => '""',
            self::DB_OCI     => '""',
            self::DB_MSSQL   => '[]',
            self::DB_SQLSRV  => '[]',
            self::DB_ODBC    => '[]',
            self::DB_SYBASE  => '[]',
            self::DB_DBLIB   => '[]',
        ];

        if ($key && isset($quotes[$driver = $this->getDriverName()])) {
            $lft = $quotes[$driver][0];
            $rgt = $quotes[$driver][1];

            return $lft.implode($rgt.'.'.$lft, explode('.', $key)).$rgt;
        }

        return $key;
    }

    /**
     * Returns pdo type.
     *
     * @param mixed       $val
     * @param string|null $type
     * @param bool        $realType
     *
     * @return int
     */
    public function pdoType($val, $type = null, $realType = false)
    {
        $types = [
            'null'     => \PDO::PARAM_NULL,
            'resource' => \PDO::PARAM_LOB,
            'int'      => \PDO::PARAM_INT,
            'integer'  => \PDO::PARAM_INT,
            'bool'     => \PDO::PARAM_BOOL,
            'boolean'  => \PDO::PARAM_BOOL,
            'blob'     => \PDO::PARAM_LOB,
            'bytea'    => \PDO::PARAM_LOB,
            'image'    => \PDO::PARAM_LOB,
            'binary'   => \PDO::PARAM_LOB,
            'float'    => self::PARAM_FLOAT,
            'real'     => self::PARAM_FLOAT,
            'double'   => self::PARAM_FLOAT,
            'decimal'  => self::PARAM_FLOAT,
            'numeric'  => self::PARAM_FLOAT,
            'default'  => \PDO::PARAM_STR,
        ];
        $use = strtolower(null === $type ? gettype($val) : $type);
        $code = isset($types[$use]) ? $types[$use] : $types['default'];

        return $realType && ($code < 0) ? $types['default'] : $code;
    }

    /**
     * Returns php value.
     *
     * @param mixed    $val
     * @param int|null $type
     *
     * @return mixed
     */
    public function phpValue($val, $type = null)
    {
        $mType = null === $type ? $this->pdoType($val, null, true) : $type;

        if (\PDO::PARAM_NULL === $mType) {
            return;
        }

        if (\PDO::PARAM_INT === $mType) {
            return intval($val);
        }

        if (\PDO::PARAM_BOOL === $mType) {
            return (bool) $val;
        }

        if (\PDO::PARAM_STR === $mType) {
            return (string) $val;
        }

        if (\PDO::PARAM_LOB === $mType) {
            return (binary) $val;
        }

        if (self::PARAM_FLOAT === $mType) {
            return floatval($val);
        }

        return $val;
    }

    /**
     * Returns true if query fetchable.
     *
     * @param string $cmd
     *
     * @return bool
     */
    public function isQueryFetchable($cmd)
    {
        $pattern1 = '/(?:^[\s\(]*(?:EXPLAIN|SELECT|PRAGMA|SHOW)|RETURNING)\b/is';
        $pattern2 = '/^\s*(?:CALL|EXEC)\b/is';

        return (bool) (preg_match($pattern1, $cmd) || preg_match($pattern2, $cmd));
    }

    /**
     * Returns true if query invalid.
     *
     * @param string $cmd
     *
     * @return bool
     */
    public function isQueryEmpty($cmd)
    {
        return '' === preg_replace('/(^\s+|[\s;]+$)/', '', $cmd);
    }

    /**
     * Returns formatted error message.
     *
     * @param mixed $obj
     *
     * @return string
     */
    protected function buildPdoErrorMessage($obj)
    {
        $error = $obj->errorInfo();

        return get_class($obj).': '.$error[2].'.';
    }

    /**
     * Replace placeholder value.
     *
     * @param string     $cmd
     * @param array|null $args
     *
     * @return string
     */
    protected function buildQuery($cmd, array $args = null)
    {
        $keys = $vals = [];

        foreach ((array) $args as $key => $val) {
            $val = is_array($val) ? array_values($val) : [$val];

            $vals[] = var_export(call_user_func_array([$this, 'phpValue'], $val), true);
            $keys[] = '/'.preg_quote(is_numeric($key) ? chr(0).'?' : $key, '/').'/';
        }

        return preg_replace($keys, $vals, str_replace('?', chr(0).'?', $cmd), 1);
    }

    /**
     * Resolve schema default value.
     *
     * @param mixed $val
     *
     * @return mixed
     */
    protected function resolveSchemaDefaultValue($val)
    {
        return is_string($val) ? $this->core->cast(preg_replace('/^\s*([\'"])(.*)\1\s*/', '\2', $val)) : $val;
    }

    /**
     * Get schema commands.
     *
     * @param string $db
     * @param string $table
     * @param string $driver
     *
     * @return array
     */
    protected function getSchemaCommand($db, $table, $driver)
    {
        $engines = [
            self::DB_SQLITE  => 1,
            self::DB_SQLITE2 => 1,
            self::DB_MYSQL   => 2,
            self::DB_MSSQL   => 3,
            self::DB_SQLSRV  => 3,
            self::DB_SYBASE  => 3,
            self::DB_DBLIB   => 3,
            self::DB_PGSQL   => 3,
            self::DB_ODBC    => 3,
            self::DB_OCI     => 4,
        ];
        $commands = [
            1 => [
                'PRAGMA table_info('.$table.')',
                'name', 'type', 'dflt_value', 'notnull', 0, 'pk', true,
            ],
            [
                'SHOW columns FROM '.$db.'.'.$table,
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
                    'C.TABLE_NAME='.$table.
                    ($db ? ' AND C.TABLE_CATALOG='.$table : ''),
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
                        ' WHERE acc.table_name='.$table.
                        ' AND acc.column_name=c.column_name'.
                        ' AND constraint_type='.$this->key('P').') AS pkey '.
                'FROM all_tab_cols c '.
                'WHERE c.table_name='.$table,
                'FIELD', 'TYPE', 'DEFVAL', 'NULLABLE', 'Y', 'PKEY', 'P',
            ],
        ];

        if (isset($engines[$driver]) && isset($commands[$engines[$driver]])) {
            return $commands[$engines[$driver]];
        }

        throw new \DomainException(sprintf('Driver %s is not supported.', $driver));
    }

    /**
     * Prohibit cloning.
     *
     * @codeCoverageIgnore
     */
    protected function __clone()
    {
    }
}

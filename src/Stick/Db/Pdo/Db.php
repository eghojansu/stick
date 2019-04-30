<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick\Db\Pdo;

use Fal\Stick\Fw;

/**
 * PDO Wrapper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class Db
{
    /**
     * @var Fw
     */
    public $fw;

    /**
     * @var DriverInterface
     */
    public $driver;

    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var array
     */
    private $constructor;

    /**
     * @var array
     */
    private $cache = array();

    /**
     * @var bool
     */
    private $trans = false;

    /**
     * @var bool
     */
    private $log = true;

    /**
     * @var int
     */
    private $rows = 0;

    /**
     * Class constructor.
     *
     * @param Fw              $fw
     * @param DriverInterface $driver
     * @param string          $dsn
     * @param string|null     $username
     * @param string|null     $password
     * @param array|null      $schemas
     * @param array|null      $options
     */
    public function __construct(Fw $fw, DriverInterface $driver, string $dsn, string $username = null, string $password = null, array $schemas = null, array $options = null)
    {
        $this->fw = $fw;
        $this->driver = $driver;
        $this->constructor = array(
            'dsn' => $dsn,
            'username' => $username,
            'password' => $password,
            'options' => (array) $options,
            'schemas' => (array) $schemas,
        );
    }

    /**
     * Prohibit clone.
     *
     * @codeCoverageIgnore
     */
    private function __clone()
    {
    }

    /**
     * Returns PDO instance.
     *
     * @return PDO
     */
    public function pdo(): \PDO
    {
        if ($this->pdo) {
            return $this->pdo;
        }

        try {
            extract($this->constructor);

            $this->pdo = new \PDO($dsn, $username, $password, $options);
            $errorMode = $this->pdo->getAttribute(\PDO::ATTR_ERRMODE);
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            foreach ($schemas as $schema) {
                $this->pdo->exec($schema);
            }

            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, $errorMode);
        } catch (\PDOException $e) {
            !$this->log || $this->fw->log(Fw::LOG_EMERGENCY, $e->getMessage());

            throw new \LogicException('Unable to connect database!');
        }

        return $this->pdo;
    }

    /**
     * Returns driver name.
     *
     * @return string
     */
    public function driver(): string
    {
        if (!isset($this->cache['driver'])) {
            $this->cache['driver'] = $this->pdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);
        }

        return $this->cache['driver'];
    }

    /**
     * Returns driver version.
     *
     * @return string
     */
    public function version(): string
    {
        if (!isset($this->cache['version'])) {
            $this->cache['version'] = $this->pdo()->getAttribute(\PDO::ATTR_SERVER_VERSION);
        }

        return $this->cache['version'];
    }

    /**
     * Returns current dbname.
     *
     * @return string
     */
    public function dbname(): string
    {
        if (!isset($this->cache['dbname'])) {
            $this->cache['dbname'] = $this->driver->resolveDbName($this->constructor['dsn']);
        }

        return $this->cache['dbname'];
    }

    /**
     * Returns last affected rows.
     *
     * @return int
     */
    public function rows(): int
    {
        return $this->rows;
    }

    /**
     * Returns true if in transaction.
     *
     * @return bool
     */
    public function trans(): bool
    {
        return $this->trans;
    }

    /**
     * Returns true if transaction flag success.
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
     * Returns true if transaction commit success.
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
     * Returns true if transaction rollback success.
     *
     * @return bool
     */
    public function rollback(): bool
    {
        $out = $this->pdo()->rollback();
        $this->trans = false;

        return $out;
    }

    /**
     * Disable/enable or returns log status.
     *
     * @param bool $enable
     *
     * @return Db
     */
    public function log(bool $enable): Db
    {
        $this->log = $enable;

        return $this;
    }

    /**
     * Prepare sql statement.
     *
     * @param string $sql
     *
     * @return PDOStatement
     */
    public function prepare(string $sql): \PDOStatement
    {
        if ('' === preg_replace('/(^\s+|[\s;]+$)/', '', $sql)) {
            throw new \LogicException('Query empty!');
        }

        $query = $this->pdo()->prepare($sql);
        $error = $this->pdo->errorInfo();

        // PDO-level error occurred?
        if (\PDO::ERR_NONE !== $error[0]) {
            if ($this->trans) {
                $this->rollback();
            }

            $message = sprintf('PDO: [%s - %s] %s.', ...$error);
            !$this->log || $this->fw->log(Fw::LOG_INFO, $message.' '.$sql);

            throw new \LogicException($message);
        }

        return $query;
    }

    /**
     * Bind values.
     *
     * @param \PDOStatement $query
     * @param array         $values
     */
    public function bindValues(\PDOStatement $query, array $values): void
    {
        // ensure 1-based values
        if (array_key_exists(0, $values)) {
            array_unshift($values, '');
            unset($values[0]);
        }

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                // User-specified data type
                $query->bindValue($key, $value[0], $value[1]);
            } else {
                // Convert to PDO data type
                $query->bindValue($key, $value, DbUtil::type($value, null, false));
            }
        }
    }

    /**
     * Execute sql query.
     *
     * @param string      $sql
     * @param array|null  $values
     * @param int         $ttl
     * @param string|null $tag
     *
     * @return mixed
     */
    public function exec(string $sql, array $values = null, int $ttl = 0, string $tag = null)
    {
        $log = $this->stringify($sql, $values);
        $hash = $this->fw->hash($log, $tag.'.sql');

        if ($ttl && $result = $this->fw->cget($hash)) {
            return $result;
        }

        $start = $this->log ? microtime(true) : null;
        $query = $this->prepare($sql);

        if ($values) {
            $this->bindValues($query, $values);
        }

        $query->execute();
        $error = $query->errorinfo();

        !$this->log || $this->fw->log(FW::LOG_INFO, sprintf('(%fs) %s', microtime(true) - $start, $log));

        if (\PDO::ERR_NONE != $error[0]) {
            // Statement-level error occurred
            if ($this->trans) {
                $this->rollback();
            }

            throw new \LogicException(sprintf('Query: [%s - %s] %s.', ...$error));
        }

        $fetch = (preg_match('/(?:^[\s\(]*(?:EXPLAIN|SELECT|PRAGMA|SHOW)|RETURNING)\b/is', $sql) ||
            preg_match('/^\s*(?:CALL|EXEC)\b/is', $sql)) && 0 < $query->columnCount();

        if ($fetch) {
            $result = $query->fetchAll(\PDO::FETCH_ASSOC);
            $this->rows = 0;

            if ($ttl) {
                $this->fw->cset($hash, $result, $ttl);
            }

            return $result;
        }

        $this->rows = $query->rowCount();
        $query->closeCursor();

        return $this->rows;
    }

    /**
     * Exec queries.
     *
     * @param array       $commands
     * @param int         $ttl
     * @param string|null $tag
     *
     * @return array
     */
    public function mexec(array $commands, int $ttl = 0, string $tag = null): array
    {
        $auto = false;
        $result = array();

        if (!$this->trans) {
            $auto = true;
            $this->begin();
        }

        foreach ($commands as $sql => $values) {
            if (is_numeric($sql)) {
                $sql = $values;
                $values = null;
            }

            $result[] = $this->exec($sql, $values, $ttl, $tag);
        }

        if ($this->trans && $auto) {
            $this->commit();
        }

        return $result;
    }

    /**
     * Returns true if table exists.
     *
     * @param string $table
     *
     * @return bool
     */
    public function exists(string $table): bool
    {
        $errmode = $this->pdo()->getAttribute(\PDO::ATTR_ERRMODE);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);
        $out = $this->pdo->query('SELECT 1 FROM '.$this->driver->quote($table).' LIMIT 1');
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, $errmode);

        return is_object($out);
    }

    /**
     * Returns table's schema.
     *
     * @param string            $table
     * @param string|array|null $fields
     * @param int               $ttl
     *
     * @return Schema
     */
    public function schema(string $table, $fields = null, int $ttl = 0): Schema
    {
        $hash = $this->fw->hash($table.$this->fw->stringify($fields), '.schema');

        if ($ttl && $schema = $this->fw->cget($hash)) {
            return $schema;
        }

        $time = $this->log ? microtime(true) : null;
        $sql = $this->driver->sqlSchema($this->dbname(), $table);
        $result = $this->exec($sql);
        $schema = $this->driver->buildSchema($result);

        if ($fields) {
            $fields = array_fill_keys($this->fw->split($fields), true);

            foreach ($schema->getFields() as $field) {
                if (!isset($fields[$field])) {
                    $schema->rem($field);
                }
            }
        }

        !$this->log || $this->fw->log(Fw::LOG_INFO, sprintf('(%fs) Get schema: %s', microtime(true) - $time, $sql));

        if ($ttl) {
            $this->fw->cset($hash, $schema, $ttl);
        }

        return $schema;
    }

    /**
     * Stringify sql arguments.
     *
     * @param string     $sql
     * @param array|null $values
     *
     * @return string
     */
    private function stringify(string $sql, array $values = null): string
    {
        if (empty($values)) {
            return $sql;
        }

        $keys = $vals = array();
        $numkeys = chr(0).'?';
        $numregex = '/'.preg_quote($numkeys, '/').'/';

        foreach ($values as $key => $val) {
            if (is_array($val)) {
                $val = DbUtil::value(...$val);
            }

            $vals[] = $this->fw->stringify($val);
            $keys[] = is_numeric($key) ? $numregex : '/'.preg_quote($key, '/').'/';
        }

        return preg_replace($keys, $vals, str_replace('?', $numkeys, $sql), 1);
    }
}

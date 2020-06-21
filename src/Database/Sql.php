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

namespace Ekok\Stick\Database;

use Ekok\Stick\Fw;

/**
 * Sql class.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Sql
{
    const LIMIT_DEFAULT = 10;
    const LIMIT_MAX = 100;
    const LIMIT_MIN = 1;

    const PARAM_FLOAT = -1;
    const PARAM_TYPES = array(
        'null' => \PDO::PARAM_NULL,
        'boolean' => \PDO::PARAM_BOOL,
        'integer' => \PDO::PARAM_INT,
        'resource' => \PDO::PARAM_LOB,
        'string' => \PDO::PARAM_STR,
        'double' => self::PARAM_FLOAT,
    );
    const PARAM_TYPES_MAP = array(
        self::PARAM_FLOAT => \PDO::PARAM_STR,
    );

    protected $fw;
    protected $hive = array(
        'trans' => false,
        'logs' => array(),
        'qb' => null,
    );
    protected $options = array(
        'log_query' => false,
    );

    public function __construct(Fw $fw, QueryBuilderInterface $qb, array $options = null)
    {
        $this->fw = $fw;
        $this->hive['qb'] = $qb;
        $this->setOptions($options ?? array());
    }

    public function __get($key)
    {
        if (
            !array_key_exists($ukey = $key, $this->hive)
            && !array_key_exists($ukey = strtolower($key), $this->hive)) {
            $this->hive[$ukey] = method_exists($this, $method = '_'.$key) ? $this->{$method}() : null;
        }

        return $this->hive[$ukey];
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function setOptions(array $options): Sql
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    public function exec(string $command, array $arguments = null): QueryResult
    {
        $time = $this->options['log_query'] ? microtime(true) : 0;

        list($query, $newCommand, $error) = $this->doExec($command, $arguments);

        if ($error) {
            $this->fw->log(Fw::LOG_LEVEL_WARNING, $this->logFormat("{$error} ({$newCommand})", $time));
        } elseif ($this->options['log_query']) {
            $this->fw->log(Fw::LOG_LEVEL_INFO, $this->logFormat($newCommand, $time));
        }

        return $error ? new EmptyResult($newCommand) : new QueryResult($query);
    }

    public function execAll(array $commands): array
    {
        $results = array();
        $autoCommit = $this->qb->supportTransaction() && count($commands) > 1 && !$this->pdo->inTransaction();

        if ($autoCommit) {
            $this->pdo->beginTransaction();
        }

        foreach ($commands as $command => $arguments) {
            if (is_int($command)) {
                $command = $arguments;
                $arguments = null;
            }

            $results[] = $this->exec($command, $arguments);
        }

        if ($autoCommit) {
            $this->pdo->commit();
        }

        return $results;
    }

    public function paginate(string $table, int $page = 1, $filter = null, array $options = null): array
    {
        $page = max($page, 1);
        $size = min(max($options['limit'] ?? static::LIMIT_DEFAULT, static::LIMIT_MIN), static::LIMIT_MAX);
        $total = $this->count($table, $filter, $options);

        $subset = null;
        $count = 0;
        $from = 0;
        $to = 0;
        $max = 0;
        $empty = true;

        if ($total > 0) {
            $options['limit'] = $size;
            $options['offset'] = $page * $size - $size;

            $subset = $this->find($table, $filter, $options);
            $count = count($subset);
            $from = $options['offset'] + 1;
            $to = $options['offset'] + $count;
            $max = (int) ceil($total / $size);
            $empty = 0 === $count;
        }

        return compact(
            'empty', // indicate that subset is empty
            'page', // current page
            'max', // maximum/last page
            'size', // maximum rows/rows per page
            'total', // total rows in table
            'count', // record count in subset
            'subset', // records
            'from', // start number
            'to' // end number
        );
    }

    public function findOne(string $table, $filter = null, array $options = null): ?array
    {
        $options['limit'] = 1;

        return (array) $this->find($table, $filter, $options)->row;
    }

    public function find(string $table, $filter = null, array $options = null): QueryResult
    {
        list($command, $arguments) = $this->qb->select($table, $filter, $options);

        return $this->exec($command, $arguments);
    }

    public function count(string $table, $filter = null, array $options = null): int
    {
        list($command, $arguments) = $this->qb->count($table, $filter, $options);

        return (int) $this->exec($command, $arguments)->column();
    }

    public function insert(string $table, array $data): int
    {
        list($command, $arguments) = $this->qb->insert($table, $data);

        return $this->exec($command, $arguments)->rowCount;
    }

    public function insertBatch(string $table, array $data): int
    {
        list($command, $arguments) = $this->qb->insertBatch($table, $data);

        return $this->exec($command, $arguments)->rowCount;
    }

    public function update(string $table, array $data, $filter = null): int
    {
        list($command, $arguments) = $this->qb->update($table, $data, $filter);

        return $this->exec($command, $arguments)->rowCount;
    }

    public function delete(string $table, $filter = null): int
    {
        list($command, $arguments) = $this->qb->delete($table, $filter);

        return $this->exec($command, $arguments)->rowCount;
    }

    public function exists(string $table): bool
    {
        $time = microtime(true);
        $command = "SELECT 1 FROM {$this->qb->quote($table)} LIMIT 1";

        list($query, $newCommand, $error) = $this->doExec($command);

        if ($error && $this->options['log_query']) {
            $this->fw->log(Fw::LOG_LEVEL_INFO, $this->logFormat($error->getMessage(), $time));
        }

        return !$error;
    }

    public function valueType($value): int
    {
        return static::PARAM_TYPES[strtolower(gettype($value))] ?? static::PARAM_TYPES['string'];
    }

    public function valueCast($value, int $type)
    {
        switch ($type) {
            case static::PARAM_FLOAT:
                return (float) (is_string($value) ? $value : str_replace(',', '.', (string) $value));
            case \PDO::PARAM_NULL:
                return null;
            case \PDO::PARAM_INT:
                return (int) $value;
            case \PDO::PARAM_BOOL:
                return (bool) $value;
            case \PDO::PARAM_LOB:
                return (string) $value;
            default: // \PDO::PARAM_STR:
                return (string) $value;
        }
    }

    protected function doExec(string $command, array $arguments = null): array
    {
        $pdo = $this->pdo;

        try {
            $query = $pdo->prepare($command);

            if ($arguments) {
                list($keys, $values) = $this->bindQueryValues($query, $arguments);

                $newCommand = preg_replace($keys, $values, str_replace('?', chr(0).'?', $command));
            }

            $query->execute();

            return array($query, $newCommand ?? $command, null);
        } catch (\Throwable $e) {
            return array(null, $command, $e);
        }
    }

    protected function bindQueryValues(\PDOStatement $query, array $params = null): array
    {
        if ($params && (isset($params[0]) || array_key_exists(0, $params))) {
            array_unshift($params, null);
            unset($params[0]);
        }

        $keys = array();
        $values = array();

        foreach ($params ?? array() as $key => $value) {
            list($val, $type) = is_array($value) ? $value : array($value, $this->valueType($value));

            $keys[] = '/'.preg_quote(is_numeric($key) ? chr(0).'?' : $key).'/';
            $values[] = $this->fw->stringify($this->valueCast($val, $type));

            $query->bindValue($key, $val, static::PARAM_TYPES_MAP[$type] ?? $type);
        }

        return array($keys, $values);
    }

    protected function _pdo(): \PDO
    {
        $time = microtime(true);

        try {
            $dsn = $this->qb->getDsn();
            $user = $this->qb->getUser();
            $password = $this->qb->getPassword();
            $options = $this->qb->getOptions();
            $commands = $this->qb->getCommands();
            $options[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;

            $pdo = new \PDO($dsn, $user, $password, $options);

            foreach ($commands ?? array() as $command) {
                $pdo->exec($command);
            }

            return $pdo;
        } catch (\PDOException $e) {
            $this->fw->log(Fw::LOG_LEVEL_EMERGENCY, $this->logFormat($e->getMessage(), $time));

            throw new \LogicException('Error establishing a database connection.');
        }
    }

    protected function logFormat(string $message, float $time): string
    {
        $elapsed = number_format(1e3 * (microtime(true) - $time), 1);

        return "[SQL] ({$elapsed}ms) {$message}";
    }
}

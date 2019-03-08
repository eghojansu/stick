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

namespace Fal\Stick\Database\Driver;

use Fal\Stick\Cache\CacheInterface;
use Fal\Stick\Cache\CacheItem;
use Fal\Stick\Database\Adhoc;
use Fal\Stick\Database\DriverInterface;
use Fal\Stick\Database\Row;
use Fal\Stick\Logging\LogLevel;
use Fal\Stick\Logging\LoggerInterface;
use Fal\Stick\Util;

/**
 * Abstract sql database driver.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
abstract class AbstractPDOSqlDriver implements DriverInterface
{
    const PARAM_FLOAT = -1;

    /**
     * @var string
     */
    protected static $filterOperatorMask = '=<>&|^!~@[] ';

    /**
     * @var array
     */
    protected static $filterOperatorMap = array(
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

    /**
     * @var CacheInterface
     */
    protected $cache;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var PDO
     */
    protected $pdo;

    /**
     * @var string
     */
    protected $dsn;

    /**
     * @var array
     */
    protected $options = array(
        'username' => null,
        'password' => null,
        'options' => null,
        'commands' => null,
    );

    /**
     * @var int
     */
    protected $affectedRows = 0;

    /**
     * @var bool
     */
    protected $trans = false;

    /**
     * Class constructor.
     *
     * @param CacheInterface  $cache
     * @param LoggerInterface $logger
     * @param array|null      $options
     */
    public function __construct(CacheInterface $cache, LoggerInterface $logger, array $options = null)
    {
        $this->cache = $cache;
        $this->logger = $logger;
        $this->options = (array) $options + $this->extraOptions($options) + $this->options;
    }

    /**
     * Prohibit cloning.
     *
     * @codeCoverageIgnore
     */
    private function __clone()
    {
    }

    /**
     * Returns extra options.
     *
     * @param array|null $options
     *
     * @return array
     */
    abstract protected function extraOptions(array $options = null): array;

    /**
     * Build PDO dsn.
     *
     * @return string
     */
    abstract protected function createDsn(): string;

    /**
     * Build table row.
     *
     * @param string $table
     * @param array  $fields
     *
     * @return Row
     */
    abstract protected function createSchema(string $table, array $fields): Row;

    /**
     * Assign or retrieve database options.
     *
     * @param array|null $options
     *
     * @return array|AbstractPDOSqlDriver
     */
    public function options(array $options = null)
    {
        if ($options) {
            $this->options = $options + $this->options;

            return $this;
        }

        return $this->options;
    }

    /**
     * Returns dsn string.
     *
     * @return string
     */
    public function dsn(): string
    {
        if (!$this->dsn) {
            $this->dsn = $this->createDsn();
        }

        return $this->dsn;
    }

    /**
     * Returns pdo instance.
     *
     * @return PDO
     */
    public function pdo(): \PDO
    {
        if ($this->pdo) {
            return $this->pdo;
        }

        try {
            $this->pdo = new \PDO($this->dsn(), $this->options['username'], $this->options['password'], $this->options['options']);
            $mode = $this->pdo->getAttribute(\PDO::ATTR_ERRMODE);

            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            foreach ((array) $this->options['commands'] as $command) {
                $this->pdo->exec($command);
            }

            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, $mode);
        } catch (\Throwable $exception) {
            $this->logger->log(LogLevel::EMERGENCY, $exception->getMessage());

            throw new \LogicException('Database connection failed!', 0, $exception);
        }

        return $this->pdo;
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
     * @param array|null   $lookup
     *
     * @return array
     */
    public function filter($filter, array $lookup = null): array
    {
        if (!$filter) {
            return array();
        }

        if (!$lookup) {
            $lookup = array();
        }

        $ctr = 0;
        $str = '';
        $result = array();
        $map = static::$filterOperatorMap;
        $mask = static::$filterOperatorMask;

        foreach ((array) $filter as $key => $value) {
            if (is_numeric($key)) {
                if (is_string($value)) {
                    // raw
                    $str .= ' '.$value;
                } elseif ($filter !== $value && $cfilter = $this->filter((array) $value, $result + $lookup)) {
                    $str .= ($str ? ' AND' : '').' ('.array_shift($cfilter).')';
                    $result = array_merge($result, $cfilter);
                }

                continue;
            }

            $raw = is_string($value) && '```' === substr($value, 0, 3);
            $expr = $raw ? substr($value, 3) : $value;
            // clear column from comments format
            $ccol = (false === ($pos = strpos($key, '#'))) ? $key : substr($key, 0, $pos);
            $col = trim($ccol, $mask);
            $kcol = str_replace('.', '_', $col);
            $a1 = substr($ccol, 0, 1);
            $a2 = substr($ccol, 0, 2);
            $a3 = substr($ccol, 0, 3);
            $b1 = substr($ccol, -1);
            $b2 = substr($ccol, -2);
            $b3 = substr($ccol, -3);

            $str .= ' '.($map[$a3] ?? $map[$a2] ?? $map[$a1] ?? ($ctr ? 'AND' : ''));

            if ($col) {
                $str .= ' '.$this->quotekey($col);
                $str .= ' '.($map[$b3] ?? $map[$b2] ?? $map[$b1] ?? '=');
            }

            if ($raw) {
                $str .= ' '.$expr;
            } else {
                if ('!><' === $b3 || '><' === $b2) {
                    if (!is_array($expr)) {
                        throw new \LogicException(sprintf('BETWEEN operator needs an array operand, %s given.', gettype($expr)));
                    }

                    $kcol1 = ':'.$kcol.'1';
                    $kcol2 = ':'.$kcol.'2';
                    $str .= ' '.$kcol1.' AND '.$kcol2;
                    $result[$kcol1] = array_shift($expr);
                    $result[$kcol2] = array_shift($expr);
                } elseif ('![]' === $b3 || '[]' === $b2) {
                    $str .= ' (';
                    $i = 1;

                    foreach ((array) $expr as $val) {
                        $k = ':'.$kcol.$i;
                        $str .= $k.', ';
                        $result[$k] = $val;
                        ++$i;
                    }

                    $str = rtrim($str, ', ').')';
                } elseif (is_array($expr)) {
                    $cfilter = $this->filter($expr, $result + $lookup);

                    if ($cfilter) {
                        $str .= ' ('.array_shift($cfilter).')';
                        $result = array_merge($result, $cfilter);
                    }
                } elseif ($kcol) {
                    $k = $this->ensureKey(':'.$kcol, array_keys($result + $lookup));
                    $str .= ' '.$k;
                    $result[$k] = $expr;
                } else {
                    $str .= ' '.$expr;
                }
            }

            ++$ctr;
        }

        if ($str = trim($str)) {
            array_unshift($result, $str);
        }

        return $result;
    }

    /**
     * Returns default value from row row.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function schemaDefaultValue($value)
    {
        return is_string($value) ? Util::cast(preg_replace('/^\s*([\'"])(.*)\1\s*/', '\2', $value)) : $value;
    }

    /**
     * Returns adhoc raw expression (could be dangerous).
     *
     * @param Adhoc[] $adhocs
     *
     * @return string
     */
    public function adhocsExpression(array $adhocs): string
    {
        if (!$adhocs) {
            return '';
        }

        $select = '';

        foreach ($adhocs as $adhoc) {
            $select .= ',('.$adhoc->expression.') AS '.$this->quotekey($adhoc->name);
        }

        return $select;
    }

    /**
     * Returns order/group by expression.
     *
     * @param string|array $expression
     *
     * @return string
     */
    public function orderExpression($expression): string
    {
        if (is_array($expression)) {
            $result = '';

            foreach ($expression as $key => $order) {
                if (is_numeric($key)) {
                    $key = $order;
                    $order = null;
                }

                $result .= $this->quotekey($key).' '.$order.', ';
            }

            return rtrim($result, ', ');
        }

        return $expression;
    }

    /**
     * Returns select query.
     *
     * @param Row        $row
     * @param string     $fields
     * @param array|null $clause
     * @param array|null $options
     *
     * @return array Array of sql and arguments to execute
     */
    public function stringify(Row $row, string $fields, array $clause = null, array $options = null): array
    {
        if (!$fields) {
            $fields = '*';
        }

        $options = (array) $options + array(
            'group' => null,
            'having' => null,
            'order' => null,
            'limit' => 0,
            'offset' => 0,
            'comment' => null,
        );
        $sql = 'SELECT '.$fields.' FROM '.$this->quotekey($row->table());
        $arguments = array();

        if ($row->alias) {
            $sql .= ' AS '.$this->quotekey($row->alias);
        }

        if ($arguments = $this->filter($clause)) {
            $sql .= ' WHERE '.array_shift($arguments);
        }

        if ($options['group']) {
            $sql .= ' GROUP BY '.$this->orderExpression($options['group']);
        }

        if ($filter = $this->filter($options['having'])) {
            $sql .= ' HAVING '.array_shift($filter);
            $arguments = array_merge($arguments, $filter);
        }

        if ($options['order']) {
            $sql .= ' ORDER BY '.$this->orderExpression($options['order']);
        }

        if ($options['limit']) {
            $sql .= ' LIMIT '.(int) $options['limit'];
        }

        if ($options['offset']) {
            $sql .= ' OFFSET '.(int) $options['offset'];
        }

        if ($options['comment']) {
            $sql .= PHP_EOL.' /* '.$options['comment'].' */';
        }

        return array($sql, $arguments);
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
    public function pdoType($val, string $type = null, bool $realType = false): int
    {
        $types = array(
            'null' => \PDO::PARAM_NULL,
            'int\b|integer' => \PDO::PARAM_INT,
            'bool' => \PDO::PARAM_BOOL,
            'blob|bytea|image|binary|resource' => \PDO::PARAM_LOB,
            'float|real|double|decimal|numeric' => $realType ? \PDO::PARAM_STR : static::PARAM_FLOAT,
        );

        if (null === $type) {
            $type = gettype($val);
        }

        foreach ($types as $pattern => $code) {
            if (preg_match('/'.$pattern.'/i', $type)) {
                return $code;
            }
        }

        return \PDO::PARAM_STR;
    }

    /**
     * Returns php value.
     *
     * @param mixed    $val
     * @param int|null $type
     *
     * @return mixed
     */
    public function phpValue($val, int $type = null)
    {
        if (null === $type) {
            $type = $this->pdoType($val, null, true);
        }

        if (\PDO::PARAM_NULL === $type) {
            return null;
        }

        if (\PDO::PARAM_INT === $type) {
            return intval($val);
        }

        if (\PDO::PARAM_BOOL === $type) {
            return (bool) $val;
        }

        if (\PDO::PARAM_STR === $type) {
            return (string) $val;
        }

        if (\PDO::PARAM_LOB === $type) {
            return (string) $val;
        }

        if (static::PARAM_FLOAT === $type) {
            return floatval($val);
        }

        return $val;
    }

    /**
     * Returns quoted key.
     *
     * @param string $key
     *
     * @return string
     */
    public function quotekey(string $key): string
    {
        if (false !== strpos($key, '.')) {
            $key = implode('`.`', explode('.', $key));
        }

        return '`'.$key.'`';
    }

    /**
     * Returns true if query empty.
     *
     * @param string $query
     *
     * @return bool
     */
    public function isQueryEmpty(string $query): bool
    {
        return !preg_replace('/(^\s+|[\s;]+$)/', '', $query);
    }

    /**
     * Returns true if query is a select form.
     *
     * @param string $query
     *
     * @return bool
     */
    public function isSelectQuery(string $query): bool
    {
        return (bool) preg_match('/(?:^[\s\(]*(?:WITH|EXPLAIN|SELECT|PRAGMA|SHOW)|RETURNING)\b/is', $query);
    }

    /**
     * Returns true if query is a call form.
     *
     * @param string $query
     *
     * @return bool
     */
    public function isCallQuery(string $query): bool
    {
        return (bool) preg_match('/^\s*(?:CALL|EXEC)\b/is', $query);
    }

    /**
     * Bind arguments to PDOStatement.
     *
     * @param PDOStatement $query
     * @param array|null   $arguments
     */
    public function bindQuery(\PDOStatement $query, array $arguments = null): void
    {
        if ($arguments) {
            // ensure 1-based arguments
            if (array_key_exists(0, $arguments)) {
                array_unshift($arguments, '');
                unset($arguments[0]);
            }

            foreach ($arguments as $key => $value) {
                if (is_array($value)) {
                    // User-specified data type
                    $query->bindValue($key, $value[0], is_string($value[1]) ? \PDO::PARAM_STR : $value[1]);
                } else {
                    // Convert to PDO data type
                    $query->bindValue($key, $value, $this->pdoType($value, null, true));
                }
            }
        }
    }

    /**
     * Returns PDOStatement from sql command.
     *
     * @param string     $command
     * @param bool       $bind
     * @param array|null $arguments
     *
     * @return PDOStatement
     */
    public function prepareQuery(string $command, bool $bind = false, array $arguments = null): \PDOStatement
    {
        if ($this->isQueryEmpty($command)) {
            throw new \LogicException('Cannot prepare an empty query.');
        }

        $query = $this->pdo()->prepare($command);
        $error = $this->pdo->errorInfo();

        if (\PDO::ERR_NONE !== $error[0]) {
            // PDO-level error occurred
            if ($this->trans) {
                $this->rollback();
            }

            throw new \LogicException(sprintf('PDO: [%s - %s] %s.', ...$error));
        }

        if ($bind) {
            $this->bindQuery($query, $arguments);
        }

        return $query;
    }

    /**
     * Returns number of affected rows by latest query.
     *
     * @return int
     */
    public function affectedRows(): int
    {
        return $this->affectedRows;
    }

    /**
     * Execute sql query.
     *
     * @param string            $command
     * @param string|array|null $arguments
     * @param int               $ttl
     * @param string|null       $tag
     *
     * @return int|array
     */
    public function exec(string $command, $arguments = null, int $ttl = 0, string $tag = null)
    {
        $hash = Util::hash($this->dsn.$command.var_export($arguments, true)).rtrim('.'.$tag, '.').'.sql';

        if ($ttl && $item = $this->cache->get($hash)) {
            return $item->getValue();
        }

        $query = $this->prepareQuery($command, true, $arguments);
        $query->execute();
        $error = $query->errorinfo();

        if (\PDO::ERR_NONE != $error[0]) {
            // Statement-level error occurred
            if ($this->trans) {
                $this->rollback();
            }

            throw new \LogicException(sprintf('Query: [%s - %s] %s.', ...$error));
        }

        if ($this->isSelectQuery($command) || ($this->isCallQuery($command) && $query->columnCount())) {
            $result = $query->fetchall(\PDO::FETCH_ASSOC);

            $this->affectedRows = count($result);

            if ($ttl) {
                // Save to cache backend
                $this->cache->set($hash, new CacheItem($result, $ttl));
            }

            return $result;
        }

        $this->affectedRows = $query->rowCount();
        $query->closeCursor();

        return $this->affectedRows;
    }

    /**
     * Execute commands and arguments pair.
     *
     * @param array       $commandsArguments
     * @param int         $ttl
     * @param string|null $tag
     *
     * @return array
     */
    public function execAll(array $commandsArguments, int $ttl = 0, string $tag = null): array
    {
        $auto = false;
        $result = array();

        if (!$this->trans) {
            $auto = true;
            $this->begin();
        }

        foreach ($commandsArguments as $command => $arguments) {
            if (is_numeric($command)) {
                $command = $arguments;
                $arguments = null;
            }

            $result[] = $this->exec($command, $arguments, $ttl, $tag);
        }

        if ($this->trans && $auto) {
            $this->commit();
        }

        return $result;
    }

    /**
     * Execute multiple arguments in single command.
     *
     * @param string $command
     * @param array  $arguments
     *
     * @return array
     */
    public function execBatch(string $command, array $arguments): array
    {
        $query = $this->prepareQuery($command);
        $select = $this->isSelectQuery($command);
        $call = $this->isCallQuery($command);
        $auto = false;
        $result = array();

        if (!$this->trans) {
            $auto = true;
            $this->begin();
        }

        foreach ($arguments as $key => $argument) {
            $this->bindQuery($query, $argument);

            $query->execute();
            $error = $query->errorinfo();

            if (\PDO::ERR_NONE != $error[0]) {
                // Statement-level error occurred
                if ($this->trans) {
                    $this->rollback();
                }

                throw new \LogicException(sprintf('Query (%s): [%s - %s] %s.', $key, ...$error));
            }

            if ($select || ($call && $query->columnCount())) {
                $result[] = $res = $query->fetchall(\PDO::FETCH_ASSOC);
                $this->affectedRows = count($res);
            } else {
                $result[] = $this->affectedRows = $query->rowCount();
                $query->closeCursor();
            }
        }

        if ($this->trans && $auto) {
            $this->commit();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function schema(string $table, array $options = null, int $ttl = 0): Row
    {
        $fields = Util::split($options['fields'] ?? null);
        $hash = Util::hash($table.var_export($fields, true)).$ttl.'.row';
        $time = microtime(true);

        if ($ttl && $item = $this->cache->get($hash)) {
            $this->logger->log(LogLevel::INFO, sprintf('(%f s) [CACHED] Retrieving row: %s', microtime(true) - $time, $table));

            return $item->getValue();
        }

        if (!$this->exists($table)) {
            throw new \LogicException(sprintf('Unknown table: %s.', $table));
        }

        $schema = $this->createSchema($table, $fields);

        if ($ttl) {
            // Save to cache backend
            $this->cache->set($hash, new CacheItem($schema, $ttl));
        }

        $this->logger->log(LogLevel::INFO, sprintf('(%f s) Retrieving row: %s', microtime(true) - $time, $table));

        return $schema;
    }

    /**
     * {@inheritdoc}
     */
    public function find(Row $row, array $clause = null, array $options = null, int $ttl = 0): array
    {
        $records = array();
        $fields = $row->getFields();
        $adhocs = $row->getAdhocs();
        $select = implode(',', array_map(array($this, 'quotekey'), array_keys($fields))).$this->adhocsExpression($adhocs);
        $fields += $adhocs;

        list($sql, $arguments) = $this->stringify($row, $select, $clause, $options);

        foreach ($this->exec($sql, $arguments, $ttl) as $key => $record) {
            foreach ($record as $field => &$value) {
                $value = $this->phpValue($value, $fields[$field]->pdo_type);
                unset($value);
            }

            $records[] = (clone $row)->fromArray($record)->commit();
        }

        return $records;
    }

    /**
     * {@inheritdoc}
     */
    public function first(Row $row, array $clause = null, array $options = null, int $ttl = 0): ?Row
    {
        $options['limit'] = 1;
        $result = $this->find($row, $clause, $options, $ttl);

        return $result[0] ?? null;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function count(Row $row, array $clause = null, array $options = null, int $ttl = 0): int
    {
        $adhocs = $row->getAdhocs();
        $subQueryMode = isset($options['group']) && $options['group'];

        if (!$subQueryMode) {
            $adhocs['_rows'] = new Adhoc('_rows', 'COUNT(*)');
        }

        $select = ltrim($this->adhocsExpression($adhocs), ',');

        if ($subQueryMode && empty($select)) {
            $select = $options['group'];
        }

        list($sql, $arguments) = $this->stringify($row, $select, $clause, $options);

        if ($subQueryMode) {
            $sql = 'SELECT COUNT(*) AS '.$this->quotekey('_rows').' '.
                'FROM ('.$sql.') AS '.$this->quotekey('_temp');
        }

        $result = $this->exec($sql, $arguments, $ttl);

        return (int) $result[0]['_rows'];
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(Row $row, int $page, int $limit = 10, array $clause = null, array $options = null, int $ttl = 0): array
    {
        $total = $this->count($row, $clause, $options, $ttl);
        $pages = (int) ceil($total / $limit);
        $count = 0;
        $start = 0;
        $end = 0;

        if ($page > 0) {
            $options['offset'] = ($page - 1) * $limit;
            $options['limit'] = $limit;

            $subset = $this->find($row, $clause, $options, $ttl);
            $count = count($subset);

            if ($count) {
                $start = $options['offset'] + 1;
                $end = $options['offset'] + $count;
            }
        }

        return compact('subset', 'total', 'count', 'pages', 'page', 'start', 'end');
    }

    /**
     * {@inheritdoc}
     */
    public function insert(Row $row): ?Row
    {
        $inc = null;
        $fields = '';
        $values = '';
        $arguments = array();

        foreach ($row->getFields() as $field) {
            $value = $field->getValue();

            // detect auto increment key
            if ($field->pkey && !$inc && \PDO::PARAM_INT == $field->pdo_type && !$value && !$field->nullable) {
                $inc = $field->name;
            }

            if (!$field->isChanged()) {
                continue;
            }

            if ($field->name !== $inc && null === $value && !$field->nullable) {
                throw new \LogicException(sprintf('Field cannot be null: %s.', $field->name));
            }

            $fields .= ','.$this->quotekey($field->name);
            $values .= ',?';
            $arguments[] = array($value, $field->pdo_type);
        }

        if (!$fields) {
            return null;
        }

        $sql = 'INSERT INTO '.$this->quotekey($row->table()).' ('.ltrim($fields, ',').') VALUES ('.ltrim($values, ',').')';

        $result = 0 < $this->exec($sql, $arguments);

        if ($result && $inc) {
            return $this->first($row, array($inc => $this->pdo->lastInsertId()))->commit();
        }

        return $result ? $row->commit() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function update(Row $row): bool
    {
        $pairs = '';
        $filter = '';
        $arguments = array();
        $filterArguments = array();

        foreach ($row->getFields() as $field) {
            $value = $field->getValue();

            if ($field->pkey) {
                $filter .= ' AND '.$this->quotekey($field->name).'=?';
                $filterArguments[] = array($field->getInitial(), $field->pdo_type);
            }

            if (!$field->isChanged()) {
                continue;
            }

            if (null === $value && !$field->nullable) {
                throw new \LogicException(sprintf('Field cannot be null: %s.', $field->name));
            }

            $pairs .= ','.$this->quotekey($field->name).'=?';
            $arguments[] = array($value, $field->pdo_type);
        }

        if (!$pairs || !$filter) {
            return false;
        }

        $sql = 'UPDATE '.$this->quotekey($row->table()).' SET '.ltrim($pairs, ',').' WHERE'.substr($filter, 4);
        $result = 0 < $this->exec($sql, array_merge($arguments, $filterArguments));

        if ($result) {
            $row->commit();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Row $row): bool
    {
        $filter = '';
        $arguments = array();

        foreach ($row->getFields() as $field) {
            if ($field->pkey && $value = $field->getInitial()) {
                $filter .= ' AND '.$this->quotekey($field->name).'=?';
                $arguments[] = array($value, $field->pdo_type);
            }
        }

        if (!$filter) {
            return false;
        }

        $sql = 'DELETE FROM '.$this->quotekey($row->table()).' WHERE'.substr($filter, 4);

        return 0 < $this->exec($sql, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByClause(Row $row, array $clause = null): int
    {
        $sql = 'DELETE FROM '.$this->quotekey($row->table());

        if ($arguments = $this->filter($clause)) {
            $sql .= ' WHERE '.array_shift($arguments);
        }

        return $this->exec($sql, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function isSupportTransaction(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function inTransaction(): bool
    {
        return $this->trans;
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
        $out = $this->pdo()->rollback();
        $this->trans = false;

        return $out;
    }

    /**
     * Ensure key is not exists.
     *
     * @param string $key
     * @param array  $keys
     *
     * @return string
     */
    protected function ensureKey(string $key, array $keys): string
    {
        if (in_array($key, $keys)) {
            $seq = 2;

            while (preg_grep('/^'.$key.'__'.$seq.'$/', $keys)) {
                ++$seq;
            }

            return $key.'__'.$seq;
        }

        return $key;
    }
}

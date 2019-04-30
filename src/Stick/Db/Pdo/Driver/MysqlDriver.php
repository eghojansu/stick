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

namespace Fal\Stick\Db\Pdo\Driver;

use Fal\Stick\Db\Pdo\DbUtil;
use Fal\Stick\Db\Pdo\Schema;
use Fal\Stick\Db\Pdo\DriverInterface;

/**
 * Sql driver.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class MysqlDriver implements DriverInterface
{
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
     * {@inheritdoc}
     */
    public function resolveDbName(string $dsn): string
    {
        return preg_match('/dbname=([^;]+)/i', $dsn, $match) ? $match[1] : '';
    }

    /**
     * {@inheritdoc}
     */
    public function quote(string $key): string
    {
        $a = $b = '`';

        if (false === strpos($key, '.')) {
            return $a.$key.$b;
        }

        return $a.str_replace('.', $b.'.'.$a, $key).$b;
    }

    /**
     * {@inheritdoc}
     */
    public function sqlSelect(string $fields, string $table, string $alias = null, $filters = null, array $options = null): array
    {
        $options = (array) $options + array(
            'group' => null,
            'having' => null,
            'order' => null,
            'limit' => 0,
            'offset' => 0,
            'comment' => null,
        );

        $sql = 'SELECT '.$fields.' FROM '.$this->quote($table);
        $arguments = array();

        if ($alias) {
            $sql .= ' AS '.$this->quote($alias);
        }

        if ($arguments = $this->filter($filters)) {
            $sql .= ' WHERE '.array_shift($arguments);
        }

        if ($options['group']) {
            $sql .= ' GROUP BY '.$this->fixOrder($options['group']);
        }

        if ($filter = $this->filter($options['having'])) {
            $sql .= ' HAVING '.array_shift($filter);
            $arguments = array_merge($arguments, $filter);
        }

        if ($options['order']) {
            $sql .= ' ORDER BY '.$this->fixOrder($options['order']);
        }

        if ($options['limit']) {
            $sql .= ' LIMIT '.(int) $options['limit'];
        }

        if ($options['offset']) {
            $sql .= ' OFFSET '.(int) $options['offset'];
        }

        if ($options['comment']) {
            $sql .= ' /* '.$options['comment'].' */';
        }

        return array($sql, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function sqlCount(?string $fields, string $table, string $alias = null, $filters = null, array $options = null): array
    {
        $subQueryMode = isset($options['group']) && $options['group'];

        if (!$subQueryMode) {
            $fields .= ($fields ? ', ' : '').'COUNT(*) AS _rows';
        }

        if ($subQueryMode && empty($fields)) {
            $fields = preg_replace('/\h*\b(asc|desc)\b/i', '', $options['group']);
        }

        list($sql, $arguments) = $this->sqlSelect($fields, $table, $alias, $filters, $options);

        if ($subQueryMode) {
            $sql = sprintf('SELECT COUNT(*) AS _rows FROM (%s) AS _temp', $sql);
        }

        return array($sql, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function sqlInsert(string $table, Schema $schema, array $data): array
    {
        $inc = null;
        $fields = '';
        $values = '';
        $arguments = array();

        foreach ($schema as $key => $field) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            // detect auto increment key
            if ($field['pkey'] && !$inc && \PDO::PARAM_INT === $field['pdo_type'] && !$data[$key] && !$field['nullable']) {
                $inc = $key;
                continue;
            }

            if ($key !== $inc && null === $data[$key] && !$field['nullable']) {
                throw new \LogicException(sprintf('Field cannot be null: %s.', $key));
            }

            $fields .= ', '.$this->quote($key);
            $values .= ', ?';
            $arguments[] = array($data[$key], $field['pdo_type']);
        }

        if (!$fields) {
            return array();
        }

        if (!$inc && $keys = $schema->getKeys()) {
            foreach ($keys as $key) {
                if (empty($data[$key]) && !$schema[$key]['nullable']) {
                    $inc = $key;
                    break;
                }
            }
        }

        $sql = 'INSERT INTO '.$this->quote($table).' ('.ltrim($fields, ', ').') VALUES ('.ltrim($values, ', ').')';

        return array($sql, $arguments, $inc);
    }

    /**
     * {@inheritdoc}
     */
    public function sqlUpdate(string $table, Schema $schema, array $data, array $keys): array
    {
        $pairs = '';
        $filter = '';
        $arguments = array();
        $filterArguments = array();

        foreach ($schema as $key => $field) {
            if ($field['pkey'] && isset($keys[$key])) {
                $filter .= ' AND '.$this->quote($key).' = ?';
                $filterArguments[] = array($keys[$key], $field['pdo_type']);
            }

            if (!array_key_exists($key, $data)) {
                continue;
            }

            if (null === $data[$key] && !$field['nullable']) {
                throw new \LogicException(sprintf('Field cannot be null: %s.', $key));
            }

            $pairs .= ', '.$this->quote($key).' = ?';
            $arguments[] = array($data[$key], $field['pdo_type']);
        }

        if (!$pairs || !$filter) {
            return array();
        }

        $sql = 'UPDATE '.$this->quote($table).' SET '.ltrim($pairs, ', ').' WHERE'.substr($filter, 4);

        return array($sql, array_merge($arguments, $filterArguments));
    }

    /**
     * {@inheritdoc}
     */
    public function sqlDelete(string $table, Schema $schema, array $keys): array
    {
        $filter = '';
        $arguments = array();

        foreach ($keys as $key => $value) {
            if ($value && isset($schema[$key]) && $schema[$key]['pkey']) {
                $filter .= ' AND '.$this->quote($key).' = ?';
                $arguments[] = array($value, $schema[$key]['pdo_type']);
            }
        }

        if (!$filter) {
            return array();
        }

        $sql = 'DELETE FROM '.$this->quote($table).' WHERE'.substr($filter, 4);

        return array($sql, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function sqlDeleteBatch(string $table, $filters): array
    {
        $sql = 'DELETE FROM '.$this->quote($table);

        if ($arguments = $this->filter($filters)) {
            $sql .= ' WHERE '.array_shift($arguments);
        }

        return array($sql, $arguments);
    }

    /**
     * {@inheritdoc}
     */
    public function sqlSchema(string $db, string $table): string
    {
        return 'SHOW COLUMNS FROM '.$this->quote($db).'.'.$this->quote($table);
    }

    /**
     * {@inheritdoc}
     */
    public function buildSchema(array $rows): Schema
    {
        $schema = new Schema();

        foreach ($rows as $field) {
            $schema->set($field['Field'], array(
                'default' => DbUtil::defaultValue($field['Default']),
                'nullable' => 'YES' === $field['Null'],
                'pkey' => false !== strpos($field['Key'], 'PRI'),
                'type' => $field['Type'],
                'pdo_type' => DbUtil::type(null, $field['Type']),
            ));
        }

        return $schema;
    }

    /**
     * Returns order/group by expression.
     *
     * @param string $expression
     *
     * @return string
     */
    public function fixOrder(string $expression): string
    {
        $fixed = '';

        foreach (explode(',', $expression) as $item) {
            preg_match('/^\h*(\w+[._\-\w]*)(?:\h+((?:ASC|DESC)[\w\h]*))?\h*$/i', $item, $match);

            $fixed .= ', '.$this->quote($match[1]);

            if (isset($match[2])) {
                $fixed .= ' '.$match[2];
            }
        }

        return trim($fixed, ', ');
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
            $b1 = substr($ccol, -1);
            $b2 = substr($ccol, -2);
            $b3 = substr($ccol, -3);

            $str .= ' ';

            if (isset($map[$a1])) {
                $str .= $map[$a1];
            } elseif ($ctr) {
                $str .= 'AND';
            }

            if ($col) {
                $str .= ' '.$this->quote($col).' ';

                if (isset($map[$b3])) {
                    $str .= $map[$b3];
                } elseif (isset($map[$b2])) {
                    $str .= $map[$b2];
                } elseif (isset($map[$b1])) {
                    $str .= $map[$b1];
                } else {
                    $str .= '=';
                }
            }

            if ($raw) {
                $str .= ' '.$expr;
            } else {
                if ('!><' === $b3 || '><' === $b2) {
                    if (!is_array($expr)) {
                        throw new \LogicException(sprintf('Operator needs an array operand, %s given.', gettype($expr)));
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

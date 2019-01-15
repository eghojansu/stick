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

/**
 * Mapper query builder helper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class QueryBuilder
{
    const FILTER_OPERATOR_MASK = '=<>&|^!~@[] ';
    public static $filterOperatorMap = [
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

    /**
     * @var Mapper
     */
    protected $mapper;

    /**
     * Class constructor.
     *
     * @param Mapper $mapper
     */
    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    public static function create(Mapper $mapper)
    {
        return new self($mapper);
    }

    /**
     * Returns count query.
     *
     * @param mixed      $filter
     * @param array|null $options
     *
     * @return array
     */
    public function count($filter = null, array $options = null)
    {
        $db = $this->mapper->db();
        $prefix = in_array($db->getDriverName(), [
            Sql::DB_MSSQL,
            Sql::DB_DBLIB,
            Sql::DB_SQLSRV,
        ]) ? 'TOP 100 PERCENT ' : '';
        list($sub, $args) = $this->select($prefix.'*', $filter, $options);

        $sql = 'SELECT COUNT(*) AS '.$db->key('_rows').
               ' FROM ('.$sub.') AS '.$db->key('_temp');

        return [$sql, $args];
    }

    /**
     * Build select query.
     *
     * @param string|null $fields
     * @param mixed       $filter
     * @param array|null  $options
     *
     * @return array
     */
    public function select($fields = null, $filter = null, array $options = null)
    {
        list($criteria, $args) = $this->resolveCriteria($filter, $options);
        $sql = sprintf('SELECT %s FROM %s%s', $fields ?: $this->fields(), $this->mapper->map(), $criteria);

        return [$sql, $args];
    }

    /**
     * Build insert query.
     *
     * @return array
     */
    public function insert()
    {
        $args = [];
        $ctr = 0;
        $sql = '';
        $fields = '';
        $values = '';
        $ckeys = [];
        $schema = [];
        $inc = null;
        $db = $this->mapper->db();
        $keys = $this->mapper->keys(false);
        $map = $this->mapper->map();

        foreach ($this->mapper->schema() as $key => $field) {
            if ($field['pkey'] && !$inc && \PDO::PARAM_INT == $field['pdo_type'] && empty($field['value']) && !$field['nullable']) {
                // detect auto increment key
                $inc = $key;
            }

            if ($field['changed'] && $key !== $inc) {
                $fields .= ','.$db->key($key);
                $values .= ',?';
                $args[++$ctr] = [$field['value'], $field['pdo_type']];
                $ckeys[] = $key;
            }

            $schema[$key] = $field;
            $schema[$key]['initial'] = $field['value'];
            $schema[$key]['changed'] = false;
        }

        if ($fields) {
            $driver = $db->getDriverName();
            $prefix = in_array($driver, [
                Sql::DB_MSSQL,
                Sql::DB_DBLIB,
                Sql::DB_SQLSRV,
            ]) && array_intersect($keys, $ckeys) ? 'SET IDENTITY_INSERT '.$map.' ON;' : '';
            $suffix = Sql::DB_PGSQL === $driver ? ' RETURNING '.$db->key(reset($keys)) : '';
            $sql = sprintf('%sINSERT INTO %s (%s) VALUES (%s)%s', $prefix, $map, ltrim($fields, ','), ltrim($values, ','), $suffix);
        }

        return [$sql, $args, $inc, $schema];
    }

    /**
     * Build update query.
     *
     * @return array
     */
    public function update()
    {
        $ctr = 0;
        $sql = '';
        $pairs = '';
        $filter = '';
        $args = [];
        $schema = [];
        $initial = [];
        $db = $this->mapper->db();

        foreach ($this->mapper->schema() as $key => $field) {
            if ($field['changed']) {
                $pairs .= ','.$db->key($key).'=?';
                $args[++$ctr] = [$field['value'], $field['pdo_type']];
            }

            $schema[$key] = $field;
            $schema[$key]['initial'] = $field['value'];
            $schema[$key]['changed'] = false;
            $initial[$key] = $field['initial'];
        }

        foreach ($this->mapper->keys(false) as $key) {
            if (isset($schema[$key]) && $initial[$key]) {
                $filter .= ' AND '.$db->key($key).'=?';
                $args[++$ctr] = [$initial[$key], $schema[$key]['pdo_type']];
            }
        }

        if ($pairs && $filter) {
            $sql = 'UPDATE '.$this->mapper->map().' SET '.ltrim($pairs, ',').' WHERE'.substr($filter, 4);
        }

        return [$sql, $args, $schema];
    }

    /**
     * Build delete query.
     *
     * @return array
     */
    public function delete()
    {
        $filter = '';
        $args = [];
        $ctr = 0;
        $sql = 'DELETE FROM '.$this->mapper->map();
        $db = $this->mapper->db();

        foreach ($this->mapper->schema() as $key => $field) {
            if ($field['pkey']) {
                $filter .= ' AND '.$db->key($key).'=?';
                $args[++$ctr] = [$field['initial'], $field['pdo_type']];
            }
        }

        $sql .= ' WHERE'.substr($filter, 4);

        return [$sql, $args];
    }

    /**
     * Build delete batch.
     *
     * @param mixed $filter
     *
     * @return array
     */
    public function deleteBatch($filter = null)
    {
        $sql = 'DELETE FROM '.$this->mapper->map();
        $args = $this->filter($filter);
        $criteria = $args ? ' WHERE '.array_shift($args) : '';

        return [$sql.$criteria, $args];
    }

    /**
     * Convert fields and adhoc as select column.
     *
     * @return string
     */
    public function fields()
    {
        $fields = '';
        $db = $this->mapper->db();
        $map = $this->mapper->map();

        foreach ($this->mapper->schema() as $key => $field) {
            $fields .= ', '.$map.'.'.$db->key($key);
        }

        foreach ($this->mapper->adhoc() as $key => $field) {
            $fields .= ', '.$field['expr'].' AS '.$db->key($key);
        }

        return ltrim($fields, ', ');
    }

    /**
     * Convert filter and options to string and args.
     *
     * @param mixed      $filter
     * @param array|null $options
     *
     * @return array
     */
    public function resolveCriteria($filter = null, array $options = null)
    {
        $default = [
            'group'  => null,
            'having' => null,
            'order'  => null,
            'join'   => null,
            'limit'  => 0,
            'offset' => 0,
        ];
        $use = ((array) $options) + $default;
        $order = '';
        $sql = rtrim(' '.$use['join']);
        $args = [];
        $sqlServer = false;
        $keys = $this->mapper->keys(false);
        $db = $this->mapper->db();

        $mFilter = $this->filter($filter);
        $criteria = array_shift($mFilter);

        if ($criteria) {
            $sql .= ' WHERE '.$criteria;
            $args = array_merge($args, $mFilter);
        }

        if ($use['group']) {
            $sql .= ' GROUP BY '.$use['group'];
        }

        $mFilter = $this->filter($use['having']);
        $criteria = array_shift($mFilter);

        if ($criteria) {
            $sql .= ' HAVING '.$criteria;
            $args = array_merge($args, $mFilter);
        }

        if ($use['order']) {
            $order = ' ORDER BY '.$use['order'];
        }

        // SQL Server fixes
        // We skip this part to test
        // @codeCoverageIgnoreStart
        $fix = in_array($db->getDriverName(), [Sql::DB_MSSQL, Sql::DB_SQLSRV, Sql::DB_ODBC]) && ($use['limit'] || $use['offset']);

        if ($fix) {
            $sqlServer = true;

            // order by pkey when no ordering option was given
            if (!$use['order'] && $keys) {
                $order = ' ORDER BY '.implode(',', array_map([$db, 'key'], $keys));
            }

            $ofs = (int) $use['offset'];
            $lmt = (int) $use['limit'];

            if (strncmp($db->getServerVersion(), '11', 2) >= 0) {
                // SQL Server >= 2012
                $sql .= $order.' OFFSET '.$ofs.' ROWS';

                if ($lmt) {
                    $sql .= ' FETCH NEXT '.$lmt.' ROWS ONLY';
                }
            } else {
                // Require primary keys or order clause
                // SQL Server 2008
                $sql = preg_replace(
                    '/SELECT/',
                    'SELECT '.
                    ($lmt > 0 ? 'TOP '.($ofs + $lmt) : '').' ROW_NUMBER() '.
                    'OVER ('.$order.') AS rnum,',
                    $sql.$order,
                    1
                );
                $sql = 'SELECT * FROM ('.$sql.') x WHERE rnum > '.$ofs;
            }
        }
        // @codeCoverageIgnoreEnd

        if (!$sqlServer) {
            $sql .= $order;

            if ($use['limit']) {
                $sql .= ' LIMIT '.(int) $use['limit'];
            }

            if ($use['offset']) {
                $sql .= ' OFFSET '.(int) $use['offset'];
            }
        }

        return [$sql, $args];
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
    public function filter($filter, array $lookup = null)
    {
        if (!$filter) {
            return [];
        }

        $ctr = 0;
        $str = '';
        $result = [];
        $mLookup = (array) $lookup;
        $map = self::$filterOperatorMap;
        $db = $this->mapper->db();

        foreach ((array) $filter as $key => $value) {
            if (is_numeric($key)) {
                if (is_string($value)) {
                    // raw
                    $str .= ' '.$value;
                } elseif ($filter !== $value && $cfilter = $this->filter((array) $value, $result + $mLookup)) {
                    $str .= ($str ? ' AND' : '').' ('.array_shift($cfilter).')';
                    $result = array_merge($result, $cfilter);
                }

                continue;
            }

            $raw = is_string($value) && '```' === substr($value, 0, 3);
            $expr = $raw ? substr($value, 3) : $value;
            // clear column from comments format
            $ccol = (false === ($pos = strpos($key, '#'))) ? $key : substr($key, 0, $pos);
            $col = trim($ccol, self::FILTER_OPERATOR_MASK);
            $kcol = str_replace('.', '_', $col);
            $a1 = substr($ccol, 0, 1);
            $a2 = substr($ccol, 0, 2);
            $a3 = substr($ccol, 0, 3);
            $b1 = substr($ccol, -1);
            $b2 = substr($ccol, -2);
            $b3 = substr($ccol, -3);

            if (isset($map[$a3])) {
                $str .= ' '.$map[$a3];
            // } elseif (isset($map[$a2])) {
            //     $str .= ' '.$map[$a2];
            } elseif (isset($map[$a1])) {
                $str .= ' '.$map[$a1];
            } else {
                $str .= $ctr ? ' AND' : ' ';
            }

            if ($col) {
                $str .= ' '.$db->key($col);

                if (isset($map[$b3])) {
                    $str .= ' '.$map[$b3];
                } elseif (isset($map[$b2])) {
                    $str .= ' '.$map[$b2];
                } elseif (isset($map[$b1])) {
                    $str .= ' '.$map[$b1];
                } else {
                    $str .= ' =';
                }
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
                        $i++;
                    }

                    $str = rtrim($str, ', ').')';
                } elseif (is_array($expr)) {
                    $cfilter = $this->filter($expr, $result + $mLookup);

                    if ($cfilter) {
                        $str .= ' ('.array_shift($cfilter).')';
                        $result = array_merge($result, $cfilter);
                    }
                } elseif ($kcol) {
                    $k = $this->ensureKey(':'.$kcol, array_keys($result + $mLookup));
                    $str .= ' '.$k;
                    $result[$k] = $expr;
                } else {
                    $str .= ' '.$expr;
                }
            }

            $ctr++;
        }

        array_unshift($result, trim($str));

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
    protected function ensureKey($key, array $keys)
    {
        if (in_array($key, $keys)) {
            $seq = 2;

            while (preg_grep('/^'.$key.'__'.$seq.'$/', $keys)) {
                $seq++;
            }

            return $key.'__'.$seq;
        }

        return $key;
    }
}

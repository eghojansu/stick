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

namespace Fal\Stick\Library\Sql;

/**
 * Mapper query builder.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class QueryBuilder
{
    const FILTER_OPERATOR_MAP = array(
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
     * @var Mapper
     */
    private $mapper;

    /**
     * Class constructor.
     *
     * @param Mapper $mapper
     */
    public function __construct(Mapper $mapper)
    {
        $this->mapper = $mapper;
    }

    /**
     * Create query builder instance.
     *
     * @param Mapper $mapper
     *
     * @return QueryBuilder
     */
    public static function create(Mapper $mapper): QueryBuilder
    {
        return new self($mapper);
    }

    /**
     * Build select query.
     *
     * @param string     $fields
     * @param mixed      $filter
     * @param array|null $options
     *
     * @return array
     */
    public function select(string $fields, $filter = null, array $options = null): array
    {
        list($criteria, $args) = $this->filterOptions($filter, $options);
        $sql = sprintf(...array(
            'SELECT %s FROM %s%s',
            $fields,
            $this->mapper->map(),
            $criteria,
        ));

        return array($sql, $args);
    }

    /**
     * Convert fields and adhoc as select column.
     *
     * @return string
     */
    public function fields(): string
    {
        $fields = '';
        $db = $this->mapper->db();
        $map = $this->mapper->map();

        foreach ($this->mapper->schema() as $key => $field) {
            $fields .= ', '.$map.'.'.$db->quotekey($key);
        }

        foreach ($this->mapper->adhoc() as $key => $field) {
            $fields .= ', '.$field['expr'].' AS '.$db->quotekey($key);
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
    public function filterOptions($filter = null, array $options = null): array
    {
        $default = array(
            'group' => null,
            'having' => null,
            'order' => null,
            'join' => null,
            'limit' => 0,
            'offset' => 0,
        );
        $use = ((array) $options) + $default;
        $db = $this->mapper->db();
        $keys = $this->mapper->keys(false);
        $driver = $db->driver();
        $order = '';
        $sql = rtrim(' '.$use['join']);
        $args = array();
        $sqlServer = false;

        $f = $this->filter($filter);
        if ($f) {
            $sql .= ' WHERE '.array_shift($f);
            $args = array_merge($args, $f);
        }

        if ($use['group']) {
            $sql .= ' GROUP BY '.$use['group'];
        }

        $f = $this->filter($use['having']);
        if ($f) {
            $sql .= ' HAVING '.array_shift($f);
            $args = array_merge($args, $f);
        }

        if ($use['order']) {
            $order = ' ORDER BY '.$use['order'];
        }

        // SQL Server fixes
        // We skip this part to test
        // @codeCoverageIgnoreStart
        if (in_array($driver, array(Connection::DB_MSSQL, Connection::DB_SQLSRV, Connection::DB_ODBC)) && ($use['limit'] || $use['offset'])) {
            $sqlServer = true;

            // order by pkey when no ordering option was given
            if (!$use['order'] && $keys) {
                $order = ' ORDER BY '.implode(',', array_map(array($db, 'quotekey'), $keys));
            }

            $ofs = (int) $use['offset'];
            $lmt = (int) $use['limit'];

            if (strncmp($db->version(), '11', 2) >= 0) {
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

        return array($sql, $args);
    }

    /**
     * Build insert query.
     *
     * @return array
     */
    public function insert(): array
    {
        $args = array();
        $ctr = 0;
        $sql = '';
        $fields = '';
        $values = '';
        $ckeys = array();
        $inc = null;
        $db = $this->mapper->db();
        $schema = $this->mapper->schema();
        $map = $this->mapper->map();
        $driver = $db->driver();

        foreach ($schema as $key => &$field) {
            if ($field['pkey'] && !$inc && \PDO::PARAM_INT == $field['pdo_type'] && empty($field['value']) && !$field['nullable']) {
                // detect auto increment key
                $inc = $key;
            }

            if ($field['changed'] && $key !== $inc) {
                $fields .= ','.$db->quotekey($key);
                $values .= ',?';
                $args[++$ctr] = array($field['value'], $field['pdo_type']);
                $ckeys[] = $key;
            }

            $field['initial'] = $field['value'];
            $field['changed'] = false;
            unset($field);
        }

        if ($fields) {
            $keys = $this->mapper->keys(false);
            $needPrefix = in_array($driver, array(Connection::DB_MSSQL, Connection::DB_DBLIB, Connection::DB_SQLSRV)) && array_intersect($keys, $ckeys);
            $needSuffix = Connection::DB_PGSQL === $driver;
            $prefix = $needPrefix ? 'SET IDENTITY_INSERT '.$map.' ON;' : '';
            $suffix = $needSuffix ? ' RETURNING '.$db->quotekey(reset($keys)) : '';
            $sql = sprintf(...array(
                '%sINSERT INTO %s (%s) VALUES (%s)%s',
                $prefix,
                $map,
                ltrim($fields, ','),
                ltrim($values, ','),
                $suffix,
            ));
        }

        return array($sql, $args, $inc, $schema);
    }

    /**
     * Build update query.
     *
     * @return array
     */
    public function update(): array
    {
        $ctr = 0;
        $sql = '';
        $pairs = '';
        $filter = '';
        $args = array();
        $keys = array();
        $db = $this->mapper->db();
        $schema = $this->mapper->schema();

        foreach ($schema as $key => &$field) {
            if ($field['changed']) {
                $pairs .= ','.$db->quotekey($key).'=?';
                $args[++$ctr] = array($field['value'], $field['pdo_type']);
            }

            if ($field['pkey']) {
                $filter .= ' AND '.$db->quotekey($key).'=?';
                $keys[] = array($field['initial'], $field['pdo_type']);
            }

            $field['initial'] = $field['value'];
            $field['changed'] = false;
            unset($field);
        }

        if ($pairs) {
            $sql = 'UPDATE '.$this->mapper->map().' SET '.ltrim($pairs, ',');

            if ($filter) {
                $sql .= ' WHERE'.substr($filter, 4);
                array_push($args, ...$keys);
            }
        }

        return array($sql, $args, $schema);
    }

    /**
     * Build delete query.
     *
     * @return array
     */
    public function delete(): array
    {
        $filter = '';
        $args = array();
        $ctr = 0;
        $db = $this->mapper->db();
        $sql = 'DELETE FROM '.$this->mapper->map();

        foreach ($this->mapper->schema() as $key => $field) {
            if ($field['pkey']) {
                $filter .= ' AND '.$db->quotekey($key).'=?';
                $args[++$ctr] = array($field['initial'], $field['pdo_type']);
            }
        }

        $sql .= ' WHERE'.substr($filter, 4);

        return array($sql, $args);
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

        // operator map
        $map = self::FILTER_OPERATOR_MAP;
        $mapkeys = '=<>&|^!~@[] ';

        $ctr = 0;
        $str = '';
        $db = $this->mapper->db();
        $result = array();
        $mLookup = (array) $lookup;
        $ensureKey = function (string $key, array $keys) {
            if (in_array($key, $keys)) {
                $seq = 2;

                while (preg_grep('/^'.$key.'__'.$seq.'$/', $keys)) {
                    ++$seq;
                }

                return $key.'__'.$seq;
            }

            return $key;
        };

        foreach ((array) $filter as $key => $value) {
            if (is_numeric($key)) {
                if (is_string($value)) {
                    // raw
                    $str .= ' '.$value;
                } elseif ($cfilter = $this->filter((array) $value, $result + $mLookup)) {
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

            $str .= ' '.($map[$a3] ?? $map[$a2] ?? $map[$a1] ?? ($ctr ? 'AND' : ''));

            if ($col) {
                $str .= ' '.$db->quotekey($col);
                $str .= ' '.($map[$b3] ?? $map[$b2] ?? $map[$b1] ?? '=');
            }

            if ($raw) {
                $str .= ' '.$expr;
            } else {
                if ('!><' === $b3 || '><' === $b2) {
                    if (!is_array($expr)) {
                        throw new \LogicException('BETWEEN operator needs an array operand, '.gettype($expr).' given.');
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
                    $cfilter = $this->filter($expr, $result + $mLookup);

                    if ($cfilter) {
                        $str .= ' ('.array_shift($cfilter).')';
                        $result = array_merge($result, $cfilter);
                    }
                } elseif ($kcol) {
                    $k = $ensureKey(':'.$kcol, array_keys($result + $mLookup));
                    $str .= ' '.$k;
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
}

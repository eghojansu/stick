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

namespace Ekok\Stick\Sql\QueryBuilder;

/**
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class MsSqlQueryBuilder extends AbstractQueryBuilder
{
    protected $options = array(
        'host' => 'localhost',
        'port' => 1433,
        'dbname' => null,
        'username' => 'sa',
        'password' => null,
        'options' => null,
        'commands' => null,
        'dsn_suffix' => null,
    );
    protected $qChar = '""';

    public function getDsn(): string
    {
        list('host' => $host, 'port' => $port, 'dbname' => $name, 'dsn_suffix' => $suffix) = $this->options;

        if ($port) {
            $host .= ','.$port;
        }

        return "sqlsrv:Server={$host};Database={$name}".$suffix;
    }

    protected function buildSelect($columns, array $options = null): string
    {
        $result = '';
        $addTop = empty($options['order']);
        $limit = $options['limit'] ?? 0;
        $offset = $options['offset'] ?? 0;

        if ($addTop && $limit > 0) {
            if ($offset > 0) {
                throw new \LogicException('Unable to perform limit-offset without order clause.');
            }

            $result = "top {$limit} ";
        }

        if (is_string($columns)) {
            return $result.$columns;
        }

        foreach ((array) $columns as $label => $key) {
            if (is_numeric($label)) {
                $label = $key;
            }

            $result .= $this->quote($key).' '.$this->quote($label).', ';
        }

        return rtrim($result, ', ');
    }

    protected function buildLimit(int $limit, int $offset): string
    {
        if ($limit > 0 && $offset > 0) {
            return "offset {$offset} rows fetch next {$limit} rows only";
        }

        return '';
    }
}

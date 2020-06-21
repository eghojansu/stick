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

namespace Ekok\Stick\Database\QueryBuilder;

use Ekok\Stick\Database\QueryBuilderInterface;

/**
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
abstract class AbstractQueryBuilder implements QueryBuilderInterface
{
    protected $options = array(
        'username' => 'root',
        'password' => null,
        'options' => null,
        'commands' => null,
    );
    protected $qChar = '``';

    public function __construct(array $options = null)
    {
        if ($options) {
            $this->options = array_merge($this->options, $options);
        }
    }

    abstract public function getDsn(): string;

    public function getUser(): string
    {
        return $this->options['username'];
    }

    public function getPassword(): ?string
    {
        return $this->options['password'];
    }

    public function getOptions(): ?array
    {
        return $this->options['options'];
    }

    public function getCommands(): ?array
    {
        return $this->options['commands'];
    }

    public function supportTransaction(): bool
    {
        return true;
    }

    public function quote(string $key): string
    {
        $q1 = $this->qChar[0];
        $q2 = $this->qChar[1];

        return $q1.str_replace('.', $q2.'.'.$q1, trim($key)).$q2;
    }

    public function select(string $table, $filter = null, array $options = null): array
    {
        return $this->stringify($table, $filter, $options);
    }

    public function count(string $table, $filter = null, array $options = null): array
    {
        list($source, $params) = $this->stringify($table, $filter, $options);
        $command = "select count(*) _count from ({$source}) _source";

        return array($command, $params);
    }

    public function insert(string $table, array $data): array
    {
        $keys = implode(', ', array_map(array($this, 'quote'), array_keys($data)));
        $placeholder = str_repeat('?, ', count($data) - 1);
        $params = array_values($data);
        $command = "insert into {$this->quote($table)} ({$keys}) values ({$placeholder}?)";

        return array($command, $params);
    }

    public function insertBatch(string $table, array $data): array
    {
        if (!is_array($first = reset($data))) {
            throw new \LogicException('Data structure should be array of array.');
        }

        $count = count($first);
        $keys = implode(', ', array_map(array($this, 'quote'), array_keys($first)));
        $placeholder = '('.str_repeat('?, ', $count - 1).'?)';
        $placeholders = str_repeat($placeholder.', ', count($data) - 1);
        $params = array();
        $command = "insert into {$this->quote($table)} ({$keys}) values {$placeholders}{$placeholder}";

        foreach ($data as $key => $item) {
            if (count($item) !== $count) {
                throw new \LogicException("Invalid data count at row {$key}.");
            }

            array_push($params, ...array_values($item));
        }

        return array($command, $params);
    }

    public function update(string $table, array $data, $filter = null): array
    {
        $params = array();
        $clause = $this->buildClause('where', $filter, $params);

        if (!$params || ctype_digit(implode('', array_keys($params)))) {
            $set = implode(' = ?, ', array_map(array($this, 'quote'), array_keys($data))).' = ?';

            array_unshift($params, ...array_values($data));
        } else {
            $set = $this->buildUpdateSet($data, $params);
        }

        $command = "update {$this->quote($table)} set {$set} {$clause}";

        return array($command, $params);
    }

    public function delete(string $table, $filter = null): array
    {
        $params = array();
        $command = "delete from {$this->quote($table)} {$this->buildClause('where', $filter, $params)}";

        return array($command, $params);
    }

    public function stringify(string $source, $filter = null, array $options = null): array
    {
        $params = $options['params'] ?? array();
        $parts = array_filter(array(
            'select',
            $this->buildSelect($options['select'] ?? '*', $options),
            'from',
            false === strpos('(', $source) ? $this->quote($source) : $source,
            $options['alias'] ?? null,
            $options['join'] ?? null,
            $this->buildClause('where', $filter, $params),
            $this->buildOrder('group', $options['group'] ?? null),
            $this->buildClause('having', $options['having'] ?? null, $params),
            $this->buildOrder('order', $options['order'] ?? null),
            $this->buildLimit($options['limit'] ?? 0, $options['offset'] ?? 0),
        ));

        return array(implode(' ', $parts), $params);
    }

    protected function buildSelect($columns, array $options = null): string
    {
        if (is_string($columns)) {
            return $columns;
        }

        $result = '';

        foreach ((array) $columns as $label => $key) {
            if (is_numeric($label)) {
                $label = $key;
            }

            $result .= $this->quote($key).' '.$this->quote($label).', ';
        }

        return rtrim($result, ', ');
    }

    protected function buildClause(string $prefix, $filter, array &$params): ?string
    {
        if ($filter) {
            $filter = (array) $filter;
            $clause = trim(array_shift($filter));
            $params = array_merge($params, $filter);

            return "{$prefix} {$clause}";
        }

        return null;
    }

    protected function buildOrder(string $prefix, $orders): ?string
    {
        if ($orders) {
            if (is_string($orders)) {
                return "{$prefix} by {$orders}";
            }

            $result = "{$prefix} by ";

            foreach ($orders as $field => $direction) {
                if (is_numeric($field)) {
                    $field = $direction;
                    $direction = null;
                }

                $result .= $this->quote($field).rtrim(' '.$direction).', ';
            }

            return rtrim($result, ', ');
        }

        return null;
    }

    protected function buildLimit(int $limit, int $offset): string
    {
        $result = '';

        if ($limit > 0) {
            $result .= "limit {$limit}";
        }

        if ($offset > 0) {
            $result .= " offset {$offset}";
        }

        return trim($result);
    }

    protected function buildUpdateSet(array $data, array &$params): string
    {
        $result = null;

        foreach ($data as $key => $value) {
            $paramKey = ':_set_'.$key;
            $params[$paramKey] = $value;

            $result .= $this->quote($key).' = '.$paramKey.', ';
        }

        return rtrim($result, ', ');
    }
}

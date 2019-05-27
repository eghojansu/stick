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

use Fal\Stick\Magic;

/**
 * Table schema.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Schema extends Magic implements \Countable, \IteratorAggregate
{
    /** @var array Row blueprint */
    const BLUEPRINT = array(
        'constraint' => null,
        'data_type' => null,
        'default' => null,
        'name' => null,
        'nullable' => true,
        'pdo_type' => \PDO::PARAM_STR,
        'pkey' => false,
        'type' => 'string',
    );

    /**
     * {inheritdoc}.
     */
    public function &get(string $field, $default = null)
    {
        if (isset($this->hive[$field])) {
            return $this->hive[$field];
        }

        throw new \LogicException(sprintf('Field not exists: %s.', $field));
    }

    /**
     * {inheritdoc}.
     */
    public function set(string $field, $schema): Magic
    {
        if (!is_array($schema)) {
            $schema = array('default' => $schema);
        }

        $this->hive[$field] = self::BLUEPRINT;

        foreach (array_intersect_key($schema, self::BLUEPRINT) as $key => $val) {
            $this->hive[$field][$key] = $val ?? self::BLUEPRINT[$key];
        }

        $this->hive[$field]['name'] = $field;

        return $this;
    }

    /**
     * {inheritdoc}.
     */
    public function count()
    {
        return count($this->hive);
    }

    /**
     * {inheritdoc}.
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->hive);
    }

    /**
     * Returns schema.
     *
     * @return array
     */
    public function getSchema(): array
    {
        return $this->hive;
    }

    /**
     * Returns schema field names.
     *
     * @return array
     */
    public function getFields(): array
    {
        return array_keys($this->hive);
    }

    /**
     * Returns keys.
     *
     * @return array
     */
    public function getKeys(): array
    {
        return array_keys(array_filter(array_column($this->hive, 'pkey', 'name')));
    }

    /**
     * Add field schema.
     *
     * @param string      $field
     * @param mixed       $default
     * @param bool|null   $nullable
     * @param bool|null   $pkey
     * @param string|null $type
     * @param int|null    $pdo_type
     *
     * @return Schema
     */
    public function add(
        string $field,
        $default = null,
        bool $nullable = null,
        bool $pkey = null,
        string $type = null,
        int $pdo_type = null
    ): Schema {
        $this->set($field, compact('default', 'nullable', 'pkey', 'type', 'pdo_type'));

        return $this;
    }
}

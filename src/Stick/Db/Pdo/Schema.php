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
    const BLUEPRINT = array(
        'default' => null,
        'nullable' => true,
        'pkey' => false,
        'type' => 'string',
        'pdo_type' => \PDO::PARAM_STR,
    );

    /**
     * @var array
     */
    private $keys;

    /**
     * Returns schema count.
     *
     * @return int
     */
    public function count()
    {
        return count($this->hive);
    }

    /**
     * Returns array iterator.
     *
     * @return ArrayIterator
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
     * Returns fields.
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
        if (null === $this->keys) {
            $this->keys = array();

            foreach ($this->hive as $key => $value) {
                if ($value['pkey']) {
                    $this->keys[] = $key;
                }
            }
        }

        return $this->keys;
    }

    /**
     * Returns field schema.
     *
     * @param string $field
     * @param mixed  $default
     *
     * @return array|null
     */
    public function &get(string $field, $default = null)
    {
        if ($this->has($field)) {
            return $this->hive[$field];
        }

        throw new \LogicException(sprintf('Field not exists: %s.', $field));
    }

    /**
     * Add field schema.
     *
     * @param string $field
     * @param mixed  $schema
     *
     * @return Magic
     */
    public function set(string $field, $schema): Magic
    {
        if (!is_array($schema)) {
            $schema = array('default' => $schema);
        }

        $this->keys = null;
        $this->hive[$field] = array();

        foreach (self::BLUEPRINT as $key => $value) {
            $this->hive[$field][$key] = $schema[$key] ?? $value;
        }

        return $this;
    }

    /**
     * {inheritdoc}.
     */
    public function rem(string $field): Magic
    {
        unset($this->hive[$field]);
        $this->keys = null;

        return $this;
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
    public function add(string $field, $default = null, bool $nullable = null, bool $pkey = null, string $type = null, int $pdo_type = null): Schema
    {
        return $this->set($field, compact('default', 'nullable', 'pkey', 'type', 'pdo_type'));
    }
}

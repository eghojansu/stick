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
use Fal\Stick\Magic;

/**
 * Table schema.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Schema extends Magic implements \Countable, \IteratorAggregate
{
    /** @var array Schema blueprint */
    const BLUEPRINT = array(
        'constraint' => null,
        'data_type' => null,
        'default' => null,
        'nullable' => true,
        'pdo_type' => \PDO::PARAM_STR,
        'pkey' => false,
        'type' => 'string',
    );

    /** @var array Schema fields */
    protected $fields = array();

    /**
     * {inheritdoc}.
     */
    public function has(string $field): bool
    {
        return isset($this->fields[$field]);
    }

    /**
     * {inheritdoc}.
     */
    public function &get(string $field)
    {
        if (isset($this->fields[$field])) {
            return $this->fields[$field];
        }

        throw new \LogicException(sprintf('Field not exists: %s.', $field));
    }

    /**
     * {inheritdoc}.
     */
    public function set(string $field, $schema): Magic
    {
        $this->fields[$field] = self::BLUEPRINT;

        if (is_array($schema)) {
            foreach (array_intersect_key($schema, self::BLUEPRINT) as $key => $val) {
                $this->fields[$field][$key] = $val ?? self::BLUEPRINT[$key];
            }
        } else {
            $this->fields[$field]['default'] = $schema;
        }

        return $this;
    }

    /**
     * {inheritdoc}.
     */
    public function rem(string $field): Magic
    {
        unset($this->fields[$field]);

        return $this;
    }

    /**
     * {inheritdoc}.
     */
    public function count()
    {
        return count($this->fields);
    }

    /**
     * {inheritdoc}.
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->fields);
    }

    /**
     * Returns field names or schema.
     *
     * @param bool $schema
     *
     * @return array
     */
    public function getFields(bool $schema = false): array
    {
        return $schema ? $this->fields : array_keys($this->fields);
    }

    /**
     * Returns keys.
     *
     * @return array
     */
    public function getKeys(): array
    {
        return array_keys(Fw::arrColumn($this->fields, 'pkey', false));
    }
}

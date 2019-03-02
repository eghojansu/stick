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

namespace Fal\Stick\Database;

/**
 * Row/schema.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Row implements \ArrayAccess, \IteratorAggregate
{
    /**
     * @var string
     */
    public $alias;

    /**
     * @var bool
     */
    public $loaded = false;

    /**
     * @var table
     */
    protected $table;

    /**
     * @var Field[]
     */
    protected $fields = array();

    /**
     * @var Adhoc[]
     */
    protected $adhocs = array();

    /**
     * Class constructor.
     *
     * @param string $table
     */
    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * Clone fields and adhoc.
     */
    public function __clone()
    {
        if ($this->fields) {
            $this->fields = array_map(function ($field) {
                return clone $field;
            }, $this->fields);
        }

        if ($this->adhocs) {
            $this->adhocs = array_map(function ($adhoc) {
                return clone $adhoc;
            }, $this->adhocs);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($name)
    {
        return $this->exists($name);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($name)
    {
        return $this->get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($name)
    {
        $this->clear($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->fields + $this->adhocs);
    }

    /**
     * Returns table name.
     *
     * @return string
     */
    public function table(): string
    {
        return $this->table;
    }

    /**
     * Returns field.
     *
     * @param string $name
     *
     * @return Field
     */
    public function getField(string $name): Field
    {
        $field = $this->fields[$name] ?? $this->adhocs[$name] ?? null;

        if (!$field) {
            throw new \LogicException(sprintf('Field not exists: %s.', $name));
        }

        return $field;
    }

    /**
     * Add field.
     *
     * @param Field $field
     *
     * @return Row
     */
    public function setField(Field $field): Row
    {
        if ($field instanceof Adhoc) {
            $this->adhocs[$field->name] = $field;
        } else {
            $this->fields[$field->name] = $field;
        }

        return $this;
    }

    /**
     * Returns true if field exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function exists(string $name): bool
    {
        return isset($this->fields[$name]) || isset($this->adhocs[$name]);
    }

    /**
     * Returns field value.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function get(string $name)
    {
        return $this->getField($name)->getValue();
    }

    /**
     * Assign field value.
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return Row
     */
    public function set(string $name, $value): Row
    {
        if (isset($this->fields[$name])) {
            $this->fields[$name]->setValue($value);
        } elseif (isset($this->adhocs[$name])) {
            $this->adhocs[$name]->setValue($value);
        } else {
            $this->adhocs[$name] = new Adhoc($name, $value);
        }

        return $this;
    }

    /**
     * Clear field.
     *
     * @param string $name
     *
     * @return Row
     */
    public function clear(string $name): Row
    {
        if (isset($this->fields[$name])) {
            $this->fields[$name]->reset();
        } else {
            $this->adhocs[$name]->reset();
        }

        return $this;
    }

    /**
     * Remove field.
     *
     * @param string $name
     *
     * @return Row
     */
    public function remove(string $name): Row
    {
        unset($this->fields[$name], $this->adhocs[$name]);

        return $this;
    }

    /**
     * Returns fields.
     *
     * @return Field[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Returns adhocs.
     *
     * @return Adhoc[]
     */
    public function getAdhocs(): array
    {
        return $this->adhocs;
    }

    /**
     * Returns true if one of field changed.
     *
     * @return bool
     */
    public function isChanged(): bool
    {
        foreach ($this->fields as $field) {
            if ($field->isChanged()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns keys.
     *
     * @return string[]
     */
    public function getKeys(): array
    {
        $keys = array();

        foreach ($this->fields as $field) {
            if ($field->pkey) {
                $keys[] = $field->name;
            }
        }

        return $keys;
    }

    /**
     * Assign data from array.
     *
     * @param array $data
     *
     * @return Row
     */
    public function fromArray(array $data): Row
    {
        foreach ($this->fields + $this->adhocs as $field) {
            if (array_key_exists($field->name, $data)) {
                $field->setValue($data[$field->name]);
            }
        }

        return $this;
    }

    /**
     * Returns array of data.
     *
     * @return array
     */
    public function toArray(): array
    {
        $data = array();

        foreach ($this->fields + $this->adhocs as $field) {
            $data[$field->name] = $field->getValue();
        }

        return $data;
    }

    /**
     * Commit each field if any of them changed.
     *
     * @return Row
     */
    public function commit(): Row
    {
        if ($this->isChanged()) {
            foreach ($this->fields + $this->adhocs as $field) {
                $field->commit();
            }
        }

        return $this;
    }

    /**
     * Reset each field.
     *
     * @return Row
     */
    public function reset(): Row
    {
        foreach ($this->fields + $this->adhocs as $field) {
            $field->reset();
        }

        $this->loaded = false;

        return $this;
    }
}

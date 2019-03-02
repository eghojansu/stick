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
 * Field class.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Field
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var bool
     */
    public $nullable = true;

    /**
     * @var bool
     */
    public $pkey = false;

    /**
     * @var array
     */
    public $extras = array();

    /**
     * @var bool
     */
    protected $changed = false;

    /**
     * @var mixed
     */
    protected $initial;

    /**
     * @var mixed
     */
    protected $default;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * Class constructor.
     *
     * @param string $name
     * @param mixed  $value
     * @param mixed  $default
     */
    public function __construct(string $name, $value, $default = null)
    {
        $this->name = $name;
        $this->default = $default;
        $this->initial = $value ?? $default;

        $this->reset();
    }

    /**
     * Returns extras value.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function __get($key)
    {
        return $this->extras[$key] ?? null;
    }

    /**
     * Returns true if field is changed.
     *
     * @return bool
     */
    public function isChanged(): bool
    {
        return $this->changed;
    }

    /**
     * Returns initial value.
     *
     * @return mixed
     */
    public function getInitial()
    {
        return $this->initial;
    }

    /**
     * Returns default value.
     *
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Returns value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Assign value.
     *
     * @param mixed $value
     *
     * @return Field
     */
    public function setValue($value): Field
    {
        $this->value = $value;
        $this->changed = $value != $this->initial;

        return $this;
    }

    /**
     * Copy value to initial.
     *
     * @return Field
     */
    public function commit(): Field
    {
        $this->initial = is_object($this->value) ? clone $this->value : $this->value;
        $this->changed = false;

        return $this;
    }

    /**
     * Copy initial to value.
     *
     * @return Field
     */
    public function reset(): Field
    {
        $this->value = is_object($this->initial) ? clone $this->initial : $this->initial;
        $this->changed = false;

        return $this;
    }
}

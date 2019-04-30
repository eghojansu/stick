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

namespace Fal\Stick\Validation;

/**
 * Validation context.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Context
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var string
     */
    private $field;

    /**
     * @var array
     */
    private $arguments = array();

    /**
     * @var array
     */
    private $validated = array();

    /**
     * Class constructor.
     *
     * @param array|null $data
     */
    public function __construct(array $data = null)
    {
        $this->data = (array) $data;
    }

    /**
     * Returns current field.
     *
     * @return string|null
     */
    public function getField(): ?string
    {
        return $this->field;
    }

    /**
     * Sets current field.
     *
     * @param string $field
     *
     * @return Context
     */
    public function setField(string $field): Context
    {
        $this->field = $field;

        return $this;
    }

    /**
     * Returns current field arguments.
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Sets current field arguments.
     *
     * @param array $arguments
     *
     * @return Context
     */
    public function setArguments(array $arguments): Context
    {
        $this->arguments = $arguments;

        return $this;
    }

    /**
     * Returns current field value.
     *
     * @return mixed
     */
    public function getValue()
    {
        if (array_key_exists($this->field, $this->validated)) {
            return $this->validated[$this->field];
        }

        return array_key_exists($this->field, $this->data) ? $this->data[$this->field] : null;
    }

    /**
     * Returns context raw data.
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Returns context validated data.
     *
     * @return array
     */
    public function getValidated(): array
    {
        return $this->validated;
    }

    /**
     * Sets validated data.
     *
     * @param array $validated
     *
     * @return Context
     */
    public function setValidated(array $validated): Context
    {
        $this->validated = $validated;

        return $this;
    }

    /**
     * Add validated value.
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return Context
     */
    public function addValidated(string $field, $value): Context
    {
        $this->validated[$field] = $value;

        return $this;
    }
}

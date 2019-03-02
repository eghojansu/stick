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
 * Validation result data.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Result
{
    /**
     * @var array
     */
    public $raw;

    /**
     * @var array
     */
    public $data = array();

    /**
     * @var array
     */
    public $errors = array();

    /**
     * Current field.
     *
     * @var string
     */
    public $field;

    /**
     * Current rule.
     *
     * @var string
     */
    public $rule;

    /**
     * Class constructor.
     *
     * @param array $raw
     */
    public function __construct(array $raw)
    {
        $this->raw = $raw;
    }

    /**
     * Set current rule and rule.
     *
     * @param string $rule
     * @param string $field
     *
     * @return Result
     */
    public function setCurrent(string $rule, string $field): Result
    {
        $this->rule = $rule;
        $this->field = $field;

        return $this;
    }

    /**
     * Returns true if current result valid.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Returns raw field value.
     *
     * @param string $field
     *
     * @return mixed
     */
    public function raw(string $field)
    {
        return array_key_exists($field, $this->raw) ? $this->raw[$field] : null;
    }

    /**
     * Returns validated field value.
     *
     * @param string $field
     *
     * @return mixed
     */
    public function data(string $field)
    {
        return array_key_exists($field, $this->data) ? $this->data[$field] : null;
    }

    /**
     * Returns current field value.
     *
     * @return mixed
     */
    public function value()
    {
        if (!$this->field) {
            return null;
        }

        return array_key_exists($this->field, $this->data) ? $this->data[$this->field] : $this->raw($this->field);
    }

    /**
     * Returns true if result has field error.
     *
     * @param string $field
     *
     * @return bool
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]) && $this->errors[$field];
    }

    /**
     * Add data.
     *
     * @param string $field
     * @param mixed  $value
     *
     * @return Result
     */
    public function addData(string $field, $value): Result
    {
        $this->data[$field] = $value;

        return $this;
    }

    /**
     * Add field error.
     *
     * @param string $field
     * @param string $message
     *
     * @return Result
     */
    public function addError(string $field, string $message): Result
    {
        $this->errors[$field][] = $message;

        return $this;
    }
}

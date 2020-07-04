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

namespace Ekok\Stick\Validation;

use Ekok\Stick\Fw;

/**
 * Validation result.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Result implements \ArrayAccess
{
    /** @var null|string Current field name */
    protected $field;

    /** @var array Raw data */
    protected $raw;

    /** @var bool Indicate current ruleset to be skipped on next rule call */
    protected $skip = false;

    /** @var bool No error has been added since last rule execution */
    protected $noErrorAdded = true;

    /** @var array Validated data */
    protected $data = array();

    /** @var array Error list */
    protected $errors = array();

    public function __construct(array $raw)
    {
        $this->raw = $raw;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($key)
    {
        $data = $this->data;

        return Fw::makeRef($key, $data, false, $exists) || $exists;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($key)
    {
        $data = $this->data;

        return Fw::makeRef($key, $data, false, $exists);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($key, $value)
    {
        throw new \LogicException('Array access is read only.');
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($key)
    {
        throw new \LogicException('Array access is read only.');
    }

    public function hasField(string $field): bool
    {
        return isset($this->raw[$field]) || array_key_exists($field, $this->raw);
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function setField(string $field): Result
    {
        $this->field = $field;

        return $this;
    }

    public function raw(): array
    {
        return $this->raw;
    }

    public function data(): array
    {
        return $this->data;
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function error(string $field = null): ?array
    {
        $this->fieldCheck(!$field);

        return $this->errors[$field ?? $this->field] ?? null;
    }

    public function errorAdd(string $error, string $field = null): Result
    {
        $this->fieldCheck(!$field);

        $this->errors[$field ?? $this->field][] = $error;
        $this->noErrorAdded = false;

        return $this;
    }

    public function noErrorAdded(): bool
    {
        return $this->noErrorAdded;
    }

    public function success(): bool
    {
        return !$this->errors;
    }

    public function failed(): bool
    {
        return (bool) $this->errors;
    }

    /**
     * Reset current result state.
     */
    public function newRule(): Result
    {
        $this->skip = false;
        $this->noErrorAdded = true;

        return $this;
    }

    public function isSkip(): bool
    {
        return $this->skip;
    }

    public function skip(): Result
    {
        $this->skip = true;

        return $this;
    }

    public function getValue(string $field = null)
    {
        $this->fieldCheck(!$field);

        $key = $field ?? $this->field;

        $data = $this->data;
        $value = Fw::makeRef($key, $data, false, $exists);

        if (!$exists) {
            $raw = $this->raw;
            $value = Fw::makeRef($key, $raw, false);
        }

        return $value;
    }

    public function setValue($value, string $field = null): Result
    {
        $this->fieldCheck(!$field);

        $ref = &Fw::makeRef($field ?? $this->field, $this->data);
        $ref = $value;

        return $this;
    }

    public function getValueAsTime(string $field = null): int
    {
        if ($field && $this->hasField($field)) {
            return (int) strtotime($this->getValue($field));
        }

        if ($field) {
            return (int) strtotime($field);
        }

        return (int) strtotime($this->getValue());
    }

    public function getValueSize(bool $numeric = true, string $field = null)
    {
        $value = $this->getValue($field);

        if ($numeric && is_numeric($value)) {
            return 0 + $value;
        }

        if (is_array($value)) {
            return count($value);
        }

        return strlen((string) $value);
    }

    /**
     * Returns value after needle.
     */
    public function getValueAfter(string $needle, string $field = null): string
    {
        $value = $this->getValue($field);
        $pos = strripos($value, $needle);

        return is_int($pos) ? substr($value, $pos + 1) : $value;
    }

    /**
     * Returns value before needle.
     */
    public function getValueBefore(string $needle, string $field = null): string
    {
        $value = $this->getValue($field);
        $pos = strripos($value, $needle);

        return is_int($pos) ? substr($value, 0, $pos) : $value;
    }

    /**
     * Returns true if current value equals to field value.
     */
    public function isValueEqualTo(string $field): bool
    {
        return $this->getValue() == $this->getValue($field);
    }

    /**
     * Returns true if field empty.
     */
    public function isValueEmpty(string $field = null): bool
    {
        return in_array($this->getValue($field), array(null, '', array()), true);
    }

    /**
     * Proxy to filter_var.
     *
     * @param mixed $filters
     */
    public function filterValue(...$filters): bool
    {
        return false !== filter_var($this->getValue(), ...$filters);
    }

    /**
     * Check if field exists.
     *
     * @throws LogicException if no field has been set
     */
    protected function fieldCheck(bool $doCheck = true): void
    {
        if ($doCheck && !$this->field) {
            throw new \LogicException('No field pointed.');
        }
    }
}

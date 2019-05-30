<?php

/**
 * This file is part of the eghojansu/stick.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick\Validation;

use Fal\Stick\Fw;

/**
 * Field rules.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class Field
{
    /** @var array */
    public $fw;

    /** @var array */
    private $validated;

    /** @var array */
    private $data;

    /** @var string */
    private $field;

    /** @var mixed */
    private $value;

    /** @var array */
    private $rules;

    /**
     * Returns parsed string expression.
     *
     * Example:
     *
     *     foo:arg,arg2|bar:arg|baz:["array arg"]|qux:{"arg":"foo"}
     *
     * @param string $expr
     *
     * @return array
     */
    public static function parse(string $expr): array
    {
        $len = strlen($expr);
        $res = array();
        $tmp = '';
        $process = false;
        $args = array();
        $quote = null;
        $astate = 0;
        $jstate = 0;

        for ($ptr = 0; $ptr < $len; ++$ptr) {
            $char = $expr[$ptr];
            $prev = $expr[$ptr - 1] ?? null;

            if (('"' === $char || "'" === $char) && '\\' !== $prev) {
                if ($quote) {
                    $quote = $quote === $char ? null : $quote;
                } else {
                    $quote = $char;
                }
                $tmp .= $char;
            } elseif (!$quote) {
                if (':' === $char && 0 === $jstate) {
                    // next chars is arg
                    $args[] = Fw::cast($tmp);
                    $tmp = '';
                } elseif (',' === $char && 0 === $astate && 0 === $jstate) {
                    if ($tmp) {
                        $args[] = Fw::cast($tmp);
                        $tmp = '';
                    }
                } elseif ('|' === $char) {
                    $process = true;
                    if ($tmp) {
                        $args[] = Fw::cast($tmp);
                        $tmp = '';
                    }
                } elseif ('[' === $char) {
                    $astate = 1;
                    $tmp .= $char;
                } elseif (']' === $char && 1 === $astate && 0 === $jstate) {
                    $astate = 0;
                    $args[] = json_decode($tmp.$char, true);
                    $tmp = '';
                } elseif ('{' === $char) {
                    $jstate = 1;
                    $tmp .= $char;
                } elseif ('}' === $char && 1 === $jstate && 0 === $astate) {
                    $jstate = 0;
                    $args[] = json_decode($tmp.$char, true);
                    $tmp = '';
                } else {
                    $tmp .= $char;
                    $astate += '[' === $char ? 1 : (']' === $char ? -1 : 0);
                    $jstate += '{' === $char ? 1 : ('}' === $char ? -1 : 0);
                }
            } else {
                $tmp .= $char;
            }

            if (!$process && $ptr === $len - 1) {
                $process = true;

                if ('' !== $tmp) {
                    $args[] = Fw::cast($tmp);
                    $tmp = '';
                }
            }

            if ($process) {
                $process = false;

                if ($args) {
                    $res[array_shift($args)] = $args;
                    $args = array();
                }
            }
        }

        return $res;
    }

    /**
     * Class constructor.
     *
     * @param Fw     $fw
     * @param array  $validated
     * @param array  $data
     * @param string $field
     * @param string $rules
     */
    public function __construct(
        Fw $fw,
        array $validated,
        array $data,
        string $field,
        string $rules
    ) {
        $this->fw = $fw;
        $this->validated = $validated;
        $this->data = $data;
        $this->field = $field;
        $this->value = $this->fieldValue($field);
        $this->rules = self::parse($rules);
    }

    /**
     * Proxy to native method.
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if (0 === strncasecmp($method, 'is', 2)) {
            return ('is_'.substr($method, 2))($this->value);
        }

        throw new \BadMethodCallException(sprintf(
            'Call to undefined method Fal\\Stick\\Validation\\Field::%s.',
            $method
        ));
    }

    /**
     * Returns true if rule exists.
     *
     * @param string $rule
     *
     * @return bool
     */
    public function hasRule(string $rule): bool
    {
        return isset($this->rules[$rule]);
    }

    /**
     * Returns field rules.
     *
     * @return array
     */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * Returns current value.
     *
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * Returns true if field empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return in_array($this->value, array(null, '', array()), true);
    }

    /**
     * Returns value after needle.
     *
     * @param string $needle
     *
     * @return string
     */
    public function after(string $needle): string
    {
        return is_int($pos = strripos($this->value, $needle)) ?
            substr($this->value, $pos + 1) : $this->value;
    }

    /**
     * Returns value before needle.
     *
     * @param string $needle
     *
     * @return string
     */
    public function before(string $needle): string
    {
        return is_int($pos = strripos($this->value, $needle)) ?
            substr($this->value, 0, $pos) : $this->value;
    }

    /**
     * Returns current field.
     *
     * @return string
     */
    public function field(): string
    {
        return $this->field;
    }

    /**
     * Returns true if field exists.
     *
     * @return bool
     */
    public function exists(): bool
    {
        $this->fieldValue($this->field, null, $exists);

        return $exists;
    }

    /**
     * Update field value.
     *
     * @param mixed $value
     *
     * @return Field
     */
    public function update($value): Field
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Returns field value.
     *
     * @param string|null $field
     * @param mixed       $default
     * @param bool        &$exists
     *
     * @return mixed
     */
    public function fieldValue(string $field, $default = null, bool &$exists = null)
    {
        $validated = $this->validated;
        $value = $this->fw->ref($field, false, $exists, $validated);

        if ($exists) {
            return $value;
        }

        $data = $this->data;
        $value = $this->fw->ref($field, false, $exists, $data);

        if ($exists) {
            return $value;
        }

        return $default;
    }

    /**
     * Returns true if current value equals to field value.
     *
     * @param string $field
     *
     * @return bool
     */
    public function equalsTo(string $field): bool
    {
        return $this->value == $this->fieldValue($field);
    }

    /**
     * Returns timestamp.
     *
     * @param string|null $field
     *
     * @return false|int
     */
    public function time(string $field = null)
    {
        $value = $field ? $this->fieldValue($field, $field) : $this->value;

        return is_string($value) ? strtotime($value) : false;
    }

    /**
     * Returns true if value match pattern.
     *
     * @param string $pattern
     *
     * @return bool
     */
    public function match(string $pattern): bool
    {
        return is_string($this->value) && preg_match($pattern, $this->value);
    }

    /**
     * Proxy to filter_var.
     *
     * @param mixed $filters
     *
     * @return bool
     */
    public function filter(...$filters): bool
    {
        return false !== filter_var($this->value, ...$filters);
    }

    /**
     * Retrieve file.
     *
     * @param array|null  &$file
     * @param string|null $field
     *
     * @return bool
     */
    public function file(array &$file = null, string $field = null): bool
    {
        $key = 'FILES.'.($field ?? $this->field);
        $file = $this->fw->ref($key, false, $found);

        return $found && UPLOAD_ERR_OK === $file['error'];
    }

    /**
     * Value size.
     *
     * @param bool $numeric
     * @param bool $file
     *
     * @return mixed
     */
    public function getSize(bool $numeric = true, bool $file = true)
    {
        if ($numeric && is_numeric($this->value)) {
            return 0 + $this->value;
        }

        if (is_array($this->value)) {
            return count($this->value);
        }

        if ($file && $this->file($size)) {
            return $size['size'] / 1024;
        }

        return strlen(strval($this->value));
    }

    /**
     * Returns expected service.
     *
     * @param string $key
     * @param string $expected
     *
     * @return mixed
     */
    public function getService(string $key, string $expected)
    {
        $service = $this->fw->get($key);

        if (!$service instanceof $expected) {
            throw new \LogicException(sprintf(
                'Instance of %s expected, given %s (key: %s).',
                $expected,
                is_object($service) ? get_class($service) : gettype($service),
                $key
            ));
        }

        return $service;
    }
}

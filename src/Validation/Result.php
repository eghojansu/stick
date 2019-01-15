<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 14, 2019 06:46
 */

namespace Fal\Stick\Validation;

class Result
{
    public $raw;
    public $data = array();
    public $errors = array();
    public $field;
    public $rule;

    public function __construct(array $raw)
    {
        $this->raw = $raw;
    }

    public function setCurrent($rule, $field)
    {
        $this->rule = $rule;
        $this->field = $field;

        return $this;
    }

    public function isValid()
    {
        return empty($this->errors);
    }

    public function raw($field)
    {
        return array_key_exists($field, $this->raw) ? $this->raw[$field] : null;
    }

    public function data($field)
    {
        return array_key_exists($field, $this->data) ? $this->data[$field] : null;
    }

    public function value()
    {
        return array_key_exists($this->field, $this->data) ? $this->data[$this->field] : $this->raw($this->field);
    }

    public function addData($field, $value)
    {
        $this->data[$field] = $value;

        return $this;
    }

    public function addError($field, $message)
    {
        $this->errors[$field][] = $message;

        return $this;
    }
}

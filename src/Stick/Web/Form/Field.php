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

namespace Fal\Stick\Web\Form;

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
     * @var string
     */
    public $type;

    /**
     * @var bool
     */
    public $rendered = false;

    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $radio_id;

    /**
     * @var string
     */
    public $checkbox_id;

    /**
     * @var string
     */
    public $label;

    /**
     * @var string
     */
    public $placeholder;

    /**
     * @var bool
     */
    public $expanded;

    /**
     * @var bool
     */
    public $multiple;

    /**
     * @var bool
     */
    public $submitted;

    /**
     * @var string|array
     */
    public $value;

    /**
     * @var array
     */
    public $errors;

    /**
     * @var string
     */
    public $constraints;

    /**
     * @var array
     */
    public $messages;

    /**
     * @var array
     */
    public $attr;

    /**
     * @var array
     */
    public $item_attr;

    /**
     * @var array
     */
    public $label_attr;

    /**
     * @var array
     */
    public $wrapper_attr;

    /**
     * @var callable
     */
    public $transformer;

    /**
     * @var callable
     */
    public $reverse_transformer;

    /**
     * Class constructor.
     *
     * @param string      $name
     * @param string|null $type
     * @param array|null  $options
     */
    public function __construct(string $name, string $type = null, array $options = null)
    {
        foreach ($options ?? array() as $property => $value) {
            $this->$property = $value;
        }

        $this->name = $name;
        $this->type = $type;
    }

    /**
     * Returns true if check type is equals.
     *
     * @param string $type
     *
     * @return bool
     */
    public function isType(string $type): bool
    {
        return $this->type && strtolower($this->type) === $type;
    }

    /**
     * Returns true if check types is equals.
     *
     * @param array $types
     *
     * @return bool
     */
    public function inType(array $types): bool
    {
        return $this->type && in_array(strtolower($this->type), $types);
    }

    /**
     * Returns attributes with extra.
     *
     * @param array|null $prepend
     * @param array|null $append
     *
     * @return array
     */
    public function attr(array $prepend = null, array $append = null): array
    {
        return ($prepend ?? array()) + ($this->attr ?? array()) + ($append ?? array());
    }

    /**
     * Returns transformed data.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function transform($data)
    {
        if ($this->transformer && is_callable($transform = $this->transformer)) {
            return $transform($data);
        }

        return $data;
    }

    /**
     * Reverse transform data.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function reverseTransform($data)
    {
        if ($this->reverse_transformer && is_callable($transform = $this->reverse_transformer)) {
            return $transform($data);
        }

        return $data;
    }
}

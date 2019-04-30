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

namespace Fal\Stick\Form;

use Fal\Stick\Util\Option;

/**
 * Field class.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Field extends Option
{
    /**
     * Class constructor.
     *
     * @param string      $name
     * @param string|null $type
     * @param array|null  $options
     */
    public function __construct(string $name, string $type = null, array $options = null)
    {
        $extras = $options['extras'] ?? array();

        $this
            ->add('attr', array())
            ->add('checkbox_id', null, 'string')
            ->add('constraints', null, 'string')
            ->add('errors', array())
            ->add('expanded', null, 'bool')
            ->add('id', null, 'string')
            ->add('items', array(), 'string|callable|array')
            ->add('item_attr', array())
            ->add('label', null, 'string')
            ->add('label_attr', array())
            ->add('messages', array())
            ->add('multiple', null, 'bool')
            ->add('name', $name)
            ->add('placeholder', null, 'string')
            ->add('radio_id', null, 'string')
            ->add('rendered', false)
            ->add('reverse_transformer', null, 'string|callable')
            ->add('submitted', null, 'bool')
            ->add('transformer', null, 'string|callable')
            ->add('type', $type)
            ->add('value', null, '')
            ->add('wrapper_attr', array())
            ->setDefaults($extras)
            ->resolve($options ?? array());
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
        return $this->type && 0 === strcasecmp($this->type, $type);
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
        return $this->type && preg_grep('/^'.preg_quote($this->type, '/').'$/i', $types);
    }

    /**
     * Returns true if check types is button.
     *
     * @return bool
     */
    public function isButton(): bool
    {
        return $this->inType(array('submit', 'reset', 'button', 'link'));
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
        return (array) $prepend + $this->attr + (array) $append;
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
        if (is_callable($transform = $this->transformer)) {
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
        if (is_callable($transform = $this->reverse_transformer)) {
            return $transform($data);
        }

        return $data;
    }
}

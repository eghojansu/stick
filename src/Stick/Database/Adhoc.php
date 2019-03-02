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
 * Adhoc field.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Adhoc extends Field
{
    /**
     * @var mixed
     */
    public $expression;

    /**
     * Class constructor.
     *
     * @param string $name
     * @param mixed  $expression
     */
    public function __construct(string $name, $expression)
    {
        $this->name = $name;
        $this->expression = $expression;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        if (!$this->changed && null === $this->value) {
            $value = is_callable($cb = $this->expression) ? $cb() : $this->expression;

            $this->setValue($value);
        }

        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function commit(): Field
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function reset(): Field
    {
        $this->value = null;
        $this->changed = false;

        return $this;
    }
}

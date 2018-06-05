<?php

declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Validation;

/**
 * Simplify validator implementation.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
abstract class AbstractValidator implements ValidatorInterface
{
    /**
     * @var array
     */
    protected $currentData;

    /**
     * {@inheritdoc}
     */
    public function has(string $rule): bool
    {
        return method_exists($this, '_'.$rule);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $rule, $value, array $args = [], string $field = '', array $validated = [], array $raw = [])
    {
        $use = '_'.$rule;
        $this->currentData = ['rule' => $rule, 'field' => $field, 'validated' => $validated, 'raw' => $raw];

        $result = $this->$use($value, ...$args);
        $this->currentData = null;

        return $result;
    }
}

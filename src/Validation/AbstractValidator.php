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

use Fal\Stick\Helper;

/**
 * Simplify validator implementation.
 */
abstract class AbstractValidator implements ValidatorInterface
{
    /** @var array Rule message */
    protected $messages = [];

    /** @var array */
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
    public function message(string $rule, $value = null, array $args = [], string $field = '', string $message = null): string
    {
        $use = $message ?? $this->messages[strtolower($rule)] ?? 'This value is not valid.';

        if (false === strpos($use, '{')) {
            return $use;
        }

        return Helper::interpolate($use, ['field' => $field, 'rule' => $rule, 'value' => $value] + $args, '{}');
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

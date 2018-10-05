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

abstract class AbstractValidator implements ValidatorInterface
{
    /**
     * Current rule data.
     *
     * @var array
     */
    protected $data;

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
    public function validate(string $rule, $value, array $args = null, string $field = null, array $validated = null, array $raw = null)
    {
        $this->data = array(
            'rule' => $rule,
            'field' => $field,
            'validated' => (array) $validated,
            'raw' => (array) $raw,
        );

        $call = array($this, '_'.$rule);
        $result = $call(...array_merge(array($value), (array) $args));

        $this->data = null;

        return $result;
    }
}

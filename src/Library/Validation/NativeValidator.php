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

namespace Fal\Stick\Library\Validation;

/**
 * Proxy rule to native PHP functions.
 *
 * Remember that value is always given as first arguments.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class NativeValidator implements ValidatorInterface
{
    /**
     * {@inheritdoc}
     */
    public function has(string $rule): bool
    {
        return is_callable($rule);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $rule, $value, array $args = null, string $field = null, array $validated = null, array $raw = null)
    {
        return $rule(...array_merge(array($value), (array) $args));
    }
}

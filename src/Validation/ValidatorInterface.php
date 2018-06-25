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

/**
 * Interface for validator.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface ValidatorInterface
{
    /**
     * Check if validator exists.
     *
     * @param string $rule
     *
     * @return bool
     */
    public function has(string $rule): bool;

    /**
     * Perform validation with given rule.
     *
     * @param string $rule
     * @param mixed  $value
     * @param array  $args
     * @param string $field
     * @param array  $validated
     * @param array  $raw
     *
     * @return mixed
     */
    public function validate(string $rule, $value, array $args = [], string $field = '', array $validated = [], array $raw = []);
}

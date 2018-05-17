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
 * Interface for validator.
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
     * Get message for rule.
     *
     * @param string $rule
     * @param mixed  $value
     * @param array  $args
     * @param string $field
     * @param string $message
     *
     * @return string
     */
    public function message(string $rule, $value = null, array $args = [], string $field = '', string $message = null): string;

    /**
     * Get real validator rule.
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

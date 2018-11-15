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
     * Returns true if validator can handle the specified rule.
     *
     * @param string $rule
     *
     * @return bool
     */
    public function has(string $rule): bool;

    /**
     * Returns the return value of performed rule.
     *
     * @param string      $rule
     * @param mixed       $value
     * @param array|null  $args
     * @param string|null $field
     * @param array|null  $validated
     * @param array|null  $raw
     *
     * @return mixed
     */
    public function validate(string $rule, $value, array $args = null, string $field = null, array $validated = null, array $raw = null);
}

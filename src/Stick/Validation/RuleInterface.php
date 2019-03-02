<?php

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
 * Rule interface.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
interface RuleInterface
{
    /*
     * Returns true if validator can handle the specified rule.
     *
     * @param string $rule
     *
     * @return bool
     */
    public function has(string $rule): bool;

    /**
     * Set validation result.
     *
     * @param Result $context
     *
     * @return RuleInterface
     */
    public function context(Result $context): RuleInterface;

    /**
     * Returns the return value of performed rule.
     *
     * @param string $rule
     * @param mixed  $value
     * @param array  $arguments
     *
     * @return mixed
     */
    public function validate(string $rule, $value, array $arguments);
}

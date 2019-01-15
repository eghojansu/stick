<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 14, 2019 05:53
 */

namespace Fal\Stick\Validation;

interface ValidatorInterface
{
    /*
     * Returns true if validator can handle the specified rule.
     *
     * @param string $rule
     *
     * @return bool
     */
    public function has($rule);

    /**
     * Set validation result.
     *
     * @param Result $context
     *
     * @return ValidatorInterface
     */
    public function context(Result $context);

    /**
     * Returns the return value of performed rule.
     *
     * @param string $rule
     * @param mixed  $value
     * @param array  $arguments
     *
     * @return mixed
     */
    public function validate($rule, $value, array $arguments);
}

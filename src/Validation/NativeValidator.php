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
    public function has($rule)
    {
        return is_callable($rule);
    }

    /**
     * {@inheritdoc}
     */
    public function validate($rule, $value, array $args = null, $field = null, array $validated = null, array $raw = null)
    {
        $passedArgs = array_merge(array($value), (array) $args);

        return call_user_func_array($rule, $passedArgs);
    }
}

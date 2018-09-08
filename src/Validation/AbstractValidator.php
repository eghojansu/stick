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
    public function has($rule)
    {
        return method_exists($this, '_'.$rule);
    }

    /**
     * {@inheritdoc}
     */
    public function validate($rule, $value, array $args = null, $field = null, array $validated = null, array $raw = null)
    {
        $this->data = array(
            'rule' => $rule,
            'field' => $field,
            'validated' => (array) $validated,
            'raw' => (array) $raw,
        );

        $call = array($this, '_'.$rule);
        $passedArgs = array_merge(array($value), (array) $args);

        $result = call_user_func_array($call, $passedArgs);

        $this->data = null;

        return $result;
    }
}

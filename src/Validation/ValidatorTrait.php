<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Validation;

trait ValidatorTrait
{
    /**
     * Current rule data.
     *
     * @var Result
     */
    protected $context;

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
    public function context(Result $context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($rule, $value, array $arguments)
    {
        array_unshift($arguments, $value);

        return call_user_func_array([$this, '_'.$rule], $arguments);
    }
}

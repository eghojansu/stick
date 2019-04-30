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
 * Rule trait.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
trait RuleTrait
{
    /**
     * Validation context.
     *
     * @var Context
     */
    protected $context;

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
    public function validate(string $rule, Context $value)
    {
        $this->context = $value;

        return $this->{'_'.$rule}($value->getValue(), ...$value->getArguments());
    }
}

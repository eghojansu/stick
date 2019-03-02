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
 * Rule trait.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
trait RuleTrait
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
    public function has(string $rule): bool
    {
        return method_exists($this, '_'.$rule);
    }

    /**
     * {@inheritdoc}
     */
    public function context(Result $context): RuleInterface
    {
        $this->context = $context;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $rule, $value, array $arguments)
    {
        array_unshift($arguments, $value);

        return (array($this, '_'.$rule))(...$arguments);
    }
}

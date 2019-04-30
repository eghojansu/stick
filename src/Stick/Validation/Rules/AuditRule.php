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

namespace Fal\Stick\Validation\Rules;

use Fal\Stick\Validation\Audit;
use Fal\Stick\Validation\Context;
use Fal\Stick\Validation\RuleInterface;

/**
 * Audit rule wrapper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class AuditRule implements RuleInterface
{
    /**
     * @var Audit
     */
    private $audit;

    /**
     * Class constructor.
     *
     * @param Audit $audit
     */
    public function __construct(Audit $audit)
    {
        $this->audit = $audit;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $rule): bool
    {
        return method_exists($this->audit, $rule);
    }

    /**
     * {@inheritdoc}
     */
    public function validate(string $rule, Context $value)
    {
        return $this->audit->$rule($value->getValue(), ...$value->getArguments());
    }
}

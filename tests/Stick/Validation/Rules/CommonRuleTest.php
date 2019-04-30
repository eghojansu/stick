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

namespace Fal\Stick\Test\Validation\Rules;

use Fal\Stick\TestSuite\MyTestCase;

class CommonRuleTest extends MyTestCase
{
    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\RuleProvider::commonRules
     */
    public function testHas($expected, $rule)
    {
        $this->assertEquals($expected, $this->commonRule->has($rule));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\RuleProvider::commonValidations
     */
    public function testValidate($expected, $rule, $value)
    {
        $this->assertEquals($expected, $this->commonRule->validate($rule, $value));
    }
}

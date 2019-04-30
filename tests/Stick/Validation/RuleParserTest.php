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

namespace Fal\Stick\Test\Validation;

use Fal\Stick\TestSuite\MyTestCase;
use Fal\Stick\Validation\RuleParser;

class RuleParserTest extends MyTestCase
{
    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\RuleParserProvider::parse
     */
    public function testParse($expected, $expr)
    {
        $this->assertEquals($expected, RuleParser::parse($expr));
    }
}

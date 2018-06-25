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

use Fal\Stick\Validation\NativeValidator;
use PHPUnit\Framework\TestCase;

class NativeValidatorTest extends TestCase
{
    private $validator;

    public function setUp()
    {
        $this->validator = new NativeValidator();
    }

    public function hasProvider()
    {
        return [
            ['is_string'],
            ['trim'],
            ['foo', false],
        ];
    }

    /**
     * @dataProvider hasProvider
     */
    public function testHas($rule, $expected = true)
    {
        $this->assertEquals($expected, $this->validator->has($rule));
    }

    public function validateProvider()
    {
        return [
            ['is_string', false, [1]],
            ['is_string', true, ['str']],
            ['trim', ',foo,', [',foo,']],
            ['trim', 'foo', [',foo,', ',']],
        ];
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate($rule, $expected, $args = [], $validated = [])
    {
        $this->assertEquals($expected, $this->validator->validate($rule, array_shift($args), $args, '', $validated));
    }
}

<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    public function testHas()
    {
        $this->assertTrue($this->validator->has('is_numeric'));
        $this->assertFalse($this->validator->has('foo'));
    }

    public function testValidate()
    {
        $this->assertTrue($this->validator->validate('is_numeric', '12'));
        $this->assertEquals('foo', $this->validator->validate('trim', ' foo '));
    }
}

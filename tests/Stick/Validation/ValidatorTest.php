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

use Fal\Stick\Fw;
use Fal\Stick\TestSuite\MyTestCase;
use Fal\Stick\Validation\Validator;
use Fal\Stick\Validation\Rules\LaravelRule;

class ValidatorTest extends MyTestCase
{
    private $fw;

    public function setup(): void
    {
        $this->fw = new Fw();
        $this->validator = new Validator($this->fw);
    }

    public function testAdd()
    {
        $this->assertSame($this->validator, $this->validator->add(new LaravelRule()));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\ValidatorProvider::validate
     */
    public function testValidate($expected, $data, $rules, $messages = null)
    {
        $this->validator->add(new LaravelRule());

        $this->assertEquals($expected, $this->validator->validate($data, $rules, $messages));
    }

    public function testValidateNotFound()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Validation rule not exists: foo.');

        $this->validator->validate(array(), array('foo' => 'foo'));
    }
}

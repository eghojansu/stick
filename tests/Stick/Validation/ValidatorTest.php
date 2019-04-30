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
use Fal\Stick\Validation\Rules\CommonRule;

class ValidatorTest extends MyTestCase
{
    private $fw;

    public function setup(): void
    {
        $this->fw = new Fw();
    }

    protected function createInstance()
    {
        return new Validator($this->fw);
    }

    public function testAdd()
    {
        $this->assertSame($this->validator, $this->validator->add(new CommonRule()));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\ValidatorProvider::validate
     */
    public function testValidate($expected, $errors, $validated, $data, $rules, $messages = null, $exception = null)
    {
        $this->validator->add(new CommonRule());

        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);
            $this->validator->validate($data, $rules, $messages);

            return;
        }

        $result = $this->validator->validate($data, $rules, $messages);
        $this->assertEquals($expected, $result->isSuccess());
        $this->assertEquals($validated, $result->getData());
        $this->assertEquals($errors, $result->getErrors());
    }
}

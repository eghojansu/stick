<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Unit\Validation;

use Fal\Stick\Validation\NativeValidator;
use Fal\Stick\Validation\SimpleValidator;
use Fal\Stick\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    private $validator;

    public function setUp()
    {
        $this->validator = new Validator;
        $this->validator->add(new SimpleValidator);
        $this->validator->add(new NativeValidator);
    }

    public function validateProvider()
    {
        return [
            [
                ['foo.bar'=>'required'],
                ['foo'=>['bar'=>'baz']],
                null,
                ['foo'=>[]],
                ['foo.bar'=>['This value should not be blank.']],
            ],
            [
                ['foo'=>'required'],
                ['foo'=>'bar'],
                null,
                [],
                ['foo'=>['This value should not be blank.']],
            ],
            [
                ['foo'=>'type:integer'],
                ['foo'=>1],
                null,
                ['foo'=>'str'],
                ['foo'=>['This value should be of type integer.']],
            ],
            [
                ['foo'=>'min:1'],
                ['foo'=>1],
                null,
                ['foo'=>0],
                ['foo'=>['This value should be 1 or more.']],
            ],
            [
                ['foo'=>'max:1'],
                ['foo'=>0],
                null,
                ['foo'=>2],
                ['foo'=>['This value should be 1 or less.']],
            ],
            [
                ['foo'=>'lt:1'],
                ['foo'=>0],
                null,
                ['foo'=>1],
                ['foo'=>['This value should be less than 1.']],
            ],
            [
                ['foo'=>'gt:1'],
                ['foo'=>2],
                null,
                ['foo'=>1],
                ['foo'=>['This value should be greater than 1.']],
            ],
            [
                ['foo'=>'lte:1'],
                ['foo'=>1],
                null,
                ['foo'=>2],
                ['foo'=>['This value should be less than or equal to 1.']],
            ],
            [
                ['foo'=>'gte:1'],
                ['foo'=>1],
                null,
                ['foo'=>0],
                ['foo'=>['This value should be greater than or equal to 1.']],
            ],
            [
                ['foo'=>'equalfield:bar'],
                ['foo'=>'baz','bar'=>'baz'],
                ['foo'=>'baz'],
                ['foo'=>'bar','bar'=>'baz'],
                ['foo'=>['This value should be equal to value of bar.']],
            ],
            [
                ['foo'=>'notequalfield:bar'],
                ['foo'=>'bar','bar'=>'baz'],
                ['foo'=>'bar'],
                ['foo'=>'baz','bar'=>'baz'],
                ['foo'=>['This value should not be equal to value of bar.']],
            ],
            [
                ['foo'=>'equal:bar'],
                ['foo'=>'bar'],
                null,
                ['foo'=>'baz'],
                ['foo'=>['This value should be equal to bar.']],
            ],
            [
                ['foo'=>'notequal:bar'],
                ['foo'=>'baz'],
                null,
                ['foo'=>'bar'],
                ['foo'=>['This value should not be equal to bar.']],
            ],
            [
                ['foo'=>'identical:bar,string'],
                ['foo'=>'bar'],
                null,
                ['foo'=>'baz'],
                ['foo'=>['This value should be identical to string bar.']],
            ],
            [
                ['foo'=>'notidentical:bar,string'],
                ['foo'=>'baz'],
                null,
                ['foo'=>'bar'],
                ['foo'=>['This value should not be identical to string bar.']],
            ],
            [
                ['foo'=>'len:3'],
                ['foo'=>'bar'],
                null,
                ['foo'=>'ba'],
                ['foo'=>['This value is not valid. It should have exactly 3 characters.']],
            ],
            [
                ['foo'=>'lenmin:3'],
                ['foo'=>'bar'],
                null,
                ['foo'=>'ba'],
                ['foo'=>['This value is too short. It should have 3 characters or more.']],
            ],
            [
                ['foo'=>'lenmax:3'],
                ['foo'=>'bar'],
                null,
                ['foo'=>'barbaz'],
                ['foo'=>['This value is too long. It should have 3 characters or less.']],
            ],
            [
                ['foo'=>'count:1'],
                ['foo'=>[1]],
                null,
                ['foo'=>[1,2]],
                ['foo'=>['This collection should contain exactly 1 elements.']],
            ],
            [
                ['foo'=>'countmin:1'],
                ['foo'=>[1]],
                null,
                ['foo'=>[]],
                ['foo'=>['This collection should contain 1 elements or more.']],
            ],
            [
                ['foo'=>'countmax:1'],
                ['foo'=>[1]],
                null,
                ['foo'=>[1,2]],
                ['foo'=>['This collection should contain 1 elements or less.']],
            ],
            [
                ['foo'=>'regex:"/^foo$/"'],
                ['foo'=>'foo'],
                null,
                ['foo'=>'bar'],
                ['foo'=>['This value is not valid.']],
            ],
            [
                ['foo'=>'choice:[1,2]'],
                ['foo'=>1],
                null,
                ['foo'=>3],
                ['foo'=>['The value you selected is not a valid choice.']],
            ],
            [
                ['foo'=>'choices:[1,2,3]'],
                ['foo'=>[1,2]],
                null,
                ['foo'=>[1,4]],
                ['foo'=>['One or more of the given values is invalid.']],
            ],
            [
                ['foo'=>'date'],
                ['foo'=>'2010-10-10'],
                null,
                ['foo'=>'2010-10'],
                ['foo'=>['This value is not a valid date.']],
            ],
            [
                ['foo'=>'datetime'],
                ['foo'=>'2010-10-10 10:10:10'],
                null,
                ['foo'=>'2010-10 10:10:10'],
                ['foo'=>['This value is not a valid datetime.']],
            ],
            [
                ['foo'=>'email'],
                ['foo'=>'bar@mail.com'],
                null,
                ['foo'=>'bar@mail'],
                ['foo'=>['This value is not a valid email address.']],
            ],
            [
                ['foo'=>'url'],
                ['foo'=>'http://foo.com'],
                null,
                ['foo'=>'foo'],
                ['foo'=>['This value is not a valid url.']],
            ],
            [
                ['foo'=>'ipv4'],
                ['foo'=>'30.88.29.1'],
                null,
                ['foo'=>'172.300.256.100'],
                ['foo'=>['This value is not a valid ipv4 address.']],
            ],
            [
                ['foo'=>'ipv6'],
                ['foo'=>'2001:DB8:0:0:8:800:200C:417A'],
                null,
                ['foo'=>'FF02:0000:0000:0000:0000:0000:0000:0000:0001'],
                ['foo'=>['This value is not a valid ipv6 address.']],
            ],
            [
                ['foo'=>'isprivate'],
                ['foo'=>'10.10.10.10'],
                null,
                ['foo'=>'201.176.14.4'],
                ['foo'=>['This value is not a private ip address.']],
            ],
            [
                ['foo'=>'isreserved'],
                ['foo'=>'240.241.242.243'],
                null,
                ['foo'=>'193.194.195.196'],
                ['foo'=>['This value is not a reserved ip address.']],
            ],
            [
                ['foo'=>'ispublic'],
                ['foo'=>'180.1.1.0'],
                null,
                ['foo'=>'10.10.10.10'],
                ['foo'=>['This value is not a public ip address.']],
            ],
            [
                ['foo'=>'is_string'],
                ['foo'=>'str'],
                null,
                ['foo'=>1],
                ['foo'=>['This value is not valid.']],
            ],
        ];
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate($rules, $trueData, $data, $falseData, $expected, $messages = [])
    {
        $first = $this->validator->validate($trueData, $rules);
        $this->assertTrue($first['success']);
        $this->assertEquals($data ?? $trueData, $first['data']);

        $second = $this->validator->validate($falseData, $rules, $messages);
        $this->assertFalse($second['success']);
        $this->assertEquals($expected, $second['error']);
    }

    public function validateMutateProvider()
    {
        return [
            [
                ['foo'=>'cdate:d-m-Y'],
                ['foo'=>'2010-10-10'],
                ['foo'=>'10-10-2010'],
                ['foo'=>'2010-13-10'],
            ],
            [
                ['foo'=>'trim:","'],
                ['foo'=>',foo,'],
                ['foo'=>'foo'],
                ['foo'=>' foo '],
            ],
        ];
    }

    /**
     * @dataProvider validateMutateProvider
     */
    public function testValidateMutate($rules, $trueData, $trueExpected, $falseData, $falseExpected = null)
    {
        $first = $this->validator->validate($trueData, $rules);
        $this->assertEquals($trueExpected ?? $trueData, $first['data']);

        $second = $this->validator->validate($falseData, $rules);
        $this->assertEquals($falseExpected ?? $falseData, $second['data']);
    }

    /**
     * @expectedException DomainException
     * @expectedExceptionMessage Rule "foo" does not exists
     */
    public function testValidateException()
    {
        $this->validator->validate(['foo'=>'foo'], ['foo'=>'foo']);
    }
}

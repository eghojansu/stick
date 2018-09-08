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

use Fal\Stick\App;
use Fal\Stick\Security\Auth;
use Fal\Stick\Security\AuthValidator;
use Fal\Stick\Security\PlainPasswordEncoder;
use Fal\Stick\Security\SimpleUserTransformer;
use Fal\Stick\Security\SqlUserProvider;
use Fal\Stick\Sql\Connection;
use Fal\Stick\Sql\MapperValidator;
use Fal\Stick\Validation\CommonValidator;
use Fal\Stick\Validation\NativeValidator;
use Fal\Stick\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    private $validator;
    private $app;

    public function setUp()
    {
        $this->app = new App();

        $this->validator = new Validator($this->app);
    }

    private function registerValidator()
    {
        $this->validator->add(new CommonValidator());
        $this->validator->add(new NativeValidator());

        $conn = new Connection($this->app, array(
            'dsn' => 'sqlite::memory:',
            'commands' => file_get_contents(FIXTURE.'files/schema.sql'),
        ));
        $conn->pdo()->exec('insert into user (username, password) values ("foo", "foo"), ("bar", "bar"), ("baz", "baz")');
        $this->validator->add(new MapperValidator($conn));

        $provider = new SqlUserProvider($conn, new SimpleUserTransformer());
        $auth = new Auth($this->app, $provider, new PlainPasswordEncoder());
        $auth->setUser($provider->findById('1'));
        $this->validator->add(new AuthValidator($auth));
    }

    public function testAdd()
    {
        $this->assertSame($this->validator, $this->validator->add(new NativeValidator()));
    }

    public function validateProvider()
    {
        return array(
            array(
                array('foo.bar' => 'required'),
                array('foo' => array('bar' => 'baz')),
                null,
                array('foo' => array()),
                array('foo.bar' => array('This value should not be blank.')),
            ),
            array(
                array('foo' => 'required'),
                array('foo' => 'bar'),
                null,
                array(),
                array('foo' => array('This value should not be blank.')),
            ),
            array(
                array('foo' => 'type:integer'),
                array('foo' => 1),
                null,
                array('foo' => 'str'),
                array('foo' => array('This value should be of type integer.')),
            ),
            array(
                array('foo' => 'min:1'),
                array('foo' => 1),
                null,
                array('foo' => 0),
                array('foo' => array('This value should be 1 or more.')),
            ),
            array(
                array('foo' => 'max:1'),
                array('foo' => 0),
                null,
                array('foo' => 2),
                array('foo' => array('This value should be 1 or less.')),
            ),
            array(
                array('foo' => 'lt:1'),
                array('foo' => 0),
                null,
                array('foo' => 1),
                array('foo' => array('This value should be less than 1.')),
            ),
            array(
                array('foo' => 'gt:1'),
                array('foo' => 2),
                null,
                array('foo' => 1),
                array('foo' => array('This value should be greater than 1.')),
            ),
            array(
                array('foo' => 'lte:1'),
                array('foo' => 1),
                null,
                array('foo' => 2),
                array('foo' => array('This value should be less than or equal to 1.')),
            ),
            array(
                array('foo' => 'gte:1'),
                array('foo' => 1),
                null,
                array('foo' => 0),
                array('foo' => array('This value should be greater than or equal to 1.')),
            ),
            array(
                array('foo' => 'equalfield:bar'),
                array('foo' => 'baz', 'bar' => 'baz'),
                array('foo' => 'baz'),
                array('foo' => 'bar', 'bar' => 'baz'),
                array('foo' => array('This value should be equal to value of bar.')),
            ),
            array(
                array('foo' => 'notequalfield:bar'),
                array('foo' => 'bar', 'bar' => 'baz'),
                array('foo' => 'bar'),
                array('foo' => 'baz', 'bar' => 'baz'),
                array('foo' => array('This value should not be equal to value of bar.')),
            ),
            array(
                array('foo' => 'equal:bar'),
                array('foo' => 'bar'),
                null,
                array('foo' => 'baz'),
                array('foo' => array('This value should be equal to bar.')),
            ),
            array(
                array('foo' => 'notequal:bar'),
                array('foo' => 'baz'),
                null,
                array('foo' => 'bar'),
                array('foo' => array('This value should not be equal to bar.')),
            ),
            array(
                array('foo' => 'identical:bar,string'),
                array('foo' => 'bar'),
                null,
                array('foo' => 'baz'),
                array('foo' => array('This value should be identical to string bar.')),
            ),
            array(
                array('foo' => 'notidentical:bar,string'),
                array('foo' => 'baz'),
                null,
                array('foo' => 'bar'),
                array('foo' => array('This value should not be identical to string bar.')),
            ),
            array(
                array('foo' => 'len:3'),
                array('foo' => 'bar'),
                null,
                array('foo' => 'ba'),
                array('foo' => array('This value is not valid. It should have exactly 3 characters.')),
            ),
            array(
                array('foo' => 'lenmin:3'),
                array('foo' => 'bar'),
                null,
                array('foo' => 'ba'),
                array('foo' => array('This value is too short. It should have 3 characters or more.')),
            ),
            array(
                array('foo' => 'lenmax:3'),
                array('foo' => 'bar'),
                null,
                array('foo' => 'barbaz'),
                array('foo' => array('This value is too long. It should have 3 characters or less.')),
            ),
            array(
                array('foo' => 'count:1'),
                array('foo' => array(1)),
                null,
                array('foo' => array(1, 2)),
                array('foo' => array('This collection should contain exactly 1 elements.')),
            ),
            array(
                array('foo' => 'countmin:1'),
                array('foo' => array(1)),
                null,
                array('foo' => array()),
                array('foo' => array('This collection should contain 1 elements or more.')),
            ),
            array(
                array('foo' => 'countmax:1'),
                array('foo' => array(1)),
                null,
                array('foo' => array(1, 2)),
                array('foo' => array('This collection should contain 1 elements or less.')),
            ),
            array(
                array('foo' => 'regex:"/^foo$/"'),
                array('foo' => 'foo'),
                null,
                array('foo' => 'bar'),
                array('foo' => array('This value is not valid.')),
            ),
            array(
                array('foo' => 'choice:[1,2]'),
                array('foo' => 1),
                null,
                array('foo' => 3),
                array('foo' => array('The value you selected is not a valid choice.')),
            ),
            array(
                array('foo' => 'choices:[1,2,3]'),
                array('foo' => array(1, 2)),
                null,
                array('foo' => array(1, 4)),
                array('foo' => array('One or more of the given values is invalid.')),
            ),
            array(
                array('foo' => 'date'),
                array('foo' => '2010-10-10'),
                null,
                array('foo' => '2010-10'),
                array('foo' => array('This value is not a valid date.')),
            ),
            array(
                array('foo' => 'datetime'),
                array('foo' => '2010-10-10 10:10:10'),
                null,
                array('foo' => '2010-10 10:10:10'),
                array('foo' => array('This value is not a valid datetime.')),
            ),
            array(
                array('foo' => 'email'),
                array('foo' => 'bar@mail.com'),
                null,
                array('foo' => 'bar@mail'),
                array('foo' => array('This value is not a valid email address.')),
            ),
            array(
                array('foo' => 'url'),
                array('foo' => 'http://foo.com'),
                null,
                array('foo' => 'foo'),
                array('foo' => array('This value is not a valid url.')),
            ),
            array(
                array('foo' => 'ipv4'),
                array('foo' => '30.88.29.1'),
                null,
                array('foo' => '172.300.256.100'),
                array('foo' => array('This value is not a valid ipv4 address.')),
            ),
            array(
                array('foo' => 'ipv6'),
                array('foo' => '2001:DB8:0:0:8:800:200C:417A'),
                null,
                array('foo' => 'FF02:0000:0000:0000:0000:0000:0000:0000:0001'),
                array('foo' => array('This value is not a valid ipv6 address.')),
            ),
            array(
                array('foo' => 'isprivate'),
                array('foo' => '10.10.10.10'),
                null,
                array('foo' => '201.176.14.4'),
                array('foo' => array('This value is not a private ip address.')),
            ),
            array(
                array('foo' => 'isreserved'),
                array('foo' => '240.241.242.243'),
                null,
                array('foo' => '193.194.195.196'),
                array('foo' => array('This value is not a reserved ip address.')),
            ),
            array(
                array('foo' => 'ispublic'),
                array('foo' => '180.1.1.0'),
                null,
                array('foo' => '10.10.10.10'),
                array('foo' => array('This value is not a public ip address.')),
            ),
            array(
                array('foo' => 'is_string'),
                array('foo' => 'str'),
                null,
                array('foo' => 1),
                array('foo' => array('This value is not valid.')),
            ),
            array(
                array('foo' => 'password'),
                array('foo' => 'foo'),
                null,
                array('foo' => 'bar'),
                array('foo' => array('This value should be equal to your user password.')),
            ),
            array(
                array('foo' => 'exists:user,username'),
                array('foo' => 'foo'),
                null,
                array('foo' => 'qux'),
                array('foo' => array('This value is not valid.')),
            ),
            array(
                array('foo' => 'unique:user,username'),
                array('foo' => 'qux'),
                null,
                array('foo' => 'foo'),
                array('foo' => array('This value is already used.')),
            ),
        );
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate($rules, $trueData, $data, $falseData, $expected, $messages = array())
    {
        $this->registerValidator();

        $first = $this->validator->validate($trueData, $rules);
        $this->assertTrue($first['success']);
        $this->assertEquals($data ?: $trueData, $first['data']);

        $second = $this->validator->validate($falseData, $rules, $messages);
        $this->assertFalse($second['success']);
        $this->assertEquals($expected, $second['errors']);
    }

    public function validateMutateProvider()
    {
        return array(
            array(
                array('foo' => 'convertDate:d-m-Y'),
                array('foo' => '2010-10-10'),
                array('foo' => '10-10-2010'),
                array('foo' => '2010-13-10'),
            ),
            array(
                array('foo' => 'trim:","'),
                array('foo' => ',foo,'),
                array('foo' => 'foo'),
                array('foo' => ' foo '),
            ),
        );
    }

    /**
     * @dataProvider validateMutateProvider
     */
    public function testValidateMutate($rules, $trueData, $trueExpected, $falseData, $falseExpected = null)
    {
        $this->registerValidator();

        $first = $this->validator->validate($trueData, $rules);
        $this->assertEquals($trueExpected ?: $trueData, $first['data']);

        $second = $this->validator->validate($falseData, $rules);
        $this->assertEquals($falseExpected ?: $falseData, $second['data']);
    }

    /**
     * @expectedException \DomainException
     * @expectedExceptionMessage Rule "foo" not exists.
     */
    public function testValidateException()
    {
        $this->validator->validate(array('foo' => 'foo'), array('foo' => 'foo'));
    }

    public function testValidateCustomMessage()
    {
        $this->registerValidator();

        $res = $this->validator->validate(array(), array('foo' => 'required'), array('foo.required' => 'Required'));
        $this->assertFalse($res['success']);
        $this->assertEquals('Required', $res['errors']['foo'][0]);
    }
}

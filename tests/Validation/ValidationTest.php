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

namespace Ekok\Stick\Tests\Validation;

use Ekok\Stick\Database\QueryBuilder\SqliteQueryBuilder;
use Ekok\Stick\Database\Sql;
use Ekok\Stick\Fw;
use Ekok\Stick\Security\Auth;
use Ekok\Stick\Security\InMemoryUserProvider;
use Ekok\Stick\Security\PlainPasswordEncoder;
use Ekok\Stick\Security\SimpleUser;
use Ekok\Stick\Validation\Validation;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Ekok\Stick\Validation\Validation
 */
final class ValidationTest extends TestCase
{
    private $fw;
    private $validator;

    protected function setUp(): void
    {
        $this->fw = new Fw();
        $this->validation = new Validation($this->fw, array(
            'foo' => 'bar',
        ));
    }

    public function testAddRule()
    {
        $this->assertSame($this->validation, $this->validation->addRule('foo', 'bar'));
    }

    public function testValidate()
    {
        $this->validation->addRule('fooRule', function ($result) {
            return trim($result->getValue());
        });
        $this->validation->addRule('barRule', function ($result) {
            $result->skip();

            return false;
        });
        $this->validation->addRule('bazRule', function ($result) {
            return false;
        });

        $rules = array(
            'foo' => 'fooRule|barRule',
            'bar' => 'bazRule:1234',
            'baz' => 'accepted',
        );
        $raw = array(
            'foo' => '  foo  ',
            'baz' => '1',
        );
        $messages = array(
            'bazRule' => 'The "%field%" value (%0%) is not valid.',
        );

        $expected = false;
        $expectedData = array(
            'foo' => 'foo',
            'baz' => '1',
        );
        $expectedErrors = array(
            'foo' => array(
                'This value is not valid.',
            ),
            'bar' => array(
                'The "bar" value (1234) is not valid.',
            ),
        );

        $result = $this->validation->validate($rules, $raw, $messages);

        $this->assertEquals($expected, $result->success());
        $this->assertEquals($expectedData, $result->data());
        $this->assertEquals($expectedErrors, $result->errors());
    }

    public function testValidateException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Rule not exists: \'foo\'.');

        $this->validation->validate(array('foo' => 'foo'));
    }

    /** @dataProvider validateAllRulesProvider */
    public function testValidateAllRules($expected, $rules, $value = null, array $raw = null)
    {
        $this->fw->set('GETTER.db', function ($fw) {
            $options = array(
                'commands' => array(
                    'create table foo (id integer not null, name text not null)',
                    'insert into foo values (1, "foo"), (2, "bar")',
                ),
            );

            return new Sql($fw, new SqliteQueryBuilder($options));
        });
        $this->fw->set('GETTER.encoder', function ($fw) {
            return new PlainPasswordEncoder();
        });
        $this->fw->set('GETTER.auth', function ($fw) {
            $provider = new InMemoryUserProvider(function ($user) {
                return SimpleUser::fromArray($user);
            });
            $provider->addUser(new SimpleUser('1', 'foo', 'bar', array('foo')));

            $auth = new Auth($fw, $provider, $fw->get('encoder'), array(
                'remember_session' => true,
            ));
            $auth->login('foo', 'bar');

            return $auth;
        });

        $raw['foo'] = $value ?? $expected;
        $result = $this->validation->validate(array('foo' => $rules), $raw);
        $data = $result->data();

        $this->assertSame($expected, $data['foo'] ?? null);
    }

    public function validateAllRulesProvider()
    {
        return array(
            'accepted' => array(
                1,
                'accepted',
            ),
            'after' => array(
                'tomorrow',
                'after:today',
            ),
            'afterOrEqual' => array(
                'today',
                'afterOrEqual:today',
            ),
            'alpha' => array(
                'alphA_n',
                'alpha',
            ),
            'aldash' => array(
                'ald-a_sh',
                'aldash',
            ),
            'alnum' => array(
                'alN123',
                'alnum',
            ),
            'array' => array(
                array('foo'),
                'array',
            ),
            'before' => array(
                'yesterday',
                'before:today',
            ),
            'beforeOrEqual' => array(
                'today',
                'beforeOrEqual:today',
            ),
            'between' => array(
                2,
                'between:1,3',
            ),
            'bool' => array(
                1,
                'bool',
            ),
            'confirmed' => array(
                'foo',
                'confirmed',
                null,
                array(
                    'foo_confirmation' => 'foo',
                ),
            ),
            'convert' => array(
                date('Y-m-d', strtotime('August 21, 1998')),
                'convert:Y-m-d',
                'August 21, 1998',
            ),
            'date' => array(
                '1998-08-21',
                'date',
            ),
            'dateEquals' => array(
                '1998-08-21',
                'dateEquals:1998-08-21',
            ),
            'dateFormat' => array(
                date('Y-m-d'),
                'dateFormat:Y-m-d',
                date('Y-m-d'),
            ),
            'different' => array(
                'bar',
                'different:bar',
                null,
                array(
                    'bar' => 'baz',
                ),
            ),
            'digits' => array(
                10,
                'digits:2',
            ),
            'digitsBetween' => array(
                10,
                'digitsBetween:1,3',
            ),
            'digitsBetween not number' => array(
                null,
                'digitsBetween:1,3',
                'foo',
            ),
            'distinct' => array(
                array('foo', 'bar'),
                'distinct',
            ),
            'email' => array(
                'foo@example.com',
                'email',
            ),
            'endsWith' => array(
                'foobar',
                'endsWith:bar',
            ),
            'not endsWith' => array(
                null,
                'endsWith:foo',
            ),
            'sqlExists' => array(
                'foo',
                'sqlExists:foo,name',
            ),
            'sqlUnique' => array(
                'foo',
                'sqlUnique:foo,1,name',
            ),
            'equalTo' => array(
                'bar',
                'equalTo:bar',
            ),
            'gt' => array(
                2,
                'gt:1',
            ),
            'gte' => array(
                2,
                'gte:2',
            ),
            'in' => array(
                'bar',
                'in:foo,bar,baz',
            ),
            'inField' => array(
                'bar',
                'inField:bar',
                null,
                array(
                    'bar' => array('foo', 'bar'),
                ),
            ),
            'int' => array(
                '10',
                'int',
            ),
            'integer' => array(
                '10',
                'integer',
            ),
            'ip' => array(
                '127.0.0.1',
                'ip',
            ),
            'ipv4' => array(
                '127.0.0.1',
                'ipv4',
            ),
            'ipv6' => array(
                '::1',
                'ipv6',
            ),
            'lt' => array(
                2,
                'lt:3',
            ),
            'lte' => array(
                2,
                'lte:2',
            ),
            'max' => array(
                '2',
                'max:2',
            ),
            'min' => array(
                2,
                'min:2',
            ),
            'notIn' => array(
                'bar',
                'notIn:foo,baz',
            ),
            'notInField' => array(
                'bar',
                'notInField:bar',
                null,
                array(
                    'bar' => array('foo', 'baz'),
                ),
            ),
            'notRegex' => array(
                'bar',
                'notRegex:/^foo$/',
            ),
            'optional' => array(
                'foo',
                'optional',
            ),
            'authPassword' => array(
                'bar',
                'authPassword',
            ),
            'notEqualTo' => array(
                'bar',
                'notEqualTo:foo',
            ),
            'numeric' => array(
                '123.3',
                'numeric',
            ),
            'regex' => array(
                'bar',
                'regex:/^bar$/',
            ),
            'rejected' => array(
                'off',
                'rejected',
            ),
            'required' => array(
                'bar',
                'required',
            ),
            'same' => array(
                'baz',
                'same:bar',
                null,
                array(
                    'bar' => 'baz',
                ),
            ),
            'size' => array(
                'foo',
                'size:3',
            ),
            'startsWith' => array(
                'foobar',
                'startsWith:foo',
            ),
            'not startsWith' => array(
                null,
                'startsWith:bar',
            ),
            'string' => array(
                'foo',
                'string',
            ),
            'timezone' => array(
                'Asia/Jakarta',
                'timezone',
            ),
            'trim' => array(
                'foo',
                'trim',
                ' foo ',
            ),
            'url' => array(
                'http://localhost',
                'url',
            ),
            'asString' => array(
                '10',
                'asString',
                10,
            ),
            'asNumber' => array(
                10,
                'asNumber',
                '10',
            ),
            'asArray' => array(
                array('foo'),
                'asArray',
                'foo',
            ),
            'toJson' => array(
                '{"foo":"bar"}',
                'toJson',
                array('foo' => 'bar'),
            ),
            'toJson prettier' => array(
                '{"foo":"bar"}',
                'toJson:JSON_PRESERVE_ZERO_FRACTION',
                array('foo' => 'bar'),
            ),
            'fromJson' => array(
                array('foo' => 'bar'),
                'fromJson',
                '{"foo":"bar"}',
            ),
            'split' => array(
                array('bar'),
                'split',
                'bar ',
            ),
            'split by pattern' => array(
                array('bar', 'baz', 'qux'),
                'split:/\=/',
                'bar=baz=qux',
            ),
            'join' => array(
                'foo,bar',
                'join',
                array('foo', 'bar'),
            ),
        );
    }
}

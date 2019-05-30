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

use Fal\Stick\Db\Pdo\Db;
use Fal\Stick\Db\Pdo\Driver\SqliteDriver;
use Fal\Stick\Fw;
use Fal\Stick\Security\Auth;
use Fal\Stick\Security\InMemoryUserProvider;
use Fal\Stick\Security\PlainPasswordEncoder;
use Fal\Stick\TestSuite\Classes\SimpleUser;
use Fal\Stick\TestSuite\MyTestCase;
use Fal\Stick\Validation\Field;

class LaravelRuleTest extends MyTestCase
{
    public function testHas()
    {
        $this->assertTrue($this->laravelRule->has('accepted'));
        $this->assertTrue($this->laravelRule->has('required'));
        $this->assertFalse($this->laravelRule->has('foo'));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\RuleProvider::laravel
     */
    public function testValidate(
        $expected,
        $rule,
        $value = 'foo',
        $arguments = array(),
        $data = null,
        $hive = null,
        $field = 'foo'
    ) {
        $fieldValue = new Field(
            new Fw(),
            array(),
            $data ?? array($field => $value),
            $field,
            $rule
        );

        if ($hive) {
            $fieldValue->fw->mset($hive);
        }

        $actual = $this->laravelRule->validate($rule, $arguments, $fieldValue);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\RuleProvider::mapper
     */
    public function testMapper(
        $expected,
        $rule,
        $arguments = array(),
        $field = 'username',
        $value = 'foo'
    ) {
        $fieldValue = new Field(
            new Fw(),
            array(),
            array($field => $value),
            $field,
            $rule
        );
        $db = new Db($fieldValue->fw, new SqliteDriver(), 'sqlite::memory:', null, null, array(
            $this->read('/files/schema_sqlite.sql'),
            'insert into user (username) values ("foo"), ("bar"), ("baz")',
        ));
        $fieldValue->fw->db = $db;
        $fieldValue->fw->alt = $db;

        $this->assertEquals($expected, $this->laravelRule->validate($rule, $arguments, $fieldValue));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\RuleProvider::auth
     */
    public function testAuth(
        $expected,
        $userId,
        $value = 'bar',
        $arguments = array()
    ) {
        $fieldValue = new Field(
            new Fw(),
            array(),
            array('password' => $value),
            'password',
            'password'
        );

        $fieldValue->fw->set('SESSION.user_login_id', $userId);

        $auth = new Auth($fieldValue->fw, new InMemoryUserProvider(), new PlainPasswordEncoder());
        $auth->provider
            ->addUser(new SimpleUser('1', 'foo', 'bar', array('foo')))
            ->addUser(new SimpleUser('2', 'bar', 'baz', array('foo', 'bar')));

        $fieldValue->fw->auth = $auth;
        $fieldValue->fw->alt = $auth;

        $this->assertEquals($expected, $this->laravelRule->validate('password', $arguments, $fieldValue));
    }
}

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

namespace Fal\Stick\Test\Security;

use Fal\Stick\Core;
use Fal\Stick\Security\Auth;
use Fal\Stick\Security\AuthValidator;
use Fal\Stick\Security\InMemoryUserProvider;
use Fal\Stick\Security\PlainPasswordEncoder;
use Fixture\SimpleUser;
use PHPUnit\Framework\TestCase;

class AuthValidatorTest extends TestCase
{
    private $validator;
    private $auth;

    public function setUp()
    {
        $this->auth = new Auth(new Core('phpunit-test'), new InMemoryUserProvider(), new PlainPasswordEncoder());
        $this->validator = new AuthValidator($this->auth);
    }

    public function testHas()
    {
        $this->assertTrue($this->validator->has('password'));
        $this->assertFalse($this->validator->has('foo'));
    }

    /**
     * @dataProvider validatePasswordProvider
     */
    public function testValidatePassword($user, $password, $expected = true)
    {
        $this->auth->setUser($user);

        $this->assertEquals($expected, $this->validator->validate('password', $password));
    }

    public function validatePasswordProvider()
    {
        return array(
            array(null, 'bar'),
            array(new SimpleUser('1', 'foo', 'bar'), 'bar'),
            array(new SimpleUser('1', 'foo', 'baz'), 'baz'),
            array(new SimpleUser('1', 'foo', 'bar'), 'baz', false),
        );
    }
}

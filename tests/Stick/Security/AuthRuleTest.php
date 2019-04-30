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

use Fal\Stick\Fw;
use Fal\Stick\Security\Auth;
use Fal\Stick\Security\AuthRule;
use Fal\Stick\Validation\Context;
use Fal\Stick\TestSuite\MyTestCase;
use Fal\Stick\TestSuite\Classes\SimpleUser;
use Fal\Stick\Security\InMemoryUserProvider;
use Fal\Stick\Security\PlainPasswordEncoder;

class AuthRuleTest extends MyTestCase
{
    private $fw;
    private $auth;

    public function setup(): void
    {
        $this->fw = new Fw();
        $this->auth = new Auth($this->fw, new InMemoryUserProvider(), new PlainPasswordEncoder());
        $this->auth->provider
            ->addUser(new SimpleUser('1', 'foo', 'bar', array('foo')))
            ->addUser(new SimpleUser('2', 'bar', 'baz', array('foo', 'bar')));
    }

    public function teardown(): void
    {
        header_remove();
        $this->fw->rem('SESSION');
    }

    protected function createInstance()
    {
        return new AuthRule($this->auth);
    }

    public function testHas()
    {
        $this->assertTrue($this->authRule->has('password'));
        $this->assertFalse($this->authRule->has('foo'));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Security\AuthRuleProvider::validatePassword
     */
    public function testValidatePassword($userId, $password, $expected = true)
    {
        $this->fw->set('SESSION.user_login_id', $userId);
        $value = new Context(array('password' => $password));
        $value->setField('password');

        $this->assertEquals($expected, $this->authRule->validate('password', $value));
    }
}

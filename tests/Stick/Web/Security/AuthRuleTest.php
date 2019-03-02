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

namespace Fal\Stick\Test\Web\Security;

use Fal\Stick\TestSuite\TestCase;
use Fal\Stick\Web\Security\AuthRule;
use Fixture\SimpleUser;

class AuthRuleTest extends TestCase
{
    private $rule;

    public function setUp()
    {
        $this->prepare();

        $this->rule = new AuthRule($this->auth);
        $this->auth->getProvider()
            ->addUser(new SimpleUser('1', 'foo', 'bar', array('foo')))
            ->addUser(new SimpleUser('2', 'bar', 'baz', array('foo', 'bar')));
    }

    public function teardown()
    {
        $this->session->destroy();
    }

    public function testHas()
    {
        $this->assertTrue($this->rule->has('password'));
        $this->assertFalse($this->rule->has('foo'));
    }

    /**
     * @dataProvider validatePasswordProvider
     */
    public function testValidatePassword($user, $password, $expected = true)
    {
        $this->session->set('user_login_id', $user);

        $this->assertEquals($expected, $this->rule->validate('password', $password, array()));
    }

    public function validatePasswordProvider()
    {
        return array(
            array(null, 'bar'),
            array('1', 'bar'),
            array('2', 'baz'),
            array('1', 'baz', false),
        );
    }
}

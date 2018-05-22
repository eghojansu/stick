<?php

declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Sql;

use Fal\Stick\App;
use Fal\Stick\Cache;
use Fal\Stick\Logger;
use Fal\Stick\Security\Auth;
use Fal\Stick\Security\AuthValidator;
use Fal\Stick\Security\PlainPasswordEncoder;
use Fal\Stick\Security\SimpleUserTransformer;
use Fal\Stick\Security\SqlUserProvider;
use Fal\Stick\Sql\Connection;
use PHPUnit\Framework\TestCase;

class AuthValidatorTest extends TestCase
{
    private $app;
    private $auth;
    private $validator;

    public function setUp()
    {
        $this->app = new App();

        $cache = new Cache('', 'test', TEMP.'cache/');
        $cache->reset();

        $logger = new Logger(TEMP.'authvalidatorlog/');
        $logger->clear();

        $db = new Connection($cache, $logger, [
            'driver' => 'sqlite',
            'location' => ':memory:',
            'debug' => true,
            'commands' => [
                <<<SQL1
CREATE TABLE `user` (
    `id` INTEGER NOT null PRIMARY KEY AUTOINCREMENT,
    `username` TEXT NOT null,
    `password` TEXT NOT NULL DEFAULT NULL,
    `roles` TEXT NOT NULL,
    `expired` INTEGER NOT NULL
);
insert into user (username,password,expired,roles)
    values ("foo","bar",0,"role_foo,role_bar"), ("baz","qux",1,"role_foo")
SQL1
,
            ],
        ]);
        $this->auth = new Auth($this->app, new SqlUserProvider($db, new SimpleUserTransformer()), new PlainPasswordEncoder());

        $this->validator = new AuthValidator($this->auth);
    }

    public function tearDown()
    {
        $this->app->mclear(explode('|', App::GLOBALS));
    }

    public function passwordProvider()
    {
        return [
            [true, null],
            [true, 'bar', 'foo', 'bar'],
            [true, 'qux', 'baz', 'qux'],
            [false, 'quux', 'foo', 'bar'],
            [false, '', 'foo', 'bar'],
        ];
    }

    /**
     * @dataProvider passwordProvider
     */
    public function testPassword($expected, $value, $username = null, $password = null)
    {
        if ($username && $password) {
            $this->auth->attempt($username, $password);
        }

        $this->assertEquals($expected, $this->validator->validate('password', $value));
    }

    public function hasProvider()
    {
        return [
            ['password'],
            ['foo', false],
        ];
    }

    /**
     * @dataProvider hasProvider
     */
    public function testHas($rule, $expected = true)
    {
        $this->assertEquals($expected, $this->validator->has($rule));
    }

    public function messageProvider()
    {
        return [
            ['password', 'This value should be equal to current user password.'],
        ];
    }

    /**
     * @dataProvider messageProvider
     */
    public function testMessage($rule, $message, $args = [])
    {
        $this->assertEquals($message, $this->validator->message($rule, null, $args));
    }
}

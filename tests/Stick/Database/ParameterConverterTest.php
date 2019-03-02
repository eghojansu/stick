<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Feb 09, 2019 17:45
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Database;

use Fal\Stick\Database\ParameterConverter;
use Fal\Stick\TestSuite\TestCase;
use Fixture\Mapper\TFriends;
use Fixture\Mapper\TUser;

class ParameterConverterTest extends TestCase
{
    public function setup()
    {
        $this->prepare()->connect()->buildSchema()->initUser()->initFriends();

        $this->converter = new ParameterConverter($this->container);
    }

    /**
     * @dataProvider resolveProvider
     */
    public function testResolve($expected, $handler, $params, $exception = false)
    {
        if ($exception) {
            $this->expectException('LogicException');
            $this->expectExceptionMessage($expected);

            $this->converter->resolve($handler, $params);

            return;
        }

        $this->assertCount($expected, $this->converter->resolve($handler, $params));
    }

    public function resolveProvider()
    {
        return array(
            array(0, function () {}, array()),
            array(1, function (TUser $user) {}, array(1)),
            array(1, function (TUser $user) {}, array('user' => 2)),
            array(2, function (TUser $user, $foo) {}, array('user' => 1, 1)),
            array(1, function (TFriends $friend) {}, array('friend' => 1, 2)),
            array(2, function (TFriends $friend) {}, array('friend' => 1, 2, 3)),
            array('Record not found (user).', function (TUser $user) {}, array('user' => 4), true),
        );
    }
}

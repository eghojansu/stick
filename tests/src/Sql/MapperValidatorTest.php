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

namespace Fal\Stick\Test\Sql;

use Fal\Stick\App;
use Fal\Stick\Sql\Connection;
use Fal\Stick\Sql\MapperValidator;
use PHPUnit\Framework\TestCase;

class MapperValidatorTest extends TestCase
{
    private $validator;

    public function setUp()
    {
        $app = App::create()->mset([
            'TEMP' => TEMP,
        ])->logClear();
        $cache = $app->get('cache');
        $cache->reset();

        $conn = new Connection($app, $cache, [
            'driver' => 'sqlite',
            'location' => ':memory:',
            'commands' => <<<SQL1
CREATE TABLE `user` (
    `id` INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
    `username` TEXT NOT NULL,
    `password` TEXT NULL DEFAULT NULL,
    `active` INTEGER NOT NULL DEFAULT 1
);
insert into user (username) values ("foo"), ("bar"), ("baz");
SQL1
        ]);

        $this->validator = new MapperValidator($conn);
    }

    public function hasProvider()
    {
        return [
            ['exists'],
            ['unique'],
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

    public function validateProvider()
    {
        return [
            ['exists', true, [1, 'user', 'id']],
            ['exists', true, ['foo', 'user', 'username']],
            ['exists', true, [1, 'user', 'active']],
            ['exists', false, ['bleh', 'user', 'username']],
            ['unique', false, [1, 'user', 'id']],
            ['unique', false, ['foo', 'user', 'username']],
            ['unique', false, [1, 'user', 'active']],
            ['unique', true, ['foo', 'user', 'username', 'id', 1]],
            ['unique', true, ['bleh', 'user', 'username']],
        ];
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate($rule, $expected, $args = [], $validated = [])
    {
        $this->assertEquals($expected, $this->validator->validate($rule, array_shift($args), $args, '', $validated));
    }
}

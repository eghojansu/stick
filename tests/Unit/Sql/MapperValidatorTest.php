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

namespace Fal\Stick\Test\Unit\Sql;

use Fal\Stick\Cache;
use Fal\Stick\Logger;
use Fal\Stick\Sql\Connection;
use Fal\Stick\Sql\Mapper;
use Fal\Stick\Sql\MapperValidator;
use PHPUnit\Framework\TestCase;

class MapperValidatorTest extends TestCase
{
    private $validator;

    public function setUp()
    {
        $cache = new Cache('', 'test', TEMP.'cache/');
        $cache->reset();

        $logger = new Logger(TEMP.'mappervalidatorlog/');
        $logger->clear();

        $conn = new Connection($cache, $logger, [
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

        $mapper = new Mapper($conn, 'user');

        $this->validator = new MapperValidator($mapper);
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

    public function messageProvider()
    {
        return [
            ['unique', 'This value is already used.'],
            ['exists', 'This value is not valid.'],
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

<?php declare(strict_types=1);

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
use Fal\Stick\Sql\Connection;
use Fal\Stick\Sql\Mapper;
use Fal\Stick\Sql\MapperValidator;
use PHPUnit\Framework\TestCase;

class MapperValidatorTest extends TestCase
{
    private $validator;

    public function setUp()
    {
        $cache = new Cache('', 'test', TEMP . 'cache/');
        $cache->reset();

        $conn = new Connection($cache, [
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

    public function existsProvider()
    {
        return [
            [true, 1, 'id'],
            [true, 'foo', 'username'],
            [true, 1, 'active'],
            [false, 'bleh', 'username'],
        ];
    }

    /**
     * @dataProvider existsProvider
     */
    public function testExists($expected, $value, $column)
    {
        $this->assertEquals($expected, $this->validator->validate('exists', $value, ['user', $column]));
    }

    public function uniqueProvider()
    {
        return [
            [false, 1, 'id'],
            [false, 'foo', 'username'],
            [false, 1, 'active'],
            [true, 'foo', 'username', 'id', 1],
            [true, 'bleh', 'username'],
        ];
    }

    /**
     * @dataProvider uniqueProvider
     */
    public function testUnique($expected, $value, $column, $fid = null, $id = null)
    {
        $this->assertEquals($expected, $this->validator->validate('unique', $value, ['user', $column, $fid, $id]));
    }

    public function testMessage()
    {
        $this->assertEquals('This value is already used.', $this->validator->message('unique'));
        $this->assertEquals('This value is not valid.', $this->validator->message('exists'));
    }
}

<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Database\Sql;

use Fal\Stick\Core;
use Fal\Stick\Database\Sql\MapperValidator;
use Fal\Stick\Database\Sql\Sql;
use PHPUnit\Framework\TestCase;

class MapperValidatorTest extends TestCase
{
    private $validator;

    public function setUp()
    {
        $fw = new Core();
        $conn = new Sql($fw, 'sqlite::memory:', null, null, [file_get_contents(TEST_FIXTURE.'/files/schema.sql')]);
        $conn->getPdo()->exec('insert into user (username, password) values ("foo", "foo"), ("bar", "bar"), ("baz", "baz")');

        $this->validator = new MapperValidator($fw, $conn);
    }

    /**
     * @dataProvider validateExistsProvider
     */
    public function testValidateExists($value, $expected = true)
    {
        $args = ['user', 'username'];

        $this->assertEquals($expected, $this->validator->validate('exists', $value, $args));
    }

    /**
     * @dataProvider validateUniqueProvider
     */
    public function testValidateUnique($value, $expected = false, $fid = null, $id = null)
    {
        $args = ['user', 'username', $fid, $id];

        $this->assertEquals($expected, $this->validator->validate('unique', $value, $args));
    }

    public function validateExistsProvider()
    {
        return [
            ['foo'],
            ['bar'],
            ['baz'],
            ['qux', false],
        ];
    }

    public function validateUniqueProvider()
    {
        return [
            ['foo'],
            ['bar'],
            ['baz'],
            ['foo', true, 'id', '1'],
            ['foo', false, 'id', '2'],
            ['qux', true],
        ];
    }
}

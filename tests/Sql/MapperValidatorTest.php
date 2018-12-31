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

use Fal\Stick\Fw;
use Fal\Stick\Sql\Connection;
use Fal\Stick\Sql\MapperValidator;
use PHPUnit\Framework\TestCase;

class MapperValidatorTest extends TestCase
{
    private $validator;

    public function setUp()
    {
        $fw = new Fw('phpunit-test');
        $conn = new Connection($fw, 'sqlite::memory:', null, null, array(file_get_contents(TEST_FIXTURE.'files/schema.sql')));
        $conn->getPdo()->exec('insert into user (username, password) values ("foo", "foo"), ("bar", "bar"), ("baz", "baz")');

        $this->validator = new MapperValidator($fw, $conn);
    }

    /**
     * @dataProvider validateExistsProvider
     */
    public function testValidateExists($value, $expected = true)
    {
        $args = array('user', 'username');

        $this->assertEquals($expected, $this->validator->validate('exists', $value, $args));
    }

    /**
     * @dataProvider validateUniqueProvider
     */
    public function testValidateUnique($value, $expected = false, $fid = null, $id = null)
    {
        $args = array('user', 'username', $fid, $id);

        $this->assertEquals($expected, $this->validator->validate('unique', $value, $args));
    }

    public function validateExistsProvider()
    {
        return array(
            array('foo'),
            array('bar'),
            array('baz'),
            array('qux', false),
        );
    }

    public function validateUniqueProvider()
    {
        return array(
            array('foo'),
            array('bar'),
            array('baz'),
            array('foo', true, 'id', '1'),
            array('foo', false, 'id', '2'),
            array('qux', true),
        );
    }
}

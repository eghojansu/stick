<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Validation;

use Fal\Stick\App;
use Fal\Stick\Sql\Connection;
use Fal\Stick\Sql\MapperValidator;
use PHPUnit\Framework\TestCase;

class MapperValidatorTest extends TestCase
{
    private $validator;

    public function setUp()
    {
        $app = App::create();
        $conn = new Connection($app, array(
            'dsn' => 'sqlite::memory:',
            'commands' => file_get_contents(FIXTURE.'files/schema.sql'),
        ));
        $conn->pdo()->exec('insert into user (username, password) values ("foo", "foo"), ("bar", "bar"), ("baz", "baz")');
        $this->validator = new MapperValidator($conn);
    }

    public function testHas()
    {
        $this->assertTrue($this->validator->has('exists'));
        $this->assertTrue($this->validator->has('unique'));
        $this->assertFalse($this->validator->has('foo'));
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

    /**
     * @dataProvider validateExistsProvider
     */
    public function testValidateExists($value, $expected = true)
    {
        $args = array('user', 'username');

        $this->assertEquals($expected, $this->validator->validate('exists', $value, $args));
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

    /**
     * @dataProvider validateUniqueProvider
     */
    public function testValidateUnique($value, $expected = false, $fid = null, $id = null)
    {
        $args = array('user', 'username', $fid, $id);

        $this->assertEquals($expected, $this->validator->validate('unique', $value, $args));
    }
}

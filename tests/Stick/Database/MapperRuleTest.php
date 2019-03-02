<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Feb 09, 2019 18:40
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Database;

use Fal\Stick\Database\MapperRule;
use Fal\Stick\TestSuite\TestCase;

class MapperRuleTest extends TestCase
{
    private $rule;

    public function setup()
    {
        $this->prepare()->connect()->buildSchema()->initUser();

        $this->rule = new MapperRule($this->container);
    }

    /**
     * @dataProvider validateExistsProvider
     */
    public function testValidateExists($value, $expected = true)
    {
        $args = array('user', 'username');

        $this->assertEquals($expected, $this->rule->validate('exists', $value, $args));
    }

    /**
     * @dataProvider validateUniqueProvider
     */
    public function testValidateUnique($value, $expected = false, $fid = null, $id = null)
    {
        $args = array('user', 'username', $fid, $id);

        $this->assertEquals($expected, $this->rule->validate('unique', $value, $args));
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

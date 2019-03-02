<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 14, 2019 07:07
 */

namespace Fal\Stick\Test\Validation\Rules;

use PHPUnit\Framework\TestCase;
use Fal\Stick\Validation\Result;
use Fal\Stick\Validation\Rules\CommonRule;

class CommonRuleTest extends TestCase
{
    private $rule;

    public function setup()
    {
        $this->rule = new CommonRule();
    }

    /**
     * @dataProvider hasProvider
     */
    public function testHas($rule, $expected = true)
    {
        $this->assertEquals($expected, $this->rule->has($rule));
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate($rule, $expected, $value, array $arguments = array(), Result $context = null)
    {
        if ($context) {
            $this->rule->context($context);
        }

        $this->assertEquals($expected, $this->rule->validate($rule, $value, $arguments));
    }

    public function hasProvider()
    {
        return array(
            array('trim'),
            array('required'),
            array('type'),
            array('min'),
            array('max'),
            array('lt'),
            array('gt'),
            array('lte'),
            array('gte'),
            array('equaltofield'),
            array('notequaltofield'),
            array('equalto'),
            array('notequalto'),
            array('identicalto'),
            array('notidenticalto'),
            array('len'),
            array('lenmin'),
            array('lenmax'),
            array('count'),
            array('countmin'),
            array('countmax'),
            array('regex'),
            array('choice'),
            array('choices'),
            array('date'),
            array('datetime'),
            array('todate'),
            array('foo', false),
        );
    }

    public function validateProvider()
    {
        $equalContext2 = new Result(array());
        $equalContext2->data = array('foo' => 'bar');

        return array(
            array('trim', 'foo', ' foo '),
            array('trim', '', null),
            array('required', true, 'foo'),
            array('required', false, null),
            array('required', false, ''),
            array('date', true, '2010-10-10'),
            array('date', false, '2010-10-32'),
            array('date', false, '2010-13-10'),
            array('date', false, '2010-13'),
            array('datetime', true, '2010-10-10 00:00:00'),
            array('datetime', false, '2010-10-10 00:00:70'),
            array('datetime', false, '2010-10-10 24:00:00'),
            array('datetime', false, '2010-10-32 00:00:00'),
            array('datetime', false, '2010-13-10 00:00:00'),
            array('todate', '2010-10-10', '2010-10-10'),
            array('todate', '2010-10-10', 'Oct 10, 2010'),
            array('todate', 'Oct 32, 2010', 'Oct 32, 2010'),
            array('type', true, 1, array('integer')),
            array('type', true, array(), array('array')),
            array('min', true, 1, array(1)),
            array('min', true, 3, array(2)),
            array('min', false, 1, array(2)),
            array('max', true, 1, array(1)),
            array('max', true, 1, array(2)),
            array('max', false, 3, array(2)),
            array('lt', true, 1, array(2)),
            array('lt', false, 2, array(2)),
            array('gt', true, 2, array(1)),
            array('gt', false, 1, array(2)),
            array('lte', true, 1, array(2)),
            array('lte', true, 2, array(2)),
            array('lte', false, 3, array(2)),
            array('gte', true, 3, array(2)),
            array('gte', true, 2, array(2)),
            array('gte', false, 1, array(2)),
            array('equalto', true, 'foo', array('foo')),
            array('equalto', false, 'bar', array('foo')),
            array('notequalto', true, 'bar', array('foo')),
            array('notequalto', false, 'foo', array('foo')),
            array('identicalto', true, 1, array(1)),
            array('identicalto', false, '1', array(1)),
            array('notidenticalto', true, '1', array(1)),
            array('notidenticalto', false, 1, array(1)),
            array('len', true, 'foo', array(3)),
            array('len', false, 'fo', array(3)),
            array('lenmin', true, 'foo', array(3)),
            array('lenmin', true, 'foobar', array(3)),
            array('lenmin', false, 'fo', array(3)),
            array('lenmax', true, 'foo', array(3)),
            array('lenmax', true, 'fo', array(3)),
            array('lenmax', false, 'foobar', array(3)),
            array('count', true, array(1, 2, 3), array(3)),
            array('count', false, array(1, 2), array(3)),
            array('countmin', true, array(1, 2, 3), array(3)),
            array('countmin', true, array(1, 2, 3, 4), array(3)),
            array('countmin', false, array(1, 2), array(3)),
            array('countmax', true, array(1, 2, 3), array(3)),
            array('countmax', true, array(1, 2), array(3)),
            array('countmax', false, array(1, 2, 3, 4), array(3)),
            array('regex', true, 'foo', array('/^foo$/')),
            array('regex', false, 'bar', array('/^foo$/')),
            array('regex', true, 'foo', array('"/^foo$/"')),
            array('choice', true, 'foo', array(array('foo', 'bar'))),
            array('choice', false, 'baz', array(array('foo', 'bar'))),
            array('choices', true, array('foo', 'bar'), array(array('foo', 'bar', 'baz'))),
            array('choices', false, array('foo', 'qux'), array(array('foo', 'bar', 'baz'))),
            array('equaltofield', true, 'bar', array('foo'), new Result(array('foo' => 'bar'))),
            array('equaltofield', false, 'foo', array('foo'), new Result(array('foo' => 'bar'))),
            array('equaltofield', false, 'foo', array('bar'), new Result(array('foo' => 'bar'))),
            array('equaltofield', true, 'bar', array('foo'), $equalContext2),
            array('notequaltofield', true, 'foo', array('foo'), new Result(array('foo' => 'bar'))),
            array('notequaltofield', true, 'foo', array('bar'), new Result(array('foo' => 'bar'))),
            array('notequaltofield', false, 'bar', array('foo'), new Result(array('foo' => 'bar'))),
            array('notequaltofield', true, 'foo', array('foo'), $equalContext2),
        );
    }
}

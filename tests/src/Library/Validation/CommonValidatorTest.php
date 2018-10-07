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

namespace Fal\Stick\Test\Library\Validation;

use Fal\Stick\Library\Validation\CommonValidator;
use PHPUnit\Framework\TestCase;

class CommonValidatorTest extends TestCase
{
    private $validator;

    public function setUp()
    {
        $this->validator = new CommonValidator();
    }

    public function ruleCheckProvider()
    {
        return array(
            array('trim'),
            array('ltrim'),
            array('rtrim'),
            array('required'),
            array('type'),
            array('min'),
            array('max'),
            array('lt'),
            array('gt'),
            array('lte'),
            array('gte'),
            array('equalfield'),
            array('notequalfield'),
            array('equal'),
            array('notequal'),
            array('identical'),
            array('notidentical'),
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
            array('convertdate'),
            array('email'),
            array('url'),
            array('ipv4'),
            array('ipv6'),
            array('isprivate'),
            array('ispublic'),
            array('isreserved'),
            array('foo', false),
        );
    }

    /**
     * @dataProvider ruleCheckProvider
     */
    public function testHas($rule, $expected = true)
    {
        $this->assertEquals($expected, $this->validator->has($rule));
    }

    public function validateProvider()
    {
        return array(
            array('trim', 'foo', ' foo '),
            array('trim', '', null),
            array('ltrim', 'foo ', ' foo '),
            array('ltrim', '', null),
            array('rtrim', ' foo', ' foo '),
            array('rtrim', '', null),
            array('required', true, 'foo'),
            array('required', false, null),
            array('required', false, ''),
            array('email', true, 'foo@bar.com'),
            array('email', false, 'foo@bar'),
            array('url', true, 'http://foo.com'),
            array('url', false, 'http'),
            array('ipv4', true, '30.88.29.1'),
            array('ipv4', false, '172.300.256.100'),
            array('ipv6', true, '2001:DB8:0:0:8:800:200C:417A'),
            array('ipv6', false, 'FF02:0000:0000:0000:0000:0000:0000:0000:0001'),
            array('isprivate', true, '10.10.10.10'),
            array('isprivate', false, '201.176.14.4'),
            array('ispublic', true, '180.1.1.0'),
            array('ispublic', false, '10.10.10.10'),
            array('isreserved', true, '240.241.242.243'),
            array('isreserved', false, '193.194.195.196'),
            array('date', true, '2010-10-10'),
            array('date', false, '2010-10-32'),
            array('date', false, '2010-13-10'),
            array('date', false, '2010-13'),
            array('datetime', true, '2010-10-10 00:00:00'),
            array('datetime', false, '2010-10-10 00:00:70'),
            array('datetime', false, '2010-10-10 24:00:00'),
            array('datetime', false, '2010-10-32 00:00:00'),
            array('datetime', false, '2010-13-10 00:00:00'),
            array('convertdate', '2010-10-10', '2010-10-10'),
            array('convertdate', '2010-10-10', 'Oct 10, 2010'),
            array('convertdate', 'Oct 32, 2010', 'Oct 32, 2010'),
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
            array('equal', true, 'foo', array('foo')),
            array('equal', false, 'bar', array('foo')),
            array('notequal', true, 'bar', array('foo')),
            array('notequal', false, 'foo', array('foo')),
            array('identical', true, 1, array(1)),
            array('identical', false, '1', array(1)),
            array('notidentical', true, '1', array(1)),
            array('notidentical', false, 1, array(1)),
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
            array('equalfield', true, 'bar', array('foo'), array('foo' => 'bar')),
            array('equalfield', false, 'foo', array('foo'), array('foo' => 'bar')),
            array('equalfield', false, 'foo', array('bar'), array('foo' => 'bar')),
            array('equalfield', true, 'bar', array('foo'), null, array('foo' => 'bar')),
            array('notequalfield', true, 'foo', array('foo'), array('foo' => 'bar')),
            array('notequalfield', true, 'foo', array('bar'), array('foo' => 'bar')),
            array('notequalfield', false, 'bar', array('foo'), array('foo' => 'bar')),
            array('notequalfield', true, 'foo', array('foo'), null, array('foo' => 'bar')),
        );
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate($rule, $expected, $value, array $args = null, array $validated = null, array $raw = null)
    {
        $this->assertEquals($expected, $this->validator->validate($rule, $value, $args, null, $validated, $raw));
    }
}

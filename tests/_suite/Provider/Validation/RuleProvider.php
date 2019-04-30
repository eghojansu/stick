<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\TestSuite\Provider\Validation;

use Fal\Stick\Validation\Context;

class RuleProvider
{
    private static function context($value = null, $arguments = null, $validated = null, $data = null, $field = 'foo')
    {
        $data[$field] = $value;

        $context = new Context($data);
        $context->setField($field);
        $context->setArguments((array) $arguments);
        $context->setValidated((array) $validated);

        return $context;
    }

    public function commonRules()
    {
        return array(
            array(false, 'foo'),
            array(true, 'trim'),
            array(true, 'ltrim'),
            array(true, 'rtrim'),
            array(true, 'required'),
            array(true, 'type'),
            array(true, 'min'),
            array(true, 'max'),
            array(true, 'lt'),
            array(true, 'gt'),
            array(true, 'lte'),
            array(true, 'gte'),
            array(true, 'equaltofield'),
            array(true, 'notequaltofield'),
            array(true, 'equalto'),
            array(true, 'notequalto'),
            array(true, 'identicalto'),
            array(true, 'notidenticalto'),
            array(true, 'len'),
            array(true, 'lenmin'),
            array(true, 'lenmax'),
            array(true, 'count'),
            array(true, 'countmin'),
            array(true, 'countmax'),
            array(true, 'regex'),
            array(true, 'choice'),
            array(true, 'choices'),
            array(true, 'date'),
            array(true, 'datetime'),
            array(true, 'todate'),
        );
    }

    public function commonValidations()
    {
        return array(
            array('foo', 'trim', self::context(' foo ')),
            array('', 'trim', self::context()),
            array('foo ', 'ltrim', self::context(' foo ')),
            array('', 'ltrim', self::context()),
            array(' foo', 'rtrim', self::context(' foo ')),
            array('', 'rtrim', self::context()),
            array(true, 'required', self::context('foo')),
            array(false, 'required', self::context()),
            array(false, 'required', self::context('')),
            array(true, 'date', self::context('2010-10-10')),
            array(false, 'date', self::context('2010-10-32')),
            array(false, 'date', self::context('2010-13-10')),
            array(false, 'date', self::context('2010-13')),
            array(true, 'datetime', self::context('2010-10-10 00:00:00')),
            array(false, 'datetime', self::context('2010-10-10 00:00:70')),
            array(false, 'datetime', self::context('2010-10-10 24:00:00')),
            array(false, 'datetime', self::context('2010-10-32 00:00:00')),
            array(false, 'datetime', self::context('2010-13-10 00:00:00')),
            array('2010-10-10', 'todate', self::context('2010-10-10')),
            array('2010-10-10', 'todate', self::context('Oct 10, 2010')),
            array('Oct 32, 2010', 'todate', self::context('Oct 32, 2010')),
            array(true, 'type', self::context(1, array('integer'))),
            array(true, 'type', self::context(array(), array('array'))),
            array(true, 'min', self::context(1, array(1))),
            array(true, 'min', self::context(3, array(2))),
            array(false, 'min', self::context(1, array(2))),
            array(true, 'max', self::context(1, array(1))),
            array(true, 'max', self::context(1, array(2))),
            array(false, 'max', self::context(3, array(2))),
            array(true, 'lt', self::context(1, array(2))),
            array(false, 'lt', self::context(2, array(2))),
            array(true, 'gt', self::context(2, array(1))),
            array(false, 'gt', self::context(1, array(2))),
            array(true, 'lte', self::context(1, array(2))),
            array(true, 'lte', self::context(2, array(2))),
            array(false, 'lte', self::context(3, array(2))),
            array(true, 'gte', self::context(3, array(2))),
            array(true, 'gte', self::context(2, array(2))),
            array(false, 'gte', self::context(1, array(2))),
            array(true, 'equalto', self::context('foo', array('foo'))),
            array(false, 'equalto', self::context('bar', array('foo'))),
            array(true, 'notequalto', self::context('bar', array('foo'))),
            array(false, 'notequalto', self::context('foo', array('foo'))),
            array(true, 'identicalto', self::context(1, array(1))),
            array(false, 'identicalto', self::context('1', array(1))),
            array(true, 'notidenticalto', self::context('1', array(1))),
            array(false, 'notidenticalto', self::context(1, array(1))),
            array(true, 'len', self::context('foo', array(3))),
            array(false, 'len', self::context('fo', array(3))),
            array(true, 'lenmin', self::context('foo', array(3))),
            array(true, 'lenmin', self::context('foobar', array(3))),
            array(false, 'lenmin', self::context('fo', array(3))),
            array(true, 'lenmax', self::context('foo', array(3))),
            array(true, 'lenmax', self::context('fo', array(3))),
            array(false, 'lenmax', self::context('foobar', array(3))),
            array(true, 'count', self::context(array(1, 2, 3), array(3))),
            array(false, 'count', self::context(array(1, 2), array(3))),
            array(true, 'countmin', self::context(array(1, 2, 3), array(3))),
            array(true, 'countmin', self::context(array(1, 2, 3, 4), array(3))),
            array(false, 'countmin', self::context(array(1, 2), array(3))),
            array(true, 'countmax', self::context(array(1, 2, 3), array(3))),
            array(true, 'countmax', self::context(array(1, 2), array(3))),
            array(false, 'countmax', self::context(array(1, 2, 3, 4), array(3))),
            array(true, 'regex', self::context('foo', array('/^foo$/'))),
            array(false, 'regex', self::context('bar', array('/^foo$/'))),
            array(true, 'regex', self::context('foo', array('"/^foo$/"'))),
            array(true, 'choice', self::context('foo', array(array('foo', 'bar')))),
            array(false, 'choice', self::context('baz', array(array('foo', 'bar')))),
            array(true, 'choices', self::context(array('foo', 'bar'), array(array('foo', 'bar', 'baz')))),
            array(false, 'choices', self::context(array('foo', 'qux'), array(array('foo', 'bar', 'baz')))),
            array(true, 'equaltofield', self::context('baz', array('bar'), array('bar' => 'baz'))),
            array(false, 'equaltofield', self::context('bar', array('bar'), array('bar' => 'baz'))),
            array(false, 'equaltofield', self::context('bar', array('bar'))),
            array(true, 'equaltofield', self::context('baz', array('bar'), null, array('bar' => 'baz'))),
            array(true, 'notequaltofield', self::context('bar', array('bar'), array('bar' => 'baz'))),
            array(true, 'notequaltofield', self::context('bar', array('bar'))),
            array(false, 'notequaltofield', self::context('baz', array('bar'), array('bar' => 'baz'))),
            array(true, 'notequaltofield', self::context('bar', array('bar'), null, array('bar' => 'baz'))),
        );
    }

    public function urlRules()
    {
        return array(
            array(true, 'email'),
            array(true, 'url'),
            array(true, 'ipv4'),
            array(true, 'ipv6'),
            array(true, 'isprivate'),
            array(true, 'ispublic'),
            array(true, 'isreserved'),
        );
    }

    public function urlValidations()
    {
        return array(
            array(true, 'email', self::context('foo@bar.com')),
            array(false, 'email', self::context('foo@bar')),
            array(true, 'url', self::context('http://foo.com')),
            array(false, 'url', self::context('http')),
            array(true, 'ipv4', self::context('30.88.29.1')),
            array(false, 'ipv4', self::context('172.300.256.100')),
            array(true, 'ipv6', self::context('2001:DB8:0:0:8:800:200C:417A')),
            array(false, 'ipv6', self::context('FF02:0000:0000:0000:0000:0000:0000:0000:0001')),
            array(true, 'isprivate', self::context('10.10.10.10')),
            array(false, 'isprivate', self::context('201.176.14.4')),
            array(true, 'ispublic', self::context('180.1.1.0')),
            array(false, 'ispublic', self::context('10.10.10.10')),
            array(true, 'isreserved', self::context('240.241.242.243')),
            array(false, 'isreserved', self::context('193.194.195.196')),
        );
    }

    public function mapperValidateExists()
    {
        return array(
            array(true, self::context('foo', array('user', 'username'))),
            array(true, self::context('bar', array('user', 'username'))),
            array(true, self::context('baz', array('user', 'username'))),
            array(false, self::context('qux', array('user', 'username'))),
        );
    }

    public function mapperValidateUnique()
    {
        return array(
            array(false, self::context('foo', array('user', 'username'))),
            array(false, self::context('bar', array('user', 'username'))),
            array(false, self::context('baz', array('user', 'username'))),
            array(false, self::context('foo', array('user', 'username', 'id', '2'))),
            array(true, self::context('foo', array('user', 'username', 'id', '1'))),
            array(true, self::context('qux', array('user', 'username'))),
        );
    }
}

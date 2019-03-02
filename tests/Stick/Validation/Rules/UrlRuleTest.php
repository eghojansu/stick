<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 14, 2019 08:27
 */

namespace Fal\Stick\Test\Validation\Rules;

use Fal\Stick\Validation\Rules\UrlRule;
use PHPUnit\Framework\TestCase;

class UrlRuleTest extends TestCase
{
    private $rule;

    public function setUp()
    {
        $this->rule = new UrlRule();
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
    public function testValidate($rule, $expected, $value, array $arguments = array())
    {
        $this->assertEquals($expected, $this->rule->validate($rule, $value, $arguments));
    }

    public function hasProvider()
    {
        return array(
            array('email'),
            array('url'),
            array('ipv4'),
            array('ipv6'),
            array('isprivate'),
            array('ispublic'),
            array('isreserved'),
        );
    }

    public function validateProvider()
    {
        return array(
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
        );
    }
}

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

namespace Fal\Stick\Test\Validation;

use Fal\Stick\Validation\UrlValidator;
use PHPUnit\Framework\TestCase;

class UrlValidatorTest extends TestCase
{
    private $validator;

    public function setUp()
    {
        $this->validator = new UrlValidator();
    }

    /**
     * @dataProvider getRules
     */
    public function testHas($rule, $expected = true)
    {
        $this->assertEquals($expected, $this->validator->has($rule));
    }

    /**
     * @dataProvider getValidations
     */
    public function testValidate($rule, $expected, $value, array $args = null, array $validated = null, array $raw = null)
    {
        $this->assertEquals($expected, $this->validator->validate($rule, $value, $args, null, $validated, $raw));
    }

    public function getRules()
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

    public function getValidations()
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

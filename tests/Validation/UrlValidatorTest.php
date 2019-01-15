<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

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
     * @dataProvider hasProvider
     */
    public function testHas($rule, $expected = true)
    {
        $this->assertEquals($expected, $this->validator->has($rule));
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate($rule, $expected, $value, array $arguments = [])
    {
        $this->assertEquals($expected, $this->validator->validate($rule, $value, $arguments));
    }

    public function hasProvider()
    {
        return [
            ['email'],
            ['url'],
            ['ipv4'],
            ['ipv6'],
            ['isprivate'],
            ['ispublic'],
            ['isreserved'],
        ];
    }

    public function validateProvider()
    {
        return [
            ['email', true, 'foo@bar.com'],
            ['email', false, 'foo@bar'],
            ['url', true, 'http://foo.com'],
            ['url', false, 'http'],
            ['ipv4', true, '30.88.29.1'],
            ['ipv4', false, '172.300.256.100'],
            ['ipv6', true, '2001:DB8:0:0:8:800:200C:417A'],
            ['ipv6', false, 'FF02:0000:0000:0000:0000:0000:0000:0000:0001'],
            ['isprivate', true, '10.10.10.10'],
            ['isprivate', false, '201.176.14.4'],
            ['ispublic', true, '180.1.1.0'],
            ['ispublic', false, '10.10.10.10'],
            ['isreserved', true, '240.241.242.243'],
            ['isreserved', false, '193.194.195.196'],
        ];
    }
}

<?php

declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Unit\Validation;

use Fal\Stick\Validation\SimpleValidator;
use PHPUnit\Framework\TestCase;

class SimpleValidatorTest extends TestCase
{
    private $validator;

    public function setUp()
    {
        $this->validator = new SimpleValidator();
    }

    public function hasProvider()
    {
        return [
            ['required'],
            ['type'],
            ['min'],
            ['max'],
            ['lt'],
            ['gt'],
            ['lte'],
            ['gte'],
            ['equalfield'],
            ['notequalfield'],
            ['equal'],
            ['notequal'],
            ['identical'],
            ['notidentical'],
            ['len'],
            ['lenmin'],
            ['lenmax'],
            ['count'],
            ['countmin'],
            ['countmax'],
            ['regex'],
            ['choice'],
            ['choices'],
            ['date'],
            ['datetime'],
            ['cdate'],
            ['email'],
            ['url'],
            ['ipv4'],
            ['ipv6'],
            ['isprivate'],
            ['ispublic'],
            ['isreserved'],
            ['foo', false],
        ];
    }

    /**
     * @dataProvider hasProvider
     */
    public function testHas($rule, $expected = true)
    {
        $this->assertEquals($expected, $this->validator->has($rule));
    }

    public function validateProvider()
    {
        return [
            ['required', false],
            ['required', true, ['bar']],
            ['type', false, ['1', 'integer']],
            ['type', true, [1, 'integer']],
            ['min', false, [0, 1]],
            ['min', true, [1, 1]],
            ['max', false, [2, 1]],
            ['max', true, [0, 1]],
            ['lt', false, [1, 1]],
            ['lt', true, [0, 1]],
            ['gt', false, [1, 1]],
            ['gt', true, [2, 1]],
            ['lte', false, [2, 1]],
            ['lte', true, [1, 1]],
            ['gte', false, [0, 1]],
            ['gte', true, [1, 1]],
            ['equalfield', false, ['baz', 'bar']],
            ['equalfield', true, ['baz', 'bar'], ['bar' => 'baz']],
            ['notequalfield', false, ['baz', 'bar'], ['bar' => 'baz']],
            ['notequalfield', false, ['baz', 'bar']],
            ['notequalfield', true, ['baz', 'bar'], ['bar' => 'foo']],
            ['equal', false, ['bar', 'baz']],
            ['equal', true, ['bar', 'bar']],
            ['notequal', false, ['bar', 'bar']],
            ['notequal', true, ['bar', 'baz']],
            ['identical', false, [1, '1', 'string']],
            ['identical', true, ['1', '1', 'string']],
            ['notidentical', false, ['1', '1', 'string']],
            ['notidentical', true, [1, '1', 'string']],
            ['len', false, ['ba', 3]],
            ['len', true, ['bar', 3]],
            ['lenmin', false, ['ba', 3]],
            ['lenmin', true, ['bar', 3]],
            ['lenmax', false, ['barbaz', 3]],
            ['lenmax', true, ['bar', 3]],
            ['count', false, [[], 1]],
            ['count', true, [['bar'], 1]],
            ['countmin', false, [[], 1]],
            ['countmin', true, [['bar'], 1]],
            ['countmax', false, [[1, 2], 1]],
            ['countmax', true, [['bar'], 1]],
            ['regex', false, ['fo', '/^foo$/']],
            ['regex', true, ['foo', '/^foo$/']],
            ['regex', true, ['foo', '"/^foo$/"']],
            ['regex', true, ['foo', "'/^foo$/'"]],
            ['choice', false, ['baz', ['foo', 'bar']]],
            ['choice', true, ['foo', ['foo', 'bar']]],
            ['choices', false, [['foo', 'baz'], ['foo', 'bar']]],
            ['choices', true, [['foo', 'bar'], ['foo', 'bar']]],
            ['date', false, ['2010-10']],
            ['date', true, ['2010-10-10']],
            ['datetime', false, ['2010-10-10 10:10']],
            ['datetime', true, ['2010-10-10 10:10:10']],
            ['cdate', '2010-13-10', ['2010-13-10', 'd-m-Y']],
            ['cdate', '10-10-2010', ['2010-10-10', 'd-m-Y']],
            ['email', false, ['bar@mail']],
            ['email', true, ['bar@mail.com']],
            ['url', false, ['http']],
            ['url', true, ['http://foo.com']],
            ['ipv4', false, ['172.300.256.100']],
            ['ipv4', true, ['30.88.29.1']],
            ['ipv6', false, ['FF02:0000:0000:0000:0000:0000:0000:0000:0001']],
            ['ipv6', true, ['2001:DB8:0:0:8:800:200C:417A']],
            ['isprivate', false, ['201.176.14.4']],
            ['isprivate', true, ['10.10.10.10']],
            ['ispublic', false, ['10.10.10.10']],
            ['ispublic', true, ['180.1.1.0']],
            ['isreserved', false, ['193.194.195.196']],
            ['isreserved', true, ['240.241.242.243']],
        ];
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate($rule, $expected, $args = [], $validated = [])
    {
        $this->assertEquals($expected, $this->validator->validate($rule, array_shift($args), $args, '', $validated));
    }

    public function messageProvider()
    {
        return [
            ['required', 'This value should not be blank.'],
            ['type', 'This value should be of type string.', ['string']],
            ['min', 'This value should be 1 or more.', [1]],
            ['max', 'This value should be 1 or less.', [1]],
            ['lt', 'This value should be less than 1.', [1]],
            ['gt', 'This value should be greater than 1.', [1]],
            ['lte', 'This value should be less than or equal to 1.', [1]],
            ['gte', 'This value should be greater than or equal to 1.', [1]],
            ['equalfield', 'This value should be equal to value of 1.', [1]],
            ['notequalfield', 'This value should not be equal to value of 1.', [1]],
            ['equal', 'This value should be equal to 1.', [1]],
            ['notequal', 'This value should not be equal to 1.', [1]],
            ['identical', 'This value should be identical to integer 1.', [1, 'integer']],
            ['notidentical', 'This value should not be identical to integer 1.', [1, 'integer']],
            ['len', 'This value is not valid. It should have exactly 1 characters.', [1]],
            ['lenmin', 'This value is too short. It should have 1 characters or more.', [1]],
            ['lenmax', 'This value is too long. It should have 1 characters or less.', [1]],
            ['count', 'This collection should contain exactly 1 elements.', [1]],
            ['countmin', 'This collection should contain 1 elements or more.', [1]],
            ['countmax', 'This collection should contain 1 elements or less.', [1]],
            ['regex', 'This value is not valid.'],
            ['choice', 'The value you selected is not a valid choice.'],
            ['choices', 'One or more of the given values is invalid.'],
            ['date', 'This value is not a valid date.'],
            ['datetime', 'This value is not a valid datetime.'],
            ['email', 'This value is not a valid email address.'],
            ['url', 'This value is not a valid url.'],
            ['ipv4', 'This value is not a valid ipv4 address.'],
            ['ipv6', 'This value is not a valid ipv6 address.'],
            ['isprivate', 'This value is not a private ip address.'],
            ['isreserved', 'This value is not a reserved ip address.'],
            ['ispublic', 'This value is not a public ip address.'],
        ];
    }

    /**
     * @dataProvider messageProvider
     */
    public function testMessage($rule, $message, $args = [])
    {
        $this->assertEquals($message, $this->validator->message($rule, null, $args));
    }
}

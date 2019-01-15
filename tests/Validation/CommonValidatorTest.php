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

use Fal\Stick\Validation\CommonValidator;
use Fal\Stick\Validation\Result;
use PHPUnit\Framework\TestCase;

class CommonValidatorTest extends TestCase
{
    private $validator;

    public function setup()
    {
        $this->validator = new CommonValidator();
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
    public function testValidate($rule, $expected, $value, array $arguments = [], Result $context = null)
    {
        if ($context) {
            $this->validator->context($context);
        }

        $this->assertEquals($expected, $this->validator->validate($rule, $value, $arguments));
    }

    public function hasProvider()
    {
        return [
            ['trim'],
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
            ['todate'],
            ['foo', false],
        ];
    }

    public function validateProvider()
    {
        $equalContext2 = new Result([]);
        $equalContext2->data = ['foo' => 'bar'];

        return [
            ['trim', 'foo', ' foo '],
            ['trim', '', null],
            ['required', true, 'foo'],
            ['required', false, null],
            ['required', false, ''],
            ['date', true, '2010-10-10'],
            ['date', false, '2010-10-32'],
            ['date', false, '2010-13-10'],
            ['date', false, '2010-13'],
            ['datetime', true, '2010-10-10 00:00:00'],
            ['datetime', false, '2010-10-10 00:00:70'],
            ['datetime', false, '2010-10-10 24:00:00'],
            ['datetime', false, '2010-10-32 00:00:00'],
            ['datetime', false, '2010-13-10 00:00:00'],
            ['todate', '2010-10-10', '2010-10-10'],
            ['todate', '2010-10-10', 'Oct 10, 2010'],
            ['todate', 'Oct 32, 2010', 'Oct 32, 2010'],
            ['type', true, 1, ['integer']],
            ['type', true, [], ['array']],
            ['min', true, 1, [1]],
            ['min', true, 3, [2]],
            ['min', false, 1, [2]],
            ['max', true, 1, [1]],
            ['max', true, 1, [2]],
            ['max', false, 3, [2]],
            ['lt', true, 1, [2]],
            ['lt', false, 2, [2]],
            ['gt', true, 2, [1]],
            ['gt', false, 1, [2]],
            ['lte', true, 1, [2]],
            ['lte', true, 2, [2]],
            ['lte', false, 3, [2]],
            ['gte', true, 3, [2]],
            ['gte', true, 2, [2]],
            ['gte', false, 1, [2]],
            ['equal', true, 'foo', ['foo']],
            ['equal', false, 'bar', ['foo']],
            ['notequal', true, 'bar', ['foo']],
            ['notequal', false, 'foo', ['foo']],
            ['identical', true, 1, [1]],
            ['identical', false, '1', [1]],
            ['notidentical', true, '1', [1]],
            ['notidentical', false, 1, [1]],
            ['len', true, 'foo', [3]],
            ['len', false, 'fo', [3]],
            ['lenmin', true, 'foo', [3]],
            ['lenmin', true, 'foobar', [3]],
            ['lenmin', false, 'fo', [3]],
            ['lenmax', true, 'foo', [3]],
            ['lenmax', true, 'fo', [3]],
            ['lenmax', false, 'foobar', [3]],
            ['count', true, [1, 2, 3], [3]],
            ['count', false, [1, 2], [3]],
            ['countmin', true, [1, 2, 3], [3]],
            ['countmin', true, [1, 2, 3, 4], [3]],
            ['countmin', false, [1, 2], [3]],
            ['countmax', true, [1, 2, 3], [3]],
            ['countmax', true, [1, 2], [3]],
            ['countmax', false, [1, 2, 3, 4], [3]],
            ['regex', true, 'foo', ['/^foo$/']],
            ['regex', false, 'bar', ['/^foo$/']],
            ['regex', true, 'foo', ['"/^foo$/"']],
            ['choice', true, 'foo', [['foo', 'bar']]],
            ['choice', false, 'baz', [['foo', 'bar']]],
            ['choices', true, ['foo', 'bar'], [['foo', 'bar', 'baz']]],
            ['choices', false, ['foo', 'qux'], [['foo', 'bar', 'baz']]],
            ['equalfield', true, 'bar', ['foo'], new Result(['foo' => 'bar'])],
            ['equalfield', false, 'foo', ['foo'], new Result(['foo' => 'bar'])],
            ['equalfield', false, 'foo', ['bar'], new Result(['foo' => 'bar'])],
            ['equalfield', true, 'bar', ['foo'], $equalContext2],
            ['notequalfield', true, 'foo', ['foo'], new Result(['foo' => 'bar'])],
            ['notequalfield', true, 'foo', ['bar'], new Result(['foo' => 'bar'])],
            ['notequalfield', false, 'bar', ['foo'], new Result(['foo' => 'bar'])],
            ['notequalfield', true, 'foo', ['foo'], $equalContext2],
        ];
    }
}

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

namespace Ekok\Stick\Tests\Validation;

use Ekok\Stick\Validation\Context;
use PHPUnit\Framework\TestCase;
use Ekok\Stick\Validation\DefaultProvider;

class DefaultProviderTest extends TestCase
{
    /** @var DefaultProvider */
    private $provider;

    public function setUp(): void
    {
        $this->provider = new DefaultProvider();
    }

    public function testIsUsable()
    {
        $this->provider->addMessages(array('foo' => 'bar'));

        $this->assertTrue($this->provider->check('trim'));
        $this->assertFalse($this->provider->check('foo'));
        $this->assertEquals('trimmed', $this->provider->validate('trim', new Context('foo', ' trimmed ')));
        $this->assertEquals(array('bar'), $this->provider->getMessages()['foo']);
    }

    public function testMessage()
    {
        $this->provider->addMessage('custom', 'Value: {value}; First Argument: {argument_0}; Arguments: {arguments}.');

        $this->assertEquals('This value is not valid.', $this->provider->message('foo', new Context('foo', 'bar')));
        $this->assertEquals('This value should be accepted.', $this->provider->message('accepted', new Context('foo', 'bar')));
        $this->assertEquals("This value should be after 'yesterday'.", $this->provider->message('after', new Context('foo', 'bar'), 'yesterday'));
        $this->assertEquals("Value: 'bar'; First Argument: 'baz'; Arguments: ['baz','qux'].", $this->provider->message('custom', new Context('foo', 'bar'), 'baz', 'qux'));
    }

    /** @dataProvider getRules */
    public function testRules($expected, string $rule, ...$arguments)
    {
        $actual = DefaultProvider::$rule(...$arguments);

        $this->assertEquals($expected, $actual);
    }

    public function getRules()
    {
        $today = new \DateTime();

        yield '_rule_accepted' => array(
            true,
            '_rule_accepted',
            new Context('foo', 'true'),
        );

        yield '_rule_after' => array(
            true,
            '_rule_after',
            new Context('foo', 'today', array(
                'raw' => array('bar' => 'yesterday'),
            )),
            'bar',
        );

        yield '_rule_after_or_equal' => array(
            true,
            '_rule_after_or_equal',
            new Context('foo', 'today', array(
                'raw' => array('bar' => 'yesterday'),
            )),
            'bar',
        );

        yield '_rule_alpha' => array(
            true,
            '_rule_alpha',
            new Context('foo', 'bar'),
        );

        yield '_rule_alnum' => array(
            true,
            '_rule_alnum',
            new Context('foo', 'bar123'),
        );

        yield '_rule_array' => array(
            true,
            '_rule_array',
            new Context('foo', array(1, 2, 3)),
        );

        yield '_rule_before' => array(
            true,
            '_rule_before',
            new Context('foo', 'today', array(
                'raw' => array('bar' => 'tomorrow'),
            )),
            'bar',
        );

        yield '_rule_before_or_equal' => array(
            true,
            '_rule_before_or_equal',
            new Context('foo', 'today', array(
                'raw' => array('bar' => 'tomorrow'),
            )),
            'bar',
        );

        yield '_rule_between' => array(
            true,
            '_rule_between',
            new Context('foo', 'bar'),
            2,
            4,
        );

        yield '_rule_boolean' => array(
            true,
            '_rule_boolean',
            new Context('foo', 'true'),
        );

        yield '_rule_confirmed' => array(
            true,
            '_rule_confirmed',
            new Context('foo', 'bar', array(
                'raw' => array('foo_confirmation' => 'bar'),
            )),
        );

        yield '_rule_date' => array(
            true,
            '_rule_date',
            new Context('foo', 'today'),
        );

        yield '_rule_date_equal' => array(
            true,
            '_rule_date_equal',
            new Context('foo', 'today'),
            'today',
        );

        yield '_rule_date_format' => array(
            true,
            '_rule_date_format',
            new Context('foo', $today->format('Y-m-d')),
            'Y-m-d',
        );

        yield '_rule_different' => array(
            true,
            '_rule_different',
            new Context('foo', 'bar'),
            'baz',
        );

        yield '_rule_digits' => array(
            true,
            '_rule_digits',
            new Context('foo', '123'),
        );

        yield '_rule_digits_between' => array(
            true,
            '_rule_digits_between',
            new Context('foo', '123'),
            1,
            3,
        );

        yield '_rule_distinct' => array(
            true,
            '_rule_distinct',
            new Context('foo', array(1,2,3)),
        );

        yield '_rule_email' => array(
            true,
            '_rule_email',
            new Context('foo', 'email@example.com'),
        );

        yield '_rule_ends_with' => array(
            true,
            '_rule_ends_with',
            new Context('foo', 'email@example.com'),
            '.com',
        );

        yield '_rule_exclude' => array(
            true,
            '_rule_exclude',
            new Context('foo', 'bar'),
        );

        yield '_rule_exclude_if' => array(
            true,
            '_rule_exclude_if',
            new Context('foo', 'bar'),
            static function() {
                return true;
            },
        );

        yield '_rule_exclude_unless' => array(
            true,
            '_rule_exclude_unless',
            new Context('foo', 'bar'),
            static function () {
                return true;
            },
        );

        yield '_rule_gt' => array(
            true,
            '_rule_gt',
            new Context('foo', 'bar', array(
                'raw' => array('bar' => 'ba'),
            )),
            'bar',
        );

        yield '_rule_gte' => array(
            true,
            '_rule_gte',
            new Context('foo', 'bar', array(
                'raw' => array('bar' => 'baz'),
            )),
            'bar',
        );

        yield '_rule_in' => array(
            true,
            '_rule_in',
            new Context('foo', 'bar'),
            'bar',
        );

        yield '_rule_in_array' => array(
            true,
            '_rule_in_array',
            new Context('foo', 'bar', array(
                'raw' => array('bar' => array('bar', 'baz')),
            )),
            'bar',
        );

        yield '_rule_integer' => array(
            true,
            '_rule_integer',
            new Context('foo', '123'),
        );

        yield '_rule_ip' => array(
            true,
            '_rule_ip',
            new Context('foo', '189.43.5.56'),
        );

        yield '_rule_ip4' => array(
            true,
            '_rule_ip4',
            new Context('foo', '189.43.5.56'),
        );

        yield '_rule_ip6' => array(
            true,
            '_rule_ip6',
            new Context('foo', '2001:0db8:85a3:0000:0000:8a2e:0370:7334'),
        );

        yield '_rule_json' => array(
            true,
            '_rule_json',
            new Context('foo', '{"bar":"baz"}'),
        );

        yield '_rule_lt' => array(
            true,
            '_rule_lt',
            new Context('foo', 'bar', array(
                'raw' => array('bar' => 'bazqux'),
            )),
            'bar',
        );

        yield '_rule_lte' => array(
            true,
            '_rule_lte',
            new Context('foo', 'bar', array(
                'raw' => array('bar' => 'baz'),
            )),
            'bar',
        );

        yield '_rule_match' => array(
            true,
            '_rule_match',
            new Context('foo', 'bar'),
            '/^bar$/',
        );

        yield '_rule_max' => array(
            true,
            '_rule_max',
            new Context('foo', 'bar'),
            3,
        );

        yield '_rule_min' => array(
            true,
            '_rule_min',
            new Context('foo', 'bar'),
            3,
        );

        yield '_rule_not_in' => array(
            true,
            '_rule_not_in',
            new Context('foo', 'bar'),
            'baz',
        );

        yield '_rule_not_match' => array(
            true,
            '_rule_not_match',
            new Context('foo', 'bar'),
            '/^abc$/',
        );

        yield '_rule_numeric' => array(
            true,
            '_rule_numeric',
            new Context('foo', '123'),
        );

        yield '_rule_optional' => array(
            true,
            '_rule_optional',
            new Context('foo', 'bar'),
        );

        yield '_rule_required' => array(
            true,
            '_rule_required',
            new Context('foo', 'bar'),
        );

        yield '_rule_required_if' => array(
            true,
            '_rule_required_if',
            new Context('foo', 'bar'),
            static function () {
                return true;
            },
        );

        yield '_rule_required_unless' => array(
            true,
            '_rule_required_unless',
            new Context('foo', 'bar'),
            static function () {
                return true;
            },
        );

        yield '_rule_same' => array(
            true,
            '_rule_same',
            new Context('foo', 'bar', array(
                'raw' => array('bar' => 'bar'),
            )),
            'bar',
        );

        yield '_rule_size' => array(
            true,
            '_rule_size',
            new Context('foo', 'bar'),
            3,
        );

        yield '_rule_starts_with' => array(
            true,
            '_rule_starts_with',
            new Context('foo', 'email@example.com'),
            'email',
        );

        yield '_rule_string' => array(
            true,
            '_rule_string',
            new Context('foo', 'bar'),
        );

        yield '_rule_url' => array(
            true,
            '_rule_url',
            new Context('foo', 'http://example.com'),
        );

        yield '_rule_trim' => array(
            'bar',
            '_rule_trim',
            new Context('foo', ' bar '),
        );

        yield '_rule_rtrim' => array(
            ' bar',
            '_rule_rtrim',
            new Context('foo', ' bar '),
        );

        yield '_rule_ltrim' => array(
            'bar ',
            '_rule_ltrim',
            new Context('foo', ' bar '),
        );
    }
}

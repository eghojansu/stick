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

namespace Fal\Stick\Test\Form;

use Fal\Stick\Form\Field;
use Fal\Stick\TestSuite\MyTestCase;

class FieldTest extends MyTestCase
{
    private $field;

    public function setup(): void
    {
        $this->field = new Field('foo', 'text');
    }

    public function testIsType()
    {
        $this->assertTrue($this->field->isType('text'));
    }

    public function testInType()
    {
        $this->assertTrue($this->field->inType(array('text')));
    }

    public function testIsButton()
    {
        $this->assertFalse($this->field->isButton());
    }

    public function testAttr()
    {
        $this->assertEquals(array(), $this->field->attr());
    }

    public function testTransform()
    {
        $this->assertNull($this->field->transform(null));

        $this->field->transformer = function ($foo) {
            return $foo.'bar';
        };

        $this->assertEquals('foobar', $this->field->transform('foo'));
    }

    public function testReverseTransform()
    {
        $this->assertNull($this->field->reverseTransform(null));

        $this->field->reverse_transformer = function ($foo) {
            return $foo.'bar';
        };

        $this->assertEquals('foobar', $this->field->reverseTransform('foo'));
    }

    public function testMagicGet()
    {
        $this->field->attr['foo'] = 'foo';
        $this->assertEquals(array('foo' => 'foo'), $this->field->attr);
    }

    public function testMagicSet()
    {
        $this->field->name = 'foo';
        $this->assertEquals('foo', $this->field->name);
    }
}

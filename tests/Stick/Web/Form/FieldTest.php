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

namespace Fal\Stick\Test\Web\Form;

use Fal\Stick\Web\Form\Field;
use PHPUnit\Framework\TestCase;

class FieldTest extends TestCase
{
    private $field;

    public function setup()
    {
        $this->field = new Field('foo', 'text', array(
            'id' => 'foo',
        ));
    }

    public function testAssignedProperty()
    {
        $this->assertEquals('foo', $this->field->id);
    }

    public function testIsType()
    {
        $this->assertTrue($this->field->isType('text'));
    }

    public function testInType()
    {
        $this->assertTrue($this->field->inType(array('text')));
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
}

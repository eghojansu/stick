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

namespace Fal\Stick\Test\Database;

use Fal\Stick\Database\Field;
use PHPUnit\Framework\TestCase;

class FieldTest extends TestCase
{
    private $field;

    public function setup()
    {
        $this->field = new Field('foo', 'bar');
    }

    public function testIsChanged()
    {
        $this->assertFalse($this->field->isChanged());
    }

    public function testGetInitial()
    {
        $this->assertEquals('bar', $this->field->getInitial());
    }

    public function testGetDefault()
    {
        $this->assertNull($this->field->getDefault());
    }

    public function testGetValue()
    {
        $this->assertEquals('bar', $this->field->getValue());
    }

    public function testSetValue()
    {
        $this->assertEquals('foo', $this->field->setValue('foo')->getValue());
        $this->assertTrue($this->field->isChanged());
    }

    public function testCommit()
    {
        $this->assertFalse($this->field->setValue('foo')->commit()->isChanged());
    }

    public function testReset()
    {
        $this->assertEquals('bar', $this->field->setValue('foo')->reset()->getValue());
    }

    public function testGetExtras()
    {
        $this->assertNull($this->field->foo);
    }
}

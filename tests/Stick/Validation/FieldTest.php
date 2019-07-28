<?php

/**
 * This file is part of the eghojansu/stick.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Validation;

use Fal\Stick\Fw;
use Fal\Stick\TestSuite\MyTestCase;
use Fal\Stick\Validation\Field;

class FieldTest extends MyTestCase
{
    private $field;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->field = new Field(
            new Fw(),
            array(
                'foo' => 'true',
            ),
            array(
                'foo' => 'true',
                'bar' => 'baz',
                'baz' => 'true',
                'date' => 'Oct 20, 1991',
            ),
            'foo',
            'optional'
        );
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\FieldProvider::parseRules
     */
    public function testParseRules($expected, $rules)
    {
        $this->assertEquals($expected, Field::parseRules($rules));
    }

    public function testValue()
    {
        $this->assertEquals('true', $this->field->value());
    }

    public function testField()
    {
        $this->assertEquals('foo', $this->field->field());
    }

    public function testExists()
    {
        $this->assertTrue($this->field->exists());
    }

    public function testUpdate()
    {
        $this->assertEquals('foo', $this->field->update('foo')->value());
    }

    public function testFieldValue()
    {
        $this->assertEquals('baz', $this->field->fieldValue('bar', null, $exists));
        $this->assertTrue($exists);

        $this->assertEquals('default', $this->field->fieldValue('qux', 'default', $exists));
        $this->assertFalse($exists);

        // set validated
        $this->field->update('bar');
        $this->assertEquals('bar', $this->field->value());
    }

    public function testRules()
    {
        $this->assertEquals(array('optional' => array()), $this->field->rules());
    }

    public function testHasRule()
    {
        $this->assertTrue($this->field->hasRule('optional'));
        $this->assertFalse($this->field->hasRule('required'));
    }

    public function testEqualTo()
    {
        $this->assertFalse($this->field->equalTo('bar'));
        $this->assertTrue($this->field->equalTo('baz'));
    }

    public function testTime()
    {
        $this->assertFalse($this->field->time());
        $this->assertEquals(strtotime('Oct 20, 1991'), $this->field->time('1991-10-20'));
        $this->assertEquals(strtotime('Oct 20, 1991'), $this->field->time('date'));
    }

    public function testFilter()
    {
        $this->assertTrue($this->field->filter(FILTER_VALIDATE_BOOLEAN));
    }

    public function testAfter()
    {
        $this->assertEquals('ue', $this->field->after('r'));
    }

    public function testBefore()
    {
        $this->assertEquals('tr', $this->field->before('u'));
    }

    public function testFile()
    {
        $this->field->fw->set('FILES.file', array(
            'error' => UPLOAD_ERR_OK,
        ));

        $this->assertFalse($this->field->file($file));
        $this->assertTrue($this->field->file($file, 'file'));
        $this->assertEquals(array(
            'error' => UPLOAD_ERR_OK,
        ), $file);
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\FieldProvider::getSize
     */
    public function testGetSize($expected, $value, $arguments = array(), $hive = null)
    {
        $this->field->update($value);

        if ($hive) {
            $this->field->fw->mset($hive);
        }

        $this->assertEquals($expected, $this->field->getSize(...$arguments));
    }

    public function testIsEmpty()
    {
        $this->assertFalse($this->field->isEmpty());
    }

    public function testIsSkip()
    {
        $this->assertFalse($this->field->isSkip());
    }

    public function testSetSkip()
    {
        $this->assertTrue($this->field->setSkip()->isSkip());
    }
}

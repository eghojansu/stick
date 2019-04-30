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

use Fal\Stick\Fw;
use Fal\Stick\Form\Form;
use Fal\Stick\TestSuite\MyTestCase;
use Fal\Stick\Validation\Validator;
use Fal\Stick\Validation\Rules\CommonRule;
use Fal\Stick\Form\FormBuilder\DivFormBuilder;

class FormTest extends MyTestCase
{
    private $fw;
    private $form;

    public function setup(): void
    {
        $this->fw = new Fw();
        $this->fw->set('CONTINUE', true);

        $validator = new Validator($this->fw);
        $validator->add(new CommonRule());
        $this->form = new Form($this->fw, $validator, new DivFormBuilder($this->fw));
    }

    public function testOffsetExists()
    {
        $this->assertFalse(isset($this->form['foo']));
    }

    public function testOffsetGet()
    {
        $ref = new \ReflectionClass($this->form);

        $validatedData = $ref->getProperty('validatedData');
        $validatedData->setAccessible(true);
        $validatedData->setValue($this->form, array(
            'validated' => 'validated',
        ));

        $initialData = $ref->getProperty('initialData');
        $initialData->setAccessible(true);
        $initialData->setValue($this->form, array(
            'initial' => 'initial',
        ));

        $formData = $ref->getProperty('formData');
        $formData->setAccessible(true);
        $formData->setValue($this->form, array(
            'form' => 'form',
        ));

        $this->assertEquals('validated', $this->form['validated']);
        $this->assertEquals('initial', $this->form['initial']);
        $this->assertEquals('form', $this->form['form']);
    }

    public function testOffsetSet()
    {
        $this->form['initial'] = 'initial';

        $this->assertEquals('initial', $this->form['initial']);
    }

    public function testOffsetUnset()
    {
        $this->form['initial'] = 'initial';
        unset($this->form['initial']);

        $this->assertNull($this->form['initial']);
    }

    public function testGetName()
    {
        $this->assertEquals('form', $this->form->getName());
    }

    public function testSetName()
    {
        $this->assertEquals('foo', $this->form->setName('foo')->getName());
    }

    public function testGetMethod()
    {
        $this->assertEquals('POST', $this->form->getMethod());
    }

    public function testSetMethod()
    {
        $this->assertEquals('FOO', $this->form->setMethod('foo')->getMethod());
    }

    public function testGetAttributes()
    {
        $this->assertEquals(array(), $this->form->getAttributes());
    }

    public function testSetAttributes()
    {
        $this->assertEquals(array('foo'), $this->form->setAttributes(array('foo'))->getAttributes());
    }

    public function testGetData()
    {
        $this->assertEquals(array(), $this->form->getData());
    }

    public function testSetData()
    {
        $this->assertEquals(array('foo' => 'bar'), $this->form->setData(array('foo' => 'bar'))->getData());
    }

    public function testGetValidatedData()
    {
        $this->assertEquals(array(), $this->form->getValidatedData());
    }

    public function testGetFormData()
    {
        $this->assertNull($this->form->getFormData());
    }

    public function testGetOptions()
    {
        $this->assertCount(0, $this->form->getOptions());
    }

    public function testSetOptions()
    {
        $this->assertCount(1, $this->form->setOptions(array('foo' => 'bar'))->getOptions());
    }

    public function testGetIgnores()
    {
        $this->assertCount(0, $this->form->getIgnores());
    }

    public function testSetIgnores()
    {
        $this->assertCount(1, $this->form->setIgnores(array('foo'))->getIgnores());
    }

    public function testIsSubmitted()
    {
        $this->fw->mset(array(
            'VERB' => 'POST',
            'POST' => array(
                '_form' => 'form',
            ),
        ));

        $this->assertTrue($this->form->isSubmitted());
        // second call
        $this->assertTrue($this->form->isSubmitted());
    }

    public function testGetResult()
    {
        $this->assertNull($this->form->getResult());
    }

    public function testHas()
    {
        $this->assertFalse($this->form->has('foo'));
    }

    public function testGet()
    {
        $this->assertNull($this->form->get('foo'));
    }

    public function testSet()
    {
        $this->form->set('foo');
        $this->form->set('bar', 'button');

        $this->assertTrue($this->form->has('foo'));
        $this->assertTrue($this->form->has('bar'));
    }

    public function testRem()
    {
        $this->form->set('foo');
        $this->form->rem('foo');

        $this->assertFalse($this->form->has('foo'));
    }

    public function testOpen()
    {
        $expected = '<form method="POST" name="form" enctype="multipart/form-data">'.PHP_EOL.
            '<input type="hidden" name="_form" value="form">';

        $this->assertEquals($expected, $this->form->open(null, true));
    }

    public function testClose()
    {
        $this->assertEquals('</form>', $this->form->close());
    }

    public function testRow()
    {
        $row = '<div><label for="form_username">Username</label> <span><input type="text" name="username" id="form_username" placeholder="Username"></span></div>';
        $button = '<div><button type="submit">Submit</button></div>';

        $this->form->set('username');
        $this->form->set('submit', 'submit');

        $this->assertEquals($row, $this->form->row('username'));
        $this->assertEquals('', $this->form->row('username'));

        $this->assertEquals($button, $this->form->row('submit'));
        $this->assertEquals('', $this->form->row('submit'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Field or button not exists: foo.');

        $this->form->row('foo');
    }

    public function testRows()
    {
        $expected = '<div><label for="form_username">Username</label> <span><input type="text" name="username" id="form_username" placeholder="Username"></span></div>';

        $this->form->set('username');

        $this->assertEquals($expected, $this->form->rows());
    }

    public function testButtons()
    {
        $expected = '<div><button type="submit">Submit</button> <a href="#">Cancel</a></div>';

        $this->form->set('submit', 'submit');
        $this->form->set('cancel', 'link');

        $this->assertEquals($expected, $this->form->buttons());
    }

    public function testRender()
    {
        $expected = '<form method="POST" name="form">'.PHP_EOL.
            '<input type="hidden" name="_form" value="form">'.PHP_EOL.
            '<div><label for="form_username">Username</label> <span><input type="text" name="username" id="form_username" placeholder="Username"></span></div>'.PHP_EOL.
            '<div><button type="submit">Submit</button> <a href="#">Cancel</a></div>'.PHP_EOL.
            '</form>';

        $this->form->set('username');
        $this->form->set('submit', 'submit');
        $this->form->set('cancel', 'link');

        $this->assertEquals($expected, $this->form->render());
    }

    public function testValid()
    {
        $this->form->set('foo', 'text', array(
            'constraints' => 'equalto:foobar',
            'messages' => array('equalto' => 'foo'),
            'reverse_transformer' => function ($val) {
                return $val.'baz';
            },
            'transformer' => function ($val) {
                return $val.'bar';
            },
        ));
        $this->form->set('bar');
        $this->form->set('baz');
        $this->form->setIgnores('baz');

        $this->fw->mset(array(
            'VERB' => 'POST',
            'POST' => array(
                'foo' => 'foo',
                'bar' => 'baz',
                'baz' => 'qux',
                'qux' => 'qux',
                '_form' => 'form',
            ),
        ));

        $this->assertTrue($this->form->isSubmitted());
        $this->assertTrue($this->form->valid());
        $this->assertEquals(array('foo' => 'foobarbaz', 'bar' => 'baz'), $this->form->getValidatedData());

        $result = $this->form->getResult();
        $this->assertInstanceOf('Fal\\Stick\\Validation\\Result', $result);
        $this->assertTrue($result->isSuccess());
    }

    public function testValidException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Cannot validate unsubmitted form.');

        $this->form->valid();
    }

    public function testGetFields()
    {
        $this->assertCount(0, $this->form->getFields());
    }

    public function testGetButtons()
    {
        $this->assertCount(0, $this->form->getButtons());
    }
}

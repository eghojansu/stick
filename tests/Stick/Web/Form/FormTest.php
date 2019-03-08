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

use Fal\Stick\Container\Container;
use Fal\Stick\Translation\Translator;
use Fal\Stick\Validation\Rules\CommonRule;
use Fal\Stick\Validation\Validator;
use Fal\Stick\Web\Form\Form;
use Fal\Stick\Web\Form\FormBuilder\DivFormBuilder;
use Fal\Stick\Web\Request;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    private $form;

    public function setup()
    {
        $translator = new Translator();
        $validator = new Validator($translator, array(
            new CommonRule(),
        ));
        $formBuilder = new DivFormBuilder(new Container());

        $this->form = new Form($validator, $translator, $formBuilder);
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
        $this->assertEquals('foo', $this->form->setMethod('foo')->getMethod());
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
        $this->form->addField('foo', 'text', array(
            'transformer' => function ($val) {
                return explode(',', $val);
            },
        ));

        $this->assertEquals(array('foo' => array('bar')), $this->form->setData(array('foo' => 'bar'))->getData());
    }

    public function testGetValidatedData()
    {
        $this->assertEquals(array(), $this->form->getValidatedData());
    }

    public function testGetFormData()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Cannot get form data.');

        $this->form->getFormData();
    }

    /**
     * @dataProvider handleProvider
     */
    public function testHandle($expected, $request, $method = 'POST')
    {
        $this->form->setMethod($method);

        $this->assertEquals($expected, $this->form->handle($request, array('bar' => 'baz'))->getFormData());
    }

    public function testIsSubmitted()
    {
        $this->form->handle(Request::create('/', 'POST', array(
            '_form' => 'form',
        )));

        $this->assertTrue($this->form->isSubmitted());
    }

    public function testIsSubmittedException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Cannot handle form without a request.');

        $this->form->isSubmitted();
    }

    public function testGetResult()
    {
        $this->assertNull($this->form->getResult());
    }

    public function testHasField()
    {
        $this->assertFalse($this->form->hasField('foo'));
    }

    public function testHasButton()
    {
        $this->assertFalse($this->form->hasButton('foo'));
    }

    public function testAddField()
    {
        $this->form->addField('foo');

        $this->assertTrue($this->form->hasField('foo'));
    }

    public function testAddButton()
    {
        $this->form->addButton('foo');

        $this->assertTrue($this->form->hasButton('foo'));
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
        $expected = '<div><label for="form_username">Username</label> <span><input type="text" name="username" id="form_username" placeholder="Username"></span></div>';

        $this->form->addField('username');

        $this->assertEquals($expected, $this->form->row('username'));
        $this->assertEquals('', $this->form->row('username'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Field or button not exists: foo.');

        $this->form->row('foo');
    }

    public function testRows()
    {
        $expected = '<div><label for="form_username">Username</label> <span><input type="text" name="username" id="form_username" placeholder="Username"></span></div>';

        $this->form->addField('username');

        $this->assertEquals($expected, $this->form->rows());
    }

    public function testButtons()
    {
        $expected = '<div><button type="submit">Submit</button> <a href="#">Cancel</a></div>';

        $this->form->addButton('submit');
        $this->form->addButton('cancel', 'link');

        $this->assertEquals($expected, $this->form->buttons());
    }

    public function testRender()
    {
        $expected = '<form method="POST" name="form">'.PHP_EOL.
            '<input type="hidden" name="_form" value="form">'.PHP_EOL.
            '<div><label for="form_username">Username</label> <span><input type="text" name="username" id="form_username" placeholder="Username"></span></div>'.PHP_EOL.
            '<div><button type="submit">Submit</button> <a href="#">Cancel</a></div>'.PHP_EOL.
            '</form>';

        $this->form->addField('username');
        $this->form->addButton('submit');
        $this->form->addButton('cancel', 'link');

        $this->assertEquals($expected, $this->form->render());
    }

    public function testValid()
    {
        $this->form->addField('foo', 'text', array(
            'constraints' => 'equalto:foobar',
            'messages' => array('equalto' => 'foo'),
            'reverse_transformer' => function ($val) {
                return $val.'baz';
            },
            'transformer' => function ($val) {
                return $val.'bar';
            },
        ));
        $this->form->addField('bar');
        $this->form->handle(Request::create('/', 'POST', array(
            'foo' => 'foo',
            'bar' => 'baz',
            '_form' => 'form',
        )));

        $this->assertTrue($this->form->isSubmitted());
        $this->assertTrue($this->form->valid());
        $this->assertEquals(array('foo' => 'foobarbaz', 'bar' => 'baz'), $this->form->getValidatedData());

        $result = $this->form->getResult();
        $this->assertInstanceOf('Fal\\Stick\\Validation\\Result', $result);
        $this->assertTrue($result->valid());
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

    public function handleProvider()
    {
        return array(
            array(
                array('foo' => 'bar'),
                Request::create('/', 'POST', array('foo' => 'bar')),
            ),
            array(
                array('foo' => 'bar'),
                Request::create('/', 'GET', array('foo' => 'bar')),
                'GET',
            ),
        );
    }
}

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

namespace Fal\Stick\Test\Html;

use Fal\Stick\Fw;
use Fal\Stick\Html\Form;
use Fal\Stick\Html\Html;
use Fal\Stick\Validation\CommonValidator;
use Fal\Stick\Validation\Validator;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    private $form;
    private $fw;
    private $validator;

    public function setUp()
    {
        $this->form = new Form($this->fw = new Fw(), $this->validator = new Validator($this->fw), new Html($this->fw));
    }

    public function testGetName()
    {
        $this->assertEquals('form', $this->form->getName());
    }

    public function testSetName()
    {
        $this->assertEquals('foo', $this->form->setName('foo')->getName());
    }

    public function testGetVerb()
    {
        $this->assertEquals('POST', $this->form->getVerb());
    }

    public function testSetVerb()
    {
        $this->assertEquals('FOO', $this->form->setVerb('foo')->getVerb());
    }

    public function testGetData()
    {
        $this->assertEquals(array(), $this->form->getData());
        $this->assertEquals(array(), $this->form->getData(true));
    }

    public function testSetData()
    {
        $this->assertEquals(array('foo' => 'bar'), $this->form->setData(array('foo' => 'bar'))->getData());
        $this->assertEquals(array(), $this->form->getData(true));
        $this->assertEquals('bar', $this->form->foo);
    }

    public function testGetOptions()
    {
        $this->assertEquals(array(), $this->form->getOptions());
    }

    public function testSetOptions()
    {
        $this->assertEquals(array('foo'), $this->form->setOptions(array('foo'))->getOptions());
    }

    public function testBuild()
    {
        $this->assertSame($this->form, $this->form->build());
    }

    public function testGetField()
    {
        $this->assertNull($this->form->getField('foo'));
    }

    public function testGetFields()
    {
        $this->assertEquals(array(), $this->form->getFields());
    }

    public function testGetButtons()
    {
        $this->assertEquals(array(), $this->form->getButtons());
    }

    public function testGetError()
    {
        $this->assertNull($this->form->getError('foo'));
    }

    public function testGetErrors()
    {
        $this->assertEquals(array(), $this->form->getErrors());
    }

    public function testAdd()
    {
        $name = 'foo';
        $type = null;
        $options = null;
        $attr = null;
        $expected = array(
            'foo' => array(
                'type' => 'text',
                'options' => array(
                    'label' => 'Foo',
                    'label_attr' => array('for' => 'form_foo'),
                    'constraints' => null,
                    'messages' => array(),
                ),
                'attr' => array(
                    'name' => 'form[foo]',
                    'type' => 'text',
                    'id' => 'form_foo',
                    'placeholder' => 'Foo',
                ),
                'rendered' => false,
            ),
        );

        $this->assertEquals($expected, $this->form->add($name, $type, $options, $attr)->getFields());
    }

    public function testAddButton()
    {
        $name = 'foo';
        $type = null;
        $label = null;
        $attr = null;
        $expected = array(
            'foo' => array(
                'type' => 'button',
                'attr' => array(
                    'name' => 'form[foo]',
                    'id' => 'form_foo',
                ),
                'label' => 'Foo',
            ),
        );

        $this->assertEquals($expected, $this->form->addButton($name, $type, $label, $attr)->getButtons());
    }

    public function testIsSubmitted()
    {
        $this->assertFalse($this->form->isSubmitted());

        $this->fw['VERB'] = 'POST';
        $this->fw['POST'] = array('form' => array('_form' => 'form'));

        $this->assertTrue($this->form->isSubmitted());
    }

    /**
     * @dataProvider getValidations
     */
    public function testValid($expected, $fields = null, $update = null, $errors = null, $data = null)
    {
        $this->validator->add(new CommonValidator());

        $this->fw['VERB'] = 'POST';
        $this->fw['POST'] = array('form' => ((array) $update) + array('_form' => 'form'));

        foreach ((array) $fields as $field) {
            $this->form->add(...$field);
        }

        $this->form->isSubmitted();

        $this->assertEquals($expected, $this->form->valid());
        $this->assertEquals((array) $errors, $this->form->getErrors());
        $this->assertEquals((array) $data, $this->form->getData());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Cannot validate unsubmitted form.
     */
    public function testValidException()
    {
        $this->form->valid();
    }

    public function testOpen()
    {
        $expected = '<form name="form" method="POST" enctype="multipart/form-data">'.PHP_EOL.
            '<input type="hidden" name="form[_form]" value="form">';

        $this->assertEquals($expected, $this->form->open(null, true));
    }

    public function testClose()
    {
        $this->assertEquals('</form>', $this->form->close());
    }

    public function testRow()
    {
        $this->form->add('foo');
        $expected = '<div><label for="form_foo">Foo</label> <span><input type="text" name="form[foo]" id="form_foo" placeholder="Foo"></span></div>'.PHP_EOL;

        $this->assertEquals($expected, $this->form->row('foo'));
        $this->assertEquals('', $this->form->row('foo'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Field "foo" does not exists.
     */
    public function testRowException()
    {
        $this->form->row('foo');
    }

    public function testRows()
    {
        $this->form->add('foo');
        $expected = '<div><label for="form_foo">Foo</label> <span><input type="text" name="form[foo]" id="form_foo" placeholder="Foo"></span></div>'.PHP_EOL;

        $this->assertEquals($expected, $this->form->rows());
    }

    public function testButtons()
    {
        $this->form->addButton('foo');
        $this->form->addButton('bar', 'link');
        $expected = '<div><button name="form[foo]" id="form_foo">Foo</button> <a name="form[bar]" id="form_bar" href="#">Bar</a></div>'.PHP_EOL;

        $this->assertEquals($expected, $this->form->buttons());
    }

    public function testRender()
    {
        $this->form->add('hidden', 'hidden');
        $this->form->add('text', 'text');
        $this->form->add('password', 'password');
        $this->form->add('checkbox', 'checkbox');
        $this->form->add('radio', 'radio');
        $this->form->add('textarea', 'textarea');
        $this->form->add('choice', 'choice', array(
            'items' => array(
                'One' => 1,
                'Two' => 2,
            ),
        ));
        $this->form->add('choice2', 'choice', array(
            'expanded' => true,
            'items' => array(
                'Three' => 3,
                'Four' => 4,
            ),
        ));
        $this->form->add('choice3', 'choice', array(
            'expanded' => true,
            'multiple' => true,
            'items' => function () {
                return array(
                    'Five' => 5,
                    'Six' => 6,
                );
            },
        ));
        $expected = <<<TXT
<form name="form" method="POST">
<input type="hidden" name="form[_form]" value="form">
<input type="hidden" name="form[hidden]" id="form_hidden">
<div><label for="form_text">Text</label> <span><input type="text" name="form[text]" id="form_text" placeholder="Text"></span></div>
<div><label for="form_password">Password</label> <span><input type="password" name="form[password]" id="form_password" placeholder="Password"></span></div>
<div><label><input type="checkbox" name="form[checkbox]" id="form_checkbox"> Checkbox</label></div>
<div><label><input type="radio" name="form[radio]" id="form_radio"> Radio</label></div>
<div><label for="form_textarea">Textarea</label> <span><textarea name="form[textarea]" id="form_textarea" placeholder="Textarea"></textarea></span></div>
<div><label for="form_choice">Choice</label> <span><select name="form[choice]" id="form_choice"><option value="1">One</option><option value="2">Two</option></select></span></div>
<div><label for="form_choice2">Choice2</label> <span><div><label><input name="form[choice2]" id="form_choice2_0" type="radio" value="3"> Three</label><label><input name="form[choice2]" id="form_choice2_1" type="radio" value="4"> Four</label></div></span></div>
<div><label for="form_choice3">Choice3</label> <span><div><label><input name="form[choice3][]" id="form_choice3_0" type="checkbox" value="5"> Five</label><label><input name="form[choice3][]" id="form_choice3_1" type="checkbox" value="6"> Six</label></div></span></div>

<div></div>

</form>
TXT;

        $this->assertEquals($expected, $this->form->render());
    }

    public function testRenderSubmitted()
    {
        $this->form->add('hidden', 'hidden');
        $this->form->add('text', 'text');
        $this->form->add('password', 'password');
        $this->form->add('checkbox', 'checkbox');
        $this->form->add('radio', 'radio');
        $this->form->add('textarea', 'textarea');
        $this->form->add('choice', 'choice', array(
            'items' => array(
                'One' => 1,
                'Two' => 2,
            ),
        ));
        $this->form->add('choice2', 'choice', array(
            'expanded' => true,
            'items' => array(
                'Three' => 3,
                'Four' => 4,
            ),
        ));
        $this->form->add('choice3', 'choice', array(
            'expanded' => true,
            'multiple' => true,
            'items' => function () {
                return array(
                    'Five' => 5,
                    'Six' => 6,
                );
            },
        ));
        $expected = <<<TXT
<form name="form" method="POST">
<input type="hidden" name="form[_form]" value="form">
<input type="hidden" name="form[hidden]" id="form_hidden" value="hidden">
<div><label for="form_text">Text</label> <span><input type="text" name="form[text]" id="form_text" placeholder="Text" value="text"></span></div>
<div><label for="form_password">Password</label> <span><input type="password" name="form[password]" id="form_password" placeholder="Password"></span></div>
<div><label><input type="checkbox" name="form[checkbox]" id="form_checkbox" checked value="checkbox"> Checkbox</label></div>
<div><label><input type="radio" name="form[radio]" id="form_radio" checked value="radio"> Radio</label></div>
<div><label for="form_textarea">Textarea</label> <span><textarea name="form[textarea]" id="form_textarea" placeholder="Textarea">textarea</textarea></span></div>
<div><label for="form_choice">Choice</label> <span><select name="form[choice]" id="form_choice"><option value="1" selected>One</option><option value="2">Two</option></select></span></div>
<div><label for="form_choice2">Choice2</label> <span><div><label><input name="form[choice2]" id="form_choice2_0" checked type="radio" value="3"> Three</label><label><input name="form[choice2]" id="form_choice2_1" type="radio" value="4"> Four</label></div></span></div>
<div><label for="form_choice3">Choice3</label> <span><div><label><input name="form[choice3][]" id="form_choice3_0" checked type="checkbox" value="5"> Five</label><label><input name="form[choice3][]" id="form_choice3_1" type="checkbox" value="6"> Six</label></div></span></div>

<div></div>

</form>
TXT;

        $data = array(
            'hidden' => 'hidden',
            'password' => 'password',
            'text' => 'text',
            'checkbox' => 'checkbox',
            'radio' => 'radio',
            'textarea' => 'textarea',
            'choice' => 1,
            'choice2' => 3,
            'choice3' => 5,
        );
        $this->fw['VERB'] = 'POST';
        $this->fw['POST'] = array(
            'form' => array('_form' => 'form') + $data,
        );

        $this->assertTrue($this->form->isSubmitted());
        $this->assertTrue($this->form->valid());
        $this->assertEquals($data, $this->form->getData());
        $this->assertEquals($expected, $this->form->render());
    }

    public function getValidations()
    {
        return array(
            array(
                true,
            ),
            array(
                true,
                array(
                    array('foo'),
                ),
            ),
            array(
                false,
                array(
                    array('foo', null, array(
                        'constraints' => 'required',
                    )),
                ),
                null,
                array(
                    'foo' => array('This value should not be blank.'),
                ),
            ),
            array(
                false,
                array(
                    array('foo', null, array(
                        'constraints' => 'required',
                        'messages' => array(
                            'required' => 'Fill it!',
                        ),
                    )),
                ),
                null,
                array(
                    'foo' => array('Fill it!'),
                ),
            ),
            array(
                true,
                array(
                    array('foo', null, array(
                        'constraints' => 'required',
                    )),
                ),
                array(
                    'foo' => 'bar',
                ),
                null,
                array(
                    'foo' => 'bar',
                ),
            ),
        );
    }
}

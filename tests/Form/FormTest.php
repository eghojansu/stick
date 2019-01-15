<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 14, 2019 19:34
 */

namespace Fal\Stick\Test\Form;

use Fal\Stick\Core;
use Fal\Stick\Form\Form;
use Fal\Stick\Util\Html;
use Fal\Stick\Validation\CommonValidator;
use Fal\Stick\Validation\Validator;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    private $form;
    private $core;
    private $validator;

    public function setUp()
    {
        $this->form = new Form($this->core = new Core(), $this->validator = new Validator($this->core), new Html($this->core));
    }

    public function testGetName()
    {
        $this->assertEquals('form', $this->form->getName());
    }

    public function testSetName()
    {
        $this->assertEquals('foo', $this->form->setName('foo')->getName());
    }

    public function testGetKey()
    {
        $this->assertEquals('REQUEST', $this->form->getKey());
    }

    public function testSetKey()
    {
        $this->assertEquals('foo', $this->form->setKey('foo')->getKey());
    }

    public function testGetVerb()
    {
        $this->assertEquals('POST', $this->form->getVerb());
    }

    public function testSetVerb()
    {
        $this->assertEquals('FOO', $this->form->setVerb('foo')->getVerb());
        $this->assertEquals('QUERY', $this->form->setVerb('GET')->getKey());
        $this->assertEquals('REQUEST', $this->form->setVerb('POST')->getKey());
    }

    public function testGetData()
    {
        $this->assertNull($this->form->getData());

        $this->core->set('VERB', 'POST');
        $this->core->set('REQUEST', array('form' => array('_form' => 'form')));
        $this->form->addField('foo');
        $this->form->isSubmitted();

        $this->assertEquals(array('foo' => null), $this->form->getData());
    }

    public function testSetData()
    {
        $this->assertEquals(array('foo' => 'bar'), $this->form->setData(array('foo' => 'bar'))->getData());
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

    public function testPrepare()
    {
        $this->assertSame($this->form, $this->form->prepare());
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

    public function testAddField()
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
                    'transformer' => null,
                    'reverse_transformer' => null,
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

        $this->assertEquals($expected, $this->form->addField($name, $type, $options, $attr)->getFields());
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

        $this->core->set('VERB', 'POST');
        $this->core->set('REQUEST', array('form' => array('_form' => 'form')));

        $this->assertTrue($this->form->isSubmitted());
    }

    /**
     * @dataProvider validProvider
     */
    public function testValid($expected, $fields = null, $update = null, $errors = null, $data = null)
    {
        $this->validator->add(new CommonValidator());

        $this->core->set('VERB', 'POST');
        $this->core->set('REQUEST', array('form' => ((array) $update) + array('_form' => 'form')));

        foreach ((array) $fields as $field) {
            $this->form->addField(...$field);
        }

        $this->form->isSubmitted();

        $this->assertEquals($expected, $this->form->valid());
        $this->assertEquals((array) $errors, $this->form->getErrors());
        $this->assertEquals((array) $data, $this->form->getData());
    }

    public function testValidException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Cannot validate unsubmitted form.');

        $this->form->valid();
    }

    public function testPosted()
    {
        $this->assertFalse($this->form->posted());
    }

    public function testPostedSubmitted()
    {
        $this->validator->add(new CommonValidator());

        $this->core->set('VERB', 'POST');
        $this->core->set('REQUEST', array('form' => array('_form' => 'form', 'foo' => 'bar ')));

        $this->form->addField('foo', 'text', array(
            'constraints' => 'trim',
        ));

        $this->assertTrue($this->form->posted());
        $this->assertEquals(array(), $this->form->getErrors());
        $this->assertEquals(array('foo' => 'bar'), $this->form->getData());
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
        $this->form->addField('foo');
        $expected = '<div><label for="form_foo">Foo</label> <span><input type="text" name="form[foo]" id="form_foo" placeholder="Foo"></span></div>'.PHP_EOL;

        $this->assertEquals($expected, $this->form->row('foo'));
        $this->assertEquals('', $this->form->row('foo'));
    }

    public function testRowException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Field "foo" does not exists.');

        $this->form->row('foo');
    }

    public function testRows()
    {
        $this->form->addField('foo');
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

    /**
     * @dataProvider renderProvider
     */
    public function testRender($expected, $submit = false, $data = array(), $initialize = array())
    {
        $this->form->addField('hidden', 'hidden');
        $this->form->addField('text', 'text');
        $this->form->addField('password', 'password');
        $this->form->addField('checkbox', 'checkbox');
        $this->form->addField('radio', 'radio');
        $this->form->addField('textarea', 'textarea');
        $this->form->addField('choice', 'choice', array(
            'items' => array(
                'One' => 1,
                'Two' => 2,
            ),
        ));
        $this->form->addField('choice2', 'choice', array(
            'expanded' => true,
            'items' => array(
                'Three' => 3,
                'Four' => 4,
            ),
        ));
        $this->form->addField('choice3', 'choice', array(
            'expanded' => true,
            'multiple' => true,
            'items' => function () {
                return array(
                    'Five' => 5,
                    'Six' => 6,
                );
            },
        ));

        if ($initialize) {
            $this->form->setData($initialize);
        }

        $this->form->prepare();

        if ($submit) {
            $this->core->set('VERB', 'POST');
            $this->core->set('REQUEST.form', array('_form' => 'form') + $data);

            $this->assertTrue($this->form->isSubmitted());
            $this->assertTrue($this->form->valid());
            $this->assertEquals($data, $this->form->getData());
        }

        $this->assertEquals($expected, $this->form->render());
    }

    /**
     * @dataProvider renderTransformationProvider
     */
    public function testRenderTransformation($expected, $submit = false)
    {
        $this->form->addField('foo', 'choice', array(
            'expanded' => true,
            'multiple' => true,
            'items' => function () {
                return array(
                    'Five' => 5,
                    'Six' => 6,
                );
            },
            'transformer' => function ($val) { return explode(',', $val); },
            'reverse_transformer' => function ($val) { return implode(',', $val); },
        ));

        $this->form->setData(array('foo' => '5,6'));
        $this->form->prepare();

        if ($submit) {
            $this->core->set('VERB', 'POST');
            $this->core->set('REQUEST.form', array('_form' => 'form', 'foo' => array(5)));

            $this->assertTrue($this->form->isSubmitted());
            $this->assertTrue($this->form->valid());
            $this->assertEquals(array('foo' => '5'), $this->form->getData());
        }

        $this->assertEquals($expected, $this->form->render());
    }

    public function testMagicGet()
    {
        $this->core->set('VERB', 'POST');
        $this->core->set('REQUEST', array('form' => array(
            '_form' => 'form',
            'normalized' => 'n',
        )));
        $this->form->setData(array('data' => 'd'));
        $this->form->addField('normalized');
        $this->form->isSubmitted();

        $this->assertEquals('d', $this->form->data);
        $this->assertEquals('n', $this->form->normalized);
        $this->assertNull($this->form->foo);

        $this->form->valid();
        $this->assertEquals('n', $this->form->normalized);
    }

    public function validProvider()
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
                null,
                null,
                array('foo' => null),
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
                    'foo' => array('This value should not be blank'),
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

    public function renderProvider()
    {
        return array(
            array(
                '<form name="form" method="POST">
<input type="hidden" name="form[_form]" value="form">
<input type="hidden" name="form[hidden]" id="form_hidden">
<div><label for="form_text">Text</label> <span><input type="text" name="form[text]" id="form_text" placeholder="Text"></span></div>
<div><label for="form_password">Password</label> <span><input type="password" name="form[password]" id="form_password" placeholder="Password"></span></div>
<div><label><input type="checkbox" name="form[checkbox]" id="form_checkbox"> Checkbox</label></div>
<div><label><input type="radio" name="form[radio]" id="form_radio"> Radio</label></div>
<div><label for="form_textarea">Textarea</label> <span><textarea name="form[textarea]" id="form_textarea" placeholder="Textarea"></textarea></span></div>
<div><label for="form_choice">Choice</label> <span><select name="form[choice]" id="form_choice"><option value="1">One</option><option value="2">Two</option></select></span></div>
<div><label for="form_choice2">Choice2</label> <span><div><label><input name="form[choice2]" id="form_choice2_0" value="3" type="radio"> Three</label><label><input name="form[choice2]" id="form_choice2_1" value="4" type="radio"> Four</label></div></span></div>
<div><label for="form_choice3">Choice3</label> <span><div><label><input name="form[choice3][]" id="form_choice3_0" value="5" type="checkbox"> Five</label><label><input name="form[choice3][]" id="form_choice3_1" value="6" type="checkbox"> Six</label></div></span></div>

<div></div>

</form>',
            ),
            array(
                '<form name="form" method="POST">
<input type="hidden" name="form[_form]" value="form">
<input type="hidden" name="form[hidden]" id="form_hidden" value="hidden">
<div><label for="form_text">Text</label> <span><input type="text" name="form[text]" id="form_text" placeholder="Text" value="text"></span></div>
<div><label for="form_password">Password</label> <span><input type="password" name="form[password]" id="form_password" placeholder="Password"></span></div>
<div><label><input type="checkbox" name="form[checkbox]" id="form_checkbox" checked> Checkbox</label></div>
<div><label><input type="radio" name="form[radio]" id="form_radio" checked> Radio</label></div>
<div><label for="form_textarea">Textarea</label> <span><textarea name="form[textarea]" id="form_textarea" placeholder="Textarea">textarea</textarea></span></div>
<div><label for="form_choice">Choice</label> <span><select name="form[choice]" id="form_choice"><option value="1" selected>One</option><option value="2">Two</option></select></span></div>
<div><label for="form_choice2">Choice2</label> <span><div><label><input name="form[choice2]" id="form_choice2_0" value="3" checked type="radio"> Three</label><label><input name="form[choice2]" id="form_choice2_1" value="4" type="radio"> Four</label></div></span></div>
<div><label for="form_choice3">Choice3</label> <span><div><label><input name="form[choice3][]" id="form_choice3_0" value="5" checked type="checkbox"> Five</label><label><input name="form[choice3][]" id="form_choice3_1" value="6" type="checkbox"> Six</label></div></span></div>

<div></div>

</form>',
                true,
                array(
                    'hidden' => 'hidden',
                    'password' => 'password',
                    'text' => 'text',
                    'checkbox' => 'on',
                    'radio' => 'on',
                    'textarea' => 'textarea',
                    'choice' => 1,
                    'choice2' => 3,
                    'choice3' => 5,
                ),
            ),
            array(
                '<form name="form" method="POST">
<input type="hidden" name="form[_form]" value="form">
<input type="hidden" name="form[hidden]" id="form_hidden" value="hidden">
<div><label for="form_text">Text</label> <span><input type="text" name="form[text]" id="form_text" placeholder="Text" value="text"></span></div>
<div><label for="form_password">Password</label> <span><input type="password" name="form[password]" id="form_password" placeholder="Password"></span></div>
<div><label><input type="checkbox" name="form[checkbox]" id="form_checkbox"> Checkbox</label></div>
<div><label><input type="radio" name="form[radio]" id="form_radio"> Radio</label></div>
<div><label for="form_textarea">Textarea</label> <span><textarea name="form[textarea]" id="form_textarea" placeholder="Textarea">textarea</textarea></span></div>
<div><label for="form_choice">Choice</label> <span><select name="form[choice]" id="form_choice"><option value="1" selected>One</option><option value="2">Two</option></select></span></div>
<div><label for="form_choice2">Choice2</label> <span><div><label><input name="form[choice2]" id="form_choice2_0" value="3" checked type="radio"> Three</label><label><input name="form[choice2]" id="form_choice2_1" value="4" type="radio"> Four</label></div></span></div>
<div><label for="form_choice3">Choice3</label> <span><div><label><input name="form[choice3][]" id="form_choice3_0" value="5" checked type="checkbox"> Five</label><label><input name="form[choice3][]" id="form_choice3_1" value="6" type="checkbox"> Six</label></div></span></div>

<div></div>

</form>',
                false,
                array(),
                array(
                    'hidden' => 'hidden',
                    'password' => 'password',
                    'text' => 'text',
                    'checkbox' => 'checkbox',
                    'radio' => 'radio',
                    'textarea' => 'textarea',
                    'choice' => 1,
                    'choice2' => 3,
                    'choice3' => 5,
                ),
            ),
            array(
                '<form name="form" method="POST">
<input type="hidden" name="form[_form]" value="form">
<input type="hidden" name="form[hidden]" id="form_hidden" value="not hidden">
<div><label for="form_text">Text</label> <span><input type="text" name="form[text]" id="form_text" placeholder="Text" value="text update"></span></div>
<div><label for="form_password">Password</label> <span><input type="password" name="form[password]" id="form_password" placeholder="Password"></span></div>
<div><label><input type="checkbox" name="form[checkbox]" id="form_checkbox" checked> Checkbox</label></div>
<div><label><input type="radio" name="form[radio]" id="form_radio" checked> Radio</label></div>
<div><label for="form_textarea">Textarea</label> <span><textarea name="form[textarea]" id="form_textarea" placeholder="Textarea">textarea</textarea></span></div>
<div><label for="form_choice">Choice</label> <span><select name="form[choice]" id="form_choice"><option value="1" selected>One</option><option value="2">Two</option></select></span></div>
<div><label for="form_choice2">Choice2</label> <span><div><label><input name="form[choice2]" id="form_choice2_0" value="3" checked type="radio"> Three</label><label><input name="form[choice2]" id="form_choice2_1" value="4" type="radio"> Four</label></div></span></div>
<div><label for="form_choice3">Choice3</label> <span><div><label><input name="form[choice3][]" id="form_choice3_0" value="5" checked type="checkbox"> Five</label><label><input name="form[choice3][]" id="form_choice3_1" value="6" type="checkbox"> Six</label></div></span></div>

<div></div>

</form>',
                true,
                array(
                    'hidden' => 'not hidden',
                    'password' => 'password',
                    'text' => 'text update',
                    'checkbox' => 'on',
                    'radio' => 'on',
                    'textarea' => 'textarea',
                    'choice' => 1,
                    'choice2' => 3,
                    'choice3' => 5,
                ),
                array(
                    'hidden' => 'hidden',
                    'password' => 'password',
                    'text' => 'text',
                    'checkbox' => 'checkbox',
                    'radio' => 'radio',
                    'textarea' => 'textarea',
                    'choice' => 1,
                    'choice2' => 3,
                    'choice3' => 5,
                ),
            ),
        );
    }

    public function renderTransformationProvider()
    {
        return array(
            array(
                '<form name="form" method="POST">
<input type="hidden" name="form[_form]" value="form">
<div><label for="form_foo">Foo</label> <span><div><label><input name="form[foo][]" id="form_foo_0" value="5" checked type="checkbox"> Five</label><label><input name="form[foo][]" id="form_foo_1" value="6" checked type="checkbox"> Six</label></div></span></div>

<div></div>

</form>',
            ),
            array(
                '<form name="form" method="POST">
<input type="hidden" name="form[_form]" value="form">
<div><label for="form_foo">Foo</label> <span><div><label><input name="form[foo][]" id="form_foo_0" value="5" checked type="checkbox"> Five</label><label><input name="form[foo][]" id="form_foo_1" value="6" type="checkbox"> Six</label></div></span></div>

<div></div>

</form>', true,
            ),
        );
    }
}

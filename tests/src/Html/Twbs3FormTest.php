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
use Fal\Stick\Html\Html;
use Fal\Stick\Html\Twbs3Form;
use Fal\Stick\Validation\CommonValidator;
use Fal\Stick\Validation\Validator;
use PHPUnit\Framework\TestCase;

class Twbs3FormTest extends TestCase
{
    private $form;
    private $fw;
    private $validator;

    public function setUp()
    {
        $this->form = new Twbs3Form($this->fw = new Fw(), $this->validator = new Validator($this->fw), new Html($this->fw));
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
                    'label_attr' => array('for' => 'twbs3_form_foo'),
                    'constraints' => null,
                    'messages' => array(),
                    'transformer' => null,
                    'reverse_transformer' => null,
                ),
                'attr' => array(
                    'class' => 'form-control',
                    'name' => 'twbs3_form[foo]',
                    'type' => 'text',
                    'id' => 'twbs3_form_foo',
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
                    'class' => 'btn btn-primary',
                    'name' => 'twbs3_form[foo]',
                    'id' => 'twbs3_form_foo',
                ),
                'label' => 'Foo',
            ),
        );

        $this->assertEquals($expected, $this->form->addButton($name, $type, $label, $attr)->getButtons());
    }

    public function testOpen()
    {
        $expected = '<form class="form-horizontal" name="twbs3_form" method="POST">'.PHP_EOL.
            '<input type="hidden" name="twbs3_form[_form]" value="twbs3_form">';

        $this->assertEquals($expected, $this->form->open());
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
<form class="form-horizontal" name="twbs3_form" method="POST">
<input type="hidden" name="twbs3_form[_form]" value="twbs3_form">
<input type="hidden" name="twbs3_form[hidden]" id="twbs3_form_hidden">
<div class="form-group"><label for="twbs3_form_text" class="control-label col-sm-2">Text</label><div class="col-sm-10"><input class="form-control" type="text" name="twbs3_form[text]" id="twbs3_form_text" placeholder="Text"></div></div>
<div class="form-group"><label for="twbs3_form_password" class="control-label col-sm-2">Password</label><div class="col-sm-10"><input class="form-control" type="password" name="twbs3_form[password]" id="twbs3_form_password" placeholder="Password"></div></div>
<div class="form-group"><div class="col-sm-offset-2 col-sm-10"><div class="checkbox"><label><input type="checkbox" name="twbs3_form[checkbox]" id="twbs3_form_checkbox"> Checkbox</label></div></div></div>
<div class="form-group"><div class="col-sm-offset-2 col-sm-10"><div class="radio"><label><input type="radio" name="twbs3_form[radio]" id="twbs3_form_radio"> Radio</label></div></div></div>
<div class="form-group"><label for="twbs3_form_textarea" class="control-label col-sm-2">Textarea</label><div class="col-sm-10"><textarea class="form-control" name="twbs3_form[textarea]" id="twbs3_form_textarea" placeholder="Textarea"></textarea></div></div>
<div class="form-group"><label for="twbs3_form_choice" class="control-label col-sm-2">Choice</label><div class="col-sm-10"><select class="form-control" name="twbs3_form[choice]" id="twbs3_form_choice"><option value="1">One</option><option value="2">Two</option></select></div></div>
<div class="form-group"><label for="twbs3_form_choice2" class="control-label col-sm-2">Choice2</label><div class="col-sm-10"><div><div class="radio"><label><input name="twbs3_form[choice2]" id="twbs3_form_choice2_0" value="3" type="radio"> Three</label></div><div class="radio"><label><input name="twbs3_form[choice2]" id="twbs3_form_choice2_1" value="4" type="radio"> Four</label></div></div></div></div>
<div class="form-group"><label for="twbs3_form_choice3" class="control-label col-sm-2">Choice3</label><div class="col-sm-10"><div><div class="checkbox"><label><input name="twbs3_form[choice3][]" id="twbs3_form_choice3_0" value="5" type="checkbox"> Five</label></div><div class="checkbox"><label><input name="twbs3_form[choice3][]" id="twbs3_form_choice3_1" value="6" type="checkbox"> Six</label></div></div></div></div>

<div class="form-group"><div class="col-sm-offset-2 col-sm-10"></div></div>

</form>
TXT;

        $this->assertEquals($expected, $this->form->render());
    }

    public function testRenderSubmitted()
    {
        $this->form->add('hidden', 'hidden');
        $this->form->add('text', 'text', array('constraints' => 'required'));
        $this->form->add('password', 'password', array('constraints' => 'required'));
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
<form class="form-horizontal" name="twbs3_form" method="POST">
<input type="hidden" name="twbs3_form[_form]" value="twbs3_form">
<input type="hidden" name="twbs3_form[hidden]" id="twbs3_form_hidden" value="hidden">
<div class="form-group has-error"><label for="twbs3_form_text" class="control-label col-sm-2">Text</label><div class="col-sm-10"><input class="form-control" type="text" name="twbs3_form[text]" id="twbs3_form_text" placeholder="Text"><span class="help-block">This value should not be blank.</span></div></div>
<div class="form-group has-success"><label for="twbs3_form_password" class="control-label col-sm-2">Password</label><div class="col-sm-10"><input class="form-control" type="password" name="twbs3_form[password]" id="twbs3_form_password" placeholder="Password"></div></div>
<div class="form-group"><div class="col-sm-offset-2 col-sm-10"><div class="checkbox"><label><input type="checkbox" name="twbs3_form[checkbox]" id="twbs3_form_checkbox" checked> Checkbox</label></div></div></div>
<div class="form-group"><div class="col-sm-offset-2 col-sm-10"><div class="radio"><label><input type="radio" name="twbs3_form[radio]" id="twbs3_form_radio" checked> Radio</label></div></div></div>
<div class="form-group"><label for="twbs3_form_textarea" class="control-label col-sm-2">Textarea</label><div class="col-sm-10"><textarea class="form-control" name="twbs3_form[textarea]" id="twbs3_form_textarea" placeholder="Textarea">textarea</textarea></div></div>
<div class="form-group"><label for="twbs3_form_choice" class="control-label col-sm-2">Choice</label><div class="col-sm-10"><select class="form-control" name="twbs3_form[choice]" id="twbs3_form_choice"><option value="1" selected>One</option><option value="2">Two</option></select></div></div>
<div class="form-group"><label for="twbs3_form_choice2" class="control-label col-sm-2">Choice2</label><div class="col-sm-10"><div><div class="radio"><label><input name="twbs3_form[choice2]" id="twbs3_form_choice2_0" value="3" checked type="radio"> Three</label></div><div class="radio"><label><input name="twbs3_form[choice2]" id="twbs3_form_choice2_1" value="4" type="radio"> Four</label></div></div></div></div>
<div class="form-group"><label for="twbs3_form_choice3" class="control-label col-sm-2">Choice3</label><div class="col-sm-10"><div><div class="checkbox"><label><input name="twbs3_form[choice3][]" id="twbs3_form_choice3_0" value="5" checked type="checkbox"> Five</label></div><div class="checkbox"><label><input name="twbs3_form[choice3][]" id="twbs3_form_choice3_1" value="6" type="checkbox"> Six</label></div></div></div></div>

<div class="form-group"><div class="col-sm-offset-2 col-sm-10"></div></div>

</form>
TXT;

        $data = array(
            'hidden' => 'hidden',
            'password' => 'password',
            // 'text' => 'text',
            'checkbox' => 'on',
            'radio' => 'on',
            'textarea' => 'textarea',
            'choice' => 1,
            'choice2' => 3,
            'choice3' => 5,
        );
        $this->fw['VERB'] = 'POST';
        $this->fw['POST'] = array(
            'twbs3_form' => array('_form' => 'twbs3_form') + $data,
        );
        $this->validator->add(new CommonValidator());
        $this->form->setOptions(array('mark' => 1 | 2));

        $this->assertTrue($this->form->isSubmitted());
        $this->assertFalse($this->form->valid());
        $this->assertEquals($data, $this->form->getData());
        $this->assertEquals($expected, $this->form->render());
    }
}

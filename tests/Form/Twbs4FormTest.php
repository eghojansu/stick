<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Form;

use Fal\Stick\Core;
use Fal\Stick\Form\Twbs4Form;
use Fal\Stick\Util\Html;
use Fal\Stick\Validation\CommonValidator;
use Fal\Stick\Validation\Validator;
use PHPUnit\Framework\TestCase;

class Twbs4FormTest extends TestCase
{
    private $form;
    private $core;
    private $validator;

    public function setUp()
    {
        $this->form = new Twbs4Form($this->core = new Core(), $this->validator = new Validator($this->core), new Html($this->core));
    }

    public function testAddField()
    {
        $name = 'foo';
        $type = null;
        $options = null;
        $attr = null;
        $expected = [
            'foo' => [
                'type'    => 'text',
                'options' => [
                    'label'               => 'Foo',
                    'label_attr'          => ['for' => 'twbs4_form_foo'],
                    'constraints'         => null,
                    'messages'            => [],
                    'transformer'         => null,
                    'reverse_transformer' => null,
                ],
                'attr' => [
                    'class'       => 'form-control',
                    'name'        => 'twbs4_form[foo]',
                    'type'        => 'text',
                    'id'          => 'twbs4_form_foo',
                    'placeholder' => 'Foo',
                ],
                'rendered' => false,
            ],
        ];

        $this->assertEquals($expected, $this->form->addField($name, $type, $options, $attr)->getFields());
    }

    public function testOpen()
    {
        $expected = '<form name="twbs4_form" method="POST">'.PHP_EOL.
            '<input type="hidden" name="twbs4_form[_form]" value="twbs4_form">';

        $this->assertEquals($expected, $this->form->open());
    }

    public function testRow()
    {
        $this->form->addField('foo');
        $expected = '<div class="form-group row"><label for="twbs4_form_foo" class="col-form-label col-sm-2">Foo</label><div class="col-sm-10"><input class="form-control" type="text" name="twbs4_form[foo]" id="twbs4_form_foo" placeholder="Foo"></div></div>'.PHP_EOL;

        $this->assertEquals($expected, $this->form->row('foo'));
        $this->assertEquals('', $this->form->row('foo'));
    }

    public function testRender()
    {
        $this->form->addField('hidden', 'hidden');
        $this->form->addField('text', 'text');
        $this->form->addField('password', 'password');
        $this->form->addField('checkbox', 'checkbox');
        $this->form->addField('radio', 'radio');
        $this->form->addField('textarea', 'textarea');
        $this->form->addField('choice', 'choice', [
            'items' => [
                'One' => 1,
                'Two' => 2,
            ],
        ]);
        $this->form->addField('choice2', 'choice', [
            'expanded' => true,
            'items'    => [
                'Three' => 3,
                'Four'  => 4,
            ],
        ]);
        $this->form->addField('choice3', 'choice', [
            'expanded' => true,
            'multiple' => true,
            'items'    => function () {
                return [
                    'Five' => 5,
                    'Six'  => 6,
                ];
            },
        ]);
        $expected = <<<'TXT'
<form name="twbs4_form" method="POST">
<input type="hidden" name="twbs4_form[_form]" value="twbs4_form">
<input type="hidden" name="twbs4_form[hidden]" id="twbs4_form_hidden">
<div class="form-group row"><label for="twbs4_form_text" class="col-form-label col-sm-2">Text</label><div class="col-sm-10"><input class="form-control" type="text" name="twbs4_form[text]" id="twbs4_form_text" placeholder="Text"></div></div>
<div class="form-group row"><label for="twbs4_form_password" class="col-form-label col-sm-2">Password</label><div class="col-sm-10"><input class="form-control" type="password" name="twbs4_form[password]" id="twbs4_form_password" placeholder="Password"></div></div>
<div class="form-group row"><div class="ml-auto col-sm-10"><div class="form-check"><input type="checkbox" name="twbs4_form[checkbox]" id="twbs4_form_checkbox" class="form-check-input"><label class="form-check-label" for="twbs4_form_checkbox">Checkbox</label></div></div></div>
<div class="form-group row"><div class="ml-auto col-sm-10"><div class="form-check"><input type="radio" name="twbs4_form[radio]" id="twbs4_form_radio" class="form-check-input"><label class="form-check-label" for="twbs4_form_radio">Radio</label></div></div></div>
<div class="form-group row"><label for="twbs4_form_textarea" class="col-form-label col-sm-2">Textarea</label><div class="col-sm-10"><textarea class="form-control" name="twbs4_form[textarea]" id="twbs4_form_textarea" placeholder="Textarea"></textarea></div></div>
<div class="form-group row"><label for="twbs4_form_choice" class="col-form-label col-sm-2">Choice</label><div class="col-sm-10"><select class="form-control" name="twbs4_form[choice]" id="twbs4_form_choice"><option value="1">One</option><option value="2">Two</option></select></div></div>
<div class="form-group row"><label for="twbs4_form_choice2" class="col-form-label col-sm-2">Choice2</label><div class="col-sm-10"><div><div class="form-check"><input name="twbs4_form[choice2]" id="twbs4_form_choice2_0" value="3" class="form-check-input" type="radio"><label class="form-check-label" for="twbs4_form_choice2_0">Three</label></div><div class="form-check"><input name="twbs4_form[choice2]" id="twbs4_form_choice2_1" value="4" class="form-check-input" type="radio"><label class="form-check-label" for="twbs4_form_choice2_1">Four</label></div></div></div></div>
<div class="form-group row"><label for="twbs4_form_choice3" class="col-form-label col-sm-2">Choice3</label><div class="col-sm-10"><div><div class="form-check"><input name="twbs4_form[choice3][]" id="twbs4_form_choice3_0" value="5" class="form-check-input" type="checkbox"><label class="form-check-label" for="twbs4_form_choice3_0">Five</label></div><div class="form-check"><input name="twbs4_form[choice3][]" id="twbs4_form_choice3_1" value="6" class="form-check-input" type="checkbox"><label class="form-check-label" for="twbs4_form_choice3_1">Six</label></div></div></div></div>

<div class="form-group row"><div class="ml-auto col-sm-10"></div></div>

</form>
TXT;

        $this->assertEquals($expected, $this->form->render());
    }

    public function testRenderSubmitted()
    {
        $this->form->addField('hidden', 'hidden');
        $this->form->addField('text', 'text', ['constraints' => 'required']);
        $this->form->addField('password', 'password', ['constraints' => 'required']);
        $this->form->addField('checkbox', 'checkbox');
        $this->form->addField('radio', 'radio');
        $this->form->addField('textarea', 'textarea');
        $this->form->addField('choice', 'choice', [
            'items' => [
                'One' => 1,
                'Two' => 2,
            ],
        ]);
        $this->form->addField('choice2', 'choice', [
            'expanded' => true,
            'items'    => [
                'Three' => 3,
                'Four'  => 4,
            ],
        ]);
        $this->form->addField('choice3', 'choice', [
            'expanded' => true,
            'multiple' => true,
            'items'    => function () {
                return [
                    'Five' => 5,
                    'Six'  => 6,
                ];
            },
        ]);
        $expected = <<<'TXT'
<form name="twbs4_form" method="POST">
<input type="hidden" name="twbs4_form[_form]" value="twbs4_form">
<input type="hidden" name="twbs4_form[hidden]" id="twbs4_form_hidden" value="hidden">
<div class="form-group row"><label for="twbs4_form_text" class="col-form-label col-sm-2">Text</label><div class="col-sm-10"><input class="form-control is-invalid" type="text" name="twbs4_form[text]" id="twbs4_form_text" placeholder="Text"><div class="invalid-feedback">This value should not be blank</div></div></div>
<div class="form-group row"><label for="twbs4_form_password" class="col-form-label col-sm-2">Password</label><div class="col-sm-10"><input class="form-control is-valid" type="password" name="twbs4_form[password]" id="twbs4_form_password" placeholder="Password"></div></div>
<div class="form-group row"><div class="ml-auto col-sm-10"><div class="form-check"><input type="checkbox" name="twbs4_form[checkbox]" id="twbs4_form_checkbox" class="form-check-input" checked><label class="form-check-label" for="twbs4_form_checkbox">Checkbox</label></div></div></div>
<div class="form-group row"><div class="ml-auto col-sm-10"><div class="form-check"><input type="radio" name="twbs4_form[radio]" id="twbs4_form_radio" class="form-check-input" checked><label class="form-check-label" for="twbs4_form_radio">Radio</label></div></div></div>
<div class="form-group row"><label for="twbs4_form_textarea" class="col-form-label col-sm-2">Textarea</label><div class="col-sm-10"><textarea class="form-control" name="twbs4_form[textarea]" id="twbs4_form_textarea" placeholder="Textarea">textarea</textarea></div></div>
<div class="form-group row"><label for="twbs4_form_choice" class="col-form-label col-sm-2">Choice</label><div class="col-sm-10"><select class="form-control" name="twbs4_form[choice]" id="twbs4_form_choice"><option value="1" selected>One</option><option value="2">Two</option></select></div></div>
<div class="form-group row"><label for="twbs4_form_choice2" class="col-form-label col-sm-2">Choice2</label><div class="col-sm-10"><div><div class="form-check"><input name="twbs4_form[choice2]" id="twbs4_form_choice2_0" value="3" checked class="form-check-input" type="radio"><label class="form-check-label" for="twbs4_form_choice2_0">Three</label></div><div class="form-check"><input name="twbs4_form[choice2]" id="twbs4_form_choice2_1" value="4" class="form-check-input" type="radio"><label class="form-check-label" for="twbs4_form_choice2_1">Four</label></div></div></div></div>
<div class="form-group row"><label for="twbs4_form_choice3" class="col-form-label col-sm-2">Choice3</label><div class="col-sm-10"><div><div class="form-check"><input name="twbs4_form[choice3][]" id="twbs4_form_choice3_0" value="5" checked class="form-check-input" type="checkbox"><label class="form-check-label" for="twbs4_form_choice3_0">Five</label></div><div class="form-check"><input name="twbs4_form[choice3][]" id="twbs4_form_choice3_1" value="6" class="form-check-input" type="checkbox"><label class="form-check-label" for="twbs4_form_choice3_1">Six</label></div></div></div></div>

<div class="form-group row"><div class="ml-auto col-sm-10"></div></div>

</form>
TXT;

        $data = [
            'hidden'   => 'hidden',
            'password' => 'password',
            // 'text' => 'text',
            'checkbox' => 'on',
            'radio'    => 'on',
            'textarea' => 'textarea',
            'choice'   => 1,
            'choice2'  => 3,
            'choice3'  => 5,
        ];
        $this->core->set('VERB', 'POST');
        $this->core->set('REQUEST.twbs4_form', ['_form' => 'twbs4_form'] + $data);

        $this->validator->add(new CommonValidator());
        $this->form->setOptions(['mark' => 1 | 2]);

        $this->assertTrue($this->form->isSubmitted());
        $this->assertFalse($this->form->valid());
        $this->assertEquals($data, $this->form->getData());
        $this->assertEquals($expected, $this->form->render());
    }
}

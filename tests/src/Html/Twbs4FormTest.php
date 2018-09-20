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

use Fal\Stick\App;
use Fal\Stick\Html\Html;
use Fal\Stick\Html\Twbs4Form;
use Fal\Stick\Validation\CommonValidator;
use Fal\Stick\Validation\Validator;
use PHPUnit\Framework\TestCase;

class Twbs4FormTest extends TestCase
{
    private $app;
    private $form;

    public function setUp()
    {
        $this->app = new App();
        $validator = new Validator($this->app);
        $validator->add(new CommonValidator());
        $this->form = new Twbs4Form($this->app, new Html(), $validator);
    }

    public function testOpen()
    {
        $expected = '<form name="twbs4_form" method="POST">'.PHP_EOL.
            '<input type="hidden" name="twbs4_form[_form]" value="twbs4_form" id="twbs4_form__form">';
        $this->assertEquals($expected, $this->form->open());
    }

    public function inputProvider()
    {
        return array(
            array(
                'date',
                '<div class="form-group row">'.
                '<label class="col-form-label col-sm-2" for="twbs4_form_foo">Foo</label>'.
                '<div class="col-sm-10">'.
                '<input class="form-control" type="date" name="twbs4_form[foo]" id="twbs4_form_foo">'.
                '</div>'.
                '</div>'.PHP_EOL,
            ),
            array(
                'text',
                '<div class="form-group row">'.
                '<label class="col-form-label col-sm-2" for="twbs4_form_foo">Foo</label>'.
                '<div class="col-sm-10">'.
                '<input class="form-control" placeholder="Foo" type="text" name="twbs4_form[foo]" id="twbs4_form_foo">'.
                '</div>'.
                '</div>'.PHP_EOL,
            ),
            array(
                'password',
                '<div class="form-group row">'.
                '<label class="col-form-label col-sm-2" for="twbs4_form_foo">Foo</label>'.
                '<div class="col-sm-10">'.
                '<input class="form-control" placeholder="Foo" type="password" name="twbs4_form[foo]" id="twbs4_form_foo">'.
                '</div>'.
                '</div>'.PHP_EOL,
            ),
            array(
                'checkbox',
                '<div class="form-group row">'.
                '<div class="ml-auto col-sm-10">'.
                '<div class="form-check">'.
                '<input class="form-check-input" id="twbs4_form_foo" type="checkbox" name="twbs4_form[foo]">'.
                '<label class="form-check-label" for="twbs4_form_foo">Foo</label>'.
                '</div>'.
                '</div>'.
                '</div>'.PHP_EOL,
            ),
            array(
                'checkbox',
                '<div class="form-group row">'.
                '<div class="ml-auto col-sm-10">'.
                '<div class="form-check">'.
                '<input class="form-check-input" id="twbs4_form_foo" checked type="checkbox" name="twbs4_form[foo]" value="bar">'.
                '<label class="form-check-label" for="twbs4_form_foo">Foo</label>'.
                '</div>'.
                '</div>'.
                '</div>'.PHP_EOL,
                true,
            ),
            array(
                'radio',
                '<div class="form-group row">'.
                '<div class="ml-auto col-sm-10">'.
                '<div class="form-check">'.
                '<input class="form-check-input" id="twbs4_form_foo" type="radio" name="twbs4_form[foo]">'.
                '<label class="form-check-label" for="twbs4_form_foo">Foo</label>'.
                '</div>'.
                '</div>'.
                '</div>'.PHP_EOL,
            ),
            array(
                'radio',
                '<div class="form-group row">'.
                '<div class="ml-auto col-sm-10">'.
                '<div class="form-check">'.
                '<input class="form-check-input" id="twbs4_form_foo" checked type="radio" name="twbs4_form[foo]" value="bar">'.
                '<label class="form-check-label" for="twbs4_form_foo">Foo</label>'.
                '</div>'.
                '</div>'.
                '</div>'.PHP_EOL,
                true,
            ),
            array(
                'textarea',
                '<div class="form-group row">'.
                '<label class="col-form-label col-sm-2" for="twbs4_form_foo">Foo</label>'.
                '<div class="col-sm-10">'.
                '<textarea class="form-control" name="twbs4_form[foo]" id="twbs4_form_foo" placeholder="Foo"></textarea>'.
                '</div>'.
                '</div>'.PHP_EOL,
            ),
            array(
                'choice',
                '<div class="form-group row">'.
                '<label class="col-form-label col-sm-2" for="twbs4_form_foo">Foo</label>'.
                '<div class="col-sm-10">'.
                '<select class="form-control" name="twbs4_form[foo]" id="twbs4_form_foo"></select>'.
                '</div>'.
                '</div>'.PHP_EOL,
            ),
            array(
                'choice',
                '<div class="form-group row">'.
                '<label class="col-form-label col-sm-2" for="twbs4_form_foo">Foo</label>'.
                '<div class="col-sm-10">'.
                '<div>'.
                '<div class="form-check">'.
                '<input class="form-check-input" id="twbs4_form_foo1" type="radio" name="twbs4_form[foo]" value="foo">'.
                '<label class="form-check-label" for="twbs4_form_foo1">Foo</label>'.
                '</div>'.
                '</div>'.
                '</div>'.
                '</div>'.PHP_EOL,
                false,
                array(
                    'expanded' => true,
                    'items' => array(
                        'Foo' => 'foo',
                    ),
                ),
            ),
            array(
                'choice',
                '<div class="form-group row">'.
                '<label class="col-form-label col-sm-2" for="twbs4_form_foo">Foo</label>'.
                '<div class="col-sm-10">'.
                '<div>'.
                '<div class="form-check">'.
                '<input class="form-check-input" id="twbs4_form_foo1" type="checkbox" name="twbs4_form[foo][]" value="foo">'.
                '<label class="form-check-label" for="twbs4_form_foo1">Foo</label>'.
                '</div>'.
                '</div>'.
                '</div>'.
                '</div>'.PHP_EOL,
                false,
                array(
                    'expanded' => true,
                    'multiple' => true,
                    'items' => array(
                        'Foo' => 'foo',
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider inputProvider
     */
    public function testRenderInputType($type, $expected, $submit = false, $options = null)
    {
        $this->form->add('foo', $type, $options);

        if ($submit) {
            $submitted = array('foo' => 'bar');
            $this->app->mset(array(
                'VERB' => 'POST',
                'REQUEST' => array('twbs4_form' => array('_form' => 'twbs4_form') + $submitted),
            ));
            $this->form->setData($submitted);
            $this->form->isSubmitted();
            $this->form->valid();
        }

        $this->assertEquals($expected, $this->form->row('foo'));
    }

    public function testRenderErrorValidation()
    {
        $this->app->mset(array(
            'VERB' => 'POST',
            'REQUEST' => array('twbs4_form' => array('_form' => 'twbs4_form')),
        ));
        $this->form->add('foo', 'text', array(
            'constraints' => 'required',
        ));
        $this->form->isSubmitted();
        $this->form->valid();

        $expected = '<div class="form-group row">'.
                '<label class="col-form-label col-sm-2" for="twbs4_form_foo">Foo</label>'.
                '<div class="col-sm-10">'.
                '<input class="form-control is-invalid" placeholder="Foo" type="text" name="twbs4_form[foo]" id="twbs4_form_foo">'.
                '<div class="invalid-feedback">This value should not be blank.</div>'.
                '</div>'.
                '</div>'.PHP_EOL;

        $this->assertEquals($expected, $this->form->row('foo'));
    }

    public function testRenderButtons()
    {
        $expected = '<div class="form-group row">'.
            '<div class="ml-auto col-sm-10">'.
            '<button class="btn btn-default" type="submit" name="twbs4_form[foo]" id="twbs4_form_foo">Foo</button>'.
            '</div>'.
            '</div>'.PHP_EOL;

        $this->form->addButton('foo');
        $this->assertEquals($expected, $this->form->buttons());
    }
}

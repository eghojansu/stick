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
use Fal\Stick\Html\Twbs3Form;
use Fal\Stick\Validation\CommonValidator;
use Fal\Stick\Validation\Validator;
use PHPUnit\Framework\TestCase;

class Twbs3FormTest extends TestCase
{
    private $app;
    private $form;

    public function setUp()
    {
        $this->app = new App();
        $validator = new Validator($this->app);
        $validator->add(new CommonValidator());
        $this->form = new Twbs3Form($this->app, new Html($this->app), $validator);
    }

    public function testOpen()
    {
        $expected = '<form class="form-horizontal" name="twbs3_form" method="POST">'.PHP_EOL.
            '<input type="hidden" name="twbs3_form[_form]" value="twbs3_form" id="twbs3_form__form">';
        $this->assertEquals($expected, $this->form->open());
    }

    public function inputProvider()
    {
        return array(
            array(
                'date',
                '<div class="form-group">'.
                '<label class="control-label col-sm-2" for="twbs3_form_foo">Foo</label>'.
                '<div class="col-sm-10">'.
                '<input class="form-control" type="date" name="twbs3_form[foo]" id="twbs3_form_foo">'.
                '</div>'.
                '</div>'.PHP_EOL,
            ),
            array(
                'text',
                '<div class="form-group">'.
                '<label class="control-label col-sm-2" for="twbs3_form_foo">Foo</label>'.
                '<div class="col-sm-10">'.
                '<input class="form-control" placeholder="Foo" type="text" name="twbs3_form[foo]" id="twbs3_form_foo">'.
                '</div>'.
                '</div>'.PHP_EOL,
            ),
            array(
                'password',
                '<div class="form-group">'.
                '<label class="control-label col-sm-2" for="twbs3_form_foo">Foo</label>'.
                '<div class="col-sm-10">'.
                '<input class="form-control" placeholder="Foo" type="password" name="twbs3_form[foo]" id="twbs3_form_foo">'.
                '</div>'.
                '</div>'.PHP_EOL,
            ),
            array(
                'checkbox',
                '<div class="form-group">'.
                '<div class="col-sm-offset-2 col-sm-10">'.
                '<div class="checkbox"><label><input type="checkbox" name="twbs3_form[foo]"> Foo</label></div>'.
                '</div>'.
                '</div>'.PHP_EOL,
            ),
            array(
                'checkbox',
                '<div class="form-group">'.
                '<div class="col-sm-offset-2 col-sm-10">'.
                '<div class="checkbox"><label><input checked type="checkbox" name="twbs3_form[foo]" value="bar"> Foo</label></div>'.
                '</div>'.
                '</div>'.PHP_EOL,
                true,
            ),
            array(
                'radio',
                '<div class="form-group">'.
                '<div class="col-sm-offset-2 col-sm-10">'.
                '<div class="radio"><label><input type="radio" name="twbs3_form[foo]"> Foo</label></div>'.
                '</div>'.
                '</div>'.PHP_EOL,
            ),
            array(
                'radio',
                '<div class="form-group">'.
                '<div class="col-sm-offset-2 col-sm-10">'.
                '<div class="radio"><label><input checked type="radio" name="twbs3_form[foo]" value="bar"> Foo</label></div>'.
                '</div>'.
                '</div>'.PHP_EOL,
                true,
            ),
            array(
                'textarea',
                '<div class="form-group">'.
                '<label class="control-label col-sm-2" for="twbs3_form_foo">Foo</label>'.
                '<div class="col-sm-10">'.
                '<textarea class="form-control" name="twbs3_form[foo]" id="twbs3_form_foo" placeholder="Foo"></textarea>'.
                '</div>'.
                '</div>'.PHP_EOL,
            ),
            array(
                'choice',
                '<div class="form-group">'.
                '<label class="control-label col-sm-2" for="twbs3_form_foo">Foo</label>'.
                '<div class="col-sm-10">'.
                '<select class="form-control" name="twbs3_form[foo]" id="twbs3_form_foo"></select>'.
                '</div>'.
                '</div>'.PHP_EOL,
            ),
            array(
                'choice',
                '<div class="form-group">'.
                '<label class="control-label col-sm-2" for="twbs3_form_foo">Foo</label>'.
                '<div class="col-sm-10">'.
                '<div>'.
                '<div class="radio"><label><input type="radio" name="twbs3_form[foo]" value="foo"> Foo</label></div>'.
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
                '<div class="form-group">'.
                '<label class="control-label col-sm-2" for="twbs3_form_foo">Foo</label>'.
                '<div class="col-sm-10">'.
                '<div>'.
                '<div class="checkbox"><label><input type="checkbox" name="twbs3_form[foo][]" value="foo"> Foo</label></div>'.
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
                'REQUEST' => array('twbs3_form' => array('_form' => 'twbs3_form') + $submitted),
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
            'REQUEST' => array('twbs3_form' => array('_form' => 'twbs3_form')),
        ));
        $this->form->add('foo', 'text', array(
            'constraints' => 'required',
        ));
        $this->form->isSubmitted();
        $this->form->valid();

        $expected = '<div class="form-group has-error">'.
                '<label class="control-label col-sm-2" for="twbs3_form_foo">Foo</label>'.
                '<div class="col-sm-10">'.
                '<input class="form-control" placeholder="Foo" type="text" name="twbs3_form[foo]" id="twbs3_form_foo">'.
                '<span class="help-block">This value should not be blank.</span>'.
                '</div>'.
                '</div>'.PHP_EOL;

        $this->assertEquals($expected, $this->form->row('foo'));
    }

    public function testRenderButtons()
    {
        $expected = '<div class="form-group">'.
            '<div class="col-sm-offset-2 col-sm-10">'.
            '<button class="btn btn-default" type="submit" name="twbs3_form[foo]" id="twbs3_form_foo">Foo</button>'.
            '</div>'.
            '</div>'.PHP_EOL;

        $this->form->addButton('foo');
        $this->assertEquals($expected, $this->form->buttons());
    }

    public function testGetLeftColClass()
    {
        $this->assertEquals('col-sm-2', $this->form->getLeftColClass());
    }

    public function testSetLeftColClass()
    {
        $this->assertEquals('foo', $this->form->setLeftColClass('foo')->getLeftColClass());
    }

    public function testGetRightOffsetColClass()
    {
        $this->assertEquals('col-sm-offset-2', $this->form->getRightOffsetColClass());
    }

    public function testSetRightOffsetColClass()
    {
        $this->assertEquals('foo', $this->form->setRightOffsetColClass('foo')->getRightOffsetColClass());
    }

    public function testGetRightColClass()
    {
        $this->assertEquals('col-sm-10', $this->form->getRightColClass());
    }

    public function testSetRightColClass()
    {
        $this->assertEquals('foo', $this->form->setRightColClass('foo')->getRightColClass());
    }
}

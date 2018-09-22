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
use Fal\Stick\Html\Form;
use Fal\Stick\Html\Html;
use Fal\Stick\Validation\CommonValidator;
use Fal\Stick\Validation\Validator;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    private $app;
    private $form;

    public function setUp()
    {
        $this->app = new App();
        $validator = new Validator($this->app);
        $validator->add(new CommonValidator());
        $this->form = new Form($this->app, new Html($this->app), $validator);
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
    }

    public function testSetData()
    {
        $this->assertEquals(array('foo' => 'bar'), $this->form->setData(array('foo' => 'bar'))->getData());
    }

    public function testGetSubmittedData()
    {
        $get = array('get' => 'foo');
        $post = array('post' => 'foo');
        $foo = array('foo' => 'foo');
        $this->app->mset(array(
            'REQUEST' => array('form' => $post),
            'QUERY' => array('form' => $get),
            'FOO' => array('form' => $foo),
        ));

        // POST
        $this->assertEquals($post, $this->form->getSubmittedData());

        // second call
        $this->assertEquals($post, $this->form->getSubmittedData());

        $this->form->setVerb('GET')->setSubmittedData(null);
        $this->assertEquals($get, $this->form->getSubmittedData());

        $this->form->setVerb('foo')->setSubmittedData(null);
        $this->assertEquals($foo, $this->form->getSubmittedData());

        $this->form->setVerb('bar')->setSubmittedData(null);
        $this->assertEquals($post, $this->form->getSubmittedData());
    }

    public function testSetSubmittedData()
    {
        $this->assertEquals(array(), $this->form->setSubmittedData(null)->getSubmittedData());
    }

    public function testGetFields()
    {
        $this->assertEquals(array(), $this->form->getFields());
    }

    public function testGetButtons()
    {
        $this->assertEquals(array(), $this->form->getButtons());
    }

    public function testGetErrors()
    {
        $this->assertEquals(array(), $this->form->getErrors());
    }

    public function testAdd()
    {
        $this->assertSame($this->form, $this->form->add('foo'));
    }

    public function testAddButton()
    {
        $this->assertSame($this->form, $this->form->addButton('foo'));
    }

    public function testIsSubmitted()
    {
        $this->assertFalse($this->form->isSubmitted());
        $this->app->mset(array(
            'VERB' => 'POST',
            'REQUEST' => array('form' => array('_form' => 'form')),
        ));
        $this->form->setSubmittedData(null);
        $this->assertTrue($this->form->isSubmitted());
    }

    public function validProvider()
    {
        return array(
            array(true, array()),
            array(true, array(
                'foo' => array('text', array('constraints' => 'required')),
            ), array(
                'foo' => 'bar',
            )),
        );
    }

    /**
     * @dataProvider validProvider
     */
    public function testValid($expected, $fields, $submitted = array())
    {
        $this->app->mset(array(
            'VERB' => 'POST',
            'REQUEST' => array('form' => array('_form' => 'form') + $submitted),
        ));

        foreach ($fields as $field => $definitions) {
            list($type, $options) = $definitions;

            $this->form->add($field, $type, $options);
        }

        $this->form->isSubmitted();
        $this->assertEquals($expected, $this->form->valid());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage You can not validate unsubmitted form.
     */
    public function testValidException()
    {
        $this->form->valid();
    }

    public function testOpen()
    {
        $expected = '<form name="form" method="POST">'.PHP_EOL.
            '<input type="hidden" name="form[_form]" value="form" id="form__form">';
        $this->assertEquals($expected, $this->form->open());

        $expected = '<form name="form" method="POST" enctype="multipart/form-data">'.PHP_EOL.
            '<input type="hidden" name="form[_form]" value="form" id="form__form">';
        $this->assertEquals($expected, $this->form->open(null, true));
    }

    public function testClose()
    {
        $this->assertEquals('</form>', $this->form->close());
    }

    public function testRow()
    {
        $expected = '<div><label for="form_foo">Foo</label> <span><input id="bar" placeholder="Foo" type="text" name="form[foo]"></span></div>'.PHP_EOL;

        $this->form->add('foo');
        $this->assertEquals($expected, $this->form->row('foo', array('id' => 'bar')));
        $this->assertEquals('', $this->form->row('foo'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Field not exists: "foo".
     */
    public function testRowException()
    {
        $this->form->row('foo');
    }

    public function testRows()
    {
        $expected = '<div><label for="form_foo">Foo</label> <span><input placeholder="Foo" type="text" name="form[foo]" id="form_foo"></span></div>'.PHP_EOL.
            '<div><label><input type="checkbox" name="form[bar]"> Bar</label></div>'.PHP_EOL;

        $this->form->add('foo');
        $this->form->add('bar', 'checkbox');
        $this->assertEquals($expected, $this->form->rows());
    }

    public function testButtons()
    {
        $expected = '<div>'.
            '<button type="submit" name="form[foo]" id="form_foo">Foo</button>'.
            ' <a href="#">Bar</a>'.
            '</div>'.PHP_EOL;

        $this->form->addButton('foo')->addButton('bar', 'a');
        $this->assertEquals($expected, $this->form->buttons());
    }

    public function testRender()
    {
        $expected = '<form name="form" method="POST">'.PHP_EOL.
            '<input type="hidden" name="form[_form]" value="form" id="form__form">'.PHP_EOL.
            '<div><label for="form_foo">Foo</label> <span><input placeholder="Foo" type="text" name="form[foo]" id="form_foo"></span></div>'.PHP_EOL.
            '<div><label><input type="checkbox" name="form[bar]"> Bar</label></div>'.PHP_EOL.PHP_EOL.
            '<div><button type="submit" name="form[baz]" id="form_baz">Baz</button></div>'.PHP_EOL.PHP_EOL.
            '</form>'.PHP_EOL;

        $this->form->add('foo')->add('bar', 'checkbox')->addButton('baz');
        $this->assertEquals($expected, $this->form->render());
    }

    public function testValidateCustomMessage()
    {
        $this->app->mset(array(
            'VERB' => 'POST',
            'REQUEST' => array('form' => array('_form' => 'form')),
        ));

        $this->form->add('foo', 'text', array(
            'constraints' => 'required',
        ));
        $this->form->add('bar', 'text', array(
            'constraints' => 'required',
            'messages' => array('required' => 'Do not leave this field empty!'),
        ));
        $this->form->isSubmitted();

        $this->assertFalse($this->form->valid());
        $this->assertEquals(array(
            'foo' => array('This value should not be blank.'),
            'bar' => array('Do not leave this field empty!'),
        ), $this->form->getErrors());
    }

    public function inputProvider()
    {
        return array(
            array(
                'date',
                '<div><label for="form_foo">Foo</label> <span><input type="date" name="form[foo]" id="form_foo"></span></div>'.PHP_EOL,
            ),
            array(
                'text',
                '<div><label for="form_foo">Foo</label> <span><input placeholder="Foo" type="text" name="form[foo]" id="form_foo"></span></div>'.PHP_EOL,
            ),
            array(
                'hidden',
                '<div><label for="form_foo">Foo</label> <span><input type="hidden" name="form[foo]" id="form_foo"></span></div>'.PHP_EOL,
            ),
            array(
                'password',
                '<div><label for="form_foo">Foo</label> <span><input placeholder="Foo" type="password" name="form[foo]" id="form_foo"></span></div>'.PHP_EOL,
            ),
            array(
                'checkbox',
                '<div><label><input type="checkbox" name="form[foo]"> Foo</label></div>'.PHP_EOL,
            ),
            array(
                'checkbox',
                '<div><label><input checked type="checkbox" name="form[foo]" value="bar"> Foo</label></div>'.PHP_EOL,
                true,
            ),
            array(
                'radio',
                '<div><label><input type="radio" name="form[foo]"> Foo</label></div>'.PHP_EOL,
            ),
            array(
                'radio',
                '<div><label><input checked type="radio" name="form[foo]" value="bar"> Foo</label></div>'.PHP_EOL,
                true,
            ),
            array(
                'textarea',
                '<div><label for="form_foo">Foo</label> <span><textarea name="form[foo]" id="form_foo" placeholder="Foo"></textarea></span></div>'.PHP_EOL,
            ),
            array(
                'choice',
                '<div><label for="form_foo">Foo</label> <span><select name="form[foo]" id="form_foo"></select></span></div>'.PHP_EOL,
            ),
            array(
                'choice',
                '<div><label for="form_foo">Foo</label> <span><div><label><input type="radio" name="form[foo]" value="foo"> Foo</label></div></span></div>'.PHP_EOL,
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
                '<div><label for="form_foo">Foo</label> <span><div><label><input type="checkbox" name="form[foo][]" value="foo"> Foo</label></div></span></div>'.PHP_EOL,
                false,
                array(
                    'expanded' => true,
                    'multiple' => true,
                    'items' => function () {
                        return array('Foo' => 'foo');
                    },
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
                'REQUEST' => array('form' => array('_form' => 'form') + $submitted),
            ));
            $this->form->setData($submitted);
            $this->form->isSubmitted();
            $this->form->valid();
        }

        $this->assertEquals($expected, $this->form->row('foo'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage The returned items should be an array.
     */
    public function testRenderChoiceException()
    {
        $this->form->add('foo', 'choice', array(
            'items' => function () { return null; },
        ));
        $this->form->row('foo');
    }

    public function testGet()
    {
        $this->assertNull($this->form->get('foo'));
    }

    public function testSet()
    {
        $this->assertEquals('bar', $this->form->set('foo', 'bar')->get('foo'));
    }

    public function testMagicGet()
    {
        $this->assertNull($this->form->foo);
    }

    public function testMagicSet()
    {
        $this->form->foo = 'bar';

        $this->assertEquals('bar', $this->form->foo);
    }
}

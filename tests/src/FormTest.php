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

namespace Fal\Stick\Test;

use Fal\Stick\App;
use Fal\Stick\Form;
use Fal\Stick\Html;
use Fal\Stick\Translator;
use Fal\Stick\Validation\SimpleValidator;
use Fal\Stick\Validation\Validator;
use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    /**
     * @var App
     */
    private $app;

    /**
     * @var Form
     */
    private $form;

    public function setUp()
    {
        $this->form = new Form(...[
            $this->app = App::create()->mset([
                'TEMP' => TEMP,
            ]),
            new Html(new Translator()),
            (new Validator(new Translator()))->add(new SimpleValidator()),
        ]);
    }

    public function testAdd()
    {
        $this->assertEquals($this->form, $this->form->add('field'));
    }

    public function testAddButton()
    {
        $this->assertEquals($this->form, $this->form->addButton('button'));
    }

    public function testIsSubmitted()
    {
        $this->assertFalse($this->form->isSubmitted());
        $this->app->mset([
            'VERB' => 'POST',
            'POST' => ['fname' => 'form'],
        ]);
        $this->assertTrue($this->form->isSubmitted());
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

    public function testGetAttr()
    {
        $this->assertEquals([], $this->form->getAttr());
    }

    public function testSetAttr()
    {
        $this->assertEquals(['foo'], $this->form->setAttr(['foo'])->getAttr());
    }

    public function testValid()
    {
        $this->app['VERB'] = 'POST';
        $this->app['POST']['fname'] = 'form';

        $this->form->add('field', null, [
            'constraint' => 'required',
        ]);

        $this->assertFalse($this->form->isSubmitted() && $this->form->valid());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage You can not validate unsubmitted form.
     */
    public function testValidException()
    {
        $this->form->valid();
    }

    public function testErrors()
    {
        $this->assertEquals([], $this->form->errors());
    }

    public function testSubmitted()
    {
        $this->assertEquals([], $this->form->submitted());
    }

    public function testOpen()
    {
        $expected = '<form method="POST">'.
            '<input name="fname" type="hidden" value="form">';

        $this->assertEquals($expected, $this->form->open());
    }

    public function testClose()
    {
        $this->assertEquals('</form>', $this->form->close());
    }

    public function testRow()
    {
        $expected = '<label for="foo">Foo</label><input name="foo" type="text" id="foo" placeholder="Foo">';

        $this->form->add('foo');

        $this->assertEquals($expected, $this->form->row('foo'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Field foo does not exists.
     */
    public function testRowException()
    {
        $this->form->row('foo');
    }

    public function testRender()
    {
        $this->form->addButton('Foo');

        $expected = '<form method="POST">'.
            '<input name="fname" type="hidden" value="form">'.
            '<button>Foo</button>'.
            '</form>';

        $this->assertEquals($expected, $this->form->render());
    }
}

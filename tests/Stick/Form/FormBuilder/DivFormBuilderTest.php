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

namespace Fal\Stick\Test\Form\FormBuilder;

use Fal\Stick\Fw;
use Fal\Stick\Form\Field;
use Fal\Stick\TestSuite\MyTestCase;
use Fal\Stick\Form\FormBuilder\DivFormBuilder;

class DivFormBuilderTest extends MyTestCase
{
    private $builder;

    public function setup(): void
    {
        $this->builder = new DivFormBuilder(new Fw());
    }

    public function testGetOptions()
    {
        $this->assertEquals(array(), $this->builder->getOptions());
    }

    public function testSetOptions()
    {
        $this->assertEquals(array('foo'), $this->builder->setOptions(array('foo'))->getOptions());
    }

    public function testOpen()
    {
        $this->assertEquals('<form type="post">', $this->builder->open(array('type' => 'post')));
    }

    public function testClose()
    {
        $this->assertEquals('</form>', $this->builder->close());
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Form\DivFormBuilderProvider::renderField
     */
    public function testRenderField($expected, $field, $exception = null)
    {
        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            $this->builder->renderField($field);

            return;
        }

        $this->assertEquals($expected, $this->builder->renderField($field));
    }

    public function testRenderButtons()
    {
        $buttons = array(
            new Field('submit', 'submit', array(
                'label' => 'Submit',
            )),
            new Field('cancel', 'link', array(
                'label' => 'Cancel',
            )),
        );
        $expected = '<div><button type="submit">Submit</button> <a href="#">Cancel</a></div>';

        $this->assertEquals($expected, $this->builder->renderButtons($buttons));
    }
}

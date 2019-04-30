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
use Fal\Stick\Form\FormBuilder\Twbs4FormBuilder;

class Twbs4FormBuilderTest extends MyTestCase
{
    private $builder;

    public function setup(): void
    {
        $this->builder = new Twbs4FormBuilder(new Fw());
    }

    public function testOpen()
    {
        $this->assertEquals('<form type="post">', $this->builder->open(array('type' => 'post')));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Form\Twbs4FormBuilderProvider::renderField
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
        $expected = '<div class="form-group row"><div class="ml-auto col-sm-10"><button type="submit" class="btn btn-primary">Submit</button> <a class="btn btn-secondary" href="#">Cancel</a></div></div>';

        $this->assertEquals($expected, $this->builder->renderButtons($buttons));
        $this->assertEquals('', $this->builder->renderButtons(array()));
    }
}

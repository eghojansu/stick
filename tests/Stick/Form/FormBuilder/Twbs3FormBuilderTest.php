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
use Fal\Stick\Form\FormBuilder\Twbs3FormBuilder;

class Twbs3FormBuilderTest extends MyTestCase
{
    private $builder;

    public function setup(): void
    {
        $this->builder = new Twbs3FormBuilder(new Fw(), array(
            'mark' => 1 | 2, // success and error
        ));
    }

    public function testOpen()
    {
        $this->assertEquals('<form type="post" class="form-horizontal">', $this->builder->open(array('type' => 'post')));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Form\Twbs3FormBuilderProvider::renderField
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
        $expected = '<div class="form-group"><div class="col-sm-offset-2 col-sm-10"><button type="submit" class="btn btn-primary">Submit</button> <a class="btn btn-default" href="#">Cancel</a></div></div>';

        $this->assertEquals($expected, $this->builder->renderButtons($buttons));
        $this->assertEquals('', $this->builder->renderButtons(array()));
    }
}

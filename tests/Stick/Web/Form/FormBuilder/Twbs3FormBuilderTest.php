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

namespace Fal\Stick\Test\Web\Form\FormBuilder;

use Fal\Stick\Web\Form\Button;
use Fal\Stick\Web\Form\Field;
use Fal\Stick\Web\Form\FormBuilder\Twbs3FormBuilder;
use PHPUnit\Framework\TestCase;

class Twbs3FormBuilderTest extends TestCase
{
    private $builder;

    public function setup()
    {
        $this->builder = new Twbs3FormBuilder(array(
            'mark' => 1 | 2, // success and error
        ));
    }

    public function testOpen()
    {
        $this->assertEquals('<form type="post" class="form-horizontal">', $this->builder->open(array('type' => 'post')));
    }

    /**
     * @dataProvider renderFieldProvider
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
            new Button('submit', 'submit', array(
                'label' => 'Submit',
            )),
            new Button('cancel', 'link', array(
                'label' => 'Cancel',
            )),
        );
        $expected = '<div class="form-group"><div class="col-sm-offset-2 col-sm-10"><button type="submit" class="btn btn-primary">Submit</button> <a class="btn btn-default" href="#">Cancel</a></div></div>';

        $this->assertEquals($expected, $this->builder->renderButtons($buttons));
        $this->assertEquals('', $this->builder->renderButtons(array()));
    }

    public function renderFieldProvider()
    {
        return array(
            array(
                '<input type="hidden" name="_form" value="form">',
                new Field('_form', 'hidden', array(
                    'value' => 'form',
                )),
            ),
            array(
                '<div class="form-group"><label for="form_username" class="control-label col-sm-2">Username</label><div class="col-sm-10"><input class="form-control" type="text" name="username" id="form_username" placeholder="Username"></div></div>',
                new Field('username', 'text', array(
                    'label' => 'Username',
                    'id' => 'form_username',
                )),
            ),
            array(
                '<div class="form-group"><label for="form_password" class="control-label col-sm-2">Password</label><div class="col-sm-10"><input class="form-control" type="password" name="password" id="form_password" placeholder="Password"></div></div>',
                new Field('password', 'password', array(
                    'label' => 'Password',
                    'id' => 'form_password',
                )),
            ),
            array(
                '<div class="form-group"><label for="form_alamat" class="control-label col-sm-2">Alamat</label><div class="col-sm-10"><textarea class="form-control" type="textarea" name="alamat" id="form_alamat" placeholder="Alamat"></textarea></div></div>',
                new Field('alamat', 'textarea', array(
                    'label' => 'Alamat',
                    'id' => 'form_alamat',
                )),
            ),
            array(
                '<div class="form-group"><label for="form_jenis_kelamin" class="control-label col-sm-2">Jenis Kelamin</label><div class="col-sm-10"><div><div class="radio"><label><input type="radio" name="jenis_kelamin" id="form_jenis_kelamin_0" value="Laki-laki"> Laki-laki</label></div><div class="radio"><label><input type="radio" name="jenis_kelamin" id="form_jenis_kelamin_1" value="Perempuan"> Perempuan</label></div></div></div></div>',
                new Field('jenis_kelamin', 'choice', array(
                    'label' => 'Jenis Kelamin',
                    'id' => 'form_jenis_kelamin',
                    'expanded' => true,
                    'items' => array(
                        'Laki-laki' => 'Laki-laki',
                        'Perempuan' => 'Perempuan',
                    ),
                )),
            ),
            array(
                '<div class="form-group"><label for="form_hobi" class="control-label col-sm-2">Hobi</label><div class="col-sm-10"><div><div class="checkbox"><label><input type="checkbox" name="hobi[]" id="form_hobi_0" value="Sepeda Jarak Pendek"> Sepeda Jarak Pendek</label></div><div class="checkbox"><label><input type="checkbox" name="hobi[]" id="form_hobi_1" value="Sepeda Jarak Menengah"> Sepeda Jarak Menengah</label></div><div class="checkbox"><label><input type="checkbox" name="hobi[]" id="form_hobi_2" value="Sepeda Jarak Jauh"> Sepeda Jarak Jauh</label></div></div></div></div>',
                new Field('hobi', 'choice', array(
                    'label' => 'Hobi',
                    'id' => 'form_hobi',
                    'expanded' => true,
                    'multiple' => true,
                    'items' => array(
                        'Sepeda Jarak Pendek' => 'Sepeda Jarak Pendek',
                        'Sepeda Jarak Menengah' => 'Sepeda Jarak Menengah',
                        'Sepeda Jarak Jauh' => 'Sepeda Jarak Jauh',
                    ),
                )),
            ),
            array(
                '<div class="form-group"><label for="form_lulusan" class="control-label col-sm-2">Lulusan</label><div class="col-sm-10"><select class="form-control" type="choice" name="lulusan" id="form_lulusan"><option value="SMK">SMK</option><option value="SMP">SMP</option></select></div></div>',
                new Field('lulusan', 'choice', array(
                    'label' => 'Lulusan',
                    'id' => 'form_lulusan',
                    'items' => array(
                        'SMK' => 'SMK',
                        'SMP' => 'SMP',
                    ),
                )),
            ),
            array(
                '<div class="form-group"><div class="col-sm-offset-2 col-sm-10"><div class="checkbox"><label><input type="checkbox" name="setuju" id="form_setuju"> Setuju</label></div></div></div>',
                new Field('setuju', 'checkbox', array(
                    'label' => 'Setuju',
                    'id' => 'form_setuju',
                )),
            ),
            array(
                '<div class="form-group"><div class="col-sm-offset-2 col-sm-10"><div class="radio"><label><input type="radio" name="setuju" id="form_setuju"> Setuju</label></div></div></div>',
                new Field('setuju', 'radio', array(
                    'label' => 'Setuju',
                    'id' => 'form_setuju',
                )),
            ),
            array(
                '<div class="form-group"><div class="col-sm-offset-2 col-sm-10"><a class="btn btn-link" href="#">Back</a></div></div>',
                new Button('back', 'link', array(
                    'label' => 'Back',
                    'attr' => array(
                        'class' => 'btn btn-link',
                    ),
                )),
            ),
            array(
                '<div class="form-group has-success"><label for="form_username" class="control-label col-sm-2">Username</label><div class="col-sm-10"><input class="form-control" type="text" name="username" id="form_username" placeholder="Username"></div></div>',
                new Field('username', 'text', array(
                    'label' => 'Username',
                    'id' => 'form_username',
                    'submitted' => true,
                    'constraints' => 'foo',
                    'errors' => array(),
                )),
            ),
            array(
                '<div class="form-group has-error"><label for="form_username" class="control-label col-sm-2">Username</label><div class="col-sm-10"><input class="form-control" type="text" name="username" id="form_username" placeholder="Username"><span class="help-block">error message</span></div></div>',
                new Field('username', 'text', array(
                    'label' => 'Username',
                    'id' => 'form_username',
                    'submitted' => true,
                    'constraints' => 'foo',
                    'errors' => array('error message'),
                )),
            ),
        );
    }
}

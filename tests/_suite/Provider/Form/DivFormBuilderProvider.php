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

namespace Fal\Stick\TestSuite\Provider\Form;

use Fal\Stick\Form\Field;

class DivFormBuilderProvider
{
    public function renderField()
    {
        return array(
            array(
                '<input type="hidden" name="_form" value="form">',
                new Field('_form', 'hidden', array(
                    'value' => 'form',
                )),
            ),
            array(
                '<div><label for="form_username">Username</label> <span><input type="text" name="username" id="form_username" placeholder="Username"></span></div>',
                new Field('username', 'text', array(
                    'label' => 'Username',
                    'id' => 'form_username',
                )),
            ),
            array(
                '<div><label for="form_password">Password</label> <span><input type="password" name="password" id="form_password" placeholder="Password"></span></div>',
                new Field('password', 'password', array(
                    'label' => 'Password',
                    'id' => 'form_password',
                )),
            ),
            array(
                '<div><label for="form_alamat">Alamat</label> <span><textarea type="textarea" name="alamat" id="form_alamat" placeholder="Alamat"></textarea></span></div>',
                new Field('alamat', 'textarea', array(
                    'label' => 'Alamat',
                    'id' => 'form_alamat',
                )),
            ),
            array(
                '<div><label for="form_jenis_kelamin">Jenis Kelamin</label> <span><div><label><input type="radio" name="jenis_kelamin" id="form_jenis_kelamin_0" value="Laki-laki"> Laki-laki</label><label><input type="radio" name="jenis_kelamin" id="form_jenis_kelamin_1" value="Perempuan"> Perempuan</label></div></span></div>',
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
                '<div><label for="form_hobi">Hobi</label> <span><div><label><input type="checkbox" name="hobi[]" id="form_hobi_0" value="Sepeda Jarak Pendek"> Sepeda Jarak Pendek</label><label><input type="checkbox" name="hobi[]" id="form_hobi_1" value="Sepeda Jarak Menengah"> Sepeda Jarak Menengah</label><label><input type="checkbox" name="hobi[]" id="form_hobi_2" value="Sepeda Jarak Jauh"> Sepeda Jarak Jauh</label></div></span></div>',
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
                '<div><label for="form_lulusan">Lulusan</label> <span><select type="choice" name="lulusan" id="form_lulusan"><option value="SMK">SMK</option><option value="SMP">SMP</option></select></span></div>',
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
                '<div><label for="form_lulusan">Lulusan</label> <span><select type="choice" name="lulusan" id="form_lulusan"><option value="SMK">SMK</option><option value="SMP">SMP</option></select></span></div>',
                new Field('lulusan', 'choice', array(
                    'label' => 'Lulusan',
                    'id' => 'form_lulusan',
                    'items' => function () {
                        return array(
                            'SMK' => 'SMK',
                            'SMP' => 'SMP',
                        );
                    },
                )),
            ),
            array(
                'Choice items should be an array or a callable that returns array.',
                new Field('lulusan', 'choice', array(
                    'label' => 'Lulusan',
                    'id' => 'form_lulusan',
                    'items' => function () {
                        return 'foo';
                    },
                )),
                'LogicException',
            ),
            array(
                'Choice items should be an array or a callable that returns array.',
                new Field('lulusan', 'choice', array(
                    'label' => 'Lulusan',
                    'id' => 'form_lulusan',
                    'items' => 'invalid_function',
                )),
                'LogicException',
            ),
            array(
                '<div><label><input type="checkbox" name="setuju" id="form_setuju"> Setuju</label></div>',
                new Field('setuju', 'checkbox', array(
                    'label' => 'Setuju',
                    'id' => 'form_setuju',
                )),
            ),
            array(
                '<div><label><input type="radio" name="setuju" id="form_setuju"> Setuju</label></div>',
                new Field('setuju', 'radio', array(
                    'label' => 'Setuju',
                    'id' => 'form_setuju',
                )),
            ),
            array(
                '<div><a class="btn btn-link" href="#">Back</a></div>',
                new Field('back', 'link', array(
                    'label' => 'Back',
                    'attr' => array(
                        'class' => 'btn btn-link',
                    ),
                )),
            ),
        );
    }
}
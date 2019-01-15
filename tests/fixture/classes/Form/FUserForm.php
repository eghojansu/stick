<?php

declare(strict_types=1);

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fixture\Form;

use Fal\Stick\Form\Form;

class FUserForm extends Form
{
    public function build(array $options = null)
    {
        $this->addField('username');

        return $this;
    }
}

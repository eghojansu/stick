<?php

declare(strict_types=1);

namespace Fixture\Form;

use Fal\Stick\Web\Form\Form;

class FUserForm extends Form
{
    protected function build(array $options, array $data)
    {
        $this->addField('username');
    }
}

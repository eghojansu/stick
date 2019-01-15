<?php

declare(strict_types=1);

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

<?php

declare(strict_types=1);

namespace Fixture\Form;

use Fal\Stick\Html\Form;

class FUserForm extends Form
{
    public function build(array $options = null): Form
    {
        $this->add('username');

        return $this;
    }
}

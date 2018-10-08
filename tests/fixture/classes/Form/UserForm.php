<?php

declare(strict_types=1);

namespace Fixture\Form;

use Fal\Stick\Library\Html\Form;

class UserForm extends Form
{
    /**
     * {@inheritdoc}
     */
    protected function doBuild(array $options)
    {
        $this
            ->add('username')
        ;
    }
}

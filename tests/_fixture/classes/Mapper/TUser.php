<?php

declare(strict_types=1);

namespace Fixture\Mapper;

use Fal\Stick\Database\Mapper;

class TUser extends Mapper
{
    protected $name = 'user';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        $this->row->set('foo', '1 + 1');
    }
}

<?php

declare(strict_types=1);

namespace Fixture\Mapper;

use Fal\Stick\Database\Mapper;

class TTb extends Mapper
{
    protected $name = 'tb';

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        $this->row->set('inc_id', 'tb.id + 1');
    }
}

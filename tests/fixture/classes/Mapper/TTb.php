<?php

declare(strict_types=1);

namespace Fixture\Mapper;

use Fal\Stick\Sql\Mapper;

class TTb extends Mapper
{
    protected $table = 'tb';
    protected $adhoc = array(
        'inc_id' => array('expr' => '(tb.id + 1)', 'value' => null),
    );
}

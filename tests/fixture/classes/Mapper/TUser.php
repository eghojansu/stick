<?php

declare(strict_types=1);

namespace Fixture\Mapper;

use Fal\Stick\Sql\Mapper;

class TUser extends Mapper
{
    protected $_table = 'user';
    protected $_adhoc = array(
        'foo' => array('expr' => '(1 + 1)', 'value' => null),
    );
}

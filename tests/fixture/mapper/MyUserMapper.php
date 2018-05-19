<?php

declare(strict_types=1);

namespace Fal\Stick\Test\fixture\mapper;

use Fal\Stick\Sql\Mapper;

class MyUserMapper extends Mapper
{
    protected $table = 'user';
}

<?php declare(strict_types=1);

namespace Fal\Stick\Test\fixture\classes;

use Fal\Stick\Database\Mapper;

class UserMapper extends Mapper
{
    protected $map = UserEntity::class;
}

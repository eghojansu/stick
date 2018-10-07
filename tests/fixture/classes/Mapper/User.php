<?php

declare(strict_types=1);

namespace Fixture\Mapper;

use Fal\Stick\Library\Sql\Mapper;

class User extends Mapper
{
    public function dateTime()
    {
        return new \DateTime();
    }
}

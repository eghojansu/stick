<?php

namespace FixtureMapper;

use Fal\Stick\Sql\Mapper;

class User extends Mapper
{
    public function dateTime()
    {
        return new \DateTime();
    }
}

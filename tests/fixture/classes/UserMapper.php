<?php declare(strict_types=1);

namespace Fal\Stick\Test\fixture\classes;

use Fal\Stick\Database\Sql\Mapper;

class UserMapper extends Mapper
{
    private $ctr = 0;

    public function ctr()
    {
        return ++$this->ctr;
    }
}

<?php declare(strict_types=1);

namespace Fal\Stick\Test\fixture\services;

class ReqDateTimeClass
{
    public $dt;

    public function __construct(\DateTime $dt)
    {
        $this->dt = $dt;
    }
}

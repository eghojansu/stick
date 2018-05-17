<?php

declare(strict_types=1);

namespace Fal\Stick\Test\fixture\services;

class DoNotRegisterClass
{
    public $time;

    public function __construct()
    {
        $this->time = microtime(true);
    }
}

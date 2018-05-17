<?php

declare(strict_types=1);

namespace Fal\Stick\Test\fixture\services;

class VariadicArgClass
{
    public $args;

    public function __construct(...$args)
    {
        $this->args = $args;
    }
}

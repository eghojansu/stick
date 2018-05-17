<?php

declare(strict_types=1);

namespace Fal\Stick\Test\fixture\services;

class WithConstructorArgClass
{
    public $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }
}

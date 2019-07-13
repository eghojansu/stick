<?php

declare(strict_types=1);

namespace Fal\Stick\TestSuite\Classes;

class MyStdCreator
{
    public function __invoke()
    {
        return new \stdClass();
    }
}

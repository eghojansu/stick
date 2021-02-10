<?php

namespace Fixtures;

class Invokable
{
    public function __invoke(...$arguments)
    {
        return $arguments;
    }
}

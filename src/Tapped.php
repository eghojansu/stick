<?php

namespace Ekok\Stick;

class Tapped
{
    public function __construct(public object $target)
    {}

    public function __call($name, $arguments)
    {
        $this->target->{$name}(...$arguments);

        return $this->target;
    }
}

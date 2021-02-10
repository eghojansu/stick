<?php

namespace Fixtures;

class Simple
{
    public static function outArguments(...$arguments)
    {
        return implode(' ', $arguments);
    }
}

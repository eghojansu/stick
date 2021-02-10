<?php

namespace Fixtures;

class CyclicB
{
    public $a;

    public function __construct(CyclicA $a)
    {
        $this->a = $a;
    }
}
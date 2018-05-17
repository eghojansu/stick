<?php

declare(strict_types=1);

namespace Fal\Stick\Test\fixture\services;

class ReqNoConstructorClass
{
    public $nocon;

    public function __construct(NoConstructorClass $nocon)
    {
        $this->nocon = $nocon;
    }
}

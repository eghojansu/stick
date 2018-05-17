<?php

declare(strict_types=1);

namespace Fal\Stick\Test\fixture\services;

class WithConstructorDefaultArgClass
{
    public $id;

    public function __construct(string $id = 'foo')
    {
        $this->id = $id;
    }

    public function getId(string $prefix = '', string $suffix = '')
    {
        return $prefix.$this->id.$suffix;
    }
}

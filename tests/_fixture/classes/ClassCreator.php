<?php

declare(strict_types=1);

namespace Fixture;

class ClassCreator
{
    public function createStd()
    {
        return new \stdClass();
    }

    public function bootStd($std)
    {
        $std->booted = true;
    }
}

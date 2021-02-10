<?php

declare(strict_types=1);

namespace Ekok\Stick;

class Event
{
    private $propagationStopped = false;

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): self
    {
        $this->propagationStopped = true;

        return $this;
    }
}

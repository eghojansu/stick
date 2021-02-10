<?php

declare(strict_types=1);

namespace Ekok\Stick\Event;

class ControllerArgumentsEvent extends RequestEvent
{
    private $arguments;
    private $argumentsSet = false;

    public function __construct(array $arguments)
    {
        $this->arguments = $arguments;
    }

    public function hasArguments(): bool
    {
        return $this->argumentsSet;
    }

    public function getArguments(): array
    {
        return $this->arguments;
    }

    public function setArguments(array $arguments, bool $set = true): self
    {
        $this->arguments = $arguments;
        $this->argumentsSet = $set;

        return $this;
    }
}

<?php

declare(strict_types=1);

namespace Ekok\Stick\Event;

class ControllerEvent extends RequestEvent
{
    private $controller;
    private $controllerSet = false;

    public function __construct($controller)
    {
        $this->controller = $controller;
    }

    public function hasController(): bool
    {
        return $this->controllerSet;
    }

    public function getController()
    {
        return $this->controller;
    }

    public function setController($controller, bool $set = true): self
    {
        $this->controller = $controller;
        $this->controllerSet = $set;

        return $this;
    }
}

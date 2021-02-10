<?php

declare(strict_types=1);

namespace Ekok\Stick\Event;

class FinishRequestEvent extends RequestEvent
{
    private $route;
    private $controller;
    private $arguments;

    public function __construct(?array $route, ?callable $controller, ?array $arguments, $response)
    {
        $this->route = $route;
        $this->controller = $controller;
        $this->arguments = $arguments;
        $this->setResponse($response, false);
    }

    public function getRoute(): ?array
    {
        return $this->route;
    }

    public function getController(): ?callable
    {
        return $this->controller;
    }

    public function getArguments(): ?array
    {
        return $this->arguments;
    }
}

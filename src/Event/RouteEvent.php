<?php

declare(strict_types=1);

namespace Ekok\Stick\Event;

class RouteEvent extends RequestEvent
{
    private $route;

    public function __construct(array $route)
    {
        $this->route = $route;
    }

    public function getRoute(): array
    {
        return $this->route;
    }
}

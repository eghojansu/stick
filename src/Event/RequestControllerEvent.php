<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Ekok\Stick\Event;

class RequestControllerEvent extends RequestEvent
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

    public function setController($controller, bool $set = true): static
    {
        $this->controller = $controller;
        $this->controllerSet = $set;

        return $this;
    }
}

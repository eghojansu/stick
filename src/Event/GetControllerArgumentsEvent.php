<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Event;

use Fal\Stick\Core;
use Fal\Stick\Event;

class GetControllerArgumentsEvent extends Event
{
    protected $core;
    protected $controller;
    protected $arguments;

    public function __construct(Core $core, callable $controller, array $arguments)
    {
        $this->core = $core;
        $this->controller = $controller;
        $this->arguments = $arguments;
    }

    public function getCore()
    {
        return $this->core;
    }

    public function getController()
    {
        return $this->controller;
    }

    public function setController(callable $controller)
    {
        $this->controller = $controller;

        return $this;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;

        return $this;
    }
}

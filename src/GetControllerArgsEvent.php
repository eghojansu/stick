<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick;

/**
 * Allows to modify controller and its arguments before executed.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class GetControllerArgsEvent extends Event
{
    /**
     * Controller object.
     *
     * @var mixed
     */
    private $controller;

    /**
     * Controller arguments.
     *
     * @var array
     */
    private $args;

    /**
     * Class constructor.
     *
     * @param mixed $controller
     * @param array $args
     */
    public function __construct($controller, array $args)
    {
        $this->controller = $controller;
        $this->args = $args;
    }

    /**
     * Returns controller object.
     *
     * @return callable
     */
    public function getController()
    {
        return $this->controller;
    }

    /**
     * Sets controller object.
     *
     * @param callable $controller
     *
     * @return GetControllerArgsEvent
     */
    public function setController($controller)
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * Returns controller arguments.
     *
     * @return array
     */
    public function getArgs()
    {
        return $this->args;
    }

    /**
     * Sets controller arguments.
     *
     * @param array $args
     *
     * @return GetControllerArgsEvent
     */
    public function setArgs(array $args)
    {
        $this->args = $args;

        return $this;
    }
}

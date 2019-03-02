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

namespace Fal\Stick\Web\Event;

use Fal\Stick\Web\KernelInterface;
use Fal\Stick\Web\Request;

/**
 * Filter controller arguments.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class FilterControllerArgumentsEvent extends KernelEvent
{
    /**
     * @var callable
     */
    protected $controller;

    /**
     * @var array
     */
    protected $arguments;

    /**
     * Class constructor.
     *
     * @param KernelInterface $kernel
     * @param Request         $request
     * @param int             $requestType
     * @param callable        $controller
     * @param array           $arguments
     */
    public function __construct(KernelInterface $kernel, Request $request, int $requestType, callable $controller, array $arguments)
    {
        parent::__construct($kernel, $request, $requestType);

        $this->controller = $controller;
        $this->arguments = $arguments;
    }

    /**
     * Returns controller.
     *
     * @return callable
     */
    public function getController(): callable
    {
        return $this->controller;
    }

    /**
     * Returns controller arguments.
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Assign controller arguments.
     *
     * @param array $arguments
     *
     * @return FilterControllerArgumentsEvent
     */
    public function setArguments(array $arguments): FilterControllerArgumentsEvent
    {
        $this->arguments = $arguments;

        return $this;
    }
}

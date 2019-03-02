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
 * Filter controller.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class FilterControllerEvent extends KernelEvent
{
    /**
     * @var callable
     */
    protected $controller;

    /**
     * Class constructor.
     *
     * @param KernelInterface $kernel
     * @param Request         $request
     * @param int             $requestType
     * @param callable        $controller
     */
    public function __construct(KernelInterface $kernel, Request $request, int $requestType, callable $controller)
    {
        parent::__construct($kernel, $request, $requestType);

        $this->controller = $controller;
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
     * Assign controller.
     *
     * @param callable $controller
     *
     * @return FilterControllerEvent
     */
    public function setController(callable $controller): FilterControllerEvent
    {
        $this->controller = $controller;

        return $this;
    }
}

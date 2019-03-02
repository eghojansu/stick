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
use Fal\Stick\EventDispatcher\Event;

/**
 * Get request event.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class GetRequestEvent extends Event
{
    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var Request
     */
    protected $request;

    /**
     * Class constructor.
     *
     * @param KernelInterface $kernel
     */
    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    /**
     * Returns kernel instance.
     *
     * @return KernelInterface
     */
    public function getKernel(): KernelInterface
    {
        return $this->kernel;
    }

    /**
     * Returns request.
     *
     * @return Request|null
     */
    public function getRequest(): ?Request
    {
        return $this->request;
    }

    /**
     * Assign request.
     *
     * @param Request $request
     *
     * @return GetRequestEvent
     */
    public function setRequest(Request $request): GetRequestEvent
    {
        $this->request = $request;

        return $this;
    }
}

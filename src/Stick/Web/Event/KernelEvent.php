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

use Fal\Stick\EventDispatcher\Event;
use Fal\Stick\Web\KernelInterface;
use Fal\Stick\Web\Request;

/**
 * Kernel event.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class KernelEvent extends Event
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
     * @var int
     */
    protected $requestType;

    /**
     * Class constructor.
     *
     * @param KernelInterface $kernel
     * @param Request         $request
     * @param int             $requestType
     */
    public function __construct(KernelInterface $kernel, Request $request, int $requestType)
    {
        $this->kernel = $kernel;
        $this->request = $request;
        $this->requestType = $requestType;
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
     * Returns request instance.
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Returns request type.
     *
     * @return int
     */
    public function getRequestType(): int
    {
        return $this->requestType;
    }

    /**
     * Returns true if request is master.
     *
     * @return bool
     */
    public function isMasterRequest(): bool
    {
        return KernelInterface::MASTER_REQUEST === $this->requestType;
    }
}

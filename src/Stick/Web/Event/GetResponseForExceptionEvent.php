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
 * Get response for exception.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class GetResponseForExceptionEvent extends GetResponseEvent
{
    /**
     * @var Throwable
     */
    protected $exception;

    /**
     * Class constructor.
     *
     * @param KernelInterface $kernel
     * @param Request         $request
     * @param int             $requestType
     * @param Throwable       $exception
     */
    public function __construct(KernelInterface $kernel, Request $request, int $requestType, \Throwable $exception)
    {
        parent::__construct($kernel, $request, $requestType);

        $this->exception = $exception;
    }

    /**
     * Returns exception.
     *
     * @return Throwable
     */
    public function getException(): \Throwable
    {
        return $this->exception;
    }
}

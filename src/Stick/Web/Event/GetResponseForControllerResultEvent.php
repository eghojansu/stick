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
 * Get response for controller result.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class GetResponseForControllerResultEvent extends GetResponseEvent
{
    /**
     * @var mixed
     */
    protected $result;

    /**
     * Class constructor.
     *
     * @param KernelInterface $kernel
     * @param Request         $request
     * @param int             $requestType
     * @param mixed           $result
     */
    public function __construct(KernelInterface $kernel, Request $request, int $requestType, $result)
    {
        parent::__construct($kernel, $request, $requestType);

        $this->result = $result;
    }

    /**
     * Returns result.
     *
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }
}

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
use Fal\Stick\Web\Response;

/**
 * Filter response.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class FilterResponseEvent extends GetResponseEvent
{
    /**
     * Class constructor.
     *
     * @param KernelInterface $kernel
     * @param Request         $request
     * @param int             $requestType
     * @param Response        $response
     */
    public function __construct(KernelInterface $kernel, Request $request, int $requestType, Response $response)
    {
        parent::__construct($kernel, $request, $requestType);

        $this->response = $response;
    }
}

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

use Fal\Stick\Web\Response;

/**
 * Get response.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class GetResponseEvent extends KernelEvent
{
    /**
     * @var Response
     */
    protected $response;

    /**
     * Returns true if has response.
     *
     * @return bool
     */
    public function hasResponse(): bool
    {
        return isset($this->response);
    }

    /**
     * Returns response instance.
     *
     * @return Response|null
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }

    /**
     * Assign response.
     *
     * @param Response $response
     *
     * @return GetResponseEvent
     */
    public function setResponse(Response $response): GetResponseEvent
    {
        $this->response = $response;
        $this->stopPropagation();

        return $this;
    }
}

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

namespace Ekok\Stick\Event;

use Ekok\Stick\Event;

class RequestEvent extends Event
{
    private $response;
    private $responseSet = false;

    public function hasResponse(): bool
    {
        return $this->responseSet;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function setResponse($response, bool $set = true): static
    {
        $this->response = $response;
        $this->responseSet = $set;

        return $this;
    }
}

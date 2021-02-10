<?php

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

    public function setResponse($response, bool $set = true): self
    {
        $this->response = $response;
        $this->responseSet = $set;

        return $this;
    }
}

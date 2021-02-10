<?php

declare(strict_types=1);

namespace Ekok\Stick\Event;

class ResponseEvent extends RequestEvent
{
    public function __construct($response)
    {
        $this->setResponse($response, false);
    }
}

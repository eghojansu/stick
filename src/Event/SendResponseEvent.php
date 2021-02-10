<?php

declare(strict_types=1);

namespace Ekok\Stick\Event;

class SendResponseEvent extends RequestEvent
{
    private $sent = false;

    public function sent(): bool
    {
        return $this->sent;
    }

    public function send(): self
    {
        $this->sent = true;

        return $this;
    }

    public function unsend(): self
    {
        $this->sent = false;

        return $this;
    }
}

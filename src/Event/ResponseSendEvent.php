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

class ResponseSendEvent extends RequestEvent
{
    private $sent = false;

    public function sent(): bool
    {
        return $this->sent;
    }

    public function send(bool $send = true): static
    {
        $this->sent = $send;

        return $this;
    }
}

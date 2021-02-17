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

class RequestRerouteEvent extends RequestEvent
{
    private $path;
    private $url;
    private $permanent;
    private $headers;
    private $resolved = false;

    public function __construct(string $path, $url, bool $permanent, array $headers = null)
    {
        $this->path = $path;
        $this->url = $url;
        $this->permanent = $permanent;
        $this->headers = $headers;
    }

    public function isResolved(): bool
    {
        return $this->resolved;
    }

    public function setResolved(bool $resolved = true): static
    {
        $this->resolved = $resolved;

        return $this;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function isPermanent(): bool
    {
        return $this->permanent;
    }

    public function getHeaders(): ?array
    {
        return $this->headers;
    }
}

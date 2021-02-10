<?php

declare(strict_types=1);

namespace Ekok\Stick\Event;

class RerouteEvent extends RequestEvent
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

    public function setResolved(bool $resolved = true): self
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

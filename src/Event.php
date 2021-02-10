<?php

declare(strict_types=1);

namespace Ekok\Stick;

class Event
{
    private $propagationStopped = false;
    private $data;
    private $dataSet = false;

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): self
    {
        $this->propagationStopped = true;

        return $this;
    }

    public function hasData(): bool
    {
        return $this->dataSet;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data, bool $set = true): self
    {
        $this->data = $data;
        $this->dataSet = $set;

        return $this;
    }
}

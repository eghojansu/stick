<?php

namespace Ekok\Stick;

class Looped
{
    private bool $store = true;
    public array $carry = array();
    public array $result = array();
    public bool $stopped = false;
    public mixed $key;

    function __construct(
        public iterable $origin,
        callable $fn,
        public bool $storing = true,
        public mixed $value = null,
        bool $assoc = true,
    )
    {
        foreach ($origin as $key => $value) {
            $result = $fn($value, $key, $this);

            // last executed value
            if ($this->store) {
                $this->value = $result === $this ? $value : $result;
            }

            if ($this->storing) {
                if ($assoc) {
                    $this->result[$key] = $this->value;
                } else {
                    $this->result[] = $this->value;
                }
            }

            $this->key = $key;

            if ($this->stopped) {
                break;
            }

            $this->store = true;
        }
    }

    public function stop(bool $stopped = true): static
    {
        $this->stopped = $stopped;

        return $this;
    }

    public function store(bool $storing = true): static
    {
        $this->storing = $storing;

        return $this;
    }

    public function last(mixed $value): static
    {
        $this->value = $value;
        $this->store = false;

        return $this;
    }

    public function add(string $name, mixed $value): static
    {
        $this->carry[$name] = $value;

        return $this;
    }
}

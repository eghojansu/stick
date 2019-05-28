<?php

namespace Fal\Stick\TestSuite\Classes;

use Fal\Stick\Magic;

class SampleMagic extends Magic
{
    private $hive;

    public function has($key): bool
    {
        return isset($this->hive[$key]);
    }

    public function &get($key)
    {
        if (isset($this->hive[$key])) {
            return $this->hive[$key];
        }

        $default = null;

        return $default;
    }

    public function set($key, $value): Magic
    {
        $this->hive[$key] = $value;

        return $this;
    }

    public function rem($key): Magic
    {
        unset($this->hive[$key]);

        return $this;
    }
}

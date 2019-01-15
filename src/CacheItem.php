<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 12, 2019 10:10
 */

namespace Fal\Stick;

class CacheItem
{
    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var int
     */
    protected $ttl;

    /**
     * @var float
     */
    protected $time;

    public function __construct($value, $ttl = null, $time = null)
    {
        $this->value = $value;
        $this->ttl = isset($ttl) ? $ttl : 0;
        $this->time = isset($time) ? $time : microtime(true);
    }

    public function isValid()
    {
        return 0 === $this->ttl || ($this->time + $this->ttl > microtime(true));
    }

    public function isExpired()
    {
        return !$this->isValid();
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getTime()
    {
        return $this->time;
    }

    public function getTtl()
    {
        return $this->ttl;
    }

    public function toString()
    {
        return serialize($this);
    }

    public static function fromString($text)
    {
        $cache = unserialize($text);

        if (!$cache instanceof CacheItem) {
            throw new \RuntimeException('Invalid cache source');
        }

        return $cache;
    }
}

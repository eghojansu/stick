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

namespace Fal\Stick\Cache;

/**
 * Cache item.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
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

    /**
     * Create cache item from string.
     *
     * @param string $text
     *
     * @return CacheItem
     *
     * @throws RuntimeException If cache invalid
     */
    public static function fromString($text): CacheItem
    {
        $cache = unserialize($text);

        if (!$cache instanceof self) {
            throw new \RuntimeException('Invalid cache source.');
        }

        return $cache;
    }

    /**
     * Class constructor.
     *
     * @param mixed $value
     * @param int   $ttl
     * @param float $time
     */
    public function __construct($value, int $ttl = null, float $time = null)
    {
        $this->value = $value;
        $this->ttl = $ttl ?? 0;
        $this->time = $time ?? microtime(true);
    }

    /**
     * Returns true if cache item valid.
     *
     * @return bool
     */
    public function valid(): bool
    {
        return 0 === $this->ttl || ($this->time + $this->ttl > microtime(true));
    }

    /**
     * Returns true if cache expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return !$this->valid();
    }

    /**
     * Returns cache value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns cache time.
     *
     * @return float
     */
    public function getTime(): float
    {
        return $this->time;
    }

    /**
     * Returns cache ttl.
     *
     * @return int
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * Returns string of cache item.
     *
     * @return string
     */
    public function toString(): string
    {
        return serialize($this);
    }
}

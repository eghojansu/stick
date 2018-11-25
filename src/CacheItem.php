<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Nov 25, 2018 12:58
 */

namespace Fal\Stick;

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
     * @var float
     */
    protected $time;

    /**
     * @var int
     */
    protected $ttl;

    /**
     * Class constructor.
     *
     * @param mixed    $value
     * @param int|null $time
     * @param int|null $ttl
     */
    public function __construct($value = null, float $time = null, int $ttl = null)
    {
        $this->value = $value;
        $this->time = $time ?? microtime(true);
        $this->ttl = $ttl ?? 0;
    }

    /**
     * Returns true if cache item is valid.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return !($this->isEmpty() || $this->isExpired());
    }

    /**
     * Returns true if value is null or an empty string.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return null === $this->value || '' === $this->value;
    }

    /**
     * Returns true if cache item is expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return 0 !== $this->ttl && $this->time + $this->ttl < microtime(true);
    }

    /**
     * Returns cache item value.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Returns cache item time.
     *
     * @return float
     */
    public function getTime(): float
    {
        return $this->time;
    }

    /**
     * Returns cache lifetime.
     *
     * @return int
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * Returns serialized cache, timestamp and ttl.
     *
     * @return string
     */
    public function toString(): string
    {
        return serialize(array($this->value, $this->time, $this->ttl));
    }

    /**
     * Returns unserialized serialized cache.
     *
     * @param string|null $str
     *
     * @return CacheItem|null
     */
    public static function fromString(string $str = null): ?CacheItem
    {
        if ($str && ($cache = (array) unserialize($str)) && 3 === count($cache)) {
            list($value, $time, $ttl) = $cache;

            return new self($value, $time, $ttl);
        }

        return null;
    }
}

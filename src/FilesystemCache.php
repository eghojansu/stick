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

namespace Fal\Stick;

/**
 * Filesystem cache implementation.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class FilesystemCache implements CacheInterface
{
    /**
     * @var string
     */
    private $directory;

    /**
     * @var string
     */
    private $seed;

    /**
     * Class constructor.
     *
     * @param string $directory
     */
    public function __construct(string $directory)
    {
        $this->directory = $directory;

        is_dir($directory) || mkdir($directory, 0755, true);
    }

    /**
     * {@inheritdoc}
     */
    public function seed(string $seed): CacheInterface
    {
        $this->seed = $seed;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return (bool) $this->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): ?CacheItem
    {
        $cache = CacheItem::fromString(is_file($file = $this->key($key)) ? file_get_contents($file) : null);

        if ($cache && $cache->isExpired()) {
            $cache = null;
            $this->clear($key);
        }

        return $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, CacheItem $value): bool
    {
        return false !== file_put_contents($this->key($key), $value->toString());
    }

    /**
     * {@inheritdoc}
     */
    public function clear(string $key): bool
    {
        return is_file($file = $this->key($key)) ? unlink($file) : true;
    }

    /**
     * {@inheritdoc}
     */
    public function reset(string $suffix = ''): CacheInterface
    {
        foreach (glob($this->directory.$this->seed.'.*'.$suffix) as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        return $this;
    }

    /**
     * Returns cache key.
     *
     * @param string $key
     *
     * @return string
     */
    private function key(string $key): string
    {
        return $this->directory.$this->seed.'.'.str_replace(array('\\', '/'), '', $key);
    }
}

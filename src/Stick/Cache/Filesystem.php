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
 * Filesystem cache.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Filesystem implements CacheInterface
{
    /**
     * @var string
     */
    protected $directory;

    /**
     * Class constructor.
     *
     * @param string $directory
     */
    public function __construct(string $directory)
    {
        $this->directory = $directory;

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists(string $key): bool
    {
        return null !== $this->get($key);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key): ?CacheItem
    {
        $file = $this->directory.str_replace(array('\\', '/'), '', $key);

        if (is_file($file) && $raw = file_get_contents($file)) {
            $cache = CacheItem::fromString($raw);

            if ($cache->valid()) {
                return $cache;
            }

            $this->clear($key);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, CacheItem $item): bool
    {
        $file = $this->directory.str_replace(array('\\', '/'), '', $key);

        return false !== file_put_contents($file, $item->toString());
    }

    /**
     * {@inheritdoc}
     */
    public function clear(string $key): bool
    {
        $file = $this->directory.str_replace(array('\\', '/'), '', $key);

        return file_exists($file) ? unlink($file) : false;
    }

    /**
     * {@inheritdoc}
     */
    public function reset(string $suffix = null): int
    {
        $affected = 0;

        foreach (glob($this->directory.'*'.$suffix) as $file) {
            $affected += (int) unlink($file);
        }

        return $affected;
    }
}

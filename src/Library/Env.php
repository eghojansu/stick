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

namespace Fal\Stick\Library;

use Fal\Stick\Util;

/**
 * Environment helper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class Env
{
    /**
     * @var array
     */
    private static $env = array();

    /**
     * Load env file.
     *
     * @param mixed $files
     */
    public static function load($files): void
    {
        foreach (Util::arr($files) as $file) {
            if (is_file($file)) {
                self::merge(Util::requireFile($file, array()));
            }
        }
    }

    /**
     * Returns env data.
     *
     * @return array
     */
    public static function all(): array
    {
        return self::$env;
    }

    /**
     * Returns env value, check global $_ENV too.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function get(string $name, $default = null)
    {
        return self::$env[$name] ?? $_ENV[$name] ?? $default;
    }

    /**
     * Sets env value.
     *
     * @param string $name
     * @param mixed  $value
     */
    public static function set(string $name, $value): void
    {
        self::$env[$name] = $value;
    }

    /**
     * Merge env.
     *
     * @param array $env
     */
    public static function merge(array $env): void
    {
        self::$env = array_replace_recursive(self::$env, $env);
    }

    /**
     * Reset env.
     */
    public static function reset()
    {
        self::$env = array();
    }
}

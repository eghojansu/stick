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

/**
 * String util.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class Str
{
    /**
     * Returns normalized slashes.
     *
     * @param string $str
     *
     * @return string
     */
    public static function fixslashes(string $str): string
    {
        return strtr($str, '\\', '/');
    }

    /**
     * Returns substring before needle.
     *
     * If needle not found it returns defaults.
     *
     * @param string      $str
     * @param string      $needle
     * @param string|null $default
     * @param bool        $with_needle
     *
     * @return string
     */
    public static function cutbefore(string $str, string $needle, string $default = null, bool $with_needle = false): string
    {
        if ($str && $needle && false !== ($pos = strpos($str, $needle))) {
            return substr($str, 0, $pos + ((int) $with_needle));
        }

        return $default ?? $str;
    }

    /**
     * Returns substring after needle.
     *
     * If needle not found it returns defaults.
     *
     * @param string      $str
     * @param string      $needle
     * @param string|null $default
     * @param bool        $with_needle
     *
     * @return string
     */
    public static function cutafter(string $str, string $needle, string $default = null, bool $with_needle = false): string
    {
        if ($str && $needle && false !== ($pos = strrpos($str, $needle))) {
            return substr($str, $pos + ((int) !$with_needle));
        }

        return $default ?? $str;
    }

    /**
     * Returns substring after prefix removed.
     *
     * @param string      $str
     * @param string      $prefix
     * @param string|null $default
     *
     * @return string
     */
    public static function cutprefix(string $str, string $prefix, string $default = null): string
    {
        if ($str && $prefix && substr($str, 0, $cut = strlen($prefix)) === $prefix) {
            return substr($str, $cut) ?: (string) $default;
        }

        return $default ?? $str;
    }

    /**
     * Returns substring after suffix removed.
     *
     * @param string      $str
     * @param string      $suffix
     * @param string|null $default
     *
     * @return string
     */
    public static function cutsuffix(string $str, string $suffix, string $default = null): string
    {
        if ($str && $suffix && substr($str, $cut = -strlen($suffix)) === $suffix) {
            return substr($str, 0, $cut) ?: (string) $default;
        }

        return $default ?? $str;
    }

    /**
     * Returns true if string starts with prefix.
     *
     * @param string $str
     * @param string $prefix
     *
     * @return bool
     */
    public static function startswith(string $str, string $prefix): bool
    {
        return substr($str, 0, strlen($prefix)) === $prefix;
    }

    /**
     * Returns true if string ends with suffix.
     *
     * @param string $str
     * @param string $suffix
     *
     * @return bool
     */
    public static function endswith(string $str, string $suffix): bool
    {
        return substr($str, -1 * strlen($suffix)) === $suffix;
    }

    /**
     * Returns camelCase string from snake_case.
     *
     * @param string $str
     *
     * @return string
     */
    public static function camelCase(string $str): string
    {
        return lcfirst(str_replace(' ', '', ucwords(strtr($str, '_', ' '))));
    }

    /**
     * Returns snake_case string from camelCase.
     *
     * @param string $str
     *
     * @return string
     */
    public static function snakeCase(string $str): string
    {
        return strtolower(preg_replace('/(?!^)\p{Lu}/u', '_\0', $str));
    }

    /**
     * Returns "Title Case" string from snake_case.
     *
     * @param string $str
     *
     * @return string
     */
    public static function titleCase(string $str): string
    {
        return ucwords(strtr($str, '_', ' '));
    }

    /**
     * Returns "Dash-Case" string from "DASH_CASE".
     *
     * @param string $name
     *
     * @return string
     */
    public static function dashCase(string $name): string
    {
        return strtr(ucwords(strtr(strtolower($name), '_', ' ')), ' ', '-');
    }

    /**
     * Returns class name.
     *
     * @param string|object $class
     *
     * @return string
     */
    public static function className($class): string
    {
        $ns = '\\'.ltrim(is_object($class) ? get_class($class) : $class, '\\');

        return substr(strrchr($ns, '\\'), 1);
    }
}

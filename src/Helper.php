<?php

/**
 * This file is part of the eghojansu/stick project.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick;

/**
 * Common helper, to make our life easier.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class Helper
{
    /**
     * Get class classname.
     *
     * @param string|object $class
     *
     * @return string
     */
    public static function classname($class): string
    {
        $ns = is_object($class) ? get_class($class) : $class;
        $pos = strrpos($ns, '\\');

        return false === $pos ? $ns : substr($ns, $pos + 1);
    }

    /**
     * Cast to PHP-value.
     *
     * @param mixed $val
     *
     * @return mixed
     */
    public static function cast($val)
    {
        if (is_numeric($val)) {
            return $val + 0;
        } elseif (is_scalar($val)) {
            $val = trim($val);

            if (preg_match('/^\w+$/i', $val) && defined($val)) {
                return constant($val);
            }
        }

        return $val;
    }

    /**
     * Convert snake_case to camelCase.
     *
     * @param string $str
     *
     * @return string
     */
    public static function camelcase(string $str): string
    {
        return lcfirst(str_replace(' ', '', ucwords(strtr($str, '_', ' '))));
    }

    /**
     * Convert camelCase to snake_case.
     *
     * @param string $str
     *
     * @return string
     */
    public static function snakecase(string $str): string
    {
        return strtolower(preg_replace('/(?!^)\p{Lu}/u', '_\0', $str));
    }

    /**
     * Check if string starts with prefix.
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
     * startswith case-insensitive.
     *
     * @param string $str
     * @param string $prefix
     *
     * @return bool
     */
    public static function istartswith(string $str, string $prefix): bool
    {
        return substr(strtolower($str), 0, strlen($prefix)) === strtolower($prefix);
    }

    /**
     * Check if string ends with suffix.
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
     * endswith case-insensitive.
     *
     * @param string $str
     * @param string $suffix
     *
     * @return bool
     */
    public static function iendswith(string $str, string $suffix): bool
    {
        return substr(strtolower($str), -1 * strlen($suffix)) === strtolower($suffix);
    }

    /**
     * Cut string after prefix.
     *
     * @param string $str
     * @param string $prefix
     * @param string $default
     *
     * @return string
     */
    public static function cutafter(string $str, string $prefix, string $default = ''): string
    {
        $cut = strlen($prefix);

        return substr($str, 0, $cut) === $prefix ? substr($str, $cut) : $default;
    }

    /**
     * cutafter case-insensitive.
     *
     * @param string $str
     * @param string $prefix
     * @param string $default
     *
     * @return string
     */
    public static function icutafter(string $str, string $prefix, string $default = ''): string
    {
        $cut = strlen($prefix);

        return substr(strtolower($str), 0, $cut) === strtolower($prefix) ? substr($str, $cut) : $default;
    }

    /**
     * Cut string before suffix.
     *
     * @param string $str
     * @param string $suffix
     * @param string $default
     *
     * @return string
     */
    public static function cutbefore(string $str, string $suffix, string $default = ''): string
    {
        $cut = strlen($suffix) * -1;

        return substr($str, $cut) === $suffix ? substr($str, 0, $cut) : $default;
    }

    /**
     * cutbefore case-insensitive.
     *
     * @param string $str
     * @param string $suffix
     * @param string $default
     *
     * @return string
     */
    public static function icutbefore(string $str, string $suffix, string $default = ''): string
    {
        $cut = strlen($suffix) * -1;

        return substr(strtolower($str), $cut) === strtolower($suffix) ? substr($str, 0, $cut) : $default;
    }

    /**
     * Parse string expression.
     *
     * Example:
     *     foo:arg,arg2|bar:arg|baz:["array arg"]|qux:{"arg":"foo"}
     *
     * @param string $expr
     *
     * @return array
     */
    public static function parsexpr(string $expr): array
    {
        $len = strlen($expr);
        $res = [];
        $tmp = '';
        $process = false;
        $args = [];
        $quote = null;
        $astate = 0;
        $jstate = 0;

        for ($ptr = 0; $ptr < $len; ++$ptr) {
            $char = $expr[$ptr];
            $prev = $expr[$ptr - 1] ?? null;

            if (('"' === $char || "'" === $char) && '\\' !== $prev) {
                if ($quote) {
                    $quote = $quote === $char ? null : $quote;
                } else {
                    $quote = $char;
                }
                $tmp .= $char;
            } elseif (!$quote) {
                if (':' === $char && 0 === $jstate) {
                    // next chars is arg
                    $args[] = self::cast($tmp);
                    $tmp = '';
                } elseif (',' === $char && 0 === $astate && 0 === $jstate) {
                    if ($tmp) {
                        $args[] = self::cast($tmp);
                        $tmp = '';
                    }
                } elseif ('|' === $char) {
                    $process = true;
                    if ($tmp) {
                        $args[] = self::cast($tmp);
                        $tmp = '';
                    }
                } elseif ('[' === $char) {
                    $astate = 1;
                    $tmp .= $char;
                } elseif (']' === $char && 1 === $astate && 0 === $jstate) {
                    $astate = 0;
                    $args[] = json_decode($tmp.$char, true);
                    $tmp = '';
                } elseif ('{' === $char) {
                    $jstate = 1;
                    $tmp .= $char;
                } elseif ('}' === $char && 1 === $jstate && 0 === $astate) {
                    $jstate = 0;
                    $args[] = json_decode($tmp.$char, true);
                    $tmp = '';
                } else {
                    $tmp .= $char;
                    $astate += '[' === $char ? 1 : (']' === $char ? -1 : 0);
                    $jstate += '{' === $char ? 1 : ('}' === $char ? -1 : 0);
                }
            } else {
                $tmp .= $char;
            }

            if (!$process && $ptr === $len - 1) {
                $process = true;
                if ('' !== $tmp) {
                    $args[] = self::cast($tmp);
                    $tmp = '';
                }
            }

            if ($process) {
                if ($args) {
                    $res[array_shift($args)] = $args;
                    $args = [];
                }
                $process = false;
            }
        }

        return $res;
    }
}

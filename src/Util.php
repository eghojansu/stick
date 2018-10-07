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
 * Framework util.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class Util
{
    /**
     * Collect request headers.
     *
     * @param array|null $server
     *
     * @return array
     */
    public static function requestHeaders(array $server = null): array
    {
        $headers = array();
        $raw = array('CONTENT_LENGTH', 'CONTENT_TYPE');

        foreach ((array) $server as $key => $val) {
            if (in_array($key, $raw)) {
                $headers[self::dashCase($key)] = $val;
            } elseif ($name = self::cutprefix($key, 'HTTP_', '')) {
                $headers[self::dashCase($name)] = $val;
            }
        }

        return $headers;
    }

    /**
     * Returns trimmed member of array after split given string
     * by comma-, semi-colon, or pipe-separated string.
     *
     * @param string $str
     *
     * @return array
     */
    public static function split(string $str): array
    {
        return array_map('trim', preg_split('/[,;\|]/', $str, 0, PREG_SPLIT_NO_EMPTY));
    }

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
     * Returns 64bit/base36 hash.
     *
     * @param string $str
     *
     * @return string
     */
    public static function hash(string $str): string
    {
        return str_pad(base_convert(substr(sha1($str), -16), 16, 36), 11, '0', STR_PAD_LEFT);
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
     * Returns true if dir exists or successfully created.
     *
     * @param string $path
     * @param int    $mode
     * @param bool   $recursive
     *
     * @return bool
     */
    public static function mkdir(string $path, int $mode = 0755, bool $recursive = true): bool
    {
        return file_exists($path) ? true : mkdir($path, $mode, $recursive);
    }

    /**
     * Returns file content with option to apply Unix LF as standard line ending.
     *
     * @param string $file
     * @param bool   $lf
     *
     * @return string
     */
    public static function read(string $file, bool $lf = false): string
    {
        $out = is_file($file) ? file_get_contents($file) : '';

        return $lf ? preg_replace('/\r\n|\r/', "\n", $out) : $out;
    }

    /**
     * Exclusive file write.
     *
     * @param string $file
     * @param string $data
     * @param bool   $append
     *
     * @return int|false
     */
    public static function write(string $file, string $data, bool $append = false)
    {
        return file_put_contents($file, $data, LOCK_EX | ((int) $append * FILE_APPEND));
    }

    /**
     * Delete file if exists.
     *
     * @param string $file
     *
     * @return bool
     */
    public static function delete(string $file): bool
    {
        return is_file($file) ? unlink($file) : false;
    }

    /**
     * Returns the return value of required file.
     *
     * It does ensure loaded file have no access to caller scope.
     *
     * @param string $file
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function requireFile(string $file, $default = null)
    {
        $content = require $file;

        return $content ?: $default;
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

    /**
     * Returns PHP-value of val.
     *
     * @param mixed $val
     *
     * @return mixed
     */
    public static function cast($val)
    {
        if (is_numeric($val)) {
            return $val + 0;
        }

        if (is_scalar($val)) {
            $val = trim($val);

            if (preg_match('/^\w+$/i', $val) && defined($val)) {
                return constant($val);
            }
        }

        return $val;
    }

    /**
     * Returns array of val.
     *
     * It does check given val, if it is not an array it splitted.
     *
     * @param string|array $val
     *
     * @return array
     */
    public static function arr($val): array
    {
        return is_array($val) ? $val : self::split((string) $val);
    }

    /**
     * Advanced array_column.
     *
     * @param array  $input
     * @param string $column_key
     *
     * @return array
     */
    public static function column(array $input, string $column_key): array
    {
        return array_combine(array_keys($input), array_column($input, $column_key));
    }

    /**
     * Apply callable to each member of an array.
     *
     * @param array    $args
     * @param callable $call
     * @param bool     $one
     *
     * @return array
     */
    public static function walk(array $args, callable $call, bool $one = true): array
    {
        $result = array();

        foreach ($args as $key => $arg) {
            $mArgs = $one ? array($arg) : (array) $arg;
            $result[$key] = $call(...$mArgs);
        }

        return $result;
    }

    /**
     * Returns parsed string expression.
     *
     * Example:
     *
     *     foo:arg,arg2|bar:arg|baz:["array arg"]|qux:{"arg":"foo"}
     *
     * @param string $expr
     *
     * @return array
     */
    public static function parseExpr(string $expr): array
    {
        $len = strlen($expr);
        $res = array();
        $tmp = '';
        $process = false;
        $args = array();
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
                    $args = array();
                }
                $process = false;
            }
        }

        return $res;
    }
}

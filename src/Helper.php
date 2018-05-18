<?php

declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick;

/**
 * Common helper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class Helper
{
    /**
     * Quote data with specified character.
     *
     * @param string|array $data
     * @param array        $quote
     * @param string       $delim
     *
     * @return string
     */
    public static function quote($data, array $quote = [], string $delim = ''): string
    {
        $open = $quote[0] ?? '';
        $close = $quote[1] ?? '';

        return $open.implode($close.$delim.$open, (array) $data).$close;
    }

    /**
     * Interpolate message.
     *
     * @param string $str
     * @param array  $args
     * @param string $quote
     *
     * @return string
     */
    public static function interpolate(string $str, array $args = [], string $quote = ''): string
    {
        $use = str_split($quote) + [1 => ''];
        $keys = array_filter(explode(',', ($args ? $use[0] : '').implode($use[1].','.$use[0], array_keys($args)).($args ? $use[1] : '')));

        return strtr($str, array_combine($keys, array_map([self::class, 'stringifyignorescalar'], $args)));
    }

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
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $str))));
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
     * Convert HEADER_KEY to Header-Key.
     *
     * @param string $str
     *
     * @return string
     */
    public static function toHKey(string $str): string
    {
        return str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower($str))));
    }

    /**
     * Convert Header-Key to HEADER_KEY.
     *
     * @param string $str
     *
     * @return string
     */
    public static function fromHKey(string $str): string
    {
        return str_replace('-', '_', strtoupper($str));
    }

    /**
     * Fix path slash.
     *
     * @param string $str
     *
     * @return string
     */
    public static function fixslashes(string $str): string
    {
        return str_replace('\\', '/', $str);
    }

    /**
     * Hash string to 13-length string.
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
     * Check if string starts with prefix.
     *
     * @param string $prefix
     * @param string $str
     *
     * @return bool
     */
    public static function startswith(string $prefix, string $str): bool
    {
        return substr($str, 0, strlen($prefix)) === $prefix;
    }

    /**
     * startswith case-insensitive.
     *
     * @param string $prefix
     * @param string $str
     *
     * @return bool
     */
    public static function istartswith(string $prefix, string $str): bool
    {
        return substr(strtolower($str), 0, strlen($prefix)) === strtolower($prefix);
    }

    /**
     * Check if string ends with suffix.
     *
     * @param string $prefix
     * @param string $str
     *
     * @return bool
     */
    public static function endswith(string $suffix, string $str): bool
    {
        return substr($str, -1 * strlen($suffix)) === $suffix;
    }

    /**
     * endswith case-insensitive.
     *
     * @param string $prefix
     * @param string $str
     *
     * @return bool
     */
    public static function iendswith(string $suffix, string $str): bool
    {
        return substr(strtolower($str), -1 * strlen($suffix)) === strtolower($suffix);
    }

    /**
     * Cut string after prefix.
     *
     * @param string $prefix
     * @param string $str
     * @param string $default
     *
     * @return string
     */
    public static function cutafter(string $prefix, string $str, string $default = ''): string
    {
        $cut = strlen($prefix);

        return substr($str, 0, $cut) === $prefix ? substr($str, $cut) : $default;
    }

    /**
     * cutafter case-insensitive.
     *
     * @param string $prefix
     * @param string $str
     * @param string $default
     *
     * @return string
     */
    public static function icutafter(string $prefix, string $str, string $default = ''): string
    {
        $cut = strlen($prefix);

        return substr(strtolower($str), 0, $cut) === strtolower($prefix) ? substr($str, $cut) : $default;
    }

    /**
     * Cut string before suffix.
     *
     * @param string $suffix
     * @param string $str
     * @param string $default
     *
     * @return string
     */
    public static function cutbefore(string $suffix, string $str, string $default = ''): string
    {
        $cut = strlen($suffix) * -1;

        return substr($str, $cut) === $suffix ? substr($str, 0, $cut) : $default;
    }

    /**
     * cutbefore case-insensitive.
     *
     * @param string $suffix
     * @param string $str
     * @param string $default
     *
     * @return string
     */
    public static function icutbefore(string $suffix, string $str, string $default = ''): string
    {
        $cut = strlen($suffix) * -1;

        return substr(strtolower($str), $cut) === strtolower($suffix) ? substr($str, 0, $cut) : $default;
    }

    /**
     * Extract array contents which starts with prefix.
     *
     * @param array  $arr
     * @param string $prefix
     *
     * @return array
     */
    public static function extract(array $arr, string $prefix): array
    {
        if (!$prefix) {
            return $arr;
        }

        $out = [];
        $cut = strlen($prefix);

        foreach ($arr as $key => $value) {
            if (substr($key, 0, $cut) === $prefix) {
                $out[substr($key, $cut)] = $value;
            }
        }

        return $out;
    }

    /**
     * Get constant value.
     *
     * @param string $var
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function constant(string $var, $default = null)
    {
        return defined($var) ? \constant($var) : $default;
    }

    /**
     * Get constants.
     *
     * @param string|object $class
     * @param string        $prefix
     *
     * @return array
     */
    public static function constants($class, string $prefix = ''): array
    {
        return self::extract((new \ReflectionClass($class))->getconstants(), $prefix);
    }

    /**
     * Split string by comma, semicolon or pipe.
     *
     * @param string $str
     * @param bool   $noempty
     *
     * @return array
     */
    public static function split(string $str, bool $noempty = true): array
    {
        return array_map('trim', preg_split('/[,;|]/', $str, 0, $noempty ? PREG_SPLIT_NO_EMPTY : 0));
    }

    /**
     * Ensure var is array.
     *
     * @param mixed $var
     * @param bool  $noempty
     *
     * @return array
     */
    public static function reqarr($var, bool $noempty = true): array
    {
        return is_array($var) ? $var : self::split($var ?? '', $noempty);
    }

    /**
     * Ensure var is string.
     *
     * @param mixed  $var
     * @param string $glue
     *
     * @return string
     */
    public static function reqstr($var, string $glue = ','): string
    {
        return is_string($var) ? $var : implode($glue, $var);
    }

    /**
     * Exclusive include.
     *
     * @param string $file
     */
    public static function exinclude(string $file): void
    {
        include $file;
    }

    /**
     * Exclusive require.
     *
     * @param string $file
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function exrequire(string $file, $default = null)
    {
        $result = require $file;

        return $result ?: $default;
    }

    /**
     * Mkdir if not exists.
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
     * Read file content.
     *
     * @param string $file
     * @param bool   $lf
     *
     * @return string
     */
    public static function read(string $file, bool $lf = false): string
    {
        $out = file_exists($file) ? file_get_contents($file) : '';

        return $lf ? preg_replace('/\r\n|\r/', "\n", $out) : $out;
    }

    /**
     * Write to file.
     *
     * @param string $file
     * @param string $data
     * @param bool   $append
     *
     * @return mixed
     */
    public static function write(string $file, string $data, bool $append = false)
    {
        return file_put_contents($file, $data, LOCK_EX | ($append ? FILE_APPEND : 0));
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
        return file_exists($file) ? unlink($file) : false;
    }

    /**
     * Convert array to string.
     *
     * @param array $args
     *
     * @return string
     */
    public static function csv(array $args): string
    {
        return implode(',', array_map('stripcslashes', array_map([self::class, 'stringify'], $args)));
    }

    /**
     * Context to string.
     *
     * @param array $context
     *
     * @return string
     */
    public static function contexttostring(array $context): string
    {
        $export = '';

        foreach ($context as $key => $value) {
            $export .= "{$key}: ";
            $export .= preg_replace([
                '/=>\s+([a-zA-Z])/im',
                '/array\(\s+\)/im',
                '/^  |\G  /m',
            ], [
                '=> $1',
                'array()',
                '    ',
            ], str_replace('array (', 'array(', var_export($value, true)));
            $export .= PHP_EOL;
        }

        return str_replace(['\\\\', '\\\''], ['\\', '\''], rtrim($export));
    }

    /**
     * Stringify if not scalar.
     *
     * @param mixed $arg
     *
     * @return mixed
     */
    public static function stringifyignorescalar($arg)
    {
        return is_scalar($arg) ? $arg : self::stringify($arg);
    }

    /**
     * Stringify PHP-value.
     *
     * @param mixed $arg
     * @param array $stack
     *
     * @return string
     */
    public static function stringify($arg, array $stack = []): string
    {
        foreach ($stack as $node) {
            if ($arg === $node) {
                return '*RECURSION*';
            }
        }

        switch (gettype($arg)) {
            case 'object':
                $str = '';
                foreach (get_object_vars($arg) as $key => $val) {
                    $str .= ','.var_export($key, true).'=>'.self::stringify($val, array_merge($stack, [$arg]));
                }
                $str = ltrim($str, ',');

                return addslashes(get_class($arg)).'::__set_state(['.$str.'])';
            case 'array':
                $str = '';
                $num = isset($arg[0]) && ctype_digit(implode('', array_keys($arg)));
                foreach ($arg as $key => $val) {
                    $str .= ','.($num ? '' : (var_export($key, true).'=>')).self::stringify($val, array_merge($stack, [$arg]));
                }
                $str = ltrim($str, ',');

                return '['.$str.']';
            default:
                return var_export($arg, true);
        }
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

    /**
     * Get ref from var, provide dot access notation.
     *
     * @param string $key
     * @param array  &$var
     * @param bool   $add
     *
     * @return mixed
     */
    public static function &ref(string $key, array &$var, bool $add = true)
    {
        $null = null;
        $parts = explode('.', $key);

        foreach ($parts as $part) {
            if (!is_array($var)) {
                $var = [];
            }

            if ($add || array_key_exists($part, $var)) {
                $var = &$var[$part];
            } else {
                $var = &$null;
                break;
            }
        }

        return $var;
    }
}

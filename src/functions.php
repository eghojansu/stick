<?php declare(strict_types=1);

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
 * Convert PHP expression/value to compressed exportable string
 *
 * @param  mixed      $arg
 * @param  array|null $stack
 *
 * @return string
 */
function stringify($arg, array $stack = []): string
{
    foreach ($stack as $node) {
        if ($arg === $node) {
            return '*RECURSION*';
        }
    }

    switch (gettype($arg)) {
        case 'object':
            $str = '';
            foreach (get_object_vars($arg) as $key=>$val) {
                $str .= ',' . var_export($key, true) . '=>' .
                        stringify($val, array_merge($stack, [$arg]));
            }
            $str = ltrim($str, ',');

            return get_class($arg) . '::__set_state([' . $str . '])';
        case 'array':
            $str = '';
            $num = isset($arg[0]) && ctype_digit(implode('', array_keys($arg)));
            foreach ($arg as $key=>$val) {
                $str .= ($num ? '' : (var_export($key, true) . '=>')) .
                        stringify($val, array_merge($stack, [$arg]));
            }
            $str = ltrim($str, ',');

            return '['.$str.']';
        default:
            return var_export($arg, true);
    }
}

/**
 * Flatten array values and return as CSV string
 *
 * @param  array  $args
 *
 * @return string
 */
function csv(array $args): string
{
    return implode(',', array_map('stripcslashes', array_map(__NAMESPACE__ . '\\stringify', $args)));
}

/**
 * Takes the given context and coverts it to a string.
 *
 * @param  array  $context The Context
 * @return string
 */
function contexttostring(array $context): string
{
    $export = '';

    foreach ($context as $key => $value) {
        $export .= "{$key}: ";
        $export .= preg_replace(
            [
                '/=>\s+([a-zA-Z])/im',
                '/array\(\s+\)/im',
                '/^  |\G  /m'
            ],
            [
                '=> $1',
                'array()',
                '    '
            ],
            str_replace('array (', 'array(', var_export($value, true))
        );
        $export .= PHP_EOL;
    }

    return str_replace(array('\\\\', '\\\''), array('\\', '\''), rtrim($export));
}

/**
 * Convert snakecase string to camelcase
 *
 * @param  string $str
 * @return string
 */
function camelcase(string $str): string
{
    return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $str))));
}

/**
 * Convert camelcase string to snakecase
 *
 * @param  string $str
 * @return string
 */
function snakecase(string $str): string
{
    return strtolower(preg_replace('/(?!^)\p{Lu}/u', '_\0', $str));
}

/**
 * Convert snakecase to dashCase
 *
 * @param  string $str
 * @return string
 */
function dashcase(string $str): string
{
    return str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower($str))));
}

/**
 * Convert backslashes to slashes
 *
 * @param  string $str
 * @return string
 */
function fixslashes(string $str): string
{
    return str_replace('\\', '/', $str);
}

/**
 * Read file (with option to apply Unix LF as standard line ending)
 *
 * @param  string  $file filepath
 * @param  boolean $lf   normalize linefeed?
 * @return string
 */
function read(string $file, bool $lf = false): string
{
    $out = file_exists($file) ? file_get_contents($file) : '';

    return $lf ? preg_replace('/\r\n|\r/', "\n", $out) : $out;
}

/**
 * Exclusive file write
 *
 * @param  string  $file   Filepath
 * @param  string  $data   Data to save
 * @param  boolean $append Append mode
 * @return int|false
 */
function write(string $file, string $data, bool $append = false)
{
    return file_put_contents($file, $data, LOCK_EX|($append ? FILE_APPEND : 0));
}

/**
 * Delete file with check
 *
 * @param  string $file Filepath
 *
 * @return bool
 */
function delete(string $file): bool
{
    return file_exists($file) ? unlink($file) : false;
}

/**
 * Split comma, semi-colon, or pipe-separated string
 *
 * @param  string  $str
 * @param  boolean $noempty
 * @return array
 */
function split(string $str, bool $noempty = true): array
{
    return array_map('trim', preg_split('/[,;|]/', $str, 0, $noempty ? PREG_SPLIT_NO_EMPTY : 0));
}

/**
 * Extract values of array whose keys start with the given prefix
 *
 * @param  array  $arr
 * @param  string $prefix
 * @return array
 */
function extract_prefix(array $arr, string $prefix): array
{
    $out = [];
    $cut = strlen($prefix);

    foreach ($arr as $key => $value) {
        if (is_string($key) && substr($key, 0, $cut) === $prefix) {
            $out[substr($key, $cut)] = $value;
        }
    }

    return $out;
}

/**
 * Convert class constants to array
 *
 * @param  string|object $class
 * @param  string        $prefix
 * @return array
 */
function constants($class, string $prefix = ''): array
{
    return extract_prefix((new \ReflectionClass($class))->getconstants(), $prefix);
}

/**
 * Generate 64bit/base36 hash
 *
 * @param  string $str
 * @return string
 */
function hash(string $str): string
{
    return str_pad(base_convert(substr(sha1($str), -16), 16, 36), 11, '0', STR_PAD_LEFT);
}

/**
 * Return Base64-encoded equivalent
 *
 * @param  string $data
 * @param  string $mime
 * @return string
 */
function base64(string $data, string $mime): string
{
    return 'data:' . $mime . ';base64,' . base64_encode($data);
}

/**
 * Override mkdir function
 *
 * @param  string       $path
 * @param  int          $mode
 * @param  bool $recursive
 * @return bool
 */
function mkdir(string $path, int $mode = 0755, bool $recursive = true): bool
{
    return file_exists($path) ? true : \mkdir($path, $mode, $recursive);
}

/**
 * Ensure var is array, or split if string
 *
 * @param  string|array $var
 * @param  bool $noempty
 *
 * @return array
 */
function reqarr($var, bool $noempty = true): array
{
    return is_array($var) ? $var : split($var, $noempty);
}

/**
 * Ensure var is string, or join by glue
 *
 * @param  string|array $var
 * @param  string       $glue
 *
 * @return string
 */
function reqstr($var, string $glue = ','): string
{
    return is_string($var) ? $var : implode($glue, $var);
}

/**
 * Get constant with check
 *
 * @param  string $var
 * @param  mixed  $default
 *
 * @return mixed
 */
function constant(string $var, $default = null)
{
    return defined($var) ? \constant($var) : $default;
}

/**
 * Quote array
 *
 * @param  array  $data
 * @param  array  $quote
 * @param  string $delim
 *
 * @return array
 */
function quoteall(array $keys, array $quote = [], string $delim = ','): array
{
    $open = $quote[0] ?? '';
    $close = $quote[1] ?? '';

    return explode($delim, $open . implode($close . $delim . $open, $keys) . $close);
}

/**
 * Quote array key
 *
 * @param  array  $data
 * @param  array  $quote
 * @param  string $delim
 *
 * @return array
 */
function quotekey(array $data, array $quote = [], string $delim = ','): array
{
    $quote += ['',''];

    return array_combine(quoteall(array_keys($data), $quote, $delim), $data);
}

/**
 * Check if string starts with prefix
 *
 * @param  string $prefix
 * @param  string $str
 *
 * @return bool
 */
function startswith(string $prefix, string $str): bool
{
    return substr($str, 0, strlen($prefix)) === $prefix;
}

/**
 * Check if string starts with prefix (case-insensitive)
 *
 * @param  string $prefix
 * @param  string $str
 *
 * @return bool
 */
function istartswith(string $prefix, string $str): bool
{
    return substr(strtolower($str), 0, strlen($prefix)) === strtolower($prefix);
}

/**
 * Check if string ends with suffix
 *
 * @param  string $suffix
 * @param  string $str
 *
 * @return bool
 */
function endswith(string $suffix, string $str): bool
{
    return substr($str, -1 * strlen($suffix)) === $suffix;
}

/**
 * Check if string ends with suffix (case-insensitive)
 *
 * @param  string $suffix
 * @param  string $str
 *
 * @return bool
 */
function iendswith(string $suffix, string $str): bool
{
    return substr(strtolower($str), -1 * strlen($suffix)) === strtolower($suffix);
}

/**
 * Cut after prefix
 *
 * @param  string $prefix
 * @param  string $str
 * @param  string $default
 *
 * @return string
 */
function cutafter(string $prefix, string $str, string $default = ''): string
{
    $cut = strlen($prefix);

    return substr($str, 0, $cut) === $prefix ? substr($str, $cut) : $default;
}

/**
 * Cut after prefix (case-insensitive)
 *
 * @param  string $prefix
 * @param  string $str
 * @param  string $default
 *
 * @return string
 */
function icutafter(string $prefix, string $str, string $default = ''): string
{
    $cut = strlen($prefix);

    if (substr(strtolower($str), 0, $cut) === strtolower($prefix)) {
        return substr($str, $cut);
    }

    return $default;
}

/**
 * Cut before suffix
 *
 * @param  string $suffix
 * @param  string $str
 * @param  string $default
 *
 * @return string
 */
function cutbefore(string $suffix, string $str, string $default = ''): string
{
    $cut = strlen($suffix) * -1;

    return substr($str, $cut) === $suffix ? substr($str, 0, $cut) : $default;
}

/**
 * Cut before suffix (case-insensitive)
 *
 * @param  string $suffix
 * @param  string $str
 * @param  string $default
 *
 * @return string
 */
function icutbefore(string $suffix, string $str, string $default = ''): string
{
    $cut = strlen($suffix) * -1;

    if (substr(strtolower($str), $cut) === strtolower($suffix)) {
        return substr($str, 0, $cut);
    }

    return $default;
}

/**
 * Cast string variable to php type or constant
 *
 * @param  mixed $val
 *
 * @return mixed
 */
function cast($val)
{
    if (is_numeric($val)){
        return $val + 0;
    }

    $val = trim($val);

    if (preg_match('/^\w+$/i', $val) && defined($val)) {
        return \constant($val);
    }

    return $val;
}

/**
 * Cast every member of array
 *
 * @param  array $args
 *
 * @return array
 */
function casts(array $args): array
{
    $casts = [];

    foreach ($args as $key => $value) {
        $casts[$key] = cast($value);
    }

    return $casts;
}

/**
 * Pick $keys from $source, stop on null item
 *
 * @param  array      $source
 * @param  array|null $keys
 *
 * @return array
 */
function picktoargs(array $source, array $keys = null): array
{
    $picked = [];

    if ($keys === null) {
        $topick = array_keys($source);
    } else {
        $topick = array_intersect(array_keys($source), $keys);
    }

    foreach ($topick as $pos => $key) {
        if ($source[$key] === null) {
            return $picked;
        }

        $picked[] = $source[$key];
    }

    return $picked;
}

/**
 * Return string representation of PHP value
 *
 * @param mixed $arg
 * @param string $serializer
 *
 * @return string
 */
function serialize($arg, string $serializer = null): string
{
    static $use;

    if ($serializer) {
        $use = $serializer;
    }

    if ($arg === null) {
        return '';
    }

    switch ($use) {
        case 'igbinary':
            return igbinary_serialize($arg);
        default:
            return \serialize($arg);
    }
}

/**
 * Return PHP value derived from string
 *
 * @param mixed $arg
 * @param string $serializer
 *
 * @return mixed
 */
function unserialize($arg, string $serializer = null)
{
    static $use;

    if ($serializer) {
        $use = $serializer;
    }

    if ($arg === null) {
        return;
    }

    switch ($use) {
        case 'igbinary':
            return igbinary_unserialize($arg);
        default:
            return \unserialize($arg);
    }
}

/**
 * Get class name (without namespace)
 *
 * @param  string|obj $class
 *
 * @return string
 */
function classname($class): string
{
    $ns = is_object($class) ? get_class($class) : $class;
    $pos = strrpos($ns, '\\');

    return $pos === false ? $ns : substr($ns, $pos + 1);
}

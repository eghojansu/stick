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
*
*   @return string
*   @param $arg mixed
*   @param $stack array
**/
/**
 * Convert PHP expression/value to compressed exportable string
 *
 * @param  mixed     $arg
 * @param  array|NULL $stack
 *
 * @return string
 */
function stringify($arg, array $stack = NULL): string
{
    if ($stack) {
        foreach ($stack as $node) {
            if ($arg === $node) {
                return '*RECURSION*';
            }
        }
    } else {
        $stack = [];
    }

    switch (gettype($arg)) {
        case 'object':
            $str = '';
            foreach (get_object_vars($arg) as $key=>$val) {
                $str .= ',' . var_export($key, TRUE) . '=>' . stringify($val, array_merge($stack, [$arg]));
            }
            $str = ltrim($str, ',');

            return get_class($arg) . '::__set_state([' . $str . '])';
        case 'array':
            $str = '';
            $num = isset($arg[0]) && ctype_digit(implode('', array_keys($arg)));
            foreach ($arg as $key=>$val) {
                $str .= ($num ? '' : (var_export($key, TRUE) . '=>')) . stringify($val, array_merge($stack, [$arg]));
            }
            $str = ltrim($str, ',');

            return '['.$str.']';
        default:
            return var_export($arg, TRUE);
    }
}

/**
 * Takes the given context and coverts it to a string.
 *
 * @param  array $context The Context
 * @return string
 */
function contexttostring(array $context): string
{
    $export = '';
    foreach ($context as $key => $value) {
        $export .= "{$key}: ";
        $export .= preg_replace([
            '/=>\s+([a-zA-Z])/im',
            '/array\(\s+\)/im',
            '/^  |\G  /m'
        ], [
            '=> $1',
            'array()',
            '    '
        ], str_replace('array (', 'array(', var_export($value, TRUE)));
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
 * Convert backslash(es) to slash(es)
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
function read(string $file, bool $lf = FALSE): string
{
    $out = file_exists($file) ? file_get_contents($file) : '';

    return $lf ? preg_replace('/\r\n|\r/', "\n", $out) : $out;
}

/**
 * Exclusive file write
 *
 * @param  string  $file   filepath
 * @param  string  $data
 * @param  boolean $append
 * @return int|FALSE
 */
function write(string $file, string $data, bool $append = FALSE)
{
    return file_put_contents($file, $data, LOCK_EX|($append ? FILE_APPEND : 0));
}

/**
 * Delete file with check
 *
 * @param  string $file
 *
 * @return bool
 */
function delete(string $file): bool
{
    return file_exists($file) ? unlink($file) : FALSE;
}

/**
 * Split comma, semi-colon, or pipe-separated string
 *
 * @param  string  $str
 * @param  boolean $noempty
 * @return array
 */
function split(string $str, bool $noempty = TRUE): array
{
    return array_map('trim', preg_split('/[,;|]/', $str, 0, $noempty ? PREG_SPLIT_NO_EMPTY : 0));
}

/**
 * Extract values of array whose keys start with the given prefix
 *
 * @param  array $arr
 * @param  string $prefix
 * @return array
 */
function extract(array $arr, string $prefix): array
{
    $out = [];
    $cut = strlen($prefix);
    foreach ($arr as $key => $value) {
        if (is_string($key) && $prefix === substr($key, 0, $cut)) {
            $out[substr($key, $cut)] = $value;
        }
    }

    return $out;
}

/**
 * Convert class constants to array
 *
 * @param  string|object $class
 * @param  string $prefix
 * @return array
 */
function constants($class, string $prefix = ''): array
{
    return extract((new \ReflectionClass($class))->getconstants(), $prefix);
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
 * @param  bool|boolean $recursive
 * @return bool
 */
function mkdir(string $path, int $mode = 0755, bool $recursive = TRUE): bool
{
    return file_exists($path) ? TRUE : \mkdir($path, $mode, $recursive);
}

/**
 * Ensure var is array, or split if string
 *
 * @param  string|array       $var
 * @param  bool|boolean $noempty
 *
 * @return array
 */
function reqarr($var, bool $noempty = TRUE): array
{
    return is_array($var) ? $var : split($var, $noempty);
}

/**
 * Ensure var is string, or join by glue
 *
 * @param  string|array       $var
 * @param  string $glue
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
 * @param  mixed $default
 *
 * @return mixed
 */
function constant(string $var, $default = NULL)
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
    return explode($delim, $quote[0] . implode($quote[1] . $delim . $quote[0], $keys) . $quote[1]);
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
    return $prefix === substr($str, 0, strlen($prefix));
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
    return strtolower($prefix) === substr(strtolower($str), 0, strlen($prefix));
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
    return $suffix === substr($str, -1 * strlen($suffix));
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
    return strtolower($suffix) === substr(strtolower($str), -1 * strlen($suffix));
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

    return $prefix === substr($str, 0, $cut) ? substr($str, $cut) : $default;
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

    return strtolower($prefix) === substr(strtolower($str), 0, $cut) ? substr($str, $cut) : $default;
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
    $cut = strlen($suffix);

    return $suffix === substr($str, -1 * $cut) ? substr($str, 0, $cut) : $default;
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
    $cut = strlen($suffix);

    return strtolower($suffix) === substr(strtolower($str), -1 * $cut) ? substr($str, 0, $cut) : $default;
}

/**
 * cast string variable to php type or constant
 *
 * @param  mixed $val
 *
 * @return mixed
 */
function cast($val)
{
    if (is_numeric($val)){
        return $val+0;
    }

    $val = trim($val);
    if (preg_match('/^\w+$/i', $val) && defined($val)) {
        return \constant($val);
    }

    return $val;
}

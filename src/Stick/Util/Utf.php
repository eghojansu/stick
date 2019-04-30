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

namespace Fal\Stick\Util;

/**
 * Unicode string manager.
 *
 * Ported from F3/Utf.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Utf
{
    /**
     * @var array
     */
    public static $emojis = array(
        ':(' => '\u2639', // frown
        ':)' => '\u263a', // smile
        '<3' => '\u2665', // heart
        ':D' => '\u1f603', // grin
        'XD' => '\u1f606', // laugh
        ';)' => '\u1f609', // wink
        ':P' => '\u1f60b', // tongue
        ':,' => '\u1f60f', // think
        ':/' => '\u1f623', // skeptic
        '8O' => '\u1f632', // oops
    );

    /**
     * Get string length.
     *
     * @param string $str
     *
     * @return int
     */
    public static function strlen(string $str): int
    {
        preg_match_all('/./us', $str, $parts);

        return count($parts[0]);
    }

    /**
     * Reverse a string.
     *
     * @param string $str
     *
     * @return string
     */
    public static function strrev(string $str): string
    {
        preg_match_all('/./us', $str, $parts);

        return implode('', array_reverse($parts[0]));
    }

    /**
     * Find position of first occurrence of a string (case-insensitive).
     *
     * @param string $stack
     * @param string $needle
     * @param int    $offset
     *
     * @return int|false
     */
    public static function stripos(string $stack, string $needle, int $offset = 0)
    {
        return self::strpos($stack, $needle, $offset, true);
    }

    /**
     * Find position of first occurrence of a string.
     *
     * @param string $stack
     * @param string $needle
     * @param int    $offset
     * @param bool   $case
     *
     * @return int|false
     */
    public static function strpos(string $stack, string $needle, int $offset = 0, bool $case = false)
    {
        return preg_match('/^(.{'.$offset.'}.*?)'.preg_quote($needle, '/').'/us'.($case ? 'i' : ''), $stack, $match) ? self::strlen($match[1]) : false;
    }

    /**
     * Returns part of haystack string from the first occurrence of needle to the end of haystack (case-insensitive).
     *
     * @param string $stack
     * @param string $needle
     * @param bool   $before
     *
     * @return string|false
     */
    public static function stristr(string $stack, string $needle, bool $before = false)
    {
        return self::strstr($stack, $needle, $before, true);
    }

    /**
     * Returns part of haystack string from the first occurrence of needle to the end of haystack.
     *
     * @param string $stack
     * @param string $needle
     * @param bool   $before
     * @param bool   $case
     *
     * @return string|false
     */
    public static function strstr(string $stack, string $needle, bool $before = false, bool $case = false)
    {
        if (!$needle) {
            return false;
        }

        preg_match('/^(.*?)'.preg_quote($needle, '/').'/us'.($case ? 'i' : ''), $stack, $match);

        return isset($match[1]) ? ($before ? $match[1] : self::substr($stack, self::strlen($match[1]))) : false;
    }

    /**
     * Return part of a string.
     *
     * @param string $str
     * @param int    $start
     * @param int    $len
     *
     * @return string|false
     */
    public static function substr(string $str, int $start, int $len = 0)
    {
        if ($start < 0) {
            $start = self::strlen($str) + $start;
        }

        if (!$len) {
            $len = self::strlen($str) - $start;
        }

        return preg_match('/^.{'.$start.'}(.{0,'.$len.'})/us', $str, $match) ? $match[1] : false;
    }

    /**
     * Count the number of substring occurrences.
     *
     * @param string $stack
     * @param string $needle
     *
     * @return int
     */
    public static function substrCount(string $stack, string $needle): int
    {
        preg_match_all('/'.preg_quote($needle, '/').'/us', $stack, $matches, PREG_SET_ORDER);

        return count($matches);
    }

    /**
     * Strip whitespaces from the beginning of a string.
     *
     * @param string $str
     *
     * @return string
     */
    public static function ltrim(string $str): string
    {
        return preg_replace('/^[\pZ\pC]+/u', '', $str);
    }

    /**
     * Strip whitespaces from the end of a string.
     *
     * @param string $str
     *
     * @return string
     */
    public static function rtrim(string $str): string
    {
        return preg_replace('/[\pZ\pC]+$/u', '', $str);
    }

    /**
     * Strip whitespaces from the beginning and end of a string.
     *
     * @param string $str
     *
     * @return string
     */
    public static function trim(string $str): string
    {
        return preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $str);
    }

    /**
     * Return UTF-8 byte order mark.
     *
     * @return string
     */
    public static function bom(): string
    {
        return chr(0xef).chr(0xbb).chr(0xbf);
    }

    /**
     * Convert code points to Unicode symbols.
     *
     * @param string $str
     *
     * @return string
     */
    public static function translate(string $str): string
    {
        return html_entity_decode(preg_replace('/\\\\u([[:xdigit:]]+)/i', '&#x\1;', $str));
    }

    /**
     * Translate emoji tokens to Unicode font-supported symbols.
     *
     * @param string $str
     *
     * @return string
     */
    public static function emojify(string $str): string
    {
        return self::translate(str_replace(array_keys(self::$emojis), array_values(self::$emojis), $str));
    }
}

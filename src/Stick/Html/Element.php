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

namespace Fal\Stick\Html;

/**
 * Html tag helper.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Element
{
    /**
     * Convert attr to string.
     *
     * @param array|null $attr
     *
     * @return string
     */
    public static function attr(?array $attr): string
    {
        if (!$attr) {
            return '';
        }

        $str = '';

        foreach ($attr as $prop => $val) {
            if (null === $val || false === $val) {
                continue;
            }

            if (is_numeric($prop)) {
                $str .= is_string($val) ? ' '.trim($val) : '';
            } else {
                $str .= ' '.$prop;

                if (true !== $val && '' !== $val) {
                    $strVal = is_scalar($val) ? trim((string) $val) : json_encode($val);
                    $str .= '="'.addslashes($strVal).'"';
                }
            }
        }

        return $str;
    }

    /**
     * Merge attributes.
     *
     * @param array|null $source
     * @param array|null $replace
     * @param array|null $concats
     *
     * @return array
     */
    public static function mergeAttr(
        array $source = null,
        array $replace = null,
        array $concats = null
    ): array {
        $updates = array();
        $defaults = array('class');

        foreach ($concats ?? $defaults as $key) {
            if (isset($replace[$key])) {
                $updates[$key] = (isset($source[$key]) ? $source[$key].' ' : null).$replace[$key];
            }
        }

        return array_replace($source ?? array(), $replace ?? array(), $updates);
    }

    /**
     * Returns html tag.
     *
     * @param string      $tag
     * @param array|null  $attr
     * @param bool        $pair
     * @param string|null $content
     *
     * @return string
     */
    public static function tag(string $tag, array $attr = null, bool $pair = false, string $content = null): string
    {
        return '<'.$tag.self::attr($attr).'>'.$content.($pair ? '</'.$tag.'>' : '');
    }
}

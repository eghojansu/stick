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
 * Utility class.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Util
{
    /**
     * File mime pattern regexes.
     *
     * @var array
     */
    public static $mimes = array(
        'au' => 'audio/basic',
        'avi' => 'video/avi',
        'bmp' => 'image/bmp',
        'bz2' => 'application/x-bzip2',
        'css' => 'text/css',
        'doc' => 'application/msword',
        'dtd' => 'application/xml-dtd',
        'gif' => 'image/gif',
        'gz' => 'application/x-gzip',
        'hqx' => 'application/mac-binhex40',
        'p?html?' => 'text/html',
        'jar' => 'application/java-archive',
        'jpe?g' => 'image/jpeg',
        'js' => 'application/x-javascript',
        'json' => 'application/json',
        'midi' => 'audio/x-midi',
        'mp3' => 'audio/mpeg',
        'mpe?g' => 'video/mpeg',
        'ogg' => 'audio/vorbis',
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'ppt' => 'application/vnd.ms-powerpoint',
        'ps' => 'application/postscript',
        'qt' => 'video/quicktime',
        'ram?' => 'audio/x-pn-realaudio',
        'rdf' => 'application/rdf',
        'rtf' => 'application/rtf',
        'sgml?' => 'text/sgml',
        'sit' => 'application/x-stuffit',
        'svg' => 'image/svg+xml',
        'swf' => 'application/x-shockwave-flash',
        'tgz' => 'application/x-tar',
        'tiff' => 'image/tiff',
        'txt' => 'text/plain',
        'wav' => 'audio/wav',
        'xls' => 'application/vnd.ms-excel',
        'xml' => 'application/xml',
        'zip' => 'application/x-zip-compressed',
    );

    /**
     * Safe url characters.
     *
     * @var array
     */
    public static $diacritics = array(
        'Ǎ' => 'A', 'А' => 'A', 'Ā' => 'A', 'Ă' => 'A', 'Ą' => 'A', 'Å' => 'A',
        'Ǻ' => 'A', 'Ä' => 'Ae', 'Á' => 'A', 'À' => 'A', 'Ã' => 'A', 'Â' => 'A',
        'Æ' => 'AE', 'Ǽ' => 'AE', 'Б' => 'B', 'Ç' => 'C', 'Ć' => 'C', 'Ĉ' => 'C',
        'Č' => 'C', 'Ċ' => 'C', 'Ц' => 'C', 'Ч' => 'Ch', 'Ð' => 'Dj', 'Đ' => 'Dj',
        'Ď' => 'Dj', 'Д' => 'Dj', 'É' => 'E', 'Ę' => 'E', 'Ё' => 'E', 'Ė' => 'E',
        'Ê' => 'E', 'Ě' => 'E', 'Ē' => 'E', 'È' => 'E', 'Е' => 'E', 'Э' => 'E',
        'Ë' => 'E', 'Ĕ' => 'E', 'Ф' => 'F', 'Г' => 'G', 'Ģ' => 'G', 'Ġ' => 'G',
        'Ĝ' => 'G', 'Ğ' => 'G', 'Х' => 'H', 'Ĥ' => 'H', 'Ħ' => 'H', 'Ï' => 'I',
        'Ĭ' => 'I', 'İ' => 'I', 'Į' => 'I', 'Ī' => 'I', 'Í' => 'I', 'Ì' => 'I',
        'И' => 'I', 'Ǐ' => 'I', 'Ĩ' => 'I', 'Î' => 'I', 'Ĳ' => 'IJ', 'Ĵ' => 'J',
        'Й' => 'J', 'Я' => 'Ja', 'Ю' => 'Ju', 'К' => 'K', 'Ķ' => 'K', 'Ĺ' => 'L',
        'Л' => 'L', 'Ł' => 'L', 'Ŀ' => 'L', 'Ļ' => 'L', 'Ľ' => 'L', 'М' => 'M',
        'Н' => 'N', 'Ń' => 'N', 'Ñ' => 'N', 'Ņ' => 'N', 'Ň' => 'N', 'Ō' => 'O',
        'О' => 'O', 'Ǿ' => 'O', 'Ǒ' => 'O', 'Ơ' => 'O', 'Ŏ' => 'O', 'Ő' => 'O',
        'Ø' => 'O', 'Ö' => 'Oe', 'Õ' => 'O', 'Ó' => 'O', 'Ò' => 'O', 'Ô' => 'O',
        'Œ' => 'OE', 'П' => 'P', 'Ŗ' => 'R', 'Р' => 'R', 'Ř' => 'R', 'Ŕ' => 'R',
        'Ŝ' => 'S', 'Ş' => 'S', 'Š' => 'S', 'Ș' => 'S', 'Ś' => 'S', 'С' => 'S',
        'Ш' => 'Sh', 'Щ' => 'Shch', 'Ť' => 'T', 'Ŧ' => 'T', 'Ţ' => 'T', 'Ț' => 'T',
        'Т' => 'T', 'Ů' => 'U', 'Ű' => 'U', 'Ŭ' => 'U', 'Ũ' => 'U', 'Ų' => 'U',
        'Ū' => 'U', 'Ǜ' => 'U', 'Ǚ' => 'U', 'Ù' => 'U', 'Ú' => 'U', 'Ü' => 'Ue',
        'Ǘ' => 'U', 'Ǖ' => 'U', 'У' => 'U', 'Ư' => 'U', 'Ǔ' => 'U', 'Û' => 'U',
        'В' => 'V', 'Ŵ' => 'W', 'Ы' => 'Y', 'Ŷ' => 'Y', 'Ý' => 'Y', 'Ÿ' => 'Y',
        'Ź' => 'Z', 'З' => 'Z', 'Ż' => 'Z', 'Ž' => 'Z', 'Ж' => 'Zh', 'á' => 'a',
        'ă' => 'a', 'â' => 'a', 'à' => 'a', 'ā' => 'a', 'ǻ' => 'a', 'å' => 'a',
        'ä' => 'ae', 'ą' => 'a', 'ǎ' => 'a', 'ã' => 'a', 'а' => 'a', 'ª' => 'a',
        'æ' => 'ae', 'ǽ' => 'ae', 'б' => 'b', 'č' => 'c', 'ç' => 'c', 'ц' => 'c',
        'ċ' => 'c', 'ĉ' => 'c', 'ć' => 'c', 'ч' => 'ch', 'ð' => 'dj', 'ď' => 'dj',
        'д' => 'dj', 'đ' => 'dj', 'э' => 'e', 'é' => 'e', 'ё' => 'e', 'ë' => 'e',
        'ê' => 'e', 'е' => 'e', 'ĕ' => 'e', 'è' => 'e', 'ę' => 'e', 'ě' => 'e',
        'ė' => 'e', 'ē' => 'e', 'ƒ' => 'f', 'ф' => 'f', 'ġ' => 'g', 'ĝ' => 'g',
        'ğ' => 'g', 'г' => 'g', 'ģ' => 'g', 'х' => 'h', 'ĥ' => 'h', 'ħ' => 'h',
        'ǐ' => 'i', 'ĭ' => 'i', 'и' => 'i', 'ī' => 'i', 'ĩ' => 'i', 'į' => 'i',
        'ı' => 'i', 'ì' => 'i', 'î' => 'i', 'í' => 'i', 'ï' => 'i', 'ĳ' => 'ij',
        'ĵ' => 'j', 'й' => 'j', 'я' => 'ja', 'ю' => 'ju', 'ķ' => 'k', 'к' => 'k',
        'ľ' => 'l', 'ł' => 'l', 'ŀ' => 'l', 'ĺ' => 'l', 'ļ' => 'l', 'л' => 'l',
        'м' => 'm', 'ņ' => 'n', 'ñ' => 'n', 'ń' => 'n', 'н' => 'n', 'ň' => 'n',
        'ŉ' => 'n', 'ó' => 'o', 'ò' => 'o', 'ǒ' => 'o', 'ő' => 'o', 'о' => 'o',
        'ō' => 'o', 'º' => 'o', 'ơ' => 'o', 'ŏ' => 'o', 'ô' => 'o', 'ö' => 'oe',
        'õ' => 'o', 'ø' => 'o', 'ǿ' => 'o', 'œ' => 'oe', 'п' => 'p', 'р' => 'r',
        'ř' => 'r', 'ŕ' => 'r', 'ŗ' => 'r', 'ſ' => 's', 'ŝ' => 's', 'ș' => 's',
        'š' => 's', 'ś' => 's', 'с' => 's', 'ş' => 's', 'ш' => 'sh', 'щ' => 'shch',
        'ß' => 'ss', 'ţ' => 't', 'т' => 't', 'ŧ' => 't', 'ť' => 't', 'ț' => 't',
        'у' => 'u', 'ǘ' => 'u', 'ŭ' => 'u', 'û' => 'u', 'ú' => 'u', 'ų' => 'u',
        'ù' => 'u', 'ű' => 'u', 'ů' => 'u', 'ư' => 'u', 'ū' => 'u', 'ǚ' => 'u',
        'ǜ' => 'u', 'ǔ' => 'u', 'ǖ' => 'u', 'ũ' => 'u', 'ü' => 'ue', 'в' => 'v',
        'ŵ' => 'w', 'ы' => 'y', 'ÿ' => 'y', 'ý' => 'y', 'ŷ' => 'y', 'ź' => 'z',
        'ž' => 'z', 'з' => 'z', 'ż' => 'z', 'ж' => 'zh', 'ь' => '', 'ъ' => '',
        '\'' => '',
    );

    /**
     * Return a URL/filesystem-friendly version of string.
     *
     * @param string $text
     *
     * @return string
     */
    public static function slug(string $text): string
    {
        return trim(strtolower(preg_replace('/([^\pL\pN])+/u', '-', trim(strtr($text, static::$diacritics)))), '-');
    }

    /**
     * Detect MIME type using file extension.
     *
     * @param string $file
     *
     * @return string
     */
    public static function mime(string $filename): string
    {
        if (preg_match('/(\w+)$/', $filename, $match)) {
            foreach (static::$mimes as $pattern => $mime) {
                if (preg_match('/^'.$pattern.'$/i', $match[1])) {
                    return $mime;
                }
            }
        }

        return 'application/octet-stream';
    }

    /**
     * Return hash text.
     *
     * @param string $text
     *
     * @return string
     */
    public static function hash(string $text): string
    {
        return str_pad(base_convert(substr(sha1($text), -16), 16, 36), 11, '0', STR_PAD_LEFT);
    }

    /**
     * Returns camel-cased snake-case text.
     *
     * @param string $text
     *
     * @return string
     */
    public static function camelCase(string $text): string
    {
        return lcfirst(str_replace('_', '', ucwords(strtolower($text), '_')));
    }

    /**
     * Returns snake-cased camel-case text.
     *
     * @param string $text
     *
     * @return string
     */
    public static function snakeCase(string $text): string
    {
        return strtolower(preg_replace('/(?!^)\p{Lu}/u', '_\0', $text));
    }

    /**
     * Returns title-cased camel-case text.
     *
     * @param string $text
     *
     * @return string
     */
    public static function titleCase(string $text): string
    {
        return ucwords(str_replace('_', ' ', static::snakeCase($text)));
    }

    /**
     * Returns class name from full namespace or instance of class.
     *
     * @param string|object $class
     *
     * @return string
     */
    public static function className($class): string
    {
        return ltrim(strrchr('\\'.(is_object($class) ? get_class($class) : $class), '\\'), '\\');
    }

    /**
     * Ensure returns array.
     *
     * @param mixed       $var
     * @param string|null $delimiter
     *
     * @return array
     */
    public static function split($var, string $delimiter = null): array
    {
        if (is_array($var)) {
            return $var;
        }

        if (!$var) {
            return array();
        }

        $pattern = '/['.preg_quote($delimiter ?? ',;|', '/').']/';

        return array_map('trim', preg_split($pattern, $var, 0, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * Ensure returns string.
     *
     * @param mixed       $var
     * @param string|null $glue
     *
     * @return string
     */
    public static function join($var, string $glue = null): string
    {
        return is_array($var) ? implode($glue ?? ',', $var) : (string) $var;
    }

    /**
     * Convert expression to php value.
     *
     * @param mixed $var
     *
     * @return mixed
     */
    public static function cast($var)
    {
        if (is_string($var)) {
            $var = trim($var);

            if (preg_match('/^\w+$/i', $var) && defined($var)) {
                return constant($var);
            }

            if (preg_match('/^(?:0x[0-9a-f]+|0[0-7]+|0b[01]+)$/i', $var)) {
                return intval($var, 0);
            }
        }

        if (is_numeric($var)) {
            return $var + 0;
        }

        return $var;
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
        return '<'.$tag.static::attr($attr).'>'.$content.($pair ? '</'.$tag.'>' : '');
    }

    /**
     * Convert attr to string.
     *
     * @param array|null $attr
     *
     * @return string
     */
    public static function attr(array $attr = null): string
    {
        if (!$attr) {
            return '';
        }

        $str = '';

        foreach ((array) $attr as $prop => $val) {
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
     * Returns argument as string.
     *
     * @param mixed $argument
     *
     * @return string
     */
    public static function stringify($argument): string
    {
        if (is_object($argument)) {
            return 'Object('.get_class($argument).')';
        }

        if (is_array($argument)) {
            return '['.implode(', ', array_map(array('Fal\\Stick\\Util', 'stringify'), $argument)).']';
        }

        return var_export($argument, true);
    }

    /**
     * Returns string of trace.
     *
     * @param array|null $trace
     *
     * @return string
     */
    public static function trace(array $trace = null): string
    {
        $text = '';

        if (null === $trace) {
            $trace = debug_backtrace();
            array_shift($trace);
        }

        foreach ($trace as $row) {
            $text .= PHP_EOL.'['.$row['file'].':'.$row['line'].']';

            if (isset($row['function'])) {
                $text .= ' ';

                if (isset($row['class'])) {
                    $text .= $row['class'].$row['type'];
                }

                $text .= $row['function'].'(';

                if ($row['args']) {
                    $text .= implode(', ', array_map(array('Fal\\Stick\\Util', 'stringify'), $row['args']));
                }

                $text .= ')';
            }
        }

        return ltrim($text);
    }
}

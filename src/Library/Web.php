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

use Fal\Stick\Fw;

/**
 * Web helper (ported from f3 web helper).
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class Web
{
    /**
     * @var Fw
     */
    private $fw;

    /**
     * Class constructor.
     *
     * @param Fw $fw
     */
    public function __construct(Fw $fw)
    {
        $this->fw = $fw;
    }

    /**
     * Return preset diacritics translation table.
     *
     * @return array
     */
    public function diacritics(): array
    {
        return array(
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
    }

    /**
     * Return a URL/filesystem-friendly version of string.
     *
     * @param string $text
     *
     * @return string
     */
    public function slug(string $text): string
    {
        return trim(strtolower(preg_replace('/([^\pL\pN])+/u', '-', trim(strtr($text, $this->diacritics())))), '-');
    }

    /**
     * Detect MIME type using file extension.
     *
     * @param string $file
     *
     * @return string
     */
    public function mime(string $file): string
    {
        if (preg_match('/\w+$/', $file, $match)) {
            $map = array(
                'au' => 'audio/basic',
                'avi' => 'video/avi',
                'bmp' => 'image/bmp',
                'bz2' => 'application/x-bzip2',
                'css' => 'text/css',
                'dtd' => 'application/xml-dtd',
                'doc' => 'application/msword',
                'gif' => 'image/gif',
                'gz' => 'application/x-gzip',
                'hqx' => 'application/mac-binhex40',
                'html?' => 'text/html',
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
            $ext = strtolower($match[0]);

            foreach ($map as $key => $val) {
                if (preg_match('/'.$key.'$/', $ext)) {
                    return $val;
                }
            }
        }

        return 'application/octet-stream';
    }

    /**
     * Transmit file to HTTP client; Return file size if successful, -1 on failure.
     *
     * @param string      $file
     * @param string|null $name
     * @param string|null $mime
     * @param int         $kbps
     * @param bool        $force
     * @param bool        $flush
     *
     * @return int
     *
     * @codeCoverageIgnore
     */
    public function send(string $file, string $name = null, string $mime = null, int $kbps = 0, bool $force = true, bool $flush = true): int
    {
        if (!is_file($file)) {
            return -1;
        }

        $size = filesize($file);
        $headers = array();
        $headers['Content-Type'] = $mime ?? $this->mime($file);

        if ($force) {
            $headers['Content-Disposition'] = 'attachment; filename="'.($name ?? basename($file)).'"';
        }

        $headers['Accept-Ranges'] = 'bytes';
        $headers['Content-Length'] = $size;
        $headers['X-Powered-By'] = $this->fw->get('PACKAGE');

        $this->fw->send(200, $headers);

        if (!$kbps && $flush) {
            while (ob_get_level()) {
                ob_end_clean();
            }

            readfile($file);
        } else {
            $ctr = 0;
            $handle = fopen($file, 'rb');
            $start = microtime(true);

            while (!feof($handle) &&
                ($info = stream_get_meta_data($handle)) &&
                !$info['timed_out'] && !connection_aborted()) {
                if ($kbps) {
                    // Throttle output
                    ++$ctr;
                    if ($ctr / $kbps > $elapsed = microtime(true) - $start) {
                        usleep(1e6 * ($ctr / $kbps - $elapsed));
                    }
                }

                // Send 1KiB and reset timer
                echo fread($handle, 1024);

                if ($flush) {
                    ob_flush();
                    flush();
                }
            }

            fclose($handle);
        }

        return $size;
    }

    /**
     * Receive file(s) from HTTP client.
     *
     * @param string        $dir
     * @param bool          $overwrite
     * @param bool          $slug
     * @param callable|null $cb
     * @param callable|null $slugCb
     *
     * @return array
     *
     * @codeCoverageIgnore
     */
    public function receive(string $dir, bool $overwrite = false, bool $slug = true, callable $cb = null, callable $slugCb = null): array
    {
        $this->fw->mkdir($dir);

        if ('PUT' === $this->fw->get('VERB')) {
            $tmpDir = $this->fw->get('TEMP').'uploads/';
            $tmp = $tmpDir.$this->fw->get('SEED').'.'.$this->fw->hash(uniqid());

            $this->fw->mkdir($tmpDir);

            if ($this->fw->is('RAW')) {
                $src = fopen('php://input', 'r');
                $dst = fopen($tmp, 'w');

                if (!$src || !$dst) {
                    return array();
                }

                while (!feof($src) &&
                    ($info = stream_get_meta_data($src)) &&
                    !$info['timed_out'] && $str = fgets($src, 4096)) {
                    fputs($dst, $str, strlen($str));
                }

                fclose($dst);
                fclose($src);
            } else {
                $this->fw->write($tmp, $this->fw->get('BODY'));
            }

            $base = basename($this->fw->get('URI'));
            $filename = $base;

            if (preg_match('/(.+?)(\.\w+)?$/', $base, $parts)) {
                $filename = $slugCb ? $slugCb($base) : $this->slug($parts[1]).($parts[2] ?? null);
            }

            $file = array(
                'name' => $dir.$filename,
                'type' => $this->mime($base),
                'size' => filesize($tmp),
                'tmp_name' => $tmp,
                'error' => UPLOAD_ERR_OK,
            );
            $unwritten = !file_exists($file['name']) || $overwrite;
            $written = false;

            if ($unwritten) {
                $written = true;
                $unhandled = !$cb || false === $cb($file);

                if ($unhandled) {
                    $written = rename($tmp, $file['name']);
                }
            }

            return array($file['name'] => $written);
        }

        $out = array();

        foreach ($_FILES as $ndx => $item) {
            if (empty($item['name'])) {
                continue;
            }

            $names = (array) $item['name'];
            $types = (array) $item['type'];
            $sizes = (array) $item['size'];
            $tmps = (array) $item['tmp_name'];
            $errors = (array) $item['error'];

            foreach ($names as $key => $name) {
                $base = basename($name);
                $filename = $base;

                if (preg_match('/(.+?)(\.\w+)?$/', $base, $parts)) {
                    $filename = $slugCb ? $slugCb($base, $ndx) : $this->slug($parts[1]).($parts[2] ?? null);
                }

                $file = array(
                    'name' => $dir.$filename,
                    'type' => $types[$key],
                    'size' => $sizes[$key],
                    'tmp_name' => $tmps[$key],
                    'error' => $errors[$key],
                );

                if ($file['error']) {
                    $out[$file['name']] = false;

                    continue;
                }

                $unwritten = !file_exists($file['name']) || $overwrite;
                $written = false;

                if ($unwritten) {
                    $written = true;
                    $unhandled = !$cb || false === $cb($file, $ndx);

                    if ($unhandled) {
                        $written = move_uploaded_file($file['tmp_name'], $file['name']);
                    }
                }

                $out[$file['name']] = $written;
            }
        }

        return $out;
    }
}

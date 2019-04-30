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
 * Image manipulation tools.
 *
 * Ported from F3\Image.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
final class Image
{
    // Positional cues
    const POS_LEFT = 1;
    const POS_CENTER = 2;
    const POS_RIGHT = 4;
    const POS_TOP = 8;
    const POS_MIDDLE = 16;
    const POS_BOTTOM = 32;

    /**
     * Image resource.
     *
     * @var resource
     */
    private $resource;

    /**
     * File path.
     *
     * @var string
     */
    private $filepath;

    /**
     * Default export/dump format.
     *
     * @var string
     */
    private $format = 'png';

    /**
     * Compare image and returns the similarity point.
     *
     * Not suitable for large images!
     *
     * (https://www.phpied.com/image-diff/).
     *
     * @param string $srcA
     * @param string $srcB
     *
     * @return float
     */
    public static function compare(string $srcA, string $srcB): float
    {
        $imgA = $srcA ? imagecreatefromstring($srcA) : null;
        $imgB = $srcB ? imagecreatefromstring($srcB) : null;

        if (!$imgA || !$imgB) {
            throw new \LogicException('Both image should be valid!');
        }

        $xA = imagesx($imgA);
        $yA = imagesy($imgA);

        if ($xA !== imagesx($imgB) && $yA !== imagesy($imgB)) {
            imagedestroy($imgA);
            imagedestroy($imgB);

            return 0.0;
        }

        $similarityPoint = 0;

        for ($x = 0; $x < $xA; ++$x) {
            for ($y = 0; $y < $yA; ++$y) {
                $rgbA = imagecolorat($imgA, $x, $y);
                $pixA = imagecolorsforindex($imgA, $rgbA);

                $rgbB = imagecolorat($imgB, $x, $y);
                $pixB = imagecolorsforindex($imgB, $rgbB);

                $similarityPoint += (int) ($pixA === $pixB);
            }
        }

        imagedestroy($imgA);
        imagedestroy($imgB);

        return $similarityPoint / ($xA * $yA);
    }

    /**
     * Convert RGB hex triad to array.
     *
     * @param int|string $color
     *
     * @return array
     */
    public static function rgb($color): array
    {
        if (is_string($color)) {
            $color = hexdec($color);
        }

        $hex = str_pad(dechex($color), $color < 4096 ? 3 : 6, '0', STR_PAD_LEFT);
        $len = strlen($hex);

        if ($len > 6) {
            throw new \LogicException(sprintf('Invalid color specified: 0x%s', $hex));
        }

        $result = array();
        $repeat = 6 / $len;

        foreach (str_split($hex, $len / 3) as $hue) {
            $result[] = hexdec(str_repeat($hue, $repeat));
        }

        return $result;
    }

    /**
     * Generate identicon.
     *
     * @param string      $text
     * @param int         $size
     * @param int         $blocks
     * @param string|null $file
     * @param string|null $format
     *
     * @return Image
     */
    public static function identicon(string $text, int $size = 64, int $blocks = 4, string $file = null, string $format = null): Image
    {
        $hash = sha1($text);
        $sprites = array(
            array(.5, 1, 1, 0, 1, 1),
            array(.5, 0, 1, 0, .5, 1, 0, 1),
            array(.5, 0, 1, 0, 1, 1, .5, 1, 1, .5),
            array(0, .5, .5, 0, 1, .5, .5, 1, .5, .5),
            array(0, .5, 1, 0, 1, 1, 0, 1, 1, .5),
            array(1, 0, 1, 1, .5, 1, 1, .5, .5, .5),
            array(0, 0, 1, 0, 1, .5, 0, 0, .5, 1, 0, 1),
            array(0, 0, .5, 0, 1, .5, .5, 1, 0, 1, .5, .5),
            array(.5, 0, .5, .5, 1, .5, 1, 1, .5, 1, .5, .5, 0, .5),
            array(0, 0, 1, 0, .5, .5, 1, .5, .5, 1, .5, .5, 0, 1),
            array(0, .5, .5, 1, 1, .5, .5, 0, 1, 0, 1, 1, 0, 1),
            array(.5, 0, 1, 0, 1, 1, .5, 1, 1, .75, .5, .5, 1, .25),
            array(0, .5, .5, 0, .5, .5, 1, 0, 1, .5, .5, 1, .5, .5, 0, 1),
            array(0, 0, 1, 0, 1, 1, 0, 1, 1, .5, .5, .25, .5, .75, 0, .5, .5, .25),
            array(0, .5, .5, .5, .5, 0, 1, 0, .5, .5, 1, .5, .5, 1, .5, .5, 0, 1),
            array(0, 0, 1, 0, .5, .5, .5, 0, 0, .5, 1, .5, .5, 1, .5, .5, 0, 1),
        );

        $resource = imagecreatetruecolor($size, $size);
        list($r, $g, $b) = self::rgb(hexdec(substr($hash, -3)));
        $fg = imagecolorallocate($resource, $r, $g, $b);
        $ctr = count($sprites);
        $dim = $blocks * floor($size / $blocks) * 2 / $blocks;

        imagefill($resource, 0, 0, IMG_COLOR_TRANSPARENT);

        for ($j = 0,$y = ceil($blocks / 2); $j < $y; ++$j) {
            for ($i = $j,$x = $blocks - 1 - $j; $i < $x; ++$i) {
                $mDim = intval($dim);
                $sprite = imagecreatetruecolor($mDim, $mDim);
                $block = $sprites[hexdec($hash[($j * $blocks + $i) * 2]) % $ctr];

                imagefill($sprite, 0, 0, IMG_COLOR_TRANSPARENT);

                for ($k = 0,$pts = count($block); $k < $pts; ++$k) {
                    $block[$k] *= $dim;
                }

                imagefilledpolygon($sprite, $block, intval($pts / 2), $fg);

                for ($k = 0; $k < 4; ++$k) {
                    imagecopyresampled($resource, $sprite, intval($i * $dim / 2), intval($j * $dim / 2), 0, 0, intval($dim / 2), intval($dim / 2), $mDim, $mDim);
                    $resource = imagerotate($resource, 90, imagecolorallocatealpha($resource, 0, 0, 0, 127));
                }

                imagedestroy($sprite);
            }
        }

        imagesavealpha($resource, true);

        return new static($resource, $file, $format);
    }

    /**
     * Generate Captcha image.
     *
     * @param string|null &$text
     * @param array|null  $options
     * @param string|null $file
     * @param string|null $format
     *
     * @return Image
     */
    public static function captcha(string &$text = null, array $options = null, string $file = null, string $format = null): Image
    {
        $options = (array) $options + array(
            'font' => null,
            'paths' => null,
            'length' => 5,
            'size' => 24,
            'fgColor' => 0xFFFFFF,
            'bgColor' => 0x000000,
        );

        $resource = null;
        $len = $options['length'];
        $paths = $options['paths'];
        $ssl = extension_loaded('openssl');
        $lengthValid = $ssl && ($len >= 4 && $len <= 12);
        $textOk = function_exists('imagettftext');

        if ($lengthValid && $textOk && $paths) {
            $size = $options['size'];
            $fg = $options['fgColor'];
            $bg = $options['bgColor'];

            foreach (is_array($paths) ? $paths : explode(';', $paths) as $dir) {
                if (is_file($path = $dir.$options['font'])) {
                    $text = strtoupper(substr($ssl ? bin2hex(openssl_random_pseudo_bytes($len)) : uniqid(), -$len));
                    $block = $size * 3;
                    $tmp = array();

                    for ($i = 0,$width = 0,$height = 0; $i < $len; ++$i) {
                        // Process at 2x magnification
                        $box = imagettfbbox($size * 2, 0, $path, $text[$i]);
                        $char = imagecreatetruecolor($block, $block);
                        $w = $box[2] - $box[0];
                        $h = $box[1] - $box[5];

                        imagefill($char, 0, 0, $bg);
                        imagettftext($char, $size * 2, 0, intval(($block - $w) / 2), $block - intval(($block - $h) / 2), $fg, $path, $text[$i]);

                        $char = imagerotate($char, mt_rand(-30, 30), imagecolorallocatealpha($char, 0, 0, 0, 127));
                        // Reduce to normal size
                        $tmp[$i] = imagecreatetruecolor(intval(($w = imagesx($char)) / 2), intval(($h = imagesy($char)) / 2));
                        imagefill($tmp[$i], 0, 0, IMG_COLOR_TRANSPARENT);
                        imagecopyresampled($tmp[$i], $char, 0, 0, 0, 0, intval($w / 2), intval($h / 2), $w, $h);
                        imagedestroy($char);
                        $width += $i + 1 < $len ? $block / 2 : $w / 2;
                        $height = max($height, $h / 2);
                    }

                    $resource = imagecreatetruecolor(intval($width), intval($height));
                    imagefill($resource, 0, 0, IMG_COLOR_TRANSPARENT);

                    for ($i = 0; $i < $len; ++$i) {
                        imagecopy($resource, $tmp[$i], $i * $block / 2, intval(($height - imagesy($tmp[$i])) / 2), 0, 0, imagesx($tmp[$i]), imagesy($tmp[$i]));
                        imagedestroy($tmp[$i]);
                    }

                    imagesavealpha($resource, true);
                }
            }

            if (!$resource) {
                throw new \LogicException('Captcha font not exists.');
            }
        }

        return new static($resource, $file, $format);
    }

    /**
     * Class constructor.
     *
     * @param resource|string|null $resource
     * @param string|null          $file
     * @param string|null          $format
     */
    public function __construct($resource = null, string $file = null, string $format = null)
    {
        if (is_resource($resource)) {
            $this->resource = $resource;
        } elseif ((is_string($resource) && is_resource($this->resource = imagecreatefromstring($resource)))
            || ($file && file_exists($file) && is_resource($this->resource = imagecreatefromstring(file_get_contents($file))))
        ) {
            imagesavealpha($this->resource, true);
        } else {
            throw new \LogicException('No image resource provided!');
        }

        if ($format) {
            if (!is_callable('image'.$format)) {
                throw new \LogicException(sprintf('Image format not supported: %s.', $format));
            }

            $this->format = $format;
        }

        $this->filepath = $file;
    }

    /**
     * Clear resource.
     */
    public function __destruct()
    {
        imagedestroy($this->resource);
    }

    /**
     * Returns file path.
     *
     * @return string|null
     */
    public function getFilepath(): ?string
    {
        return $this->filepath;
    }

    /**
     * Returns image format.
     *
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * Returns resource.
     *
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Returns image as string.
     *
     * @param mixed ...$arguments
     *
     * @return string
     */
    public function dump(...$arguments): string
    {
        $format = 'image'.$this->format;

        ob_start();
        $format($this->resource, null, ...$arguments);

        return ob_get_clean();
    }

    /**
     * Returns image as base64 encoded.
     *
     * @param mixed ...$arguments
     *
     * @return string
     */
    public function base64(...$arguments)
    {
        return 'data:image/'.$this->format.';base64,'.base64_encode($this->dump(...$arguments));
    }

    /**
     * Save image file.
     *
     * @param mixed ...$arguments
     *
     * @return bool
     */
    public function save(...$arguments)
    {
        if (!$this->filepath) {
            throw new \LogicException('No file to save!');
        }

        return false !== file_put_contents($this->filepath, $this->dump(...$arguments));
    }

    /**
     * Return image width.
     *
     * @return int
     */
    public function width(): int
    {
        return imagesx($this->resource);
    }

    /**
     * Return image height.
     *
     * @return int
     */
    public function height(): int
    {
        return imagesy($this->resource);
    }

    /**
     * Invert image.
     *
     * @return Image
     */
    public function invert(): Image
    {
        imagefilter($this->resource, IMG_FILTER_NEGATE);

        return $this;
    }

    /**
     * Adjust brightness (range:-255 to 255).
     *
     * @param int $level
     *
     * @return Image
     */
    public function brightness($level): Image
    {
        imagefilter($this->resource, IMG_FILTER_BRIGHTNESS, $level);

        return $this;
    }

    /**
     * Adjust contrast (range:-100 to 100).
     *
     * @param int $level
     *
     * @return Image
     */
    public function contrast($level): Image
    {
        imagefilter($this->resource, IMG_FILTER_CONTRAST, $level);

        return $this;
    }

    /**
     * Convert to grayscale.
     *
     * @return Image
     */
    public function grayscale(): Image
    {
        imagefilter($this->resource, IMG_FILTER_GRAYSCALE);

        return $this;
    }

    /**
     * Adjust smoothness.
     *
     * @param int $level
     *
     * @return Image
     */
    public function smooth(int $level): Image
    {
        imagefilter($this->resource, IMG_FILTER_SMOOTH, $level);

        return $this;
    }

    /**
     * Emboss the image.
     *
     * @return Image
     */
    public function emboss(): Image
    {
        imagefilter($this->resource, IMG_FILTER_EMBOSS);

        return $this;
    }

    /**
     * Apply sepia effect.
     *
     * @return Image
     */
    public function sepia(): Image
    {
        imagefilter($this->resource, IMG_FILTER_GRAYSCALE);
        imagefilter($this->resource, IMG_FILTER_COLORIZE, 90, 60, 45);

        return $this;
    }

    /**
     * Pixelate the image.
     *
     * @param int $size
     *
     * @return Image
     */
    public function pixelate(int $size): Image
    {
        imagefilter($this->resource, IMG_FILTER_PIXELATE, $size, true);

        return $this;
    }

    /**
     * Blur the image using Gaussian filter.
     *
     * @param bool $selective
     *
     * @return Image
     */
    public function blur(bool $selective = false): Image
    {
        imagefilter($this->resource, $selective ? IMG_FILTER_SELECTIVE_BLUR : IMG_FILTER_GAUSSIAN_BLUR);

        return $this;
    }

    /**
     * Apply sketch effect.
     *
     * @return Image
     */
    public function sketch(): Image
    {
        imagefilter($this->resource, IMG_FILTER_MEAN_REMOVAL);

        return $this;
    }

    /**
     * Flip on horizontal axis.
     *
     * @return Image
     */
    public function hflip(): Image
    {
        $tmp = imagecreatetruecolor($width = $this->width(), $height = $this->height());

        imagesavealpha($tmp, true);
        imagefill($tmp, 0, 0, IMG_COLOR_TRANSPARENT);
        imagecopyresampled($tmp, $this->resource, 0, 0, $width - 1, 0, $width, $height, -$width, $height);
        imagedestroy($this->resource);

        $this->resource = $tmp;

        return $this;
    }

    /**
     * Flip on vertical axis.
     *
     * @return Image
     */
    public function vflip(): Image
    {
        $tmp = imagecreatetruecolor($width = $this->width(), $height = $this->height());

        imagesavealpha($tmp, true);
        imagefill($tmp, 0, 0, IMG_COLOR_TRANSPARENT);
        imagecopyresampled($tmp, $this->resource, 0, 0, 0, $height - 1, $width, $height, $width, -$height);
        imagedestroy($this->resource);

        $this->resource = $tmp;

        return $this;
    }

    /**
     * Crop the image.
     *
     * @param int $x1
     * @param int $y1
     * @param int $x2
     * @param int $y2
     *
     * @return Image
     */
    public function crop(int $x1, int $y1, int $x2, int $y2): Image
    {
        $tmp = imagecreatetruecolor($width = $x2 - $x1 + 1, $height = $y2 - $y1 + 1);

        imagesavealpha($tmp, true);
        imagefill($tmp, 0, 0, IMG_COLOR_TRANSPARENT);
        imagecopyresampled($tmp, $this->resource, 0, 0, $x1, $y1, $width, $height, $width, $height);
        imagedestroy($this->resource);

        $this->resource = $tmp;

        return $this;
    }

    /**
     * Resize image (Maintain aspect ratio).
     *
     * Crop relative to center; if flag is enabled; Enlargement allowed if flag is enabled.
     *
     * @param int|null $width
     * @param int|null $height
     * @param bool     $crop
     * @param bool     $enlarge
     *
     * @return Image
     */
    public function resize(int $width = null, int $height = null, bool $crop = true, bool $enlarge = true): Image
    {
        if (is_null($width) && is_null($height)) {
            return $this;
        }

        $origw = $this->width();
        $origh = $this->height();
        $mWidth = $width;
        $mHeight = $height;

        if (is_null($width)) {
            $mWidth = round(($height / $origh) * $origw);
        }

        if (is_null($height)) {
            $mHeight = round(($width / $origw) * $origh);
        }

        // Adjust dimensions; retain aspect ratio
        $ratio = $origw / $origh;

        if (!$crop) {
            if ($mWidth / $ratio <= $mHeight) {
                $mHeight = round($mWidth / $ratio);
            } else {
                $mWidth = round($mHeight * $ratio);
            }
        }

        if (!$enlarge) {
            $mWidth = min($origw, $mWidth);
            $mHeight = min($origh, $mHeight);
        }

        // Create blank image
        $tmp = imagecreatetruecolor(intval($mWidth), intval($mHeight));

        imagesavealpha($tmp, true);
        imagefill($tmp, 0, 0, IMG_COLOR_TRANSPARENT);

        // Resize
        if ($crop) {
            if ($mWidth / $ratio <= $mHeight) {
                $cropw = round($origh * $mWidth / $mHeight);
                imagecopyresampled($tmp, $this->resource, 0, 0, intval(($origw - $cropw) / 2), 0, intval($mWidth), intval($mHeight), intval($cropw), $origh);
            } else {
                $croph = round($origw * $mHeight / $mWidth);
                imagecopyresampled($tmp, $this->resource, 0, 0, 0, intval(($origh - $croph) / 2), intval($mWidth), intval($mHeight), $origw, intval($croph));
            }
        } else {
            imagecopyresampled($tmp, $this->resource, 0, 0, 0, 0, intval($mWidth), intval($mHeight), $origw, $origh);
        }

        imagedestroy($this->resource);

        $this->resource = $tmp;

        return $this;
    }

    /**
     * Rotate image.
     *
     * @param int $angle
     *
     * @return Image
     */
    public function rotate(int $angle): Image
    {
        $this->resource = imagerotate($this->resource, $angle, imagecolorallocatealpha($this->resource, 0, 0, 0, 127));

        imagesavealpha($this->resource, true);

        return $this;
    }

    /**
     * Apply an image overlay.
     *
     * @param Image $overlay
     * @param mixed $align
     * @param int   $alpha
     *
     * @return Image
     */
    public function overlay(Image $overlay, $align = null, int $alpha = 100): Image
    {
        $imgw = $this->width();
        $imgh = $this->height();
        $ovrw = $overlay->width();
        $ovrh = $overlay->height();

        if (is_null($align)) {
            $align = self::POS_RIGHT | self::POS_BOTTOM;
        } elseif (is_array($align) && 2 === count($align)) {
            list($posx, $posy) = array_values($align);
            $align = 0;
        }

        if ($align & self::POS_LEFT) {
            $posx = 0;
        }

        if ($align & self::POS_CENTER) {
            $posx = ($imgw - $ovrw) / 2;
        }

        if ($align & self::POS_RIGHT) {
            $posx = $imgw - $ovrw;
        }

        if ($align & self::POS_TOP) {
            $posy = 0;
        }

        if ($align & self::POS_MIDDLE) {
            $posy = ($imgh - $ovrh) / 2;
        }

        if ($align & self::POS_BOTTOM) {
            $posy = $imgh - $ovrh;
        }

        $posx = empty($posx) ? 0 : intval($posx);
        $posy = empty($posy) ? 0 : intval($posy);
        $ovr = imagecreatefromstring($overlay->dump());
        imagesavealpha($ovr, true);

        if (100 == $alpha) {
            imagecopy($this->resource, $ovr, $posx, $posy, 0, 0, $ovrw, $ovrh);
        } else {
            $cut = imagecreatetruecolor($ovrw, $ovrh);

            imagecopy($cut, $this->resource, 0, 0, $posx, $posy, $ovrw, $ovrh);
            imagecopy($cut, $ovr, 0, 0, 0, 0, $ovrw, $ovrh);
            imagecopymerge($this->resource, $cut, $posx, $posy, 0, 0, $ovrw, $ovrh, $alpha);
        }

        return $this;
    }
}

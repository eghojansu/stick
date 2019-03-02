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

namespace Fal\Stick\Helper;

use Fal\Stick\Util;

/**
 * Image manipulation tools.
 *
 * Ported from F3\Image.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Image
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
    protected $data;

    /**
     * File path.
     *
     * @var string
     */
    protected $file;

    /**
     * @var array
     */
    protected $options = array(
        'image_format' => 'png',
        'captcha_font' => null,
        'captcha_font_path' => null,
        'captcha_length' => 5,
        'captcha_size' => 24,
        'captcha_fg_color' => 0xFFFFFF,
        'captcha_bg_color' => 0x000000,
    );

    /**
     * Compare image by create the difference.
     *
     * (https://www.phpied.com/image-diff/).
     *
     * @param string $imgA
     * @param string $imgB
     *
     * @return float
     */
    public static function pixelCompare(string $imgA, string $imgB): float
    {
        $resA = null;
        $resB = null;

        if ($imgA) {
            $resA = imagecreatefromstring($imgA);
        }

        if ($imgB) {
            $resB = imagecreatefromstring($imgB);
        }

        if (!$imgA || !$imgB || !$resA || !$resB) {
            throw new \LogicException('Both image should be valid!');
        }

        $xA = imagesx($resA);
        $yA = imagesy($resA);

        if ($xA !== imagesx($resB) && $yA !== imagesy($resB)) {
            imagedestroy($resA);
            imagedestroy($resB);

            return 100.0;
        }

        $differentPixels = 0;

        for ($x = 0; $x < $xA; ++$x) {
            for ($y = 0; $y < $yA; ++$y) {
                $rgbA = imagecolorat($resA, $x, $y);
                $pixA = imagecolorsforindex($resA, $rgbA);

                $rgbB = imagecolorat($resB, $x, $y);
                $pixB = imagecolorsforindex($resB, $rgbB);

                $differentPixels += (int) ($pixA !== $pixB);
            }
        }

        imagedestroy($resA);
        imagedestroy($resB);

        return $differentPixels / ($xA * $yA);
    }

    /**
     * Class constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = null)
    {
        $this->setOptions($options ?? array());
    }

    /**
     * Clear resource.
     */
    public function __destruct()
    {
        $this->reset();
    }

    /**
     * Returns options.
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Assign options.
     *
     * @param array $options
     *
     * @return Image
     */
    public function setOptions(array $options): Image
    {
        $this->options = $options + $this->options;

        return $this;
    }

    /**
     * Convert RGB hex triad to array.
     *
     * @param int|string $color
     *
     * @return array
     */
    public function rgb($color): array
    {
        $mColor = $color;

        if (is_string($color)) {
            $mColor = hexdec($color);
        }

        $hex = str_pad(dechex($mColor), $mColor < 4096 ? 3 : 6, '0', STR_PAD_LEFT);
        $len = strlen($hex);

        if ($len > 6) {
            throw new \LogicException(sprintf('Invalid color specified: 0x%s.', $hex));
        }

        $result = array();
        $repeat = 6 / $len;

        foreach (str_split($hex, $len / 3) as $hue) {
            $result[] = hexdec(str_repeat($hue, $repeat));
        }

        return $result;
    }

    /**
     * Reset image resource.
     *
     * @return Image
     */
    public function reset(): Image
    {
        if (is_resource($this->data)) {
            imagedestroy($this->data);
        }

        return $this;
    }

    /**
     * Returns resource.
     *
     * @return resource|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Load image from file.
     *
     * @param string $file
     *
     * @return Image
     *
     * @throws LogicException if file not exists
     */
    public function loadFile(string $file): Image
    {
        if (!is_file($file)) {
            throw new \LogicException(sprintf('File "%s" is not found.', $file));
        }

        $this->file = $file;

        return $this->load(file_get_contents($file));
    }

    /**
     * Load image from string.
     *
     * @param string $str
     *
     * @return Image
     */
    public function load(string $str): Image
    {
        if ($this->data = imagecreatefromstring($str)) {
            imagesavealpha($this->data, true);
        }

        return $this;
    }

    /**
     * Returns image as base64 encoded.
     *
     * @param string|null $format
     * @param mixed       ...$args
     *
     * @return string
     */
    public function base64(string $format = null, ...$args): string
    {
        $mFormat = $format ?? $this->options['image_format'];

        return 'data:image/'.$mFormat.';base64,'.base64_encode($this->dump($mFormat, ...$args));
    }

    /**
     * Returns image as string.
     *
     * @param string|null $format
     * @param mixed       ...$args
     *
     * @return string
     */
    public function dump(string $format = null, ...$args): string
    {
        $formatter = 'image'.($format ?? $this->options['image_format']);

        if (!is_callable($formatter)) {
            throw new \LogicException(sprintf('Image function "%s" does not exists.', $formatter));
        }

        ob_start();
        $formatter($this->data, null, ...$args);

        return ob_get_clean();
    }

    /**
     * Save image file.
     *
     * @param string|null $file
     *
     * @return bool
     */
    public function save(string $file = null): bool
    {
        $mFile = $file ?? $this->file;

        if (!$mFile) {
            throw new \LogicException('No file to save!');
        }

        return false !== file_put_contents($mFile, $this->dump());
    }

    /**
     * Return image width.
     *
     * @return int
     */
    public function width(): int
    {
        return imagesx($this->data);
    }

    /**
     * Return image height.
     *
     * @return int
     */
    public function height(): int
    {
        return imagesy($this->data);
    }

    /**
     * Invert image.
     *
     * @return Image
     */
    public function invert(): Image
    {
        imagefilter($this->data, IMG_FILTER_NEGATE);

        return $this;
    }

    /**
     * Adjust brightness (range:-255 to 255).
     *
     * @param int $level
     *
     * @return Image
     */
    public function brightness(int $level): Image
    {
        imagefilter($this->data, IMG_FILTER_BRIGHTNESS, $level);

        return $this;
    }

    /**
     * Adjust contrast (range:-100 to 100).
     *
     * @param int $level
     *
     * @return Image
     */
    public function contrast(int $level): Image
    {
        imagefilter($this->data, IMG_FILTER_CONTRAST, $level);

        return $this;
    }

    /**
     * Convert to grayscale.
     *
     * @return Image
     */
    public function grayscale(): Image
    {
        imagefilter($this->data, IMG_FILTER_GRAYSCALE);

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
        imagefilter($this->data, IMG_FILTER_SMOOTH, $level);

        return $this;
    }

    /**
     * Emboss the image.
     *
     * @return Image
     */
    public function emboss(): Image
    {
        imagefilter($this->data, IMG_FILTER_EMBOSS);

        return $this;
    }

    /**
     * Apply sepia effect.
     *
     * @return Image
     */
    public function sepia(): Image
    {
        imagefilter($this->data, IMG_FILTER_GRAYSCALE);
        imagefilter($this->data, IMG_FILTER_COLORIZE, 90, 60, 45);

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
        imagefilter($this->data, IMG_FILTER_PIXELATE, $size, true);

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
        imagefilter($this->data, $selective ? IMG_FILTER_SELECTIVE_BLUR : IMG_FILTER_GAUSSIAN_BLUR);

        return $this;
    }

    /**
     * Apply sketch effect.
     *
     * @return Image
     */
    public function sketch(): Image
    {
        imagefilter($this->data, IMG_FILTER_MEAN_REMOVAL);

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
        imagecopyresampled($tmp, $this->data, 0, 0, $width - 1, 0, $width, $height, -$width, $height);
        imagedestroy($this->data);

        $this->data = $tmp;

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
        imagecopyresampled($tmp, $this->data, 0, 0, 0, $height - 1, $width, $height, $width, -$height);
        imagedestroy($this->data);

        $this->data = $tmp;

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
        imagecopyresampled($tmp, $this->data, 0, 0, $x1, $y1, $width, $height, $width, $height);
        imagedestroy($this->data);

        $this->data = $tmp;

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
                imagecopyresampled($tmp, $this->data, 0, 0, intval(($origw - $cropw) / 2), 0, intval($mWidth), intval($mHeight), intval($cropw), $origh);
            } else {
                $croph = round($origw * $mHeight / $mWidth);
                imagecopyresampled($tmp, $this->data, 0, 0, 0, intval(($origh - $croph) / 2), intval($mWidth), intval($mHeight), $origw, intval($croph));
            }
        } else {
            imagecopyresampled($tmp, $this->data, 0, 0, 0, 0, intval($mWidth), intval($mHeight), $origw, $origh);
        }

        imagedestroy($this->data);

        $this->data = $tmp;

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
        $this->data = imagerotate($this->data, $angle, imagecolorallocatealpha($this->data, 0, 0, 0, 127));

        imagesavealpha($this->data, true);

        return $this;
    }

    /**
     * Apply an image overlay.
     *
     * @param Image $img
     * @param mixed $align
     * @param int   $alpha
     *
     * @return Image
     */
    public function overlay(Image $img, $align = null, int $alpha = 100): Image
    {
        $mAlign = $align ?? 0;

        if (is_null($align)) {
            $mAlign = static::POS_RIGHT | static::POS_BOTTOM;
        } elseif (is_array($align)) {
            list($posx, $posy) = $align;
            $mAlign = 0;
        }

        $ovr = imagecreatefromstring($img->dump());
        imagesavealpha($ovr, true);

        $imgw = $this->width();
        $imgh = $this->height();

        $ovrw = imagesx($ovr);
        $ovrh = imagesy($ovr);

        if ($mAlign & static::POS_LEFT) {
            $posx = 0;
        }

        if ($mAlign & static::POS_CENTER) {
            $posx = ($imgw - $ovrw) / 2;
        }

        if ($mAlign & static::POS_RIGHT) {
            $posx = $imgw - $ovrw;
        }

        if ($mAlign & static::POS_TOP) {
            $posy = 0;
        }

        if ($mAlign & static::POS_MIDDLE) {
            $posy = ($imgh - $ovrh) / 2;
        }

        if ($mAlign & static::POS_BOTTOM) {
            $posy = $imgh - $ovrh;
        }

        if (empty($posx)) {
            $posx = 0;
        }

        if (empty($posy)) {
            $posy = 0;
        }

        if (100 == $alpha) {
            imagecopy($this->data, $ovr, intval($posx), intval($posy), 0, 0, $ovrw, $ovrh);
        } else {
            $cut = imagecreatetruecolor($ovrw, $ovrh);

            imagecopy($cut, $this->data, 0, 0, intval($posx), intval($posy), $ovrw, $ovrh);
            imagecopy($cut, $ovr, 0, 0, 0, 0, $ovrw, $ovrh);
            imagecopymerge($this->data, $cut, intval($posx), intval($posy), 0, 0, $ovrw, $ovrh, $alpha);
        }

        return $this;
    }

    /**
     * Generate identicon.
     *
     * @param string $str
     * @param int    $size
     * @param int    $blocks
     *
     * @return Image
     */
    public function identicon(string $str, int $size = 64, int $blocks = 4): Image
    {
        $this->reset();

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
        $hash = sha1($str);

        $this->data = imagecreatetruecolor($size, $size);
        list($r, $g, $b) = $this->rgb(hexdec(substr($hash, -3)));
        $fg = imagecolorallocate($this->data, $r, $g, $b);
        $ctr = count($sprites);
        $dim = $blocks * floor($size / $blocks) * 2 / $blocks;

        imagefill($this->data, 0, 0, IMG_COLOR_TRANSPARENT);

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
                    imagecopyresampled($this->data, $sprite, intval($i * $dim / 2), intval($j * $dim / 2), 0, 0, intval($dim / 2), intval($dim / 2), $mDim, $mDim);
                    $this->data = imagerotate($this->data, 90, imagecolorallocatealpha($this->data, 0, 0, 0, 127));
                }

                imagedestroy($sprite);
            }
        }

        imagesavealpha($this->data, true);

        return $this;
    }

    /**
     * Generate CAPTCHA image.
     *
     * @return string
     */
    public function captcha(): ?string
    {
        $this->reset();

        $len = $this->options['captcha_length'];
        $size = $this->options['captcha_size'];
        $fg = $this->options['captcha_fg_color'];
        $bg = $this->options['captcha_bg_color'];
        $path = $this->options['captcha_font_path'];
        $font = $this->options['captcha_font'];
        $ssl = extension_loaded('openssl');

        foreach (Util::split($path) as $dir) {
            if (is_file($path = $dir.$font)) {
                $seed = strtoupper(substr($ssl ? bin2hex(openssl_random_pseudo_bytes($len)) : uniqid(), -$len));
                $block = $size * 3;
                $tmp = array();

                for ($i = 0,$width = 0,$height = 0; $i < $len; ++$i) {
                    // Process at 2x magnification
                    $box = imagettfbbox($size * 2, 0, $path, $seed[$i]);
                    $char = imagecreatetruecolor($block, $block);
                    $w = $box[2] - $box[0];
                    $h = $box[1] - $box[5];

                    imagefill($char, 0, 0, $bg);
                    imagettftext($char, $size * 2, 0, intval(($block - $w) / 2), $block - intval(($block - $h) / 2), $fg, $path, $seed[$i]);

                    $char = imagerotate($char, mt_rand(-30, 30), imagecolorallocatealpha($char, 0, 0, 0, 127));
                    // Reduce to normal size
                    $tmp[$i] = imagecreatetruecolor(intval(($w = imagesx($char)) / 2), intval(($h = imagesy($char)) / 2));
                    imagefill($tmp[$i], 0, 0, IMG_COLOR_TRANSPARENT);
                    imagecopyresampled($tmp[$i], $char, 0, 0, 0, 0, intval($w / 2), intval($h / 2), $w, $h);
                    imagedestroy($char);
                    $width += $i + 1 < $len ? $block / 2 : $w / 2;
                    $height = max($height, $h / 2);
                }

                $this->data = imagecreatetruecolor(intval($width), intval($height));
                imagefill($this->data, 0, 0, IMG_COLOR_TRANSPARENT);

                for ($i = 0; $i < $len; ++$i) {
                    imagecopy($this->data, $tmp[$i], $i * $block / 2, intval(($height - imagesy($tmp[$i])) / 2), 0, 0, imagesx($tmp[$i]), imagesy($tmp[$i]));
                    imagedestroy($tmp[$i]);
                }

                imagesavealpha($this->data, true);

                return $seed;
            }
        }

        throw new \LogicException('CAPTCHA font is not found.');
    }
}

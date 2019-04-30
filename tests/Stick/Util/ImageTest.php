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

namespace Fal\Stick\Test\Util;

use Fal\Stick\Util\Image;
use Fal\Stick\TestSuite\MyTestCase;

class ImageTest extends MyTestCase
{
    private $file;
    private $save;

    protected function createInstance()
    {
        return new Image(file_get_contents($this->file), $this->save);
    }

    public function setup(): void
    {
        $this->file = $this->fixture('/images/original.png');
        $this->save = $this->tmp('/original-backup.png');
    }

    public function testCompare()
    {
        $img = file_get_contents($this->fixture('/images/original.png'));
        $imgFlipped = file_get_contents($this->fixture('/images/hflip.png'));
        $imgResized = file_get_contents($this->fixture('/images/resize-50-40.png'));

        $this->assertSame(1.0, Image::compare($img, $img));
        $this->assertSame(0.0, Image::compare($img, $imgResized));
        $this->assertSame(0.0, Image::compare($img, $imgFlipped));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Both image should be valid!');
        Image::compare('', '');
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Util\ImageProvider::rgb
     */
    public function testRgb($expected, $color, $exception = null)
    {
        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            Image::rgb($color);

            return;
        }

        $this->assertEquals($expected, Image::rgb($color));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Util\ImageProvider::identicon
     */
    public function testIdenticon($expected, $text)
    {
        $this->assertSame(1.0, Image::compare($expected, Image::identicon($text)->dump()));
    }

    public function testCaptcha()
    {
        $this->assertInstanceOf('Fal\\Stick\\Util\\Image', Image::captcha($text, array(
            'font' => 'Lato-Black.ttf',
            'paths' => $this->fixture('/fonts/'),
        )));
        $this->assertEquals(5, strlen($text));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Captcha font not exists.');
        Image::captcha($seed, array(
            'font' => 'Lato-Black-Unknown.ttf',
            'paths' => $this->fixture('/fonts/'),
        ));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Util\ImageProvider::construct
     */
    public function testConstruct($expected, $resource = null, $file = null, $format = null, $exception = null)
    {
        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            new Image($resource, $file, $format);

            return;
        }

        $image = new Image($resource, $file, $format);

        $this->assertEquals($expected, $image->getFilepath());
    }

    public function testGetFilepath()
    {
        $this->assertEquals($this->save, $this->image->getFilepath());
    }

    public function testGetFormat()
    {
        $this->assertEquals('png', $this->image->getFormat());
    }

    public function testGetResource()
    {
        $this->assertTrue(is_resource($this->image->getResource()));
    }

    public function testDump()
    {
        $this->assertEquals(file_get_contents($this->file), $this->image->dump());
    }

    public function testBase64()
    {
        $this->assertRegexp('~^data:image/png;base64,~', $this->image->base64());
    }

    public function testSave()
    {
        $this->tmp(null, true);
        $file = $this->image->getFilepath();

        $this->assertFileNotExists($file);
        $this->assertTrue($this->image->save());
        $this->assertFileExists($file);
        unlink($file);

        $clone = new Image($this->image->dump());

        $this->expectException('LogicException');
        $this->expectExceptionMessage('No file to save!');
        $clone->save();
    }

    public function testWidth()
    {
        $this->assertEquals(80, $this->image->width());
    }

    public function testHeight()
    {
        $this->assertEquals(62, $this->image->height());
    }

    public function testInvert()
    {
        $imgA = $this->read('/images/invert.png');
        $imgB = $this->image->invert()->dump();

        $this->assertSame(1.0, Image::compare($imgA, $imgB));
    }

    public function testBrightness()
    {
        $imgA = $this->read('/images/brightness-100.png');
        $imgB = $this->image->brightness(100)->dump();

        $this->assertSame(1.0, Image::compare($imgA, $imgB));
    }

    public function testContrast()
    {
        $imgA = $this->read('/images/contrast-100.png');
        $imgB = $this->image->contrast(100)->dump();

        $this->assertSame(1.0, Image::compare($imgA, $imgB));
    }

    public function testGrayscale()
    {
        $imgA = $this->read('/images/grayscale.png');
        $imgB = $this->image->grayscale()->dump();

        $this->assertSame(1.0, Image::compare($imgA, $imgB));
    }

    public function testSmooth()
    {
        $imgA = $this->read('/images/smooth-50.png');
        $imgB = $this->image->smooth(50)->dump();

        $this->assertSame(1.0, Image::compare($imgA, $imgB));
    }

    public function testEmboss()
    {
        $imgA = $this->read('/images/emboss.png');
        $imgB = $this->image->emboss()->dump();

        $this->assertSame(1.0, Image::compare($imgA, $imgB));
    }

    public function testSepia()
    {
        $imgA = $this->read('/images/sepia.png');
        $imgB = $this->image->sepia()->dump();

        $this->assertSame(1.0, Image::compare($imgA, $imgB));
    }

    public function testPixelate()
    {
        $imgA = $this->read('/images/pixelate-10.png');
        $imgB = $this->image->pixelate(10)->dump();

        $this->assertSame(1.0, Image::compare($imgA, $imgB));
    }

    public function testBlur()
    {
        $imgA = $this->read('/images/blur.png');
        $imgB = $this->image->blur()->dump();

        $this->assertSame(1.0, Image::compare($imgA, $imgB));
    }

    public function testBlurSelective()
    {
        $imgA = $this->read('/images/blur-selective.png');
        $imgB = $this->image->blur(true)->dump();

        $this->assertSame(1.0, Image::compare($imgA, $imgB));
    }

    public function testSketch()
    {
        $imgA = $this->read('/images/sketch.png');
        $imgB = $this->image->sketch()->dump();

        $this->assertSame(1.0, Image::compare($imgA, $imgB));
    }

    public function testHflip()
    {
        $imgA = $this->read('/images/hflip.png');
        $imgB = $this->image->hflip()->dump();

        $this->assertSame(1.0, Image::compare($imgA, $imgB));
    }

    public function testVflip()
    {
        $imgA = $this->read('/images/vflip.png');
        $imgB = $this->image->vflip()->dump();

        $this->assertSame(1.0, Image::compare($imgA, $imgB));
    }

    public function testCrop()
    {
        $imgA = $this->read('/images/crop-0-0-80-40.png');
        $imgB = $this->image->crop(0, 0, 80, 40)->dump();

        $this->assertSame(1.0, Image::compare($imgA, $imgB));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Util\ImageProvider::resize
     */
    public function testResize($file, $width = null, $height = null, $crop = true, $enlarge = true)
    {
        $imgA = $this->read('/images/'.$file.'.png');
        $imgB = $this->image->resize($width, $height, $crop, $enlarge)->dump();

        $this->assertSame(1.0, Image::compare($imgA, $imgB));
    }

    public function testRotate()
    {
        $imgA = $this->read('/images/rotate-30.png');
        $imgB = $this->image->rotate(30)->dump();

        $this->assertSame(1.0, Image::compare($imgA, $imgB));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Util\ImageProvider::overlay
     */
    public function testOverlay($file, $align = null, $alpha = 100)
    {
        $foo = new Image($this->read('/images/identicon-foo.png'));
        $bar = new Image($this->read('/images/identicon-bar.png'));

        $imgA = $this->read('/images/'.$file.'.png');
        $imgB = $bar->overlay($foo, $align, $alpha)->dump();

        $this->assertSame(1.0, Image::compare($imgA, $imgB));
    }
}

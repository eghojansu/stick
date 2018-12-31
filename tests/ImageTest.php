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

namespace Fal\Stick\Test;

use Fal\Stick\Core;
use Fal\Stick\Image;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    private $fw;
    private $image;

    public function setUp()
    {
        $this->fw = new Core();
        $this->image = new Image($this->fw);
    }

    public function testPixelCompare()
    {
        $img = file_get_contents(TEST_FIXTURE.'images/original.png');
        $imgFlip = file_get_contents(TEST_FIXTURE.'images/hflip.png');
        $imgResize = file_get_contents(TEST_FIXTURE.'images/resize-50-40.png');

        $this->assertEquals(0.0, Image::pixelCompare($img, $img));
        $this->assertEquals(100.0, Image::pixelCompare($img, $imgResize));
        $this->assertGreaterThan(0.0, Image::pixelCompare($img, $imgFlip));
    }

    public function testPixelCompareException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Both image should be valid!');

        Image::pixelCompare('', '');
    }

    public function testCreate()
    {
        $this->assertNotSame($this->image, $this->image->create());
    }

    public function testGetDefaultFormat()
    {
        $this->assertEquals('png', $this->image->getDefaultFormat());
    }

    public function testSetDefaultFormat()
    {
        $this->assertEquals('foo', $this->image->setDefaultFormat('foo')->getDefaultFormat());
    }

    public function testGetData()
    {
        $this->assertNull($this->image->getData());
    }

    public function testLoad()
    {
        $this->assertNotNull($this->image->load(file_get_contents(TEST_FIXTURE.'images/original.png'))->getData());
    }

    public function testLoadFile()
    {
        $this->assertNotNull($this->image->loadFile(TEST_FIXTURE.'images/original.png')->getData());
    }

    public function testLoadFileException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessageRegExp('/File ".+" is not found\./');

        $this->image->loadFile(TEST_FIXTURE.'images/none.png');
    }

    public function testDestroy()
    {
        $this->image->loadFile(TEST_FIXTURE.'images/original.png');
        $this->image = null;

        $this->assertNull($this->image);
    }

    /**
     * @dataProvider rgbProvider
     */
    public function testRgb($expected, $color, $exception = null)
    {
        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            $this->image->rgb($color);
        }

        $this->assertEquals($expected, $this->image->rgb($color));
    }

    public function testBase64()
    {
        $this->image->loadFile(TEST_FIXTURE.'images/original.png');

        $this->assertStringStartsWith('data:image/', $this->image->base64());
    }

    public function testDump()
    {
        $this->image->loadFile(TEST_FIXTURE.'images/original.png');
        $imgA = $this->image->dump();
        $imgB = file_get_contents(TEST_FIXTURE.'images/original.png');

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testDumpException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No image loaded!');

        $this->image->dump();
    }

    public function testDumpException2()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Image function "imagefoo" does not exists.');

        $this->image->loadFile(TEST_FIXTURE.'images/original.png');
        $this->image->dump('foo');
    }

    public function testRender()
    {
        $this->fw->set('QUIET', true);

        $this->image->loadFile(TEST_FIXTURE.'images/original.png');
        $this->image->render();

        $expected = array(
            'Content-Type' => 'image/png',
            'Content-Length' => 9853,
            'X-Powered-By' => $this->fw->get('PACKAGE'),
        );

        $this->assertEquals($expected, $this->fw->get('RESPONSE'));
        $this->assertNotNull($this->fw->get('OUTPUT'));
    }

    public function testSave()
    {
        $this->image->loadFile($original = TEST_FIXTURE.'images/original.png');
        $saved = $this->image->save($file = TEST_TEMP.'image-test.png');

        $this->assertTrue($saved);
        $this->assertFileEquals($file, $original);
    }

    public function testSaveException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No file to save!');

        $this->image->load(file_get_contents(TEST_FIXTURE.'images/original.png'));
        $this->image->save();
    }

    public function testWidth()
    {
        $this->image->loadFile(TEST_FIXTURE.'images/original.png');

        $this->assertEquals(80, $this->image->width());
    }

    public function testHeight()
    {
        $this->image->loadFile(TEST_FIXTURE.'images/original.png');

        $this->assertEquals(62, $this->image->height());
    }

    public function testInvert()
    {
        $imgA = file_get_contents(TEST_FIXTURE.'images/invert.png');
        $imgB = $this->image->loadFile(TEST_FIXTURE.'images/original.png')->invert()->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testBrightness()
    {
        $imgA = file_get_contents(TEST_FIXTURE.'images/brightness-100.png');
        $imgB = $this->image->loadFile(TEST_FIXTURE.'images/original.png')->brightness(100)->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testContrast()
    {
        $imgA = file_get_contents(TEST_FIXTURE.'images/contrast-100.png');
        $imgB = $this->image->loadFile(TEST_FIXTURE.'images/original.png')->contrast(100)->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testGrayscale()
    {
        $imgA = file_get_contents(TEST_FIXTURE.'images/grayscale.png');
        $imgB = $this->image->loadFile(TEST_FIXTURE.'images/original.png')->grayscale()->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testSmooth()
    {
        $imgA = file_get_contents(TEST_FIXTURE.'images/smooth-50.png');
        $imgB = $this->image->loadFile(TEST_FIXTURE.'images/original.png')->smooth(50)->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testEmboss()
    {
        $imgA = file_get_contents(TEST_FIXTURE.'images/emboss.png');
        $imgB = $this->image->loadFile(TEST_FIXTURE.'images/original.png')->emboss()->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testSepia()
    {
        $imgA = file_get_contents(TEST_FIXTURE.'images/sepia.png');
        $imgB = $this->image->loadFile(TEST_FIXTURE.'images/original.png')->sepia()->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testPixelate()
    {
        $imgA = file_get_contents(TEST_FIXTURE.'images/pixelate-10.png');
        $imgB = $this->image->loadFile(TEST_FIXTURE.'images/original.png')->pixelate(10)->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testBlur()
    {
        $imgA = file_get_contents(TEST_FIXTURE.'images/blur.png');
        $imgB = $this->image->loadFile(TEST_FIXTURE.'images/original.png')->blur()->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testBlurSelective()
    {
        $imgA = file_get_contents(TEST_FIXTURE.'images/blur-selective.png');
        $imgB = $this->image->loadFile(TEST_FIXTURE.'images/original.png')->blur(true)->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testSketch()
    {
        $imgA = file_get_contents(TEST_FIXTURE.'images/sketch.png');
        $imgB = $this->image->loadFile(TEST_FIXTURE.'images/original.png')->sketch()->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testHflip()
    {
        $imgA = file_get_contents(TEST_FIXTURE.'images/hflip.png');
        $imgB = $this->image->loadFile(TEST_FIXTURE.'images/original.png')->hflip()->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testVflip()
    {
        $imgA = file_get_contents(TEST_FIXTURE.'images/vflip.png');
        $imgB = $this->image->loadFile(TEST_FIXTURE.'images/original.png')->vflip()->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testCrop()
    {
        $imgA = file_get_contents(TEST_FIXTURE.'images/crop-0-0-80-40.png');
        $imgB = $this->image->loadFile(TEST_FIXTURE.'images/original.png')->crop(0, 0, 80, 40)->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    /**
     * @dataProvider resizeProvider
     */
    public function testResize($file, $width = null, $height = null, $crop = true, $enlarge = true)
    {
        $imgA = file_get_contents(TEST_FIXTURE.'images/'.$file.'.png');
        $imgB = $this->image->loadFile(TEST_FIXTURE.'images/original.png')->resize($width, $height, $crop, $enlarge)->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testRotate()
    {
        $imgA = file_get_contents(TEST_FIXTURE.'images/rotate-30.png');
        $imgB = $this->image->loadFile(TEST_FIXTURE.'images/original.png')->rotate(30)->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testIdenticon()
    {
        $imgA = file_get_contents(TEST_FIXTURE.'images/identicon-foo.png');
        $imgB = $this->image->identicon('foo')->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testIdenticonBar()
    {
        $imgA = file_get_contents(TEST_FIXTURE.'images/identicon-bar.png');
        $imgB = $this->image->identicon('bar')->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    /**
     * @dataProvider overlayProvider
     */
    public function testOverlay($file, $align = null, $alpha = 100)
    {
        $imgA = file_get_contents(TEST_FIXTURE.'images/'.$file.'.png');
        $imgB = $this->image->identicon('bar')->overlay($this->image->identicon('foo'), $align, $alpha)->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testCaptcha()
    {
        $this->image->captcha($seed, 'Lato-Black.ttf', TEST_FIXTURE.'fonts/');

        $this->assertEquals(5, strlen($seed));
    }

    public function testCaptchaException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Invalid CAPTCHA length: 5.');

        $this->image->captcha($seed, 'Lato-Black.ttf', TEST_FIXTURE.'fonts/', 5, 24, 0, 0, 'openssl');
    }

    public function testCaptchaException2()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No TrueType support in GD module.');

        $this->image->captcha($seed, 'Lato-Black.ttf', TEST_FIXTURE.'fonts/', 5, 24, 0, 0, 'font');
    }

    public function testCaptchaException3()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('CAPTCHA font is not found.');

        $this->image->captcha($seed, 'Lato-Black-unknown.ttf', TEST_FIXTURE.'fonts/');
    }

    public function rgbProvider()
    {
        return array(
            array(array(0, 0, 0), '000000'),
            array(array(255, 0, 0), 'FF0000'),
            array(array(255, 0, 0), 'F00'),
            array('Invalid color specified: 0xff00000', 'FF00000', 'LogicException'),
        );
    }

    public function resizeProvider()
    {
        return array(
            array('original'),
            array('resize-50-40', 50, 40),
            array('resize-width', 50, null),
            array('resize-height', null, 40),
            array('resize-100-100', 100, 100, false),
            array('resize-10-5-nocrop-noenlarge', 10, 5, false, false),
        );
    }

    public function overlayProvider()
    {
        return array(
            array('overlay'),
            array('overlay-alpha50', null, 50),
            array('overlay-x10-y10', array(10, 10)),
            array('overlay-left-center-top-middle', 1 | 2 | 8 | 16),
        );
    }
}

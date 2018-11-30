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

use Fal\Stick\Fw;
use Fal\Stick\Util\Image;
use PHPUnit\Framework\TestCase;

class ImageTest extends TestCase
{
    private $fw;
    private $image;

    public function setUp()
    {
        $this->fw = new Fw();
        $this->image = new Image($this->fw);
    }

    public function testPixelCompare()
    {
        $img = file_get_contents(FIXTURE.'images/original.png');
        $imgFlip = file_get_contents(FIXTURE.'images/hflip.png');
        $imgResize = file_get_contents(FIXTURE.'images/resize-50-40.png');

        $this->assertEquals(0.0, Image::pixelCompare($img, $img));
        $this->assertEquals(100.0, Image::pixelCompare($img, $imgResize));
        $this->assertGreaterThan(0.0, Image::pixelCompare($img, $imgFlip));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Both image should be valid!
     */
    public function testPixelCompareException()
    {
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
        $this->assertNotNull($this->image->load(file_get_contents(FIXTURE.'images/original.png'))->getData());
    }

    public function testLoadFile()
    {
        $this->assertNotNull($this->image->loadFile(FIXTURE.'images/original.png')->getData());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessageRegExp /File ".+" is not found\./
     */
    public function testLoadFileException()
    {
        $this->image->loadFile(FIXTURE.'images/none.png');
    }

    public function testDestroy()
    {
        $this->image->loadFile(FIXTURE.'images/original.png');
        $this->image = null;

        $this->assertNull($this->image);
    }

    /**
     * @dataProvider getColors
     */
    public function testRgb($expected, $color)
    {
        $this->assertEquals($expected, $this->image->rgb($color));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Invalid color specified: 0xff00000
     */
    public function testRgbException()
    {
        $this->image->rgb('FF00000');
    }

    public function testBase64()
    {
        $this->image->loadFile(FIXTURE.'images/original.png');

        $this->assertStringStartsWith('data:image/', $this->image->base64());
    }

    public function testDump()
    {
        $this->image->loadFile(FIXTURE.'images/original.png');
        $imgA = $this->image->dump();
        $imgB = file_get_contents(FIXTURE.'images/original.png');

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage No image loaded!
     */
    public function testDumpException()
    {
        $this->image->dump();
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Image function "imagefoo" does not exists.
     */
    public function testDumpException2()
    {
        $this->image->loadFile(FIXTURE.'images/original.png');

        $this->image->dump('foo');
    }

    public function testRender()
    {
        $this->fw['QUIET'] = true;

        $this->image->loadFile(FIXTURE.'images/original.png');
        $this->image->render();

        $expected = array(
            'Content-Type' => 'image/png',
            'Content-Length' => 9853,
            'X-Powered-By' => $this->fw['PACKAGE'],
        );

        $this->assertEquals($expected, $this->fw['RESPONSE']);
        $this->assertNotNull($this->fw['OUTPUT']);
    }

    public function testSave()
    {
        $this->image->loadFile($original = FIXTURE.'images/original.png');
        $saved = $this->image->save($file = TEMP.'image-test.png');

        $this->assertTrue($saved);
        $this->assertFileEquals($file, $original);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage No file to save!
     */
    public function testSaveException()
    {
        $this->image->load(file_get_contents(FIXTURE.'images/original.png'));
        $this->image->save();
    }

    public function testWidth()
    {
        $this->image->loadFile(FIXTURE.'images/original.png');

        $this->assertEquals(80, $this->image->width());
    }

    public function testHeight()
    {
        $this->image->loadFile(FIXTURE.'images/original.png');

        $this->assertEquals(62, $this->image->height());
    }

    public function testInvert()
    {
        $imgA = file_get_contents(FIXTURE.'images/invert.png');
        $imgB = $this->image->loadFile(FIXTURE.'images/original.png')->invert()->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testBrightness()
    {
        $imgA = file_get_contents(FIXTURE.'images/brightness-100.png');
        $imgB = $this->image->loadFile(FIXTURE.'images/original.png')->brightness(100)->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testContrast()
    {
        $imgA = file_get_contents(FIXTURE.'images/contrast-100.png');
        $imgB = $this->image->loadFile(FIXTURE.'images/original.png')->contrast(100)->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testGrayscale()
    {
        $imgA = file_get_contents(FIXTURE.'images/grayscale.png');
        $imgB = $this->image->loadFile(FIXTURE.'images/original.png')->grayscale()->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testSmooth()
    {
        $imgA = file_get_contents(FIXTURE.'images/smooth-50.png');
        $imgB = $this->image->loadFile(FIXTURE.'images/original.png')->smooth(50)->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testEmboss()
    {
        $imgA = file_get_contents(FIXTURE.'images/emboss.png');
        $imgB = $this->image->loadFile(FIXTURE.'images/original.png')->emboss()->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testSepia()
    {
        $imgA = file_get_contents(FIXTURE.'images/sepia.png');
        $imgB = $this->image->loadFile(FIXTURE.'images/original.png')->sepia()->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testPixelate()
    {
        $imgA = file_get_contents(FIXTURE.'images/pixelate-10.png');
        $imgB = $this->image->loadFile(FIXTURE.'images/original.png')->pixelate(10)->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testBlur()
    {
        $imgA = file_get_contents(FIXTURE.'images/blur.png');
        $imgB = $this->image->loadFile(FIXTURE.'images/original.png')->blur()->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testBlurSelective()
    {
        $imgA = file_get_contents(FIXTURE.'images/blur-selective.png');
        $imgB = $this->image->loadFile(FIXTURE.'images/original.png')->blur(true)->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testSketch()
    {
        $imgA = file_get_contents(FIXTURE.'images/sketch.png');
        $imgB = $this->image->loadFile(FIXTURE.'images/original.png')->sketch()->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testHflip()
    {
        $imgA = file_get_contents(FIXTURE.'images/hflip.png');
        $imgB = $this->image->loadFile(FIXTURE.'images/original.png')->hflip()->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testVflip()
    {
        $imgA = file_get_contents(FIXTURE.'images/vflip.png');
        $imgB = $this->image->loadFile(FIXTURE.'images/original.png')->vflip()->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testCrop()
    {
        $imgA = file_get_contents(FIXTURE.'images/crop-0-0-80-40.png');
        $imgB = $this->image->loadFile(FIXTURE.'images/original.png')->crop(0, 0, 80, 40)->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    /**
     * @dataProvider getSizes
     */
    public function testResize($file, $width = null, $height = null, $crop = true, $enlarge = true)
    {
        $imgA = file_get_contents(FIXTURE.'images/'.$file.'.png');
        $imgB = $this->image->loadFile(FIXTURE.'images/original.png')->resize($width, $height, $crop, $enlarge)->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testRotate()
    {
        $imgA = file_get_contents(FIXTURE.'images/rotate-30.png');
        $imgB = $this->image->loadFile(FIXTURE.'images/original.png')->rotate(30)->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testIdenticon()
    {
        $imgA = file_get_contents(FIXTURE.'images/identicon-foo.png');
        $imgB = $this->image->identicon('foo')->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testIdenticonBar()
    {
        $imgA = file_get_contents(FIXTURE.'images/identicon-bar.png');
        $imgB = $this->image->identicon('bar')->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    /**
     * @dataProvider getOverlays
     */
    public function testOverlay($file, $align = null, $alpha = 100)
    {
        $imgA = file_get_contents(FIXTURE.'images/'.$file.'.png');
        $imgB = $this->image->identicon('bar')->overlay($this->image->identicon('foo'), $align, $alpha)->dump();

        $this->assertEquals(0.0, Image::pixelCompare($imgA, $imgB));
    }

    public function testCaptcha()
    {
        $this->image->captcha($seed, 'Lato-Black.ttf', FIXTURE.'fonts/');

        $this->assertEquals(5, strlen($seed));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Invalid CAPTCHA length: 5.
     */
    public function testCaptchaException()
    {
        $this->image->captcha($seed, 'Lato-Black.ttf', FIXTURE.'fonts/', 5, 24, 0, 0, 'openssl');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage No TrueType support in GD module.
     */
    public function testCaptchaException2()
    {
        $this->image->captcha($seed, 'Lato-Black.ttf', FIXTURE.'fonts/', 5, 24, 0, 0, 'font');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage CAPTCHA font is not found.
     */
    public function testCaptchaException3()
    {
        $this->image->captcha($seed, 'Lato-Black-unknown.ttf', FIXTURE.'fonts/');
    }

    public function getColors()
    {
        return array(
            array(array(0, 0, 0), '000000'),
            array(array(255, 0, 0), 'FF0000'),
            array(array(255, 0, 0), 'F00'),
        );
    }

    public function getSizes()
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

    public function getOverlays()
    {
        return array(
            array('overlay'),
            array('overlay-alpha50', null, 50),
            array('overlay-x10-y10', array(10, 10)),
            array('overlay-left-center-top-middle', 1 | 2 | 8 | 16),
        );
    }
}

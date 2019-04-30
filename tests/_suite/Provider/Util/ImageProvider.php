<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\TestSuite\Provider\Util;

use Fal\Stick\TestSuite\MyTestCase;

class ImageProvider
{
    public function rgb()
    {
        return array(
            array(array(0, 0, 0), '000000'),
            array(array(255, 0, 0), 'FF0000'),
            array(array(255, 0, 0), 'F00'),
            array('Invalid color specified: 0xff00000', 'FF00000', 'LogicException'),
        );
    }

    public function resize()
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

    public function overlay()
    {
        return array(
            array('overlay'),
            array('overlay-alpha50', null, 50),
            array('overlay-x10-y10', array(10, 10)),
            array('overlay-left-center-top-middle', 1 | 2 | 8 | 16),
        );
    }

    public function identicon()
    {
        return array(
            array(MyTestCase::read('/images/identicon-foo.png'), 'foo'),
            array(MyTestCase::read('/images/identicon-bar.png'), 'bar'),
        );
    }

    public function construct()
    {
        $original = MyTestCase::fixture('/images/original.png');

        return array(
            array(null, file_get_contents($original)),
            array($original, null, $original, 'png'),
            array('No image resource provided!', null, null, null, 'LogicException'),
            array('Image format not supported: foo.', null, $original, 'foo', 'LogicException'),
        );
    }
}

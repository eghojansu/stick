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

namespace Fal\Stick\Test\Html;

use Fal\Stick\Html\Element;
use Fal\Stick\TestSuite\MyTestCase;

class ElementTest extends MyTestCase
{
    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Html\ElementProvider::attr
     */
    public function testAttr($expected, $attr)
    {
        $this->assertEquals($expected, Element::attr($attr));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Html\ElementProvider::tag
     */
    public function testElement($expected, $tag, $attr = null, $pair = false, $content = null)
    {
        $this->assertEquals($expected, Element::tag($tag, $attr, $pair, $content));
    }
}

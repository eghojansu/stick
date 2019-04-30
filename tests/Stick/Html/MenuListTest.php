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

use Fal\Stick\Fw;
use Fal\Stick\Html\MenuList;
use Fal\Stick\TestSuite\MyTestCase;

class MenuListTest extends MyTestCase
{
    private $list;
    private $fw;

    public function setup(): void
    {
        $this->fw = new Fw();
        $this->list = new MenuList($this->fw);
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Html\MenuListProvider::build
     */
    public function testBuild($expected, $items, $activeRoute = null, $config = null, $hive = null)
    {
        if ($hive) {
            $this->fw->mset($hive);
        }

        $this->fw->set('ALIASES', array(
            'foo' => '/foo',
            'bar' => '/bar/@bar',
            'baz' => '/baz/@baz*',
            'qux' => '/qux/@qux/@quux*',
        ));

        $this->assertEquals($expected, $this->list->build($items, $activeRoute, $config));
    }
}

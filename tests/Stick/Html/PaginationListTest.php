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
use Fal\Stick\Html\PaginationList;
use Fal\Stick\TestSuite\MyTestCase;

class PaginationListTest extends MyTestCase
{
    private $fw;
    private $list;

    public function setup(): void
    {
        $this->fw = new Fw();
        $this->list = new PaginationList($this->fw);
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Html\PaginationListProvider::build
     */
    public function testBuild($expected, $page, $max, $hive = null)
    {
        if ($hive) {
            $this->fw->mset($hive);
        }

        $this->fw->set('ALIASES', array(
            'foo' => '/',
            'bar' => '/bar/@bar',
            'baz' => '/baz/@baz*',
            'qux' => '/qux/@qux/@quux*',
        ));

        $content = $expected ? '<nav aria-label="Page navigation"><ul class="pagination">'.$expected.'</ul></nav>' : $expected;

        $this->assertEquals($content, $this->list->build($page, $max));
    }
}

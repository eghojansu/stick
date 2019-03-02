<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 13, 2019 01:34
 */

namespace Fal\Stick\Test\Web\Helper;

use Fal\Stick\Web\Helper\PaginationList;
use Fal\Stick\Web\Request;
use Fal\Stick\Web\RequestStack;
use Fal\Stick\Web\Router\Router;
use Fal\Stick\Web\UrlGenerator;
use PHPUnit\Framework\TestCase;

class PaginationListTest extends TestCase
{
    private $list;
    private $requestStack;
    private $router;

    public function setUp()
    {
        $this->router = new Router();
        $this->requestStack = new RequestStack();
        $this->list = new PaginationList(new UrlGenerator($this->requestStack, $this->router), $this->requestStack, $this->router);

        $this->router
            ->route('GET foo /', 'foo')
            ->route('GET bar /bar/@bar', 'bar')
            ->route('GET baz /baz/@baz*', 'baz')
            ->route('GET qux /qux/@qux/@quux*', 'qux')
        ;
    }

    /**
     * @dataProvider buildProvider
     */
    public function testBuild($path, $page, $max, $content, $query = null)
    {
        $this->requestStack->push($request = Request::create($path, 'GET', $query));
        $this->router->handle($request);

        $expected = $content ? '<nav aria-label="Page navigation"><ul class="pagination">'.$content.'</ul></nav>' : $content;

        $this->assertEquals($expected, $this->list->build($page, $max));
    }

    public function buildProvider()
    {
        return array(
            array('/', 1, 0, ''),
            array('/', 0, 1, ''),
            array('/', 1, 1, '<li class="active"><a href="/?page=1">1</a></li>'),
            array('/', 2, 1, '<li><a href="/?page=1">1</a></li>'),
            array('/', 1, 2,
                '<li class="active"><a href="/?page=1">1</a></li>'.
                '<li><a href="/?page=2">2</a></li>',
            ),
            array('/', 2, 3,
                '<li><a href="/?page=1">1</a></li>'.
                '<li class="active"><a href="/?page=2">2</a></li>'.
                '<li><a href="/?page=3">3</a></li>',
            ),
            array('/', 1, 1,
                '<li class="active"><a href="/?page=1&bar=baz">1</a></li>',
                array('bar' => 'baz'),
            ),
            array('/', 5, 9,
                '<li><a href="/?page=4">Prev</a></li>'.
                '<li><a href="/?page=1">1</a></li>'.
                '<li class="gap"><span>&hellip;</span></li>'.
                '<li><a href="/?page=3">3</a></li>'.
                '<li><a href="/?page=4">4</a></li>'.
                '<li class="active"><a href="/?page=5">5</a></li>'.
                '<li><a href="/?page=6">6</a></li>'.
                '<li><a href="/?page=7">7</a></li>'.
                '<li class="gap"><span>&hellip;</span></li>'.
                '<li><a href="/?page=9">9</a></li>'.
                '<li><a href="/?page=6">Next</a></li>',
            ),
            array('/bar/baz', 1, 1,
                '<li class="active"><a href="/bar/baz?page=1">1</a></li>',
            ),
            array('/baz/bar/baz', 1, 1,
                '<li class="active"><a href="/baz/bar/baz?page=1">1</a></li>',
            ),
            array('/qux/qux/bar/baz', 1, 1,
                '<li class="active"><a href="/qux/qux/bar/baz?page=1">1</a></li>',
            ),
        );
    }
}

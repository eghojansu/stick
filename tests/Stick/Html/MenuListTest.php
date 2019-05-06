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
use Fal\Stick\Security\Auth;
use Fal\Stick\Security\InMemoryUserProvider;
use Fal\Stick\Security\PlainPasswordEncoder;
use Fal\Stick\TestSuite\Classes\SimpleUser;
use Fal\Stick\TestSuite\MyTestCase;

class MenuListTest extends MyTestCase
{
    private $list;
    private $fw;
    private $auth;

    public function setup(): void
    {
        $this->fw = new Fw();
        $this->auth = new Auth($this->fw, new InMemoryUserProvider(), new PlainPasswordEncoder());
        $this->auth->setUser(new SimpleUser('1', 'foo', 'bar', array('foo', 'bar')));
        $this->list = new MenuList($this->fw, $this->auth);
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

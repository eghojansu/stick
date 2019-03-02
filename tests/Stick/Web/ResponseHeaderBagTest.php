<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 27, 2019 10:23
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web;

use Fal\Stick\Web\Cookie;
use PHPUnit\Framework\TestCase;
use Fal\Stick\Web\ResponseHeaderBag;

class ResponseHeaderBagTest extends TestCase
{
    private $bag;

    public function setup()
    {
        $this->bag = new ResponseHeaderBag();
    }

    public function testAddCookie()
    {
        $this->assertCount(1, $this->bag->addCookie(new Cookie('foo'))->getFlatCookies());
    }

    public function testRemoveCookie()
    {
        $this->assertCount(0, $this->bag->removeCookie('foo')->getFlatCookies());
    }

    public function testClearCookie()
    {
        $this->assertCount(1, $this->bag->clearCookie('foo')->getFlatCookies());
    }

    public function testGetCookies()
    {
        $this->assertCount(0, $this->bag->getCookies());
    }

    public function testGetFlatCookies()
    {
        $this->assertCount(0, $this->bag->getFlatCookies());
    }

    public function testClearCookies()
    {
        $this->assertCount(0, $this->bag->addCookie(new Cookie('foo'))->clearCookies()->getFlatCookies());
    }
}

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

namespace Fal\Stick\Test\Library;

use Fal\Stick\App;
use Fal\Stick\Library\Web;
use PHPUnit\Framework\TestCase;

class WebTest extends TestCase
{
    private $web;

    public function setUp()
    {
        $this->web = new Web(new App());
    }

    public function testDiacritics()
    {
        $this->assertNotEmpty($this->web->diacritics());
    }

    public function testSlug()
    {
        $this->assertEquals('foo-bar-baz', $this->web->slug('Foo BAR BAZ'));
    }

    public function testMime()
    {
        $this->assertEquals('application/json', $this->web->mime('foo.json'));
        $this->assertEquals('application/octet-stream', $this->web->mime('foo'));
    }
}

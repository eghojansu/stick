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

namespace Fal\Stick\Test\Web;

use Fal\Stick\Web\Mime;
use Fal\Stick\TestSuite\MyTestCase;

class MimeTest extends MyTestCase
{
    public function testSlug()
    {
        $this->assertEquals('foo-bar-baz', Mime::slug('Foo BAR BAZ'));
    }

    public function testType()
    {
        $this->assertEquals('application/json', Mime::type('foo.json'));
        $this->assertEquals('application/octet-stream', Mime::type('foo'));
    }
}

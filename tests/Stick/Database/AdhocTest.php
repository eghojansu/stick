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

namespace Fal\Stick\Test\Database;

use Fal\Stick\Database\Adhoc;
use PHPUnit\Framework\TestCase;

class AdhocTest extends TestCase
{
    private $adhoc;

    public function setup()
    {
        $this->adhoc = new Adhoc('foo', function () {
            return 'bar';
        });
    }

    public function testGetValue()
    {
        $this->assertEquals('bar', $this->adhoc->getValue());
        $this->assertTrue($this->adhoc->isChanged());
    }

    public function testCommit()
    {
        $this->assertEquals('bar', $this->adhoc->commit()->getValue());
    }

    public function testReset()
    {
        $this->assertEquals('bar', $this->adhoc->reset()->getValue());
    }
}

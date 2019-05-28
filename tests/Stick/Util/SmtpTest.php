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

namespace Fal\Stick\Test\Util;

use Fal\Stick\TestSuite\MyTestCase;

class SmtpTest extends MyTestCase
{
    public function testHas()
    {
        $this->assertFalse($this->smtp->has('foo'));
    }

    public function testGet()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Header not exists: foo.');

        $this->smtp->get('foo');
    }

    public function testSet()
    {
        $this->assertEquals('bar', $this->smtp->set('foo', 'bar')->get('Foo'));
    }

    public function testRem()
    {
        $this->smtp->set('foo', 'bar')->rem('foo');

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Header not exists: foo.');

        $this->smtp->get('foo');
    }

    public function testLog()
    {
        $this->assertEquals('', $this->smtp->log());
    }

    public function testAttach()
    {
        $this->assertSame($this->smtp, $this->smtp->attach($this->fixture('/files/foo.txt')));

        $file = $this->fixture('/files/none.txt');
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Attachment file not found: '.$file.'.');
        $this->smtp->attach($file);
    }

    public function testSend()
    {
        // assume, all correct
        $this->assertTrue(true);
    }
}

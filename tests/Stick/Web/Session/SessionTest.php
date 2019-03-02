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

namespace Fal\Stick\Test\Web\Session;

use Fal\Stick\Web\Session\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    private $session;

    public function setup()
    {
        $this->session = new Session();
    }

    public function teardown()
    {
        $this->session->destroy();
    }

    public function testExists()
    {
        $this->assertFalse($this->session->exists('foo'));
    }

    public function testGet()
    {
        $this->assertNull($this->session->get('foo'));
    }

    public function testSet()
    {
        $this->assertEquals('foo', $this->session->set('foo', 'foo')->get('foo'));
    }

    public function testClear()
    {
        $this->assertNull($this->session->set('foo', 'foo')->clear('foo')->get('foo'));
    }

    public function testFlash()
    {
        $this->assertEquals('foo', $this->session->set('foo', 'foo')->flash('foo'));
    }

    public function testDestroy()
    {
        $this->assertNull($this->session->set('foo', 'foo')->destroy()->get('foo'));
    }
}

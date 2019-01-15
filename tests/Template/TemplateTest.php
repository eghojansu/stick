<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Template;

use Fal\Stick\Core;
use Fal\Stick\Template\Template;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    private $template;
    private $core;

    public function setup()
    {
        $this->template = new Template($this->core = new Core());
    }

    public function testGetDirectories()
    {
        $this->assertEquals([], $this->template->getDirectories());
    }

    public function testAddDirectory()
    {
        $this->template->addDirectory('foo');
        $this->template->addDirectory('bar', true);

        $this->assertEquals(['bar', 'foo'], $this->template->getDirectories());
    }

    public function testSetDirectories()
    {
        $this->assertEquals(['foo'], $this->template->setDirectories(['foo'])->getDirectories());
    }

    public function testGetExtension()
    {
        $this->assertEquals('.php', $this->template->getExtension());
    }

    public function testSetExtension()
    {
        $this->assertEquals('foo', $this->template->setExtension('foo')->getExtension());
    }

    public function testAddFunction()
    {
        $this->template->addFunction('foo', function () {
            return 'foo';
        });
        $this->template->addFunction('bar', function ($core, $bar) {
            return [$core, $bar];
        });

        $this->assertEquals('foo', $this->template->foo());
        $this->assertEquals([$this->core, 'foo'], $this->template->bar('foo'));
        $this->assertEquals('/path', $this->template->path('path'));
    }

    public function testCallException()
    {
        $this->expectException('BadFunctionCallException');
        $this->expectExceptionMessage('Call to undefined function: foo');

        $this->template->foo();
    }

    public function testFindView()
    {
        $this->template->addDirectory(__DIR__.'/view/');

        $this->assertEquals(__DIR__.'/view/simple.php', $this->template->findView('simple'));
    }

    public function testFindViewException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('View not exists: "simple"');

        $this->template->findView('simple');
    }

    public function testRender()
    {
        $this->template->addDirectory(__DIR__.'/view/');

        $this->assertEquals('simple', $this->template->render('simple'));
        $this->assertEquals('text/html', $this->core->get('RESPONSE.Content-Type'));
        $this->assertEquals(6, $this->core->get('RESPONSE.Content-Length'));
    }
}

<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 13, 2019 11:48
 */

namespace Fal\Stick\Test\Template;

use Fal\Stick\Container\Container;
use PHPUnit\Framework\TestCase;
use Fal\Stick\Template\Template;

class TemplateTest extends TestCase
{
    private $template;
    private $container;

    public function setup()
    {
        $this->template = new Template($this->container = new Container());
    }

    public function testGetDirectories()
    {
        $this->assertEquals(array(), $this->template->getDirectories());
    }

    public function testAddDirectory()
    {
        $this->template->addDirectory('foo');
        $this->template->addDirectory('bar', true);

        $this->assertEquals(array('bar', 'foo'), $this->template->getDirectories());
    }

    public function testSetDirectories()
    {
        $this->assertEquals(array('foo'), $this->template->setDirectories(array('foo'))->getDirectories());
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
        $this->template->addFunction('bar', function ($bar) {
            return array($bar);
        });

        $this->assertEquals('foo', $this->template->foo());
        $this->assertEquals(array('foo'), $this->template->bar('foo'));
    }

    public function testCallException()
    {
        $this->expectException('BadFunctionCallException');
        $this->expectExceptionMessage('Call to undefined function: foo');

        $this->template->foo();
    }

    public function testFindView()
    {
        $this->template->addDirectory(TEST_FIXTURE.'views/');

        $this->assertEquals(TEST_FIXTURE.'views/simple.php', $this->template->findView('simple'));
    }

    public function testFindViewException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('View not exists: "simple"');

        $this->template->findView('simple');
    }

    public function testRender()
    {
        $this->template->addDirectory(TEST_FIXTURE.'views/');

        $this->assertEquals('simple', $this->template->render('simple'));
    }
}

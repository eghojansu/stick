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

namespace Ekok\Stick\Tests;

use Ekok\Stick\Fw;
use Ekok\Stick\View;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Ekok\Stick\View
 */
final class ViewTest extends TestCase
{
    private $view;

    protected function setUp(): void
    {
        $this->view = new View(new Fw(), TEST_FIXTURE.'/views/');
    }

    public function testMagicGet()
    {
        $this->assertEquals('/', $this->view->PATH);
    }

    public function testMagicCall()
    {
        $this->assertEquals('/', $this->view->path('/'));
    }

    public function testMagicInvoke()
    {
        $view = $this->view;

        $this->assertEquals('/', $view('path', '/'));
        $this->assertEquals('html', $view('getExtension'));
    }

    public function testOffsetExists()
    {
        $this->assertTrue(isset($this->view['PATH']));
    }

    public function testOffsetGet()
    {
        $this->assertEquals('/', $this->view['PATH']);
    }

    public function testOffsetSet()
    {
        $this->view['PATH'] = '/foo';

        $this->assertEquals('/foo', $this->view['PATH']);
    }

    public function testOffsetUnset()
    {
        $this->view['foo'] = 'bar';
        unset($this->view['foo']);

        $this->assertNull($this->view['foo']);
    }

    public function testEsc()
    {
        $this->assertEquals('foo &amp; bar', $this->view->esc('foo & bar'));
    }

    public function testRaw()
    {
        $this->assertEquals('foo & bar', $this->view->raw('foo &amp; bar'));
    }

    public function testGetDirectories()
    {
        $this->assertEquals(array(TEST_FIXTURE.'/views/'), $this->view->getDirectories());
    }

    public function testAddDirectory()
    {
        $expected = array(TEST_FIXTURE.'/views/', 'foo/bar/');

        $this->assertEquals($expected, $this->view->addDirectory('foo\\bar\\')->getDirectories());
    }

    public function testSetDirectories()
    {
        $this->assertEquals(array('foo/bar/'), $this->view->setDirectories('foo\\bar\\')->getDirectories());
    }

    public function testGetExtension()
    {
        $this->assertEquals('html', $this->view->getExtension());
    }

    public function testSetExtension()
    {
        $this->assertEquals('foo', $this->view->setExtension('foo')->getExtension());
    }

    public function testGetLayout()
    {
        $this->assertEquals(null, $this->view->getLayout());
    }

    public function testLayout()
    {
        $this->assertEquals('foo', $this->view->layout('foo')->getLayout());
    }

    public function testSection()
    {
        $this->assertEquals(null, $this->view->section('foo'));
    }

    public function testStart()
    {
        $this->view->start('foo');
        echo 'foo';
        $this->view->stop();

        $this->assertEquals('foo', $this->view->section('foo'));
    }

    public function testStop()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No opening section.');

        $this->view->stop();
    }

    public function testFindView()
    {
        $expected = TEST_FIXTURE.'/views/layout/main.html';

        $this->assertEquals($expected, $this->view->findView('layout/main.html'));
        $this->assertEquals($expected, $this->view->findView('layout/main'));
        $this->assertEquals($expected, $this->view->findView('layout.main'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage("View not found: 'foo'.");

        $this->view->findView('foo');
    }

    public function testLoad()
    {
        $this->assertEquals('Main content.', $this->view->load('layout.main'));
    }

    public function testLoadViewException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('View throws an exception.');

        $this->view->load('exception');
    }

    public function testRender()
    {
        $expected = 'A wrapper that contain child: Child content.';
        $this->view->layout('wrapper');

        $this->assertEquals($expected, $this->view->render('child'));

        $this->view->layout(null);
        $this->assertEquals('Main content.', $this->view->render('layout.main'));
    }
}

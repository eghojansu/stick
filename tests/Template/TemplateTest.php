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

namespace Fal\Stick\Test\Template;

use Fal\Stick\Fw;
use Fal\Stick\Template\Template;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    private $template;

    public function setUp()
    {
        $this->template = new Template(new Fw('phpunit-test'), TEST_FIXTURE.'template/', array(
            'profile' => true,
        ));
    }

    public function testGetPaths()
    {
        $this->assertEquals(array(TEST_FIXTURE.'template/'), $this->template->getPaths());
    }

    public function testAddPath()
    {
        $this->assertEquals(array(TEST_FIXTURE.'template/', 'foo'), $this->template->addPath('foo')->getPaths());
    }

    public function testPrependPath()
    {
        $this->assertEquals(array('foo', TEST_FIXTURE.'template/'), $this->template->prependPath('foo')->getPaths());
    }

    public function testFind()
    {
        $this->assertEquals(TEST_FIXTURE.'template/layout.php', $this->template->find('layout'));
    }

    public function testFindException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Template "foo" does not exists.');

        $this->template->find('foo');
    }

    public function testGetGlobals()
    {
        $this->assertEquals(array('profile' => true), $this->template->getGlobals());
    }

    public function testSetGlobals()
    {
        $this->assertEquals(array('foo'), $this->template->setGlobals(array('foo'))->getGlobals());
        $this->assertEquals(array('foo'), $this->template->setGlobals(function () {
            return array('foo');
        })->getGlobals());
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
            return 'bar';
        });

        $this->assertEquals('bar', $this->template->foo());
        $this->assertEquals('/foo', $this->template->path('foo'));
    }

    public function testCallException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Call to undefined function "foo".');

        $this->template->foo();
    }

    /**
     * @dataProvider renderProvider
     */
    public function testRender($name, $context = null)
    {
        $actual = $this->template->render($name, $context);
        $expected = file_get_contents(TEST_FIXTURE.'template/'.$name.'.html');

        $this->assertEquals($expected, $actual);
    }

    public function testRenderException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Stop without starting point is not possible.');

        $this->template->render('nostart');
    }

    public function renderProvider()
    {
        return array(
            array('layout'),
            array('layout2'),
            array('dashboard'),
            array('include', array('title' => 'Included')),
            array('profile', array('username' => '<span>user</span>')),
            array('service'),
        );
    }
}

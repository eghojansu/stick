<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Template;

use Fal\Stick\App;
use Fal\Stick\Template\Template;
use Fal\Stick\Template\TemplateFile;
use PHPUnit\Framework\TestCase;

class TemplateFileTest extends TestCase
{
    private $app;
    private $engine;
    private $file;

    public function setUp()
    {
        $this->app = new App();
        $this->engine = new Template($this->app, FIXTURE.'template/');
        $this->prepareFile('valid.php');
    }

    private function prepareFile($file)
    {
        $this->file = new TemplateFile($this->engine, $this->app, $file);
    }

    private function removeAllWhiteSpace($str)
    {
        return preg_replace('/\s+/', '', $str);
    }

    public function testRender()
    {
        $expected = file_get_contents(FIXTURE.'/template/valid.html');
        $actual = $this->file->render();

        $this->assertEquals($this->removeAllWhiteSpace($expected), $this->removeAllWhiteSpace($actual));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Template file not exists: "unknown.php".
     */
    public function testFindFile()
    {
        $this->prepareFile('unknown.php');

        $this->file->render();
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage A template could not have more than one parent.
     */
    public function testExtendTwice()
    {
        $this->prepareFile('extend_twice.php');

        $this->file->render();
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage A template could not have self as parent.
     */
    public function testExtendSelf()
    {
        $this->prepareFile('extend_self.php');

        $this->file->render();
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Please open block first!
     */
    public function testNoOpenBlock()
    {
        $this->prepareFile('no_open_block.php');

        $this->file->render();
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Nested block is not supported.
     */
    public function testNestedBlock()
    {
        $this->prepareFile('nested_block.php');

        $this->file->render();
    }

    public function testIncludeIncluded()
    {
        $this->prepareFile('include_included.php');

        $expected = 'it contains included content.';
        $this->assertEquals($expected, $this->file->render());
    }

    public function testCall()
    {
        $this->assertEquals('foo', $this->file->lower('FOO'));
    }

    public function testService()
    {
        $this->prepareFile('call_service.php');

        $this->assertEquals('DateTime !== DateTime', $this->file->render());
    }
}

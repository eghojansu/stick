<?php declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Unit;

use Fal\Stick as f;
use Fal\Stick\Template;
use Fal\Stick\TemplateEngine;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    protected $template;

    public function setUp()
    {
        $engine = new TemplateEngine(FIXTURE . 'template/');
        $this->template = new Template($engine, FIXTURE . 'template/main.php', ['var'=>'<span>foo</span>']);
    }

    public function testParent()
    {
        $this->assertEquals('', $this->template->parent());

        $this->template->render();
        $expected = '<script src="script.js"></script>';
        $this->assertEquals($expected, trim($this->template->parent()));
    }

    public function testGetBlock()
    {
        $this->template->render();
        $this->assertEquals('Main page - Template Layout', $this->template->getBlock('title'));
    }

    public function testGetBlocks()
    {
        $this->template->render();
        $this->assertEquals(3, count($this->template->getBlocks()));
    }

    public function testGetRendered()
    {
        $this->template->render();
        $expected = file_get_contents(FIXTURE . 'template/main.html') . "\n";
        $this->assertEquals($expected, $this->template->getRendered());
    }
}

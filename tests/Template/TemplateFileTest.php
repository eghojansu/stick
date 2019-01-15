<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 13, 2019 17:39
 */

namespace Fal\Stick\Test\Template;

use Fal\Stick\Core;
use PHPUnit\Framework\TestCase;
use Fal\Stick\Template\Template;
use Fal\Stick\Template\TemplateFile;

class TemplateFileTest extends TestCase
{
    public function testCreate()
    {
        $core = new Core();
        $engine = new Template($core, array(__DIR__.'/view/'));

        $this->assertInstanceOf('Fal\\Stick\\Template\\TemplateFile', TemplateFile::create($engine, $core, 'simple'));
    }

    /**
     * @dataProvider renderProvider
     */
    public function testRender($expected, $view, $context = null)
    {
        $core = new Core();
        $engine = new Template($core, array(__DIR__.'/view/'));
        $template = new TemplateFile($engine, $core, $view, $context);

        $std = new \stdClass();
        $std->foo = 'bar';

        $core->rule('std', $std);

        $this->assertEquals($expected, $template->render());
    }

    public function renderProvider()
    {
        $dir = __DIR__.'/view/';

        return array(
            array('simple', 'simple'),
            array(file_get_contents($dir.'layout.html'), 'layout'),
            array(file_get_contents($dir.'view.html'), 'view'),
            array(file_get_contents($dir.'load.html'), 'load', array('parent_context' => 'pcontext')),
            array(file_get_contents($dir.'get_service.html'), 'get_service'),
            array(null, 'noblock'),
            array(null, 'parent_noblock'),
            array("Nested 3\n", 'nested3'),
            array("Nested 3 - Parent is Nested 2\n", 'nested3_parent'),
        );
    }
}

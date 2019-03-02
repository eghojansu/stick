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

use Fal\Stick\Container\Container;
use Fal\Stick\Container\Definition;
use Fal\Stick\Template\Template;
use Fal\Stick\Template\TemplateFile;
use PHPUnit\Framework\TestCase;

class TemplateFileTest extends TestCase
{
    /**
     * @dataProvider renderProvider
     */
    public function testRender($expected, $view, $context = null)
    {
        $container = new Container();
        $engine = new Template($container, array(TEST_FIXTURE.'views/'));
        $template = new TemplateFile($engine, $container, $view, $context);

        $std = new \stdClass();
        $std->foo = 'bar';

        $container->set('std', new Definition('std', $std));
        $engine->addFunction('callFoo', function ($foo) {
            return $foo.'barbaz';
        });

        $this->assertEquals($expected, $template->render());
    }

    public function renderProvider()
    {
        $dir = TEST_FIXTURE.'views/';

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

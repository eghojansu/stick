<?php

/**
 * This template is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * template that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Template;

use Fal\Stick\Fw;
use Fal\Stick\Template\Environment;
use Fal\Stick\TestSuite\MyTestCase;

class TemplateTest extends MyTestCase
{
    private $fw;
    private $env;
    private $template;

    public function setup(): void
    {
        $this->fw = new Fw(array(
            'TEMP' => $this->tmp('/'),
        ));
        $this->env = new Environment($this->fw, $this->fixture('/template/'), null, null, true);
        $this->template = $this->env->loadTemplate('foo.shtml');
    }

    public function teardown(): void
    {
        $this->fw->creset();
    }

    public function testGetTemplateName()
    {
        $this->assertEquals('foo.shtml', $this->template->getTemplateName());
    }

    public function testGetSourcePath()
    {
        $this->assertEquals($this->fixture('/template/foo.shtml'), $this->template->getSourcePath());
    }

    public function testGetCompiledPath()
    {
        $this->assertFileExists($this->template->getCompiledPath());
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Template\TemplateProvider::render
     */
    public function testRender($expected, $compiled, $view, $context = null, $hive = null)
    {
        if ($hive) {
            $this->fw->mset($hive);
        }

        $template = $this->env->loadTemplate($view);

        $this->assertEquals($expected, $template->render($context));
        $this->assertFileEquals($compiled, $template->getCompiledPath());
    }

    public function testRenderException()
    {
        $this->expectException('RuntimeException');
        $this->expectExceptionMessage('An exception has been thrown during the rendering of a template: throw_error.shtml ("foo").');

        $this->env->loadTemplate('throw_error.shtml')->render();
    }
}

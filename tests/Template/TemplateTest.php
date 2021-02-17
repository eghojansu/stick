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

namespace Ekok\Stick\Tests\Template;

use Ekok\Stick\Template\Context;
use PHPUnit\Framework\TestCase;
use Ekok\Stick\Template\Template;

class TemplateTest extends TestCase
{
    /** @var Template */
    private $template;

    public function setUp(): void
    {
        $this->template = new Template(array(
            TEST_FIXTURE . '/templates',
            'simple' => TEST_FIXTURE . '/templates/simple',
        ));
    }

    public function testCreateTemplate()
    {
        $this->assertInstanceOf(Context::class, $this->template->createTemplate('simple'));
    }

    public function testRender()
    {
        $content = $this->template->render('profile', array(
            'name' => 'Jonathan',
        ));

        $this->assertStringContainsString('Hello Jonathan', $content);
        $this->assertStringContainsString('Example Link', $content);
    }

    public function getOptions()
    {
        $this->assertCount(4, $this->template->getOptions());
    }

    public function testOptions()
    {
        $this->template->setOptions(array('extension' => 'html'));

        $this->assertEquals('html', $this->template->getOptions()['extension']);
    }

    public function testGetDirectories()
    {
        $directories = $this->template->getDirectories();

        $this->assertArrayHasKey('default', $directories);
        $this->assertArrayHasKey('simple', $directories);
    }

    public function testAddDirectory()
    {
        $this->template->addDirectory('foo');
        $options = $this->template->getDirectories();

        $this->assertCount(2, $options);
        $this->assertCount(2, $options['default']);
    }

    public function testAddDirectories()
    {
        $this->template->addDirectories(array('foo', 'bar' => 'baz'));
        $options = $this->template->getDirectories();

        $this->assertCount(3, $options);
        $this->assertCount(2, $options['default']);
        $this->assertCount(1, $options['bar']);
    }

    public function testGetGlobals()
    {
        $this->assertCount(0, $this->template->getGlobals());
    }

    public function testAddGlobal()
    {
        $this->template->addGlobal('foo', 'bar');

        $this->assertCount(1, $this->template->getGlobals());
    }

    public function testAddGlobalException()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Variable name is reserved for *this*: _.');

        $this->template->addGlobal('_', 'bar');
    }

    public function testAddGlobals()
    {
        $this->template->addGlobals(array('foo' => 'bar'));

        $this->assertCount(1, $this->template->getGlobals());
    }

    public function testExtendingFunction()
    {
        $this->template->addFunction('foo', static function () {
            return 'foo';
        });

        $this->assertEquals('foo', $this->template->foo());
        $this->assertEquals('trimmed', $this->template->trim(' trimmed '));
        $this->assertEquals('&lt;b&gt;strong&lt;/b&gt;', $this->template->escape('<b>strong</b>'));
        $this->assertEquals('&lt;b&gt;strong&lt;/b&gt;', $this->template->esc('<b>strong</b>'));
        $this->assertEquals('&lt;b&gt;strong&lt;/b&gt;', $this->template->e('<b>strong</b>'));
    }

    public function testExtendingFunctionException()
    {
        $this->expectException('BadFunctionCallException');
        $this->expectExceptionMessage('Function is not found in any context: foo.');

        $this->template->foo();
    }

    public function testFindPath()
    {
        $this->assertEquals(TEST_FIXTURE . '/templates/simple.php', $this->template->findPath('simple'));
        $this->assertEquals(TEST_FIXTURE . '/templates/simple/profile.php', $this->template->findPath('simple/profile.php'));
        $this->assertEquals(TEST_FIXTURE . '/templates/simple/profile.php', $this->template->findPath('simple.profile'));
        $this->assertEquals(TEST_FIXTURE . '/templates/simple/profile.php', $this->template->findPath('simple/profile'));

        $this->template->setOptions(array('extension' => 'prefix.php'));
        $this->assertEquals(TEST_FIXTURE . '/templates/consumer.prefix.php', $this->template->findPath('consumer'));
    }

    public function testFindPathException()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage("Template not found: 'unknown'.");

        $this->template->findPath('unknown');
    }

    public function testGetTemplateDirectories()
    {
        $this->assertEquals(array(array(TEST_FIXTURE . '/templates/simple/'), 'profile'), $this->template->getTemplateDirectories('simple:profile'));
        $this->assertEquals(array(array(TEST_FIXTURE . '/templates/'), 'profile'), $this->template->getTemplateDirectories('profile'));
    }

    public function testGetTemplateDirectoriesExtension()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage("Directory not exists for template: 'unknown:profile'.");

        $this->template->getTemplateDirectories('unknown:profile');
    }

    public function testChain()
    {
        define('jkl',1);
        $this->assertEquals('trimmed', $this->template->chain(' trimmed ', 'ltrim|rtrim'));
    }

    public function testEscape()
    {
        $this->assertEquals('&lt;b&gt;strong&lt;/b&gt;', $this->template->escape('<b>strong</b>'));
    }
}

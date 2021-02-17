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

use PHPUnit\Framework\TestCase;
use Ekok\Stick\Template\Context;
use Ekok\Stick\Template\Template;

class ContextTest extends TestCase
{
    /** @var Template */
    private $template;

    private function create(string $name, array $data = null)
    {
        return new Context($this->template, $name, $data);
    }

    public function setUp(): void
    {
        $this->template = new Template(array(
            TEST_FIXTURE . '/templates',
            'simple' => TEST_FIXTURE . '/templates/simple',
        ));
    }

    public function testIsUsable()
    {
        $context = $this->create('simple', array(
            'title' => 'Simple Page',
            'name' => 'Administrator',
        ));

        $this->assertSame($context->getEngine(), $this->template);
        $this->assertEquals('&lt;b&gt;strong&lt;/b&gt;', $context->escape('<b>strong</b>'));
        $this->assertEquals('simple', $context->getName());
        $this->assertEquals(TEST_FIXTURE . '/templates/simple.php', $context->getFilepath());
        $this->assertEquals(array(
            'title' => 'Simple Page',
            'name' => 'Administrator',
        ), $context->getData());
    }

    /** @dataProvider getRenders */
    public function testRender(string $expected, string $exception = null, ...$arguments)
    {
        if ('consumer' === $arguments[0]) {
            $this->template->setOptions(array(
                'extension' => 'prefix.php',
            ));
            $this->template->addGlobals(array(
                'foo' => 'FoO',
                'html' => '<strong>Strong as stone</strong>',
            ));
            $this->template->addGlobal('bar', 'baz');
            $this->template->addFunction('upper', static function ($str) {
                return strtoupper($str);
            });
        }

        if ($exception) {
            $this->expectException($exception);
            $this->expectExceptionMessage($expected);

            $this->create(...$arguments)->render();
        } elseif ('~' === $expected[0]) {
            $this->assertMatchesRegularExpression('~' . substr($expected, 1) . '~', $this->create(...$arguments)->render());
        } elseif ('|' === $expected[0]) {
            $content = $this->create(...$arguments)->render();

            foreach (explode('|', substr($expected, 1)) as $line) {
                $this->assertStringContainsString($line, $content);
            }
        } else {
            $this->assertEquals($expected, $this->create(...$arguments)->render());
        }
    }

    public function getRenders()
    {
        yield 'simple' => array(
            <<<'HTML'
<html>
<head>
  <title>Simple Page</title>
</head>
<body>
  Welcome, Administrator.
</body>
</html>
HTML,
            null,
            'simple',
            array(
                'title' => 'Simple Page',
                'name' => 'Administrator',
            ),
        );

        yield 'load view' => array(
            '|Hello Jonathan|Example Link',
            null,
            'profile',
            array(
                'name' => 'Jonathan',
            ),
        );

        yield 'load view safely' => array(
            '|Hello Jonathan|Default Sidebar Menu',
            null,
            'no-sidebar',
            array(
                'name' => 'Jonathan',
            ),
        );

        yield 'can be used like blade template' => array(
            '~Default body content.[\h\v]+This is body from profile.[\h\v]+Default body content 2.',
            null,
            'blade.profile',
            array(
                'foo' => 'bar',
            ),
        );

        yield 'namespaced template' => array(
            '|Hello, Jonathan',
            null,
            'simple:profile',
            array(
                'name' => 'Jonathan',
            ),
        );

        yield 'stacked template, with dot notation' => array(
            '|Article Title|Article content',
            null,
            'stacked.article',
            array(
                'article' => array(
                    'title' => 'Article Title',
                    'content' => 'Article content',
                ),
            ),
        );

        yield 'can be used in template file' => array(
            '|Foo is uppercased: FOO.' .
            '|Bar is displayed as is "baz".' .
            '|HTML escaped: "&lt;strong&gt;Strong as stone&lt;/strong&gt;".' .
            '|Loading unknown template fallback.',
            null,
            'consumer',
            array(
                'loadUnknown' => true,
            ),
        );

        yield 'call unknown function' => array(
            'Function is not found in any context: unknown.',
            'BadFunctionCallException',
            'consumer',
            array(
                'call' => 'unknown',
            ),
        );

        yield 'prevent self rendering' => array(
            'Recursive view rendering is not supported.',
            'LogicException',
            'consumer',
            array(
                'selfRendering' => true,
            ),
        );

        yield 'calling parent outside context' => array(
            'Calling parent when not in section context is forbidden.',
            'LogicException',
            'consumer',
            array(
                'callParent' => true,
            ),
        );

        yield 'prevent use reserved section name' => array(
            'Section name is reserved: content.',
            'InvalidArgumentException',
            'consumer',
            array(
                'startContent' => true,
            ),
        );

        yield 'nested section is not supported' => array(
            'Nested section is not supported.',
            'LogicException',
            'consumer',
            array(
                'doubleStart' => true,
            ),
        );

        yield 'no start context' => array(
            'No section has been started.',
            'LogicException',
            'consumer',
            array(
                'endContent' => true,
            ),
        );

        yield 'prevent use *$this* variable name' => array(
            'Variable name is reserved for *this*: _.',
            'InvalidArgumentException',
            'simple/profile',
            array(
                '_' => 'foo',
            ),
        );

        yield 'use relative path inside template' => array(
            '|From template.|From My Relative1.|From My Relative2.',
            null,
            'relative/template.php',
        );

        yield 'unknown relative view' => array(
            "Relative view not found: './unknown_relative'.",
            'InvalidArgumentException',
            'relative/template',
            array(
                'loadUnrelative' => true,
            ),
        );
    }
}

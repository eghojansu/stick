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
use Fal\Stick\Template\Environment;
use Fal\Stick\TestSuite\MyTestCase;

class EnvironmentTest extends MyTestCase
{
    private $fw;
    private $environment;

    public function setup(): void
    {
        $this->fw = new Fw(array(
            'TEMP' => $this->tmp('/'),
        ));
        $this->environment = new Environment($this->fw, $this->fixture('/template/'), null, true);
    }

    public function teardown(): void
    {
        $this->fw->creset();
    }

    public function testMagicCall()
    {
        $this->environment->filter('foo', function () {
            return 'foo';
        });

        $this->assertEquals('/foo', $this->environment->path('/foo'));
        $this->assertEquals('foo', $this->environment->foo());
    }

    public function testIsAutoreload()
    {
        $this->assertTrue($this->environment->isAutoreload());
    }

    public function testSetAutoreload()
    {
        $this->assertFalse($this->environment->setAutoreload(false)->isAutoreload());
    }

    public function testFindTemplate()
    {
        $this->assertEquals($this->fixture('/template/partial.shtml'), $this->environment->findTemplate('partial.shtml'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Template not found: unknown.html');
        $this->environment->findTemplate('unknown.html');
    }

    public function testLoadTemplate()
    {
        $template = $this->environment->loadTemplate('foo.shtml');

        $this->assertEquals('foo.shtml', $template->getTemplateName());
        $this->assertFileEquals($this->fixture('/template/foo.php'), $template->getCompiledPath());
    }

    public function testRender()
    {
        $source = 'foo.shtml';

        $this->assertEquals('notfoobar.', $this->environment->render($source));
        $this->assertEquals('foobar.', $this->environment->render($source, array('foo' => 'foo')));
    }

    public function testExtend()
    {
        $this->assertSame($this->environment, $this->environment->extend('foo', 'bar'));
    }

    public function testFilter()
    {
        $this->assertSame($this->environment, $this->environment->filter('foo', 'bar'));
    }

    public function testEsc()
    {
        $this->assertEquals('&lt;a&gt;foo&lt;/a&gt;', $this->environment->esc('<a>foo</a>'));
    }

    public function testRaw()
    {
        $this->assertEquals('<a>foo</a>', $this->environment->raw('&lt;a&gt;foo&lt;/a&gt;'));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Template\EnvironmentProvider::compile
     */
    public function testCompile($expected, $str)
    {
        $this->assertEquals($expected, $this->environment->compile($str));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Template\EnvironmentProvider::token
     */
    public function testToken($expected, $str)
    {
        $this->environment->filter('foo', 'bar');

        $this->assertEquals($expected, $this->environment->token($str));
    }

    public function testTokenize()
    {
        $this->assertEquals('', $this->environment->tokenize(''));
        $this->assertEquals("'foo'", $this->environment->tokenize('foo'));
        $this->assertEquals('$foo', $this->environment->tokenize('{{ @foo }}'));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Template\EnvironmentProvider::buildText
     */
    public function testBuildText($expected, $text)
    {
        $this->assertEquals($expected, $this->environment->buildText($text));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Template\EnvironmentProvider::build
     */
    public function testBuild($expected, $node)
    {
        $this->environment->extend('footag', function () {
            return 'footag called';
        });

        $this->assertEquals($expected, $this->environment->build($node));
    }

    public function testUnbuild()
    {
        $nodes = array(
            'foo',
            array(
                'foo' => array(
                    '@attrib' => array(
                        'foo' => 'bar',
                    ),
                ),
                'bar' => array(
                    'foo',
                ),
            ),
        );
        $expected = 'foo<foo foo="bar" /><bar>foo</bar>';

        $this->assertEquals($expected, $this->environment->unbuild($nodes));
    }

    public function testParseXml()
    {
        $text = <<<'HTML'
{{ foo }}
<block name="foo" bar='foo' {{ @foo }}>
  <set foo="bar" />
</block>
HTML;
        $expected = array(
            "{{ foo }}\n",
            array(
                'block' => array(
                    '@attrib' => array(
                        'name' => 'foo',
                        'bar' => 'foo',
                        '{{ @foo }}',
                    ),
                    "\n  ",
                    array(
                        'set' => array(
                            '@attrib' => array(
                                'foo' => 'bar',
                            ),
                        ),
                    ),
                    "\n",
                ),
            ),
        );

        $this->assertEquals($expected, $this->environment->parseXml($text));
    }

    public function test_set()
    {
        $expected = '<?php $foo=\'foo\';$bar=$foo ?>';
        $node = array(
            '@attrib' => array(
                'foo' => 'foo',
                'bar' => '{{ @foo }}',
            ),
        );

        $this->assertEquals($expected, $this->environment->_set($node));
    }

    public function test_include()
    {
        $expected = '<?php if (true) echo $this->env->render(\'foo.html\',[\'foo\'=>$bar,\'bar\'=>true]+$__context) ?>';
        $node = array(
            '@attrib' => array(
                'if' => 'true',
                'href' => 'foo.html',
                'with' => 'foo=@bar,bar=true',
            ),
        );

        $this->assertEquals($expected, $this->environment->_include($node));

        $expected = '<?php echo $this->env->render(\'foo.html\',[]+$__context) ?>';
        $node = array(
            '@attrib' => array(
                'href' => 'foo.html',
            ),
        );

        $this->assertEquals($expected, $this->environment->_include($node));
    }

    public function test_extends()
    {
        $expected = '<?php $this->extend(\'foo.html\') ?>';
        $node = array(
            '@attrib' => array(
                'href' => 'foo.html',
            ),
        );

        $this->assertEquals($expected, $this->environment->_extends($node));
    }

    public function test_parent()
    {
        $expected = '<?= $this->parent() ?>';
        $node = array(
            '@attrib' => array(
                'name' => '',
            ),
        );

        $this->assertEquals($expected, $this->environment->_parent($node));
    }

    public function test_block()
    {
        $expected = '<?php $this->start(\'foo\') ?>foo<?php $this->stop() ?>';
        $node = array(
            '@attrib' => array(
                'name' => 'foo',
            ),
            'foo',
        );
        $this->assertEquals($expected, $this->environment->_block($node));

        $expected = '<?= $this->block(\'foo\') ?>';
        $node = array(
            '@attrib' => array(
                'name' => 'foo',
            ),
        );

        $this->assertEquals($expected, $this->environment->_block($node));
    }

    public function test_exclude()
    {
        $expected = '';
        $node = array(
            '@attrib' => array(
            ),
        );

        $this->assertEquals($expected, $this->environment->_exclude($node));
    }

    public function test_ignore()
    {
        $expected = '<set foo="bar" />';
        $node = array(
            '<set foo="bar" />',
        );

        $this->assertEquals($expected, $this->environment->_ignore($node));
    }

    public function test_loop()
    {
        $expected = '<?php for ($i = 0;$i < 10;$i++): ?><?= $i ?><?php endfor ?><?php if (!isset($i)): ?>nothing to repeat<?php endif ?>';
        $node = array(
            '@attrib' => array(
                'from' => '{{ @i = 0 }}',
                'to' => '{{ @i < 10 }}',
                'step' => '{{ @i++ }}',
            ),
            '{{ @i }}',
            array(
                'otherwise' => array(
                    '@attrib' => array(
                        'if' => '{{ !isset(@i) }}',
                    ),
                    'nothing to repeat',
                ),
            ),
        );

        $this->assertEquals($expected, $this->environment->_loop($node));
    }

    public function test_repeat()
    {
        $expected = '<?php $foo=0; foreach ($bar?:[] as $baz=>$qux): $foo++ ?><?= $foo ?><?php endforeach ?>';
        $node = array(
            '@attrib' => array(
                'counter' => '{{ @foo }}',
                'group' => '{{ @bar }}',
                'key' => '{{ @baz }}',
                'value' => '{{ @qux }}',
            ),
            '{{ @foo }}',
        );

        $this->assertEquals($expected, $this->environment->_repeat($node));

        $expected = '<?php $foo=0; foreach ($bar?:[] as $baz=>$qux): $foo++ ?><?= $foo ?><?php endforeach ?><?php if (!isset($qux)): ?>nothing to repeat<?php endif ?>';
        $node = array(
            '@attrib' => array(
                'counter' => '{{ @foo }}',
                'group' => '{{ @bar }}',
                'key' => '{{ @baz }}',
                'value' => '{{ @qux }}',
            ),
            '{{ @foo }}',
            array(
                'otherwise' => array(
                    'nothing to repeat',
                ),
            ),
        );

        $this->assertEquals($expected, $this->environment->_repeat($node));
    }

    public function test_check()
    {
        $expected = '<?php if (true): ?><?= 1 ?><?php endif ?>';
        $node = array(
            '@attrib' => array(
                'if' => 'true',
            ),
            '{{ 1 }}',
        );

        $this->assertEquals($expected, $this->environment->_check($node));

        $expected = '<?php if (1 === $foo): ?>one<?php else: ?>not one<?php endif ?>';
        $node = array(
            '@attrib' => array(
                'if' => '{{ 1 === @foo }}',
            ),
            'one',
            array(
                'false' => array(
                    'not one',
                ),
            ),
        );

        $this->assertEquals($expected, $this->environment->_check($node));

        $expected = '<?php if ($foo === 1): ?>one<?php else: ?>to much<?php endif ?>';
        $node = array(
            '@attrib' => array(
                'if' => '{{ @foo === 1 }}',
            ),
            array(
                'false' => array(
                    'to much',
                ),
            ),
            array(
                'true' => array(
                    'one',
                ),
            ),
        );

        $this->assertEquals($expected, $this->environment->_check($node));

        $expected = '<?php if ($foo === 1): ?>one<?php elseif ($foo === 2): ?>two<?php elseif ($foo === 3): ?>three<?php else: ?>to much<?php endif ?>';
        $node = array(
            '@attrib' => array(
                'if' => '{{ @foo === 1 }}',
            ),
            array(
                'false' => array(
                    'to much',
                ),
            ),
            array(
                'true' => array(
                    '@attrib' => array(
                        'if' => '{{ @foo === 2 }}',
                    ),
                    'two',
                ),
            ),
            array(
                'true' => array(
                    '@attrib' => array(
                        'if' => '{{ @foo === 3 }}',
                    ),
                    'three',
                ),
            ),
            array(
                'true' => array(
                    'one',
                ),
            ),
            '  ',
        );

        $this->assertEquals($expected, $this->environment->_check($node));

        $node = array(
            '@attrib' => array(
                'if' => '{{ @foo === 1 }}',
            ),
            array(
                'false' => array(
                    'to much',
                ),
            ),
        );
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Invalid check statement.');
        $this->environment->_check($node);
    }

    public function test_true()
    {
        $expected = '';
        $node = array(
            '@attrib' => array(
            ),
        );

        $this->assertEquals($expected, $this->environment->_true($node));

        $expected = '<?php elseif (true): ?>';
        $node = array(
            '@attrib' => array(
                'if' => 'true',
            ),
        );

        $this->assertEquals($expected, $this->environment->_true($node));
    }

    public function test_false()
    {
        $expected = '<?php else: ?>';
        $node = array(
            '@attrib' => array(
            ),
        );

        $this->assertEquals($expected, $this->environment->_false($node));
    }

    public function test_switch()
    {
        $expected = '<?php switch (true): ?><?php default: ?>foo<?php break ?><?php endswitch ?>';
        $node = array(
            '@attrib' => array(
                'expr' => 'true',
            ),
            ' ',
            array(
                'default' => array(
                    'foo',
                ),
            ),
        );

        $this->assertEquals($expected, $this->environment->_switch($node));
    }

    public function test_case()
    {
        $expected = '<?php case \'true\': ?><?php if (true) break ?>';
        $node = array(
            '@attrib' => array(
                'value' => 'true',
                'break' => 'true',
            ),
        );

        $this->assertEquals($expected, $this->environment->_case($node));
    }

    public function test_default()
    {
        $expected = '<?php default: ?><?php break ?>';
        $node = array(
            '@attrib' => array(
            ),
        );

        $this->assertEquals($expected, $this->environment->_default($node));
    }

    public function testRequiredAttrib()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Missing property: name.');
        $this->environment->_block(array());
    }
}

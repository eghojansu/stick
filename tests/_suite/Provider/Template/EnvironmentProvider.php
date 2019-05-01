<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\TestSuite\Provider\Template;

class EnvironmentProvider
{
    public function compile()
    {
        return array(
            array(
                "\$foo['bar']",
                '@foo.bar',
            ),
            array(
                '$foo->bar',
                '@foo->bar',
            ),
            array(
                '$foo[0]',
                '@foo[0]',
            ),
            array(
                "\$foo['bar'][0]",
                '@foo.bar.0',
            ),
            array(
                "\$foo->bar['baz']",
                '@foo->bar.baz',
            ),
            array(
                'trim($foo)',
                'trim(@foo)',
            ),
            array(
                "\$foo['bar'].trim(\$foo)",
                '@foo.bar.trim(@foo)',
            ),
            array(
                "\$foo['bar']('baz')",
                "@foo.bar('baz')",
            ),
        );
    }

    public function token()
    {
        return array(
            array(
                '$foo',
                '@foo',
            ),
            array(
                "\$foo['bar']",
                '@foo.bar',
            ),
            array(
                "\$foo['bar']->baz",
                '@foo.bar->baz',
            ),
            array(
                'trim($foo)',
                '@foo|trim',
            ),
            array(
                "trim('foo')",
                "'foo' | trim",
            ),
            array(
                "\$this->foo('foo')",
                "'foo' | foo",
            ),
        );
    }

    public function buildText()
    {
        return array(
            'echo' => array(
                '<?= $foo ?>',
                '{{ @foo }}',
            ),
            'raw' => array(
                '<?php $foo ?>',
                '{~ @foo ~}',
            ),
            'raw full' => array(
                '@foo',
                '{- @foo -}',
            ),
            'comment' => array(
                '',
                '{# @foo #}',
            ),
        );
    }

    public function build()
    {
        return array(
            array(
                <<<'HTML'
foo
<?= 'foo'.'
' ?>
<?php $foo='foo' ?>
foo
<?php $foo = 'bar' ?>
footag called
HTML
,
                array(
                    "foo\n",
                    "{{ 'foo' }}\n",
                    array(
                        'set' => array(
                            '@attrib' => array(
                                'foo' => 'foo',
                            ),
                        ),
                    ),
                    array(
                        "{# comment #}\n{- foo -}\n{~ @foo = 'bar' ~}",
                        "\n",
                        array(
                            'footag' => array(
                                'foo',
                            ),
                        ),
                    ),
                ),
            ),
        );
    }
}

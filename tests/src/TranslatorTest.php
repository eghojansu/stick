<?php

declare(strict_types=1);

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test;

use Fal\Stick\Translator;
use PHPUnit\Framework\TestCase;

class TranslatorTest extends TestCase
{
    private $translator;

    public function setUp()
    {
        $this->translator = new Translator();
    }

    public function transProvider()
    {
        return [
            [
                'foo',
            ],
            [
                "You're beautiful",
            ],
            [
                'Kamu cantik', "You're beautiful", [], 'id',
            ],
            [
                'She is beautiful', 'She.is.beautiful',
            ],
            [
                'She is wonderful', 'She.is.wonderful',
            ],
            [
                'She is wonderful', 'She.is.wonderful', [], 'id',
            ],
            [
                'She.is.awesome',
            ],
            [
                'Dia cantik', 'She.is.beautiful', [], 'id',
            ],
            [
                'Dia menakjubkan', 'She.is.awesome', [], 'id',
            ],
            [
                'Her name is Mumtaz', 'Her name is %name%', ['%name%' => 'Mumtaz'],
            ],
            [
                'Namanya adalah Mumtaz', 'Her name is %name%', ['%name%' => 'Mumtaz'], 'id',
            ],
        ];
    }

    /**
     * @dataProvider transProvider
     */
    public function testTrans($expected, $key = null, $args = [], $lang = 'en')
    {
        $this->translator->setLocales(FIXTURE.'dict/')->setLanguages($lang)->trans('foo');
        $this->assertEquals($expected, $this->translator->trans($key ?? $expected, $args));
    }

    /**
     * @expectedException \UnexpectedValueException
     * @expectedExceptionMessage Message reference is not a string
     */
    public function testTransException()
    {
        $this->translator->add('foo.bar', 'baz')->trans('foo');
    }

    public function choiceProvider()
    {
        return [
            [1, 'There is no apple', 'There is no apple'],
            [0, 'There is no apple', 'There is no apple|There is one apple|There is # apples'],
            [1, 'There is one apple', 'There is no apple|There is one apple|There is # apples'],
            [2, 'There is 2 apples', 'There is no apple|There is one apple|There is # apples'],
            [3, 'There is 3 apples', 'There is no apple|There is one apple|There is # apples'],
            [1, 'Ada 1 gadis', 'girl_count', [], 'id'],
        ];
    }

    /**
     * @dataProvider choiceProvider
     */
    public function testChoice($count, $expected, $key, $args = [], $lang = 'en')
    {
        $this->translator->setLocales(FIXTURE.'dict/')->setLanguages($lang)->trans('foo');
        $this->assertEquals($expected, $this->translator->choice($key, $count, $args));
    }

    public function testGetDict()
    {
        $this->assertEquals([], $this->translator->getDict());
    }

    public function testAdd()
    {
        $this->assertEquals('baz', $this->translator->add('foo.bar', 'baz')->trans('foo.bar'));
    }

    public function testGetLocales()
    {
        $this->assertEquals(['./'], $this->translator->getLocales());
    }

    public function testSetLocales()
    {
        $this->assertEquals(['foo'], $this->translator->setLocales('foo')->getLocales());
    }

    public function testGetLanguages()
    {
        $this->assertEquals('en', $this->translator->getLanguages());
    }

    public function testSetLanguages()
    {
        $this->assertEquals('id,en', $this->translator->setLanguages('id')->getLanguages());
        $this->assertEquals('id-ID,id,en', $this->translator->setLanguages('id-ID')->getLanguages());
    }

    public function testGetFallback()
    {
        $this->assertEquals('en', $this->translator->getFallback());
    }

    public function testSetFallback()
    {
        $this->assertEquals('foo', $this->translator->setFallback('foo')->getFallback());
    }
}

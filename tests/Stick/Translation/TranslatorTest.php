<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 25, 2019 20:14
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Translation;

use PHPUnit\Framework\TestCase;
use Fal\Stick\Translation\Translator;

class TranslatorTest extends TestCase
{
    private $translator;

    public function setup()
    {
        $this->translator = new Translator();
    }

    public function testGetLocales()
    {
        $this->assertEquals(array(), $this->translator->getLocales());
    }

    public function testSetLocales()
    {
        $this->assertEquals(array('foo'), $this->translator->setLocales(array('foo' => true))->getLocales());
    }

    public function testAddLocale()
    {
        $this->assertEquals(array('bar', 'foo'), $this->translator->addLocale('foo')->addLocale('bar', true)->getLocales());
    }

    public function testGetLanguage()
    {
        $this->assertEquals('', $this->translator->getLanguage());
    }

    public function testSetLanguage()
    {
        $this->assertEquals('id-ID', $this->translator->setLanguage('id-ID')->getLanguage());
    }

    public function testGetFallback()
    {
        $this->assertEquals('en', $this->translator->getFallback());
    }

    public function testSetFallback()
    {
        $this->assertEquals('id', $this->translator->setFallback('id')->getFallback());
    }

    /**
     * @dataProvider transProvider
     */
    public function testTrans($expected, $message, $parameters = null)
    {
        $this->translator->addLocale(TEST_FIXTURE.'dict/')->setLanguage('id-ID');

        $this->assertEquals($expected, $this->translator->trans($message, $parameters));
    }

    /**
     * @dataProvider choiceProvider
     */
    public function testChoice($expected, $message, $count, $parameters = null, $fallback = null)
    {
        $this->translator->addLocale(TEST_FIXTURE.'dict/');

        $this->assertEquals($expected, $this->translator->choice($message, $count, $parameters, $fallback));
    }

    /**
     * @dataProvider transAltProvider
     */
    public function testTransAlt($expected, $messages, $parameters = null)
    {
        $this->translator->addLocale(TEST_FIXTURE.'dict/')->setLanguage('id-ID');

        $this->assertEquals($expected, $this->translator->transAlt($messages, $parameters));
    }

    public function testExists()
    {
        $this->assertFalse($this->translator->exists('foo'));
    }

    public function testGet()
    {
        $this->assertNull($this->translator->get('foo'));
    }

    public function testSet()
    {
        $this->assertEquals('bar', $this->translator->set('foo', 'bar')->get('foo'));
    }

    public function testClear()
    {
        $this->assertNull($this->translator->set('foo', 'bar')->clear('foo')->get('foo'));
    }

    public function testTransAdv()
    {
        $this->assertEquals('bar', $this->translator->set('foo', 'bar')->transAdv('foo'));
        $this->assertNull($this->translator->transAdv('bar'));
    }

    public function transProvider()
    {
        return array(
            array('foo', 'foo'),
            array('Sebuah bendera nasional', 'a_flag'),
            array('There is a blueberry', 'there.is.one.blueberry'),
            array('Ada sebuah apel', 'there.is.one.apple'),
            array('Ada sebuah jeruk', 'there.is.one.orange'),
            array('Ada sebuah mangga', 'there.is.one.mango'),
            array('Ada sebuah strawberi', 'there.is.one.fruit', array('{fruit}' => 'strawberi')),
            array('there.is.one.pineaplle', 'there.is.one.pineaplle'),
        );
    }

    public function choiceProvider()
    {
        return array(
            array('foo', 'foo', 0),
            array('There is no apple', 'apples', 0),
            array('There is one apple', 'apples', 1),
            array('There is 2 apples', 'apples', 2),
            array('There is 99 apples', 'apples', 99),
            array('There is no fruits', 'fruits', 0, array(
                '{fruit1}' => 'apple',
                '{fruit2}' => 'orange',
                '{fruit3}' => 'mango',
            )),
            array('There is apple, orange and mango', 'fruits', 1, array(
                '{fruit1}' => 'apple',
                '{fruit2}' => 'orange',
                '{fruit3}' => 'mango',
            )),
            array('There is lots of apple, orange and mango', 'fruits', 2, array(
                '{fruit1}' => 'apple',
                '{fruit2}' => 'orange',
                '{fruit3}' => 'mango',
            )),
        );
    }

    public function transAltProvider()
    {
        return array(
            array(
                'Ada sebuah apel',
                array('there.is.one.apple'),
            ),
            array(
                'Tidak ada buah yang diinginkan',
                array('there.is.one.melon', 'Tidak ada buah yang diinginkan'),
            ),
            array(
                'Ada sebuah melon',
                array('there.is.one.fruit'),
                array('{fruit}' => 'melon'),
            ),
        );
    }
}

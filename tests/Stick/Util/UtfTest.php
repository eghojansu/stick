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

namespace Fal\Stick\Test\Util;

use Fal\Stick\Util\Utf;
use Fal\Stick\TestSuite\MyTestCase;

class UtfTest extends MyTestCase
{
    public function testStrlen()
    {
        $this->assertEquals(22, Utf::strlen('⠊⠀⠉⠁⠝⠀⠑⠁⠞⠀⠛⠇⠁⠎⠎⠀⠁⠝⠙⠀⠊⠞'));
    }

    public function testStrrev()
    {
        $this->assertEquals('êl-op hàihc gnàt-ē aóG', Utf::strrev('Góa ē-tàng chiàh po-lê'));
    }

    public function testStripos()
    {
        $this->assertEquals(12, Utf::stripos('Góa ē-tàng chia̍h po-lê', 'h'));
    }

    public function testStrpos()
    {
        $this->assertEquals(12, Utf::strpos('Góa ē-tàng chia̍h po-lê', 'h'));
        $this->assertEquals(12, Utf::strpos('123 456 789 123 4', '123', 7));
    }

    public function testStristr()
    {
        $this->assertEquals('ᛁᚳᛚᚢᚾ᛫ᚻᛦᛏ᛫ᛞᚫᛚᚪᚾ', Utf::stristr('ᛋᚳᛖᚪᛚ᛫ᚦᛖᚪᚻ᛫ᛗᚪᚾᚾᚪ᛫ᚷᛖᚻᚹᛦᛚᚳ᛫ᛗᛁᚳᛚᚢᚾ᛫ᚻᛦᛏ᛫ᛞᚫᛚᚪᚾ', 'ᛁᚳᛚᚢᚾ'));
    }

    public function testStrstr()
    {
        $this->assertFalse(Utf::strstr('ᛋᚳᛖᚪᛚ᛫ᚦᛖᚪᚻ᛫ᛗᚪᚾᚾᚪ᛫ᚷᛖᚻᚹᛦᛚᚳ᛫ᛗᛁᚳᛚᚢᚾ᛫ᚻᛦᛏ᛫ᛞᚫᛚᚪᚾ', ''));
        $this->assertEquals('ᛁᚳᛚᚢᚾ᛫ᚻᛦᛏ᛫ᛞᚫᛚᚪᚾ', Utf::strstr('ᛋᚳᛖᚪᛚ᛫ᚦᛖᚪᚻ᛫ᛗᚪᚾᚾᚪ᛫ᚷᛖᚻᚹᛦᛚᚳ᛫ᛗᛁᚳᛚᚢᚾ᛫ᚻᛦᛏ᛫ᛞᚫᛚᚪᚾ', 'ᛁᚳᛚᚢᚾ'));
        $this->assertEquals('ᛋᚳᛖᚪᛚ᛫ᚦᛖᚪᚻ᛫ᛗᚪᚾᚾᚪ᛫ᚷᛖᚻᚹᛦᛚᚳ᛫ᛗ', Utf::strstr('ᛋᚳᛖᚪᛚ᛫ᚦᛖᚪᚻ᛫ᛗᚪᚾᚾᚪ᛫ᚷᛖᚻᚹᛦᛚᚳ᛫ᛗᛁᚳᛚᚢᚾ᛫ᚻᛦᛏ᛫ᛞᚫᛚᚪᚾ', 'ᛁᚳᛚᚢᚾ', true));
    }

    public function testSubstr()
    {
        $this->assertEquals('Я можу', Utf::substr('Я можу їсти скло', 0, 6));
        $this->assertEquals('kiló', Utf::substr('El pingüino Wenceslao hizo kilómetros', -10, 4));
        $this->assertEquals('유리를', Utf::substr('나는 유리를 먹을 수 있어요. 그래도', 3, 3));
        $this->assertFalse(Utf::substr('', 7));
    }

    public function testSubstrCount()
    {
        $this->assertEquals(2, Utf::substrCount('Можам да јадам стакло, а не ме штета.', 'д'));
    }

    public function testLtrim()
    {
        $this->assertEquals("#string#\xc2\xa0\xe1\x9a\x80", Utf::ltrim("\xe2\x80\x83\x20#string#\xc2\xa0\xe1\x9a\x80"));
    }

    public function testRtrim()
    {
        $this->assertEquals("\xe2\x80\x83\x20#string#", Utf::rtrim("\xe2\x80\x83\x20#string#\xc2\xa0\xe1\x9a\x80"));
    }

    public function testTrim()
    {
        $this->assertEquals('#string#', Utf::trim("\xe2\x80\x83\x20#string#\xc2\xa0\xe1\x9a\x80"));
    }

    public function testBom()
    {
        $this->assertEquals(chr(0xef).chr(0xbb).chr(0xbf), Utf::bom());
    }

    public function testTranslate()
    {
        $this->assertEquals('foo', Utf::translate('foo'));
    }

    public function testEmojify()
    {
        $this->assertEquals('I am sad ☹', Utf::emojify('I am sad :('));
    }
}

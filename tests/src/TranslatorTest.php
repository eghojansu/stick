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

    public function testTransAlt()
    {
        $this->translator->add('foo', ['bar' => 'baz', 'qux' => 'quux']);

        $this->assertEquals('baz', $this->translator->transAlt('foo.bar'));
        $this->assertEquals('quux', $this->translator->transAlt('foo.baz', null, null, 'foo.qux'));
        $this->assertEquals('foo.baz', $this->translator->transAlt('foo.baz', null, null, 'foo.quux'));
        $this->assertEquals('none', $this->translator->transAlt('foo.baz', null, 'none', 'foo.quux'));
    }

    public function testGetDict()
    {
        $this->assertEquals([], $this->translator->getDict());
    }

    public function testAdd()
    {
        $this->assertEquals('baz', $this->translator->add('foo.bar', 'baz')->trans('foo.bar'));
        $this->assertEquals('c', $this->translator->add('a', ['b' => 'c'])->trans('a.b'));
    }

    public function testGetLocales()
    {
        $this->assertContains('./', $this->translator->getLocales());
    }

    public function testSetLocales()
    {
        $this->assertContains('foo', $this->translator->setLocales('foo')->getLocales());
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

    public function enMessages()
    {
        return [
            ['default', 'This value is not valid.'],
            ['required', 'This value should not be blank.'],
            ['type', 'This value should be of type string.', ['{0}' => 'string']],
            ['min', 'This value should be 1 or more.', ['{0}' => '1']],
            ['max', 'This value should be 1 or less.', ['{0}' => '1']],
            ['lt', 'This value should be less than 1.', ['{0}' => '1']],
            ['gt', 'This value should be greater than 1.', ['{0}' => '1']],
            ['lte', 'This value should be less than or equal to 1.', ['{0}' => '1']],
            ['gte', 'This value should be greater than or equal to 1.', ['{0}' => '1']],
            ['equalfield', 'This value should be equal to value of 1.', ['{0}' => '1']],
            ['notequalfield', 'This value should not be equal to value of 1.', ['{0}' => '1']],
            ['equal', 'This value should be equal to 1.', ['{0}' => '1']],
            ['notequal', 'This value should not be equal to 1.', ['{0}' => '1']],
            ['identical', 'This value should be identical to string foo.', ['{0}' => 'foo', '{1}' => 'string']],
            ['notidentical', 'This value should not be identical to string foo.', ['{0}' => 'foo', '{1}' => 'string']],
            ['len', 'This value is not valid. It should have exactly 1 characters.', ['{0}' => '1']],
            ['lenmin', 'This value is too short. It should have 1 characters or more.', ['{0}' => '1']],
            ['lenmax', 'This value is too long. It should have 1 characters or less.', ['{0}' => '1']],
            ['count', 'This collection should contain exactly 1 elements.', ['{0}' => '1']],
            ['countmin', 'This collection should contain 1 elements or more.', ['{0}' => '1']],
            ['countmax', 'This collection should contain 1 elements or less.', ['{0}' => '1']],
            ['choice', 'The value you selected is not a valid choice.'],
            ['choices', 'One or more of the given values is invalid.'],
            ['date', 'This value is not a valid date.'],
            ['datetime', 'This value is not a valid datetime.'],
            ['email', 'This value is not a valid email address.'],
            ['url', 'This value is not a valid url.'],
            ['ipv4', 'This value is not a valid ipv4 address.'],
            ['ipv6', 'This value is not a valid ipv6 address.'],
            ['isprivate', 'This value is not a private ip address.'],
            ['isreserved', 'This value is not a reserved ip address.'],
            ['ispublic', 'This value is not a public ip address.'],
            ['unique', 'This value is already used.'],
            ['password', 'This value should be equal to your user password.'],
        ];
    }

    /**
     * @dataProvider enMessages
     */
    public function testEnValidatorDomainMessages($key, $expected, $args = null)
    {
        $this->translator->setLanguages('en');

        $this->assertEquals($expected, $this->translator->trans('validation.'.$key, $args));
    }

    public function idMessages()
    {
        return [
            ['default', 'Nilai ini tidak valid.'],
            ['required', 'Nilai ini tidak boleh kosong.'],
            ['type', 'Tipe nilai ini harus string.', ['{0}' => 'string']],
            ['min', 'Nilai ini minimal 1.', ['{0}' => '1']],
            ['max', 'Nilai ini maksimal 1.', ['{0}' => '1']],
            ['lt', 'Nilai ini harus kurang dari 1.', ['{0}' => '1']],
            ['gt', 'Nilai ini harus lebih dari 1.', ['{0}' => '1']],
            ['lte', 'Nilai ini harus kurang dari atau sama dengan 1.', ['{0}' => '1']],
            ['gte', 'Nilai ini harus lebih dari atau sama dengan 1.', ['{0}' => '1']],
            ['equalfield', 'Nilai ini harus sama dengan nilai dari 1.', ['{0}' => '1']],
            ['notequalfield', 'Nilai ini harus tidak sama dengan nilai dari 1.', ['{0}' => '1']],
            ['equal', 'Nilai ini harus sama dengan 1.', ['{0}' => '1']],
            ['notequal', 'Nilai ini harus tidak sama dengan 1.', ['{0}' => '1']],
            ['identical', 'Nilai ini harus sama dengan string foo.', ['{0}' => 'foo', '{1}' => 'string']],
            ['notidentical', 'Nilai ini harus tidak sama dengan string foo', ['{0}' => 'foo', '{1}' => 'string']],
            ['len', 'Nilai ini tidak valid. Maksimal 1 karakter.', ['{0}' => '1']],
            ['lenmin', 'Nilai ini terlalu pendek. Minimal 1 karakter.', ['{0}' => '1']],
            ['lenmax', 'Nilai ini terlalu panjang. Maksimal 1 karakter.', ['{0}' => '1']],
            ['count', 'Pilihan ini harus memiliki 1 elemen.', ['{0}' => '1']],
            ['countmin', 'Pilihan ini harus memiliki 1 elemen atau lebih.', ['{0}' => '1']],
            ['countmax', 'Pilihan ini harus memiliki 1 elemen atau kurang.', ['{0}' => '1']],
            ['choice', 'Nilai yang Anda pilih bukan pilihan yang valid.'],
            ['choices', 'Satu atau lebih dari pilihan Anda tidak valid.'],
            ['date', 'Nilai ini bukan tanggal yang valid.'],
            ['datetime', 'Nilai ini bukan waktu dan tanggal yang valid.'],
            ['email', 'Nilai ini bukan email yang valid.'],
            ['url', 'Nilai ini bukan url yang valid.'],
            ['ipv4', 'Nilai ini bukan ipv4 yang valid.'],
            ['ipv6', 'Nilai ini bukan ipv6 yang valid.'],
            ['isprivate', 'Nilai ini bukan ip private.'],
            ['isreserved', 'Nilai ini bukan ip reserved.'],
            ['ispublic', 'Nilai ini bukan ip public.'],
            ['unique', 'Nilai ini sudah digunakan.'],
            ['password', 'Nilai ini harus sesuai dengan password user Anda.'],
        ];
    }

    /**
     * @dataProvider idMessages
     */
    public function testIdValidatorDomainMessages($key, $expected, $args = null)
    {
        $this->translator->setLanguages('id');

        $this->assertEquals($expected, $this->translator->trans('validation.'.$key, $args));
    }
}

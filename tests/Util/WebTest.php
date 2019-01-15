<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Util;

use Fal\Stick\Core;
use Fal\Stick\Util\Web;
use PHPUnit\Framework\TestCase;

class WebTest extends TestCase
{
    private $web;
    private $core;

    public function setUp()
    {
        $this->web = new Web($this->core = new Core());
    }

    public function tearDown()
    {
        foreach (glob(TEST_TEMP.'/uploaded/*') as $file) {
            unlink($file);
        }
    }

    public function testGetDiacritics()
    {
        $this->assertNotEmpty($this->web->getDiacritics());
    }

    public function testAddDiacritics()
    {
        $this->assertContains('bar', $this->web->addDiacritics(['foo' => 'bar'])->getDiacritics());
    }

    public function testSlug()
    {
        $this->assertEquals('foo-bar-baz', $this->web->slug('Foo BAR BAZ'));
    }

    public function testMime()
    {
        $this->assertEquals('application/json', $this->web->mime('foo.json'));
        $this->assertEquals('application/octet-stream', $this->web->mime('foo'));
    }

    /**
     * @dataProvider sendProvider
     */
    public function testSend($expected, $headers, $output, $file, $kbps = 0)
    {
        $this->expectOutputString($output);

        $size = $this->web->send($file, null, null, $kbps);

        $this->assertEquals($expected, $size);
        $this->assertEquals($headers, $this->core->get('RESPONSE'));
    }

    public function testReceive()
    {
        $this->core->set('FILES', [
            'foo' => [
                'name'     => ['foo.txt', 'bar.txt'],
                'type'     => ['text/plain', null],
                'size'     => [3, 0, 0],
                'tmp_name' => [TEST_TEMP.'/test-uploads/foo.txt', null],
                'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE],
            ],
        ]);

        if (!is_dir(TEST_TEMP.'/test-uploads')) {
            mkdir(TEST_TEMP.'/test-uploads');
        }

        file_put_contents(TEST_TEMP.'/test-uploads/foo.txt', 'foo');

        $dir = TEST_TEMP.'/uploaded/';
        $expected = [
            TEST_TEMP.'/uploaded/foo.txt' => true,
            TEST_TEMP.'/uploaded/bar.txt' => false,
        ];

        $this->assertEquals($expected, $this->web->receive($dir, false, null, null, true));
        $this->assertEquals('foo', file_get_contents(TEST_TEMP.'/uploaded/foo.txt'));
    }

    /**
     * @dataProvider receivePutFilesProvider
     */
    public function testReceivePut($expected, $file, $uri, $content, $dir, $handler = null)
    {
        $this->core->set('PATH', $uri);
        $this->core->set('RAW', false);
        $this->core->set('CONTENT', $content);
        $this->core->set('VERB', 'PUT');

        $this->assertEquals([$file => $expected], $this->web->receive($dir, false, $handler, null, true));

        if ($expected) {
            $this->assertEquals($content, file_get_contents($file));
        }
    }

    public function sendProvider()
    {
        return [
            [
                -1,
                null,
                '',
                TEST_FIXTURE.'/files/unknown.txt',
            ],
            [
                3128,
                [
                    'Content-Type'        => 'text/plain',
                    'Content-Disposition' => 'attachment; filename="long.txt"',
                    'Accept-Ranges'       => 'bytes',
                    'Content-Length'      => 3128,
                ],
                file_get_contents(TEST_FIXTURE.'/files/long.txt'),
                TEST_FIXTURE.'/files/long.txt',
            ],
            [
                3128,
                [
                    'Content-Type'        => 'text/plain',
                    'Content-Disposition' => 'attachment; filename="long.txt"',
                    'Accept-Ranges'       => 'bytes',
                    'Content-Length'      => 3128,
                ],
                file_get_contents(TEST_FIXTURE.'/files/long.txt'),
                TEST_FIXTURE.'/files/long.txt',
                1024,
            ],
        ];
    }

    public function receivePutFilesProvider()
    {
        return [
            [
                true,
                TEST_TEMP.'/uploaded/foo.txt',
                '/upload/foo.txt',
                'foo',
                TEST_TEMP.'/uploaded/',
            ],
            [
                true,
                TEST_TEMP.'/uploaded/foo.txt',
                '/upload/foo.txt',
                TEST_TEMP.'/uploaded/foo.txt',
                TEST_TEMP.'/uploaded/',
                function ($file) {
                    return file_put_contents($file['name'], $file['name']);
                },
            ],
        ];
    }
}

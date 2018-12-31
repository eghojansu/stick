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

use Fal\Stick\Core;
use Fal\Stick\Web;
use PHPUnit\Framework\TestCase;

class WebTest extends TestCase
{
    private $web;
    private $fw;

    public function setUp()
    {
        $this->web = new Web($this->fw = new Core('phpunit-test'));
    }

    public function tearDown()
    {
        foreach (glob(TEST_TEMP.'uploaded/*') as $file) {
            unlink($file);
        }

        foreach (glob(TEST_TEMP.'uploads/*') as $file) {
            unlink($file);
        }

        foreach ($_FILES as $key => $value) {
            unset($_FILES[$key]);
        }
    }

    public function testGetDiacritics()
    {
        $this->assertNotEmpty($this->web->getDiacritics());
    }

    public function testAddDiacritics()
    {
        $this->assertContains('bar', $this->web->addDiacritics(array('foo' => 'bar'))->getDiacritics());
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
        $this->assertEquals($headers, $this->fw->get('RESPONSE'));
    }

    public function testReceive()
    {
        $_FILES = array(
            'foo' => array(
                'name' => array('foo.txt', 'bar.txt'),
                'type' => array('text/plain', null),
                'size' => array(3, 0, 0),
                'tmp_name' => array(TEST_TEMP.'test-uploads/foo.txt', null),
                'error' => array(UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE),
            ),
        );

        if (!is_dir(TEST_TEMP.'test-uploads')) {
            mkdir(TEST_TEMP.'test-uploads');
        }

        file_put_contents(TEST_TEMP.'test-uploads/foo.txt', 'foo');

        $dir = TEST_TEMP.'uploaded/';
        $expected = array(
            TEST_TEMP.'uploaded/foo.txt' => true,
            TEST_TEMP.'uploaded/bar.txt' => false,
        );

        $this->assertEquals($expected, $this->web->receive($dir, false, null, null, true));
        $this->assertEquals('foo', file_get_contents(TEST_TEMP.'uploaded/foo.txt'));
    }

    /**
     * @dataProvider receivePutFilesProvider
     */
    public function testReceivePut($expected, $file, $uri, $content, $dir, $handler = null)
    {
        $this->fw->set('URI', $uri);
        $this->fw->set('BODY', $content);
        $this->fw->set('VERB', 'PUT');

        $this->assertEquals(array($file => $expected), $this->web->receive($dir, false, $handler, null, true));

        if ($expected) {
            $this->assertEquals($content, file_get_contents($file));
        }
    }

    public function sendProvider()
    {
        return array(
            array(
                -1,
                null,
                '',
                TEST_FIXTURE.'files/unknown.txt',
            ),
            array(
                3128,
                array(
                    'Content-Type' => 'text/plain',
                    'Content-Disposition' => 'attachment; filename="long.txt"',
                    'Accept-Ranges' => 'bytes',
                    'Content-Length' => 3128,
                    'X-Powered-By' => 'Stick-Framework',
                ),
                file_get_contents(TEST_FIXTURE.'files/long.txt'),
                TEST_FIXTURE.'files/long.txt',
            ),
            array(
                3128,
                array(
                    'Content-Type' => 'text/plain',
                    'Content-Disposition' => 'attachment; filename="long.txt"',
                    'Accept-Ranges' => 'bytes',
                    'Content-Length' => 3128,
                    'X-Powered-By' => 'Stick-Framework',
                ),
                file_get_contents(TEST_FIXTURE.'files/long.txt'),
                TEST_FIXTURE.'files/long.txt',
                1024,
            ),
        );
    }

    public function receivePutFilesProvider()
    {
        return array(
            array(
                true,
                TEST_TEMP.'uploaded/foo.txt',
                '/upload/foo.txt',
                'foo',
                TEST_TEMP.'uploaded/',
            ),
            array(
                true,
                TEST_TEMP.'uploaded/foo.txt',
                '/upload/foo.txt',
                TEST_TEMP.'uploaded/foo.txt',
                TEST_TEMP.'uploaded/',
                function ($file) {
                    return file_put_contents($file['name'], $file['name']);
                },
            ),
        );
    }
}

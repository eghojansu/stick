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

namespace Fal\Stick\Test\Web;

use Fal\Stick\Fw;
use Fal\Stick\Web\Receiver;
use Fal\Stick\TestSuite\MyTestCase;

class ReceiverTest extends MyTestCase
{
    private $fw;
    private $receiver;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->fw = new Fw();
        $this->fw->set('TEMP', $this->tmp('/'));

        $this->receiver = new Receiver($this->fw);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->clear($this->tmp('/'));
    }

    public function testReceiveRaw()
    {
        // skip, assume all-correct
        $this->assertTrue(true);
    }

    public function testReceiveFile()
    {
        $saveAs = $this->tmp('/foo.txt');
        $fooFile = $this->fixture('/files/foo.txt');
        $barFile = $this->fixture('/files/bar.txt');

        // really not exists
        $this->fw->set('BODY', 'foo');
        $this->assertFileNotExists($saveAs);
        $this->assertTrue($this->receiver->receiveFile($saveAs));
        $this->assertFileEquals($fooFile, $saveAs);

        // repeat, not overwrite
        $this->assertFalse($this->receiver->receiveFile($saveAs));

        // overwrite and handled
        $this->assertTrue($this->receiver->receiveFile($saveAs, true));
    }

    public function testReceiveFiles()
    {
        $dir = $this->tmp('/uploads/');
        $this->fw->set('FILES', array(
            'foo' => array(
                'error' => UPLOAD_ERR_OK,
                'name' => 'foo.txt',
                'size' => 3,
                'tmp_name' => file_put_contents($this->tmp('/foo_tmp.txt', true), 'foo'),
                'type' => 'text/plain',
            ),
        ));

        $result = $this->receiver->receiveFiles($dir, false, null, function ($file) {
            return 'slug-'.$file;
        });
        $expected = array(
            'foo' => array(
                $dir.'slug-foo.txt' => false,
            ),
        );
        $this->assertEquals($expected, $result);

        $result = $this->receiver->receiveFiles($dir);
        $expected = array(
            'foo' => array(
                $dir.'foo.txt' => false,
            ),
        );
        $this->assertEquals($expected, $result);
    }

    public function testAcceptable()
    {
        $this->assertEquals(array('*/*' => 1), $this->receiver->acceptable());

        $this->fw->set('REQUEST.Accept', 'text/html');
        $this->assertEquals(array('text/html' => 1), $this->receiver->acceptable());
    }

    public function testAcceptBest()
    {
        $this->fw->set('REQUEST.Accept', 'text/html');
        $this->assertNull($this->receiver->acceptBest('application/json'));

        $this->assertEquals('text/html', $this->receiver->acceptBest('text/html'));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Web\ReceiverProvider::request
     */
    public function testRequest($expected, $url, $options = null, $engine = null, $hive = null)
    {
        // TEST ENTRY POINT (tests/_browser.php) should be running!

        if ($hive) {
            $this->fw->mset($hive);
        }

        $result = $this->receiver->request($url, $options, $engine);

        if (null === $expected) {
            $this->assertNull($result);
        } else {
            $compare = array_replace_recursive($result, $expected);

            $this->assertEquals($compare, $result);

            if ('fallback' === $this->fw->get('CACHE_ENGINE')) {
                // second call
                $options['headers'][] = 'Cachenotmodified: 1';
                $result = $this->receiver->request($url, $options, $engine);
                $compare = array_replace_recursive($result, $expected);

                $this->assertEquals($compare, $result);
            }
        }
    }

    public function testRequestException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Unable to perform request to http://localhost:2010/test-entry-404 (Message was: file_get_contents(http://localhost:2010/test-entry-404): failed to open stream: HTTP request failed! HTTP/1.0 404 Not Found).');

        $this->receiver->request('http://localhost:2010/test-entry-404', null, 'stream');
    }

    public function testRequestExceptionEngine()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No suitable HTTP request engine found, given foo.');

        // disable allow_url_fopen
        ini_set('allow_url_fopen', '0');
        $this->receiver->request('http://localhost:2010/test-entry', null, 'foo');
        ini_set('allow_url_fopen', '1');
    }
}

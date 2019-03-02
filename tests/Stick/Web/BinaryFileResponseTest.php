<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Feb 02, 2019 07:31
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web;

use Fal\Stick\Web\Request;
use PHPUnit\Framework\TestCase;
use Fal\Stick\Web\BinaryFileResponse;

class BinaryFileResponseTest extends TestCase
{
    private $response;

    public function setup()
    {
        $this->response = new BinaryFileResponse(TEST_FIXTURE.'files/foo.txt');
    }

    public function testSetFile()
    {
        $this->response->setFile($file = TEST_FIXTURE.'files/foo.txt', 'attachment', true, true);
        $etag = base64_encode(hash_file('sha256', realpath($file), true));
        $date = date('D, d M Y H:i:s', filemtime($file)).' GMT';
        $disposition = 'attachment; filename="foo.txt"';

        $this->assertEquals($file, $this->response->getFile());
        $this->assertEquals('"'.$etag.'"', $this->response->headers->first('ETag'));
        $this->assertEquals($date, $this->response->headers->first('Last-Modified'));
        $this->assertEquals($disposition, $this->response->headers->first('Content-Disposition'));

        // exception
        $this->expectException('LogicException');
        $this->expectExceptionMessage('File must be readable.');

        $this->response->setFile('not-exists.txt');
    }

    public function testGetFile()
    {
        $this->assertEquals(TEST_FIXTURE.'files/foo.txt', $this->response->getFile());
    }

    public function testSetAutoLastModified()
    {
        $modified = date('D, d M Y H:i:s', filemtime(TEST_FIXTURE.'files/foo.txt')).' GMT';

        $this->assertEquals($modified, $this->response->setAutoLastModified()->headers->first('Last-Modified'));
    }

    public function testSetAutoEtag()
    {
        $etag = base64_encode(hash_file('sha256', realpath(TEST_FIXTURE.'files/foo.txt'), true));

        $this->assertEquals('"'.$etag.'"', $this->response->setAutoEtag()->headers->first('ETag'));
    }

    public function testSetContentDisposition()
    {
        $this->assertEquals('foo; filename="bar"', $this->response->setContentDisposition('foo', 'bar')->headers->first('Content-Disposition'));
    }

    public function testPrepare()
    {
        $file = TEST_FIXTURE.'files/foo.txt';
        $expected = array(
            'Content-Type' => array('text/plain'),
            'Content-Length' => array(3),
            'Last-Modified' => array(date('D, d M Y H:i:s', filemtime($file)).' GMT'),
        );

        $this->response->prepare(Request::create('/'));
        $actual = array_intersect_key($expected, $this->response->headers->all());

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider sendContentProvider
     */
    public function testSendContent($expected, $file, $status = null, $kbps = 0)
    {
        $this->expectOutputString($expected);

        $tmp = TEST_TEMP.basename($file);

        if (file_exists($file)) {
            if (file_exists($tmp)) {
                unlink($tmp);
            }

            copy($file, $tmp);
        }

        if ($status) {
            $this->response->status($status);
        }

        $this->response->setKbps($kbps);
        $this->response->setFile($tmp);
        $this->response->deleteFileAfterSend();
        $this->response->send();
    }

    public function testSetContent()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('The content cannot be set on a BinaryFileResponse instance.');

        $this->response->setContent('foo');
    }

    public function testDeleteFileAfterSend()
    {
        $this->assertSame($this->response, $this->response->deleteFileAfterSend());
    }

    public function sendContentProvider()
    {
        return array(
            array('', TEST_FIXTURE.'files/foo.txt', 404),
            array('foo', TEST_FIXTURE.'files/foo.txt'),
            array('foo', TEST_FIXTURE.'files/foo.txt', null, 1),
        );
    }
}

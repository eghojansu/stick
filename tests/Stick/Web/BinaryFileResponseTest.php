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
use Fal\Stick\Web\BinaryFileResponse;
use Fal\Stick\TestSuite\MyTestCase;

class BinaryFileResponseTest extends MyTestCase
{
    private $fw;
    private $binaryFileResponse;

    public function setup(): void
    {
        $this->fw = new Fw();
        $this->binaryFileResponse = new BinaryFileResponse($this->fw);
    }

    public function testGetFilepath()
    {
        $this->assertNull($this->binaryFileResponse->getFilepath());
    }

    public function testSetFilepath()
    {
        $foo = $this->fixture('/files/foo.txt');

        $this->assertEquals($foo, $this->binaryFileResponse->setFilepath($foo)->getFilepath());

        $this->expectException('LogicException');
        $this->expectExceptionMessage('File not exists: foo.');
        $this->binaryFileResponse->setFilepath('foo');
    }

    public function testGetContent()
    {
        $this->assertNull($this->binaryFileResponse->getContent());
    }

    public function testSetContent()
    {
        $this->assertEquals('foo', $this->binaryFileResponse->setContent('foo')->getContent());
    }

    public function testSend()
    {
        $this->expectOutputString('foo');

        $this->fw->hset('foo', 'bar');
        $this->binaryFileResponse->setContent('foo');
        $this->binaryFileResponse->send();

        $this->assertCount(0, $this->fw->get('RESPONSE'));
    }

    public function testSendFile()
    {
        $this->expectOutputString('foo');

        $this->binaryFileResponse->setFilepath($this->fixture('/files/foo.txt'));
        $this->binaryFileResponse->send();
    }

    public function testSendException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Response has no content.');

        $this->binaryFileResponse->send();
    }

    public function testObjectCall()
    {
        $this->expectOutputString('foo');

        $this->binaryFileResponse->setContent('foo');
        ($this->binaryFileResponse)();
    }
}

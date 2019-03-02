<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Feb 02, 2019 08:29
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web;

use Fal\Stick\Web\StreamedResponse;
use PHPUnit\Framework\TestCase;

class StreamedResponseTest extends TestCase
{
    private $response;

    public function setup()
    {
        $this->response = new StreamedResponse(function () {
            echo 'foo';
        });
    }

    public function testSetCallback()
    {
        $this->assertSame($this->response, $this->response->setCallback('trim'));
    }

    public function testSendHeaders()
    {
        $this->assertSame($this->response, $this->response->sendHeaders()->sendHeaders());
    }

    public function testSendContent()
    {
        $this->expectOutputString('foo');

        $this->response->sendContent();

        // second call
        $this->response->sendContent();
    }

    public function testSendContentException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('The Response callback must not be null.');

        $response = new StreamedResponse();
        $response->sendContent();
    }

    public function testSetContent()
    {
        $this->assertSame($this->response, $this->response->setContent(null));
    }

    public function testSetContentException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('The content cannot be set on a StreamedResponse instance.');

        $this->response->setContent('foo');
    }
}

<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Feb 02, 2019 08:46
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web;

use Fal\Stick\Web\ChunkedResponse;
use PHPUnit\Framework\TestCase;

class ChunkedResponseTest extends TestCase
{
    private $response;

    public function setup()
    {
        $this->response = new ChunkedResponse();
    }

    public function testGetKbps()
    {
        $this->assertEquals(0, $this->response->getKbps());
    }

    public function testSetKbps()
    {
        $this->assertEquals(1, $this->response->setKbps(1)->getKbps());
    }

    /**
     * @dataProvider sendProvider
     */
    public function testSend($kbps)
    {
        $this->expectOutputString('foo');

        $this->response->setContent('foo');
        $this->response->setKbps($kbps);

        $this->response->send();
    }

    public function sendProvider()
    {
        return array(
            array(0),
            array(1),
        );
    }
}

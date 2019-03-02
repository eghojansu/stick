<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 30, 2019 12:10
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web;

use Fal\Stick\Web\JsonResponse;
use PHPUnit\Framework\TestCase;

class JsonResponseTest extends TestCase
{
    private $response;

    public function setup()
    {
        $this->response = new JsonResponse(array('foo'));
    }

    public function testGetData()
    {
        $this->assertEquals(array('foo'), $this->response->getData());
    }

    public function testSetContent()
    {
        $this->assertEquals('["bar"]', $this->response->setContent(array('bar'))->getContent());
    }
}

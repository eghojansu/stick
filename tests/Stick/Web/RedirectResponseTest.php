<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 30, 2019 23:40
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web;

use Fal\Stick\Web\RedirectResponse;
use PHPUnit\Framework\TestCase;

class RedirectResponseTest extends TestCase
{
    private $response;

    public function setup()
    {
        $this->response = new RedirectResponse('foo');
    }

    public function testGetTargetUrl()
    {
        $this->assertEquals('foo', $this->response->getTargetUrl());
    }

    public function testSetTargetUrl()
    {
        $expected = str_replace('{target}', 'bar', file_get_contents(TEST_FIXTURE.'files/redirect.html'));

        $this->assertEquals('bar', $this->response->setTargetUrl('bar')->getTargetUrl());
        $this->assertEquals(array('bar'), $this->response->headers->get('Location'));
        $this->assertEquals($expected, $this->response->getContent());
    }

    public function testSetTargetUrlException()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('Cannot redirect to an empty URL.');

        $this->response->setTargetUrl('');
    }

    public function testConstructException()
    {
        $this->expectException('InvalidArgumentException');
        $this->expectExceptionMessage('The HTTP status code is not a redirect ("404" given).');

        new RedirectResponse('foo', 404);
    }
}

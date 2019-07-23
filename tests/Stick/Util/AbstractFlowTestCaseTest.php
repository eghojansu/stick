<?php

/**
 * This file is part of the eghojansu/stick.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Util;

use Fal\Stick\Fw;
use Fal\Stick\Util\AbstractFlowTestCase;

class AbstractFlowTestCaseTest extends AbstractFlowTestCase
{
    public function setup(): void
    {
        $this->fw = new Fw(array(
            'QUIET' => true,
        ));
        $this->fw->route('GET|POST|PUT home /', function (Fw $fw) {
            return $fw->VERB.' '.$fw->ALIAS;
        });
        $this->fw->route('GET /info', function (Fw $fw) {
            return $fw->status(100);
        });
        $this->fw->route('GET /redirect', function (Fw $fw) {
            return $fw->reroute('home');
        });
        $this->fw->route('GET /bad', function (Fw $fw) {
            return $fw->error(400);
        });
        $this->fw->route('GET /server', function (Fw $fw) {
            return $fw->error(500);
        });
        $this->fw->route('GET /none', function (Fw $fw) {
            return $fw->error(404);
        });
        $this->fw->route('GET /forbidden', function (Fw $fw) {
            return $fw->error(403);
        });
        $this->fw->route('GET /allowed', function (Fw $fw) {
            return $fw->error(405);
        });
    }

    public function testFirst()
    {
        $this->assertEquals(0, $this->first()->ptr);
    }

    public function testPrev()
    {
        $this->visit('/')->post('home');

        $this->assertEquals('GET home', $this->prev()->get('OUTPUT'));
    }

    public function testNext()
    {
        $this->visit('/')->post('home');

        $this->assertEquals('POST home', $this->prev()->next()->get('OUTPUT'));
    }

    public function testLast()
    {
        $this->visit('/')->post('home');

        $this->assertEquals('POST home', $this->first()->last()->get('OUTPUT'));
    }

    public function testGet()
    {
        $this->assertNull($this->get('status'));
    }

    public function testAll()
    {
        $this->assertCount(0, $this->all());
    }

    public function testRequestRoute()
    {
        $this->assertEquals('', $this->requestRoute());
    }

    public function testRequest()
    {
        $this->request('GET', '/');
        $this->request('GET', 'home');

        $this->assertCount(2, $this->history);
        $this->assertEquals('GET', $this->get('VERB'));
        $this->assertEquals($this->history[0][1], $this->history[1][1]);
    }

    public function testVisit()
    {
        $this->visit('/');

        $this->assertCount(1, $this->history);
        $this->assertEquals('GET', $this->get('VERB'));
    }

    public function testPost()
    {
        $this->post('/');

        $this->assertCount(1, $this->history);
        $this->assertEquals('POST', $this->get('VERB'));
    }

    public function testExpectInformational()
    {
        $this->visit('/info')->expectInformational();
    }

    public function testExpectSuccessful()
    {
        $this->visit('/')->expectSuccessful();
    }

    public function testExpectRedirection()
    {
        $this->visit('/redirect')->expectRedirection();
    }

    public function testExpectRequestError()
    {
        $this->visit('/bad')->expectRequestError();
    }

    public function testExpectServerError()
    {
        $this->visit('/server')->expectServerError();
    }

    public function testExpectNotFound()
    {
        $this->visit('/none')->expectNotFound();
    }

    public function testExpectForbidden()
    {
        $this->visit('/forbidden')->expectForbidden();
    }

    public function testExpectNotAllowed()
    {
        $this->visit('/allowed')->expectNotAllowed();
    }

    public function testExpectStatusCode()
    {
        $this->visit('/')->expectStatusCode(200);
    }

    public function testExpectStatus()
    {
        $this->visit('/')->expectStatus('OK');
    }

    public function testExpectStatusContains()
    {
        $this->visit('/')->expectStatusContains('OK');
    }

    public function testExpectOutput()
    {
        $this->visit('/')->expectOutput('GET home');
    }

    public function testExpectOutputContains()
    {
        $this->visit('/')->expectOutputContains('GET home');
    }

    public function testExpectUri()
    {
        $this->visit('/info')->expectUri('/info');
    }

    public function testExpectUriContains()
    {
        $this->visit('/info')->expectUriContains('/info');
    }

    public function testExpectTrue()
    {
        $this->visit('/')->expectTrue(function ($res) {
            return 'GET home' === $res['OUTPUT'];
        });
    }

    public function testExpectFalse()
    {
        $this->visit('/')->expectFalse(function ($res) {
            return 'GET home' !== $res['OUTPUT'];
        });
    }

    public function testExpectEquals()
    {
        $this->visit('/')->expectEquals('ALIAS', 'home');
    }

    public function testExpectContains()
    {
        $this->visit('/')->expectContains('OUTPUT', 'home');
    }
}

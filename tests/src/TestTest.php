<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Dec 08, 2018 09:59
 */

declare(strict_types=1);

namespace Fal\Stick\Test;

use Fal\Stick\Fw;
use Fal\Stick\Test;
use PHPUnit\Framework\TestCase;

class TestTest extends TestCase
{
    private $fw;
    private $test;

    public function setUp()
    {
        $this->test = new Test($this->fw = new Fw('phpunit-test'));
        $this->fw
            ->route('GET /foo', function () {
                return 'foo';
            })
            ->route('GET /home', function () {
                return file_get_contents(TEST_FIXTURE.'response/home.html');
            })
            ->route('POST /home', function (Fw $fw) {
                if ($data = $fw->get('POST.data')) {
                    return 'Data valid: '.$data;
                }

                return 'Data invalid';
            })
        ;
    }

    public function testGetFw()
    {
        $this->assertSame($this->fw, $this->test->getFw());
    }

    public function testLatest()
    {
        $this->assertFalse($this->test->latest());
    }

    public function testReset()
    {
        $this->assertSame($this->test, $this->test->reset());
    }

    public function testRequest()
    {
        $this->assertSame($this->test, $this->test->request('GET', '/home', null, true));
        $this->assertEquals('/home', $this->fw->get('PATH'));
        $this->assertTrue($this->fw->get('AJAX'));
        $this->assertFalse($this->fw->get('CLI'));

        $this->test->request('GET', '/home', null, false, true);
        $this->assertFalse($this->fw->get('AJAX'));
        $this->assertTrue($this->fw->get('CLI'));
    }

    public function testVisit()
    {
        $this->assertSame($this->test, $this->test->visit('/home'));
        $this->assertEquals('/home', $this->fw->get('PATH'));
    }

    public function testPost()
    {
        $this->assertSame($this->test, $this->test->post('/home'));
        $this->assertEquals('Data invalid', $this->fw->get('OUTPUT'));

        $this->test->post('/home', array('data' => 'foo'));
        $this->assertEquals('Data valid: foo', $this->fw->get('OUTPUT'));
    }

    public function testForm()
    {
        $this->test->visit('/home');

        $this->assertSame($this->test, $this->test->form('Submit'));
    }

    public function testFormException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Form with button "Cancel" is not found.');

        $this->test->visit('/home');
        $this->test->form('Cancel');
    }

    public function testSubmit()
    {
        $this->test->visit('/home');
        $this->test->form('Submit');
        $this->test->submit(array('data' => 'foo'));

        $this->assertEquals('Data valid: foo', $this->fw->get('OUTPUT'));
    }

    public function testSubmitException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No form selected.');

        $this->test->visit('/home');
        $this->test->submit(array('data' => 'foo'));
    }

    public function testExpect()
    {
        $this->assertTrue($this->test->expect(true, 'equals')->latest());
    }

    public function testIsCode()
    {
        $this->assertTrue($this->test->isCode(200)->latest());
    }

    public function testIsNotCode()
    {
        $this->assertTrue($this->test->isNotCode(500)->latest());
    }

    public function testIsSuccess()
    {
        $this->assertTrue($this->test->isSuccess()->latest());
    }

    public function testIsNotSuccess()
    {
        $this->assertFalse($this->test->isNotSuccess()->latest());
    }

    public function testIsFound()
    {
        $this->assertTrue($this->test->isFound()->latest());
    }

    public function testIsNotFound()
    {
        $this->assertFalse($this->test->isNotFound()->latest());
    }

    public function testIsForbidden()
    {
        $this->assertFalse($this->test->isForbidden()->latest());
    }

    public function testIsNotForbidden()
    {
        $this->assertTrue($this->test->isNotForbidden()->latest());
    }

    public function testIsServerError()
    {
        $this->assertFalse($this->test->isServerError()->latest());
    }

    public function testIsNotServerError()
    {
        $this->assertTrue($this->test->isNotServerError()->latest());
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->test->isEmpty()->latest());
    }

    public function testIsNotEmpty()
    {
        $this->assertFalse($this->test->isNotEmpty()->latest());
    }

    public function testIsEquals()
    {
        $this->assertTrue($this->test->visit('/foo')->isEquals('foo')->latest());
    }

    public function testIsNotEquals()
    {
        $this->assertFalse($this->test->visit('/foo')->isNotEquals('foo')->latest());
    }

    public function testContains()
    {
        $this->assertTrue($this->test->visit('/foo')->contains('oo')->latest());
    }

    public function testNotContains()
    {
        $this->assertFalse($this->test->visit('/foo')->notContains('oo')->latest());
    }

    public function testRegexp()
    {
        $this->assertTrue($this->test->visit('/foo')->regexp('/^foo$/')->latest());
    }

    public function testNotRegexp()
    {
        $this->assertFalse($this->test->visit('/foo')->notRegexp('/^foo$/')->latest());
    }

    public function testElementExists()
    {
        $this->assertTrue($this->test->visit('/home')->elementExists('*//h1')->latest());
    }

    public function testElementNotExists()
    {
        $this->assertFalse($this->test->visit('/home')->elementNotExists('*//h1')->latest());
    }

    public function testElementEquals()
    {
        $this->assertTrue($this->test->visit('/home')->elementEquals('*//h1', 'My Home')->latest());
    }

    public function testElementNotEquals()
    {
        $this->assertFalse($this->test->visit('/home')->elementNotEquals('*//h1', 'My Home')->latest());
    }

    public function testElementContains()
    {
        $this->assertTrue($this->test->visit('/home')->elementContains('*//h1', 'Home')->latest());
    }

    public function testElementNotContains()
    {
        $this->assertFalse($this->test->visit('/home')->elementNotContains('*//h1', 'Home')->latest());
    }

    public function testElementRegexp()
    {
        $this->assertTrue($this->test->visit('/home')->elementRegexp('*//h1', '/^My Home$/')->latest());
    }

    public function testElementNotRegexp()
    {
        $this->assertFalse($this->test->visit('/home')->elementNotRegexp('*//h1', '/^My Home$/')->latest());
    }

    public function testFindXpathWhenNoResponse()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No response to parse.');

        $this->test->elementExists('foo');
    }
}

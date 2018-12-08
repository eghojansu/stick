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
    private $tester;

    public function setUp()
    {
        $this->tester = new Test($this->fw = new Fw('phpunit-test'));
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

    public function testLatest()
    {
        $this->assertFalse($this->tester->latest());
    }

    public function testReset()
    {
        $this->assertSame($this->tester, $this->tester->reset());
    }

    public function testRequest()
    {
        $this->assertSame($this->tester, $this->tester->request('GET', '/home', null, true));
        $this->assertEquals('/home', $this->fw->get('PATH'));
        $this->assertTrue($this->fw->get('AJAX'));
        $this->assertFalse($this->fw->get('CLI'));

        $this->tester->request('GET', '/home', null, false, true);
        $this->assertFalse($this->fw->get('AJAX'));
        $this->assertTrue($this->fw->get('CLI'));
    }

    public function testVisit()
    {
        $this->assertSame($this->tester, $this->tester->visit('/home'));
        $this->assertEquals('/home', $this->fw->get('PATH'));
    }

    public function testPost()
    {
        $this->assertSame($this->tester, $this->tester->post('/home'));
        $this->assertEquals('Data invalid', $this->fw->get('OUTPUT'));

        $this->tester->post('/home', array('data' => 'foo'));
        $this->assertEquals('Data valid: foo', $this->fw->get('OUTPUT'));
    }

    public function testForm()
    {
        $this->tester->visit('/home');

        $this->assertSame($this->tester, $this->tester->form('Submit'));
    }

    public function testFormException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Form with button "Cancel" is not found.');

        $this->tester->visit('/home');
        $this->tester->form('Cancel');
    }

    public function testSubmit()
    {
        $this->tester->visit('/home');
        $this->tester->form('Submit');
        $this->tester->submit(array('data' => 'foo'));

        $this->assertEquals('Data valid: foo', $this->fw->get('OUTPUT'));
    }

    public function testSubmitException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No form selected.');

        $this->tester->visit('/home');
        $this->tester->submit(array('data' => 'foo'));
    }

    public function testExpect()
    {
        $this->assertTrue($this->tester->expect(true, 'equals')->latest());
    }

    public function testIsCode()
    {
        $this->assertTrue($this->tester->isCode(200)->latest());
    }

    public function testIsNotCode()
    {
        $this->assertTrue($this->tester->isNotCode(500)->latest());
    }

    public function testIsSuccess()
    {
        $this->assertTrue($this->tester->isSuccess()->latest());
    }

    public function testIsNotSuccess()
    {
        $this->assertFalse($this->tester->isNotSuccess()->latest());
    }

    public function testIsFound()
    {
        $this->assertTrue($this->tester->isFound()->latest());
    }

    public function testIsNotFound()
    {
        $this->assertFalse($this->tester->isNotFound()->latest());
    }

    public function testIsForbidden()
    {
        $this->assertFalse($this->tester->isForbidden()->latest());
    }

    public function testIsNotForbidden()
    {
        $this->assertTrue($this->tester->isNotForbidden()->latest());
    }

    public function testIsServerError()
    {
        $this->assertFalse($this->tester->isServerError()->latest());
    }

    public function testIsNotServerError()
    {
        $this->assertTrue($this->tester->isNotServerError()->latest());
    }

    public function testIsEmpty()
    {
        $this->assertTrue($this->tester->isEmpty()->latest());
    }

    public function testIsNotEmpty()
    {
        $this->assertFalse($this->tester->isNotEmpty()->latest());
    }

    public function testIsEquals()
    {
        $this->assertTrue($this->tester->visit('/foo')->isEquals('foo')->latest());
    }

    public function testIsNotEquals()
    {
        $this->assertFalse($this->tester->visit('/foo')->isNotEquals('foo')->latest());
    }

    public function testContains()
    {
        $this->assertTrue($this->tester->visit('/foo')->contains('oo')->latest());
    }

    public function testNotContains()
    {
        $this->assertFalse($this->tester->visit('/foo')->notContains('oo')->latest());
    }

    public function testRegexp()
    {
        $this->assertTrue($this->tester->visit('/foo')->regexp('/^foo$/')->latest());
    }

    public function testNotRegexp()
    {
        $this->assertFalse($this->tester->visit('/foo')->notRegexp('/^foo$/')->latest());
    }

    public function testElementExists()
    {
        $this->assertTrue($this->tester->visit('/home')->elementExists('*//h1')->latest());
    }

    public function testElementNotExists()
    {
        $this->assertFalse($this->tester->visit('/home')->elementNotExists('*//h1')->latest());
    }

    public function testElementEquals()
    {
        $this->assertTrue($this->tester->visit('/home')->elementEquals('*//h1', 'My Home')->latest());
    }

    public function testElementNotEquals()
    {
        $this->assertFalse($this->tester->visit('/home')->elementNotEquals('*//h1', 'My Home')->latest());
    }

    public function testElementContains()
    {
        $this->assertTrue($this->tester->visit('/home')->elementContains('*//h1', 'Home')->latest());
    }

    public function testElementNotContains()
    {
        $this->assertFalse($this->tester->visit('/home')->elementNotContains('*//h1', 'Home')->latest());
    }

    public function testElementRegexp()
    {
        $this->assertTrue($this->tester->visit('/home')->elementRegexp('*//h1', '/^My Home$/')->latest());
    }

    public function testElementNotRegexp()
    {
        $this->assertFalse($this->tester->visit('/home')->elementNotRegexp('*//h1', '/^My Home$/')->latest());
    }

    public function testFindXpathWhenNoResponse()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No response to parse.');

        $this->tester->elementExists('foo');
    }
}

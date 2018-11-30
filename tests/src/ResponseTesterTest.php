<?php

namespace Fal\Stick\Test;

use Fal\Stick\Fw;
use Fal\Stick\ResponseTester;
use PHPUnit\Framework\TestCase;

class ResponseTesterTest extends TestCase
{
    private $fw;
    private $tester;

    public function setUp()
    {
        $this->tester = new ResponseTester($this->fw = new Fw());
        $this->fw
            ->route('GET /foo', function () {
                return 'foo';
            })
            ->route('GET /home', function () {
                return file_get_contents(FIXTURE.'response/home.html');
            })
            ->route('POST /home', function (Fw $fw) {
                if (isset($fw['POST']['data']) && $fw['POST']['data']) {
                    return 'Data valid: '.$fw['POST']['data'];
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
        $this->assertEquals('/home', $this->fw['PATH']);
        $this->assertTrue($this->fw['AJAX']);
        $this->assertFalse($this->fw['CLI']);

        $this->tester->request('GET', '/home', null, false, true);
        $this->assertFalse($this->fw['AJAX']);
        $this->assertTrue($this->fw['CLI']);
    }

    public function testVisit()
    {
        $this->assertSame($this->tester, $this->tester->visit('/home'));
        $this->assertEquals('/home', $this->fw['PATH']);
    }

    public function testPost()
    {
        $this->assertSame($this->tester, $this->tester->post('/home'));
        $this->assertEquals('Data invalid', $this->fw['OUTPUT']);

        $this->tester->post('/home', array('data' => 'foo'));
        $this->assertEquals('Data valid: foo', $this->fw['OUTPUT']);
    }

    public function testForm()
    {
        $this->tester->visit('/home');

        $this->assertSame($this->tester, $this->tester->form('Submit'));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Form with button "Cancel" is not found.
     */
    public function testFormException()
    {
        $this->tester->visit('/home');
        $this->tester->form('Cancel');
    }

    public function testSubmit()
    {
        $this->tester->visit('/home');
        $this->tester->form('Submit');
        $this->tester->submit(array('data' => 'foo'));

        $this->assertEquals('Data valid: foo', $this->fw['OUTPUT']);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage No form selected.
     */
    public function testSubmitException()
    {
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

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage No response to parse.
     */
    public function testFindXpathWhenNoResponse()
    {
        $this->tester->elementExists('foo');
    }
}

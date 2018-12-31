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
            ->route('GET foo /foo', function () {
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
            ->route('GET login /login', function () {
                return 'Login';
            })
            ->route('POST login', function (Fw $fw) {
                return $fw->get('POST.username') ? 'Login Success' : 'Login Failed';
            })
            ->route('GET /none', function () {
                return null;
            })
        ;
    }

    public function testGetFw()
    {
        $this->assertSame($this->fw, $this->test->getFw());
    }

    public function testSuccess()
    {
        $this->assertFalse($this->test->success());
    }

    public function testClear()
    {
        $this->assertSame($this->test, $this->test->clear());
    }

    public function testRequest()
    {
        $this->assertSame($this->test, $this->test->request('GET', '/home', null, array('ajax' => true)));
        $this->assertEquals('/home', $this->fw->get('PATH'));
        $this->assertTrue($this->fw->get('AJAX'));
        $this->assertFalse($this->fw->get('CLI'));

        $this->test->request('GET', '/home', null, array('cli' => true));
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

        $this->assertInstanceOf('DomNode', $this->test->form('Submit')->response('form'));
        $this->assertInstanceOf('DomNode', $this->test->form('Login', false)->response('form'));
    }

    public function testFormException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Form with button labeled "Cancel" is not found.');

        $this->test->visit('/home');
        $this->test->form('Cancel');
    }

    public function testSubmit()
    {
        $this->test->visit('/home');
        $this->test->form('Submit');
        $this->test->submit(array('data' => 'foo'));

        $this->assertEquals('Data valid: foo', $this->fw->get('OUTPUT'));

        $this->test->visit('/home');
        $this->test->form('Login', false);
        $this->test->submit(array('username' => 'foo'));

        $this->assertEquals('Login Success', $this->fw->get('OUTPUT'));
    }

    public function testSubmitException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No form selected.');

        $this->test->visit('/home');
        $this->test->submit(array('data' => 'foo'));
    }

    public function testClick()
    {
        $this->test->visit('/home');
        $this->test->click('Login');

        $this->assertEquals('Login', $this->fw->get('OUTPUT'));
    }

    public function testClickException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Link labeled "Register" not exists.');

        $this->test->visit('/home');
        $this->test->click('Register');
    }

    public function testMoveTo()
    {
        $this->test->visit('/foo');
        $this->test->visit('/home');

        $this->assertEquals('foo', $this->test->moveTo(0)->response());
    }

    public function testMoveToException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No response at position: 1.');

        $this->test->moveTo(1);
    }

    public function testFirst()
    {
        $this->test->visit('/foo');
        $this->test->visit('/home');

        $this->assertEquals('foo', $this->test->first()->response());
    }

    public function testFirstException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No response at position: 0.');

        $this->test->first();
    }

    public function testLast()
    {
        $this->test->visit('/home');
        $this->test->visit('/foo');

        $this->assertEquals('foo', $this->test->last()->response());
    }

    public function testLastException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No response at position: -1.');

        $this->test->last();
    }

    public function testPrev()
    {
        $this->test->visit('/foo');
        $this->test->visit('/home');

        $this->assertEquals('foo', $this->test->prev()->response());
    }

    public function testPrevException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Cannot move backward!');

        $this->test->prev();
    }

    public function testNext()
    {
        $this->test->visit('/home');
        $this->test->visit('/foo');

        $this->assertEquals('foo', $this->test->prev()->next()->response());
    }

    public function testNextException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Cannot move forward!');

        $this->test->next();
    }

    public function testExpect()
    {
        $this->assertTrue($this->test->expect(true, 'equals')->success());
    }

    public function testExpectNull()
    {
        $this->assertTrue($this->test->expectNull(null)->success());
        $this->assertFalse($this->test->expectNull('')->success());
    }

    public function testExpectNotNull()
    {
        $this->assertTrue($this->test->expectNotNull('')->success());
        $this->assertFalse($this->test->expectNotNull(null)->success());
    }

    public function testExpectTrue()
    {
        $this->assertTrue($this->test->expectTrue(true)->success());
        $this->assertFalse($this->test->expectTrue(false)->success());
    }

    public function testExpectNotTrue()
    {
        $this->assertTrue($this->test->expectNotTrue(false)->success());
        $this->assertFalse($this->test->expectNotTrue(true)->success());
    }

    public function testExpectFalse()
    {
        $this->assertTrue($this->test->expectFalse(false)->success());
        $this->assertFalse($this->test->expectFalse(true)->success());
    }

    public function testExpectNotFalse()
    {
        $this->assertTrue($this->test->expectNotFalse(true)->success());
        $this->assertFalse($this->test->expectNotFalse(false)->success());
    }

    public function testExpectEmpty()
    {
        $this->assertTrue($this->test->expectEmpty('')->success());
        $this->assertFalse($this->test->expectEmpty('foo')->success());
    }

    public function testExpectNotEmpty()
    {
        $this->assertTrue($this->test->expectNotEmpty('foo')->success());
        $this->assertFalse($this->test->expectNotEmpty('')->success());
    }

    public function testExpectEquals()
    {
        $this->assertTrue($this->test->expectEquals('foo', 'foo')->success());
        $this->assertFalse($this->test->expectEquals('foo', 'bar')->success());
    }

    public function testExpectNotEquals()
    {
        $this->assertTrue($this->test->expectNotEquals('foo', 'bar')->success());
        $this->assertFalse($this->test->expectNotEquals('foo', 'foo')->success());
    }

    public function testExpectSame()
    {
        $this->assertTrue($this->test->expectSame('foo', 'foo')->success());
        $this->assertFalse($this->test->expectSame('foo', 'bar')->success());
    }

    public function testExpectNotSame()
    {
        $this->assertTrue($this->test->expectNotSame('foo', 'bar')->success());
        $this->assertFalse($this->test->expectNotSame('foo', 'foo')->success());
    }

    public function testExpectContains()
    {
        $this->assertTrue($this->test->expectContains('foo', 'foobar')->success());
        $this->assertTrue($this->test->expectContains('foo', array('foo', 'bar'))->success());
        $this->assertFalse($this->test->expectContains('baz', 'foobar')->success());
    }

    public function testExpectNotContains()
    {
        $this->assertTrue($this->test->expectNotContains('baz', 'foobar')->success());
        $this->assertTrue($this->test->expectNotContains('baz', array('foo', 'bar'))->success());
        $this->assertFalse($this->test->expectNotContains('foo', 'foobar')->success());
    }

    public function testExpectRegexp()
    {
        $this->assertTrue($this->test->expectRegexp('/^foo$/', 'foo')->success());
        $this->assertFalse($this->test->expectRegexp('/^bar$/', 'foo')->success());
    }

    public function testExpectNotRegexp()
    {
        $this->assertTrue($this->test->expectNotRegexp('/^bar$/', 'foo')->success());
        $this->assertFalse($this->test->expectNotRegexp('/^foo$/', 'foo')->success());
    }

    public function testHiveTrue()
    {
        $this->assertTrue($this->test->hiveTrue('CLI')->success());
    }

    public function testHiveNotTrue()
    {
        $this->assertTrue($this->test->hiveNotTrue('AJAX')->success());
    }

    public function testHiveFalse()
    {
        $this->assertTrue($this->test->hiveFalse('AJAX')->success());
    }

    public function testHiveNotFalse()
    {
        $this->assertTrue($this->test->hiveNotFalse('CLI')->success());
    }

    public function testHiveNull()
    {
        $this->assertTrue($this->test->hiveNull('foo')->success());
    }

    public function testHiveNotNull()
    {
        $this->assertTrue($this->test->hiveNotNull('CLI')->success());
    }

    public function testHiveEmpty()
    {
        $this->assertTrue($this->test->hiveEmpty('foo')->success());
    }

    public function testHiveNotEmpty()
    {
        $this->assertTrue($this->test->hiveNotEmpty('CLI')->success());
    }

    public function testHiveEquals()
    {
        $this->assertTrue($this->test->hiveEquals('CLI', true)->success());
    }

    public function testHiveNotEquals()
    {
        $this->assertTrue($this->test->hiveNotEquals('CLI', false)->success());
    }

    public function testHiveContains()
    {
        $this->fw->set('foo', 'foobar');

        $this->assertTrue($this->test->hiveContains('foo', 'bar')->success());
    }

    public function testHiveNotContains()
    {
        $this->fw->set('foo', 'foobaz');

        $this->assertTrue($this->test->hiveNotContains('foo', 'bar')->success());
    }

    public function testHiveRegexp()
    {
        $this->fw->set('foo', 'bar');

        $this->assertTrue($this->test->hiveRegexp('foo', '/^bar$/')->success());
    }

    public function testHiveNotRegexp()
    {
        $this->fw->set('foo', 'baz');

        $this->assertTrue($this->test->hiveNotRegexp('foo', '/^bar$/')->success());
    }

    public function testAtRoute()
    {
        $this->test->visit('/foo');

        $this->assertTrue($this->test->atRoute('foo')->success());
    }

    public function testNotAtRoute()
    {
        $this->test->visit('/foo');

        $this->assertFalse($this->test->notAtRoute('foo')->success());
    }

    public function testAtPath()
    {
        $this->test->visit('/foo');

        $this->assertTrue($this->test->atPath('/foo')->success());
    }

    public function testNotAtPath()
    {
        $this->test->visit('/foo');

        $this->assertFalse($this->test->notAtPath('/foo')->success());
    }

    public function testParametersEquals()
    {
        $this->test->visit('/foo');

        $this->assertTrue($this->test->parametersEquals(array())->success());
    }

    public function testParametersNotEquals()
    {
        $this->test->visit('/foo');

        $this->assertFalse($this->test->parametersNotEquals(array())->success());
    }

    public function testWithParameters()
    {
        $this->test->visit('/foo');

        $this->assertTrue($this->test->withParameters(array())->success());
    }

    public function testCheck()
    {
        $this->assertTrue($this->test->check(function () { $this->test->expect(true, 'equals'); })->success());
    }

    public function testIsCode()
    {
        $this->test->visit('/foo');

        $this->assertTrue($this->test->isCode(200)->success());
    }

    public function testIsNotCode()
    {
        $this->test->visit('/foo');

        $this->assertTrue($this->test->isNotCode(500)->success());
    }

    public function testIsSuccessful()
    {
        $this->test->visit('/foo');

        $this->assertTrue($this->test->isSuccessful()->success());
    }

    public function testIsNotSuccessful()
    {
        $this->test->visit('/foo');

        $this->assertFalse($this->test->isNotSuccessful()->success());
    }

    public function testIsFound()
    {
        $this->test->visit('/foo');

        $this->assertTrue($this->test->isFound()->success());
    }

    public function testIsNotFound()
    {
        $this->test->visit('/foo');

        $this->assertFalse($this->test->isNotFound()->success());
    }

    public function testIsForbidden()
    {
        $this->test->visit('/foo');

        $this->assertFalse($this->test->isForbidden()->success());
    }

    public function testIsNotForbidden()
    {
        $this->test->visit('/foo');

        $this->assertTrue($this->test->isNotForbidden()->success());
    }

    public function testIsServerError()
    {
        $this->test->visit('/foo');

        $this->assertFalse($this->test->isServerError()->success());
    }

    public function testIsNotServerError()
    {
        $this->test->visit('/foo');

        $this->assertTrue($this->test->isNotServerError()->success());
    }

    public function testIsEmpty()
    {
        $this->test->visit('/none');

        $this->assertTrue($this->test->isEmpty()->success());
    }

    public function testIsNotEmpty()
    {
        $this->test->visit('/foo');

        $this->assertTrue($this->test->isNotEmpty()->success());
    }

    public function testIsEquals()
    {
        $this->test->visit('/foo');

        $this->assertTrue($this->test->visit('/foo')->isEquals('foo')->success());
    }

    public function testIsNotEquals()
    {
        $this->test->visit('/foo');

        $this->assertFalse($this->test->visit('/foo')->isNotEquals('foo')->success());
    }

    public function testContains()
    {
        $this->test->visit('/foo');

        $this->assertTrue($this->test->visit('/foo')->contains('oo')->success());
    }

    public function testNotContains()
    {
        $this->test->visit('/foo');

        $this->assertFalse($this->test->visit('/foo')->notContains('oo')->success());
    }

    public function testRegexp()
    {
        $this->test->visit('/foo');

        $this->assertTrue($this->test->visit('/foo')->regexp('/^foo$/')->success());
    }

    public function testNotRegexp()
    {
        $this->test->visit('/foo');

        $this->assertFalse($this->test->visit('/foo')->notRegexp('/^foo$/')->success());
    }

    public function testElementExists()
    {
        $this->test->visit('/home');

        $this->assertTrue($this->test->elementExists('h1')->success());
        $this->assertTrue($this->test->elementExists('*//h1', false)->success());
    }

    public function testElementNotExists()
    {
        $this->test->visit('/home');

        $this->assertFalse($this->test->elementNotExists('h1')->success());
        $this->assertFalse($this->test->elementNotExists('*//h1', false)->success());
    }

    public function testElementEquals()
    {
        $this->test->visit('/home');

        $this->assertTrue($this->test->elementEquals('h1', 'My Home')->success());
        $this->assertTrue($this->test->elementEquals('*//h1', 'My Home', false)->success());
    }

    public function testElementNotEquals()
    {
        $this->test->visit('/home');

        $this->assertFalse($this->test->elementNotEquals('h1', 'My Home')->success());
        $this->assertFalse($this->test->elementNotEquals('*//h1', 'My Home', false)->success());
    }

    public function testElementContains()
    {
        $this->test->visit('/home');

        $this->assertTrue($this->test->elementContains('h1', 'Home')->success());
        $this->assertTrue($this->test->elementContains('*//h1', 'Home', false)->success());
    }

    public function testElementNotContains()
    {
        $this->test->visit('/home');

        $this->assertFalse($this->test->elementNotContains('h1', 'Home')->success());
        $this->assertFalse($this->test->elementNotContains('*//h1', 'Home', false)->success());
    }

    public function testElementRegexp()
    {
        $this->test->visit('/home');

        $this->assertTrue($this->test->elementRegexp('h1', '/^My Home$/')->success());
        $this->assertTrue($this->test->elementRegexp('*//h1', '/^My Home$/', false)->success());
    }

    public function testElementNotRegexp()
    {
        $this->test->visit('/home');

        $this->assertFalse($this->test->elementNotRegexp('h1', '/^My Home$/')->success());
        $this->assertFalse($this->test->elementNotRegexp('*//h1', '/^My Home$/', false)->success());
    }

    public function testLinkExists()
    {
        $this->test->visit('/home');

        $this->assertTrue($this->test->linkExists('Foo Link')->success());
        $this->assertTrue($this->test->linkExists(' Going None')->success());
        $this->assertFalse($this->test->linkExists('None')->success());
    }

    public function testLinkNotExists()
    {
        $this->test->visit('/home');

        $this->assertFalse($this->test->linkNotExists('Foo Link')->success());
        $this->assertFalse($this->test->linkNotExists(' Going None')->success());
        $this->assertTrue($this->test->linkNotExists('None')->success());
    }

    public function testResponse()
    {
        $this->test->visit('/foo');

        $this->assertEquals('foo', $this->test->response());
        $this->assertEquals('/foo', $this->test->response('path'));
    }

    public function testResponseException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('No response set! Please make request first.');

        $this->test->response();
    }

    public function testFindElements()
    {
        $this->test->visit('/home');

        $this->assertInstanceOf('DomNodeList', $list = $this->test->findElements('h2'));
        $this->assertCount(0, $list);

        $this->assertInstanceOf('DomNodeList', $list = $this->test->findElements('h1'));
        $this->assertCount(1, $list);

        $this->assertInstanceOf('DomNodeList', $list = $this->test->findElements('*//h1', false));
        $this->assertCount(1, $list);
    }

    public function testFindElementsException()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Response is empty!');

        $this->test->visit('/none');
        $this->test->findElements('h1');
    }

    public function testFindFirstElement()
    {
        $this->test->visit('/home');

        $this->assertNull($this->test->findFirstElement('h2'));
        $this->assertInstanceOf('DomNode', $this->test->findFirstElement('h1'));
        $this->assertInstanceOf('DomNode', $this->test->findFirstElement('*//h1', false));
    }
}

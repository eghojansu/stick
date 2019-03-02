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

use Fal\Stick\Web\Request;
use Fal\Stick\Web\RequestStack;
use PHPUnit\Framework\TestCase;

class RequestStackTest extends TestCase
{
    private $stack;

    public function setup()
    {
        $this->stack = new RequestStack();
    }

    public function testPush()
    {
        $request = Request::create('/');

        $this->assertSame($request, $this->stack->push($request)->getMasterRequest());
    }

    public function testPop()
    {
        $request = Request::create('/');

        $this->stack->push($request);

        $this->assertSame($request, $this->stack->pop());

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Request stack empty!');
        $this->stack->pop();
    }

    public function testGetCurrentRequest()
    {
        $request = Request::create('/');

        $this->stack->push($request);

        $this->assertSame($request, $this->stack->getCurrentRequest());
        $this->stack->pop();

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Request stack empty!');
        $this->stack->getCurrentRequest();
    }

    public function testGetMasterRequest()
    {
        $request = Request::create('/');

        $this->stack->push($request);

        $this->assertSame($request, $this->stack->getMasterRequest());
        $this->stack->pop();

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Request stack empty!');
        $this->stack->getMasterRequest();
    }

    public function testCount()
    {
        $this->assertEquals(0, $this->stack->count());
    }
}

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

namespace Fal\Stick\Test\Web\Router;

use Fal\Stick\Web\Router\RouteMatch;
use PHPUnit\Framework\TestCase;

class RouteMatchTest extends TestCase
{
    private $match;

    public function setup()
    {
        $this->match = new RouteMatch('foo', null, array('bar'), 'baz', array('qux'));
    }

    public function testGetPattern()
    {
        $this->assertEquals('foo', $this->match->getPattern());
    }

    public function testGetAlias()
    {
        $this->assertNull($this->match->getAlias());
    }

    public function testGetAllowedMethods()
    {
        $this->assertEquals(array('bar'), $this->match->getAllowedMethods());
    }

    public function testGetController()
    {
        $this->assertEquals('baz', $this->match->getController());
    }

    public function testGetArguments()
    {
        $this->assertEquals(array('qux'), $this->match->getArguments());
    }
}

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

namespace Fal\Stick\Test\Cli;

use Fal\Stick\Cli\InputArgument;
use Fal\Stick\TestSuite\MyTestCase;

class InputArgumentTest extends MyTestCase
{
    private $argument;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->argument = new InputArgument('foo');
    }

    public function testCreate()
    {
        $this->assertInstanceOf('Fal\\Stick\\Cli\\InputArgument', InputArgument::create('foo'));
    }

    public function testGetName()
    {
        $this->assertEquals('foo', $this->argument->getName());
    }

    public function testGetDescription()
    {
        $this->assertEquals('', $this->argument->getDescription());
    }

    public function testGetDefaultValue()
    {
        $this->assertNull($this->argument->getDefaultValue());
    }

    public function testGetValueRequirement()
    {
        $this->assertEquals(0, $this->argument->getValueRequirement());
    }
}

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

use Fal\Stick\Cli\InputOption;
use Fal\Stick\TestSuite\MyTestCase;

class InputOptionTest extends MyTestCase
{
    private $option;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->option = new InputOption('foo');
    }

    public function testCreate()
    {
        $this->assertInstanceOf('Fal\\Stick\\Cli\\InputOption', InputOption::create('foo'));
    }

    public function testGetName()
    {
        $this->assertEquals('foo', $this->option->getName());
    }

    public function testGetAlias()
    {
        $this->assertEquals('', $this->option->getAlias());
    }

    public function testGetDescription()
    {
        $this->assertEquals('', $this->option->getDescription());
    }

    public function testGetDefaultValue()
    {
        $this->assertNull($this->option->getDefaultValue());
    }

    public function testGetValueRequirement()
    {
        $this->assertEquals(0, $this->option->getValueRequirement());
    }
}

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

namespace Fal\Stick\Test\Cli;

use Fal\Stick\Cli\Input;
use Fal\Stick\TestSuite\MyTestCase;

class InputTest extends MyTestCase
{
    private $input;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->input = new Input(
            array(
                'arg1' => 'foo',
            ),
            array(
                'opt1' => 'foo',
            )
        );
    }

    public function testGetArgument()
    {
        $this->assertEquals('foo', $this->input->getArgument('arg1'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Argument not exists: foo.');

        $this->input->getArgument('foo');
    }

    public function testGetArguments()
    {
        $this->assertCount(1, $this->input->getArguments());
    }

    public function testGetOption()
    {
        $this->assertEquals('foo', $this->input->getOption('opt1'));

        $this->expectException('LogicException');
        $this->expectExceptionMessage('Option not exists: foo.');

        $this->input->getOption('foo');
    }

    public function testGetOptions()
    {
        $this->assertCount(1, $this->input->getOptions());
    }
}

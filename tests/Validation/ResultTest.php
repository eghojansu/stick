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

namespace Ekok\Stick\Tests\Validation;

use PHPUnit\Framework\TestCase;
use Ekok\Stick\Validation\Result;

class ResultTest extends TestCase
{
    public function testIsUsable()
    {
        $result = new Result();
        $result->addData('foo', 'bar');
        $result->addError('foo', array('bar'));
        $result->addErrors(array('bar' => array('baz')));

        $this->assertCount(0, $result->getRaw());
        $this->assertCount(1, $result->getData());
        $this->assertCount(2, $result->getErrors());
        $this->assertCount(1, $result->getError('foo'));
        $this->assertTrue($result->invalid());
        $this->assertFalse($result->valid());
    }
}

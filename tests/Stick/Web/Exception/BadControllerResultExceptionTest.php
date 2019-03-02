<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 30, 2019 12:37
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web\Exception;

use PHPUnit\Framework\TestCase;
use Fal\Stick\Web\Exception\BadControllerResultException;

class BadControllerResultExceptionTest extends TestCase
{
    private $exception;

    public function setup()
    {
        $this->exception = new BadControllerResultException();
    }

    public function testGetMessage()
    {
        $this->assertEquals('', $this->exception->getMessage());
    }
}

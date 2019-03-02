<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Jan 30, 2019 12:40
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Web\Exception;

use PHPUnit\Framework\TestCase;
use Fal\Stick\Web\Exception\ForbiddenException;

class ForbiddenExceptionTest extends TestCase
{
    private $exception;

    public function setup()
    {
        $this->exception = new ForbiddenException();
    }

    public function testGetStatusCode()
    {
        $this->assertEquals(403, $this->exception->getStatusCode());
    }
}

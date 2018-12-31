<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Dec 05, 2018 10:50
 */

declare(strict_types=1);

namespace Fal\Stick\Test;

use Fal\Stick as f;
use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    public function testIncludeFile()
    {
        $this->assertEquals('foo', f\includeFile(TEST_FIXTURE.'files/foo.php'));
    }

    public function testRequireFile()
    {
        $this->assertEquals('foo', f\requireFile(TEST_FIXTURE.'files/foo.php'));
    }
}

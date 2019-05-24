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

namespace Fal\Stick\Test\Db\Pdo;

use Fal\Stick\Db\Pdo\DbUtil;
use Fal\Stick\TestSuite\MyTestCase;

class DbUtilTest extends MyTestCase
{
    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Db\Pdo\DbUtilProvider::defaultValue
     */
    public function testDefaultValue($expected, $value)
    {
        $this->assertEquals($expected, DbUtil::defaultValue($value));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Db\Pdo\DbUtilProvider::type
     */
    public function testType($expected, $val, $type = null, $hybrid = true)
    {
        $this->assertEquals($expected, DbUtil::type($val, $type, $hybrid));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Db\Pdo\DbUtilProvider::value
     */
    public function testValue($expected, $val, $type = null)
    {
        $this->assertEquals($expected, DbUtil::value($val, $type));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Db\Pdo\DbUtilProvider::extractType
     */
    public function testExtractType($expected, $type)
    {
        $this->assertEquals($expected, DbUtil::extractType($type));
    }
}

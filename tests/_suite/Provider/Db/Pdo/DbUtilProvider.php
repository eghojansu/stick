<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\TestSuite\Provider\Db\Pdo;

class DbUtilProvider
{
    public function defaultValue()
    {
        return array(
            array('foo', 'foo'),
            array('foo', '"foo"'),
            array('foo', "'foo'"),
            array(null, 'null'),
            array(null, 'NULL'),
            array(0, 0),
        );
    }

    public function type()
    {
        return array(
            array(0, null, 'null'),
            array(0, null),
            array(1, 1),
            array(2, 'foo'),
            array(3, null, 'blob'),
            array(5, true),
            array(-1, 0.5),
            array(2, 0.5, null, false),
        );
    }

    public function value()
    {
        return array(
            'null' => array(null, null),
            'bool' => array(true, true),
            'int' => array(1, 1),
            'float' => array(0.5, 0.5),
            'string' => array('foo', 'foo'),
            'blob' => array('foo', 'foo', 3),
            'null #2' => array(null, 'foo', 0),
            'int #2' => array(1, '1', 1),
            'default' => array('1', '1', -10),
        );
    }
}

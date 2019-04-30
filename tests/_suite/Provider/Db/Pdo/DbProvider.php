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

class DbProvider
{
    public function prepare()
    {
        return array(
            array(true, 'select * from user'),
            array('Query empty!', '', 'LogicException'),
            array('PDO: [HY000 - 1] incomplete input.', 'select', 'LogicException'),
        );
    }
}

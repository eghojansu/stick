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

namespace Ekok\Stick\Sql\QueryBuilder;

/**
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class MySqlQueryBuilder extends AbstractQueryBuilder
{
    protected $options = array(
        'host' => 'localhost',
        'port' => 3306,
        'dbname' => null,
        'username' => 'root',
        'password' => null,
        'options' => null,
        'commands' => null,
        'dsn_suffix' => null,
    );

    public function getDsn(): string
    {
        list('host' => $host, 'port' => $port, 'dbname' => $name, 'dsn_suffix' => $suffix) = $this->options;

        if ($port) {
            $suffix = ';port='.$port.$suffix;
        }

        return "mysql:host={$host};dbname={$name}".$suffix;
    }
}

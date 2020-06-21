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

namespace Ekok\Stick\Database\QueryBuilder;

/**
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class SqliteQueryBuilder extends AbstractQueryBuilder
{
    protected $options = array(
        'path' => ':memory:',
        'sqlite2' => false,
        'username' => 'root',
        'password' => null,
        'options' => null,
        'commands' => null,
    );

    public function getDsn(): string
    {
        list(
            'path' => $path,
            'sqlite2' => $sqlite2) = $this->options;

        return sprintf('sqlite%s:%s', $sqlite2 ? '2' : '', $path);
    }
}

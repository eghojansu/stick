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

namespace Fal\Stick\Db\Pdo\Driver;

use Fal\Stick\Db\Pdo\DbUtil;
use Fal\Stick\Db\Pdo\Schema;

/**
 * Sqlite driver.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class SqliteDriver extends MysqlDriver
{
    /**
     * {@inheritdoc}
     */
    public function resolveDbName(string $dsn): string
    {
        return preg_match('/^sqlite(?:\d+)?:(.+)/i', $dsn, $match) ? $match[1] : '';
    }

    /**
     * {@inheritdoc}
     */
    public function sqlSchema(string $db, string $table): string
    {
        return 'PRAGMA table_info('.$this->quote($table).')';
    }

    /**
     * {@inheritdoc}
     */
    public function buildSchema(array $rows): Schema
    {
        $schema = new Schema();

        foreach ($rows as $field) {
            $schema->set($field['name'], array(
                'default' => DbUtil::defaultValue($field['dflt_value']),
                'nullable' => 0 == $field['notnull'],
                'pkey' => 0 < intval($field['pk']),
                'type' => $field['type'],
                'pdo_type' => DbUtil::type(null, $field['type']),
            ) + DbUtil::extractType($field['type']));
        }

        return $schema;
    }
}

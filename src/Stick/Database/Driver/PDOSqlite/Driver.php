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

namespace Fal\Stick\Database\Driver\PDOSqlite;

use Fal\Stick\Database\Driver\AbstractPDOSqlDriver;
use Fal\Stick\Database\Row;
use Fal\Stick\Database\Field;

/**
 * Sqlite database driver.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Driver extends AbstractPDOSqlDriver
{
    /**
     * {@inheritdoc}
     */
    protected function extraOptions(array $options = null): array
    {
        return array(
            'path' => ':memory:',
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createDsn(): string
    {
        return sprintf('sqlite:%s', $this->options['path']);
    }

    /**
     * {@inheritdoc}
     */
    protected function createSchema(string $table, array $fields): Row
    {
        $schema = new Row($table);
        $command = 'PRAGMA table_info('.$table.')';
        $query = $this->pdo()->query($command);

        foreach ($query->fetchAll(\PDO::FETCH_ASSOC) as $field) {
            $name = $field['name'];

            if ($fields && !in_array($name, $fields)) {
                continue;
            }

            $item = new Field($name, null, $this->schemaDefaultValue($field['dflt_value']));
            $item->nullable = 0 === intval($field['notnull']);
            $item->pkey = 0 < intval($field['pk']);
            $item->extras = array(
                'type' => $field['type'],
                'pdo_type' => $this->pdoType(null, $field['type']),
            );

            $schema->setField($item);
        }

        return $schema;
    }
}

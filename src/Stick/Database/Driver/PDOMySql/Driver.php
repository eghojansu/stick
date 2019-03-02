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

namespace Fal\Stick\Database\Driver\PDOMySql;

use Fal\Stick\Database\Driver\AbstractPDOSqlDriver;
use Fal\Stick\Database\Row;
use Fal\Stick\Database\Field;

/**
 * MySQL database driver.
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
            'username' => 'root',
            'host' => 'localhost',
            'port' => 3306,
            'dbname' => null,
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createDsn(): string
    {
        return sprintf('mysql:host=%s;port=%s;dbname=%s', $this->options['host'], $this->options['port'], $this->options['dbname']);
    }

    /**
     * {@inheritdoc}
     */
    protected function createSchema(string $table, array $fields): Row
    {
        $db = $this->options['dbname'];

        if (strpos($table, '.')) {
            list($db, $table) = explode('.', $table);
        }

        $schema = new Row($table);
        $command = 'SHOW columns FROM '.$this->quotekey($db).'.'.$this->quotekey($table);
        $query = $this->pdo()->query($command);

        foreach ($query->fetchAll(\PDO::FETCH_ASSOC) as $field) {
            $name = $field['Field'];

            if ($fields && !in_array($name, $fields)) {
                continue;
            }

            $item = new Field($name, null, $this->schemaDefaultValue($field['Default']));
            $item->nullable = 'YES' === $field['Null'];
            $item->pkey = 'PRI' === $field['Key'];
            $item->extras = array(
                'type' => $field['Type'],
                'pdo_type' => $this->pdoType(null, $field['Type']),
            );

            $schema->setField($item);
        }

        return $schema;
    }
}

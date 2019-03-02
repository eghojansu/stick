<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Feb 06, 2019 03:42
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Database\Driver\PDOSqlite;

use Fal\Stick\Cache\NoCache;
use Fal\Stick\Database\Driver\PDOSqlite\Driver;
use Fal\Stick\Database\Field;
use Fal\Stick\Database\Row;
use Fal\Stick\Logging\Logger;
use PHPUnit\Framework\TestCase;

class DriverTest extends TestCase
{
    public function testAbstractMethods()
    {
        $driver = new Driver(new NoCache(), new Logger());

        // schema
        $driver->pdo()->exec(file_get_contents(TEST_FIXTURE.'files/schema_sqlite.sql'));

        $schema = $driver->schema('user', array('fields' => 'id,username'));
        $id = new Field('id', null);
        $id->nullable = false;
        $id->pkey = true;
        $id->extras = array(
            'type' => 'INTEGER',
            'pdo_type' => \PDO::PARAM_INT,
        );

        $username = new Field('username', null);
        $username->nullable = false;
        $username->extras = array(
            'type' => 'TEXT',
            'pdo_type' => \PDO::PARAM_STR,
        );

        $expected = new Row('user');
        $expected->setField($id);
        $expected->setField($username);

        $this->assertEquals($expected, $schema);
    }
}

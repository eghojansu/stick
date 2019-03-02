<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Created at Feb 06, 2019 03:49
 */

declare(strict_types=1);

namespace Fal\Stick\Test\Database\Driver\PDOMySql;

use Fal\Stick\Cache\NoCache;
use Fal\Stick\Database\Driver\PDOMySql\Driver;
use Fal\Stick\Database\Field;
use Fal\Stick\Database\Row;
use Fal\Stick\Logging\Logger;
use PHPUnit\Framework\TestCase;

class DriverTest extends TestCase
{
    public function testAbstractMethods()
    {
        $driver = new Driver(new NoCache(), new Logger(), array(
            'host' => TEST_MYSQL_HOST,
            'port' => TEST_MYSQL_PORT,
            'username' => TEST_MYSQL_USER,
            'password' => TEST_MYSQL_PASS,
            'dbname' => TEST_MYSQL_DBNAME,
        ));

        // schema
        $driver->pdo()->exec(file_get_contents(TEST_FIXTURE.'files/schema_mysql.sql'));

        $schema = $driver->schema(TEST_MYSQL_DBNAME.'.user', array('fields' => 'id,username'));

        $id = new Field('id', null);
        $id->nullable = false;
        $id->pkey = true;
        $id->extras = array(
            'type' => 'int(11)',
            'pdo_type' => \PDO::PARAM_INT,
        );

        $username = new Field('username', null);
        $username->nullable = false;
        $username->extras = array(
            'type' => 'varchar(100)',
            'pdo_type' => \PDO::PARAM_STR,
        );

        $expected = new Row('user');
        $expected->setField($id);
        $expected->setField($username);

        $this->assertEquals($expected, $schema);
    }
}

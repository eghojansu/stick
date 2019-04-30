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

use Fal\Stick\Fw;
use Fal\Stick\Db\Pdo\Db;
use Fal\Stick\Db\Pdo\MapperRule;
use Fal\Stick\TestSuite\MyTestCase;
use Fal\Stick\Db\Pdo\Driver\SqliteDriver;

class MapperRuleTest extends MyTestCase
{
    private $fw;
    private $db;

    public function setup(): void
    {
        $this->fw = new Fw();
        $this->db = new Db($this->fw, new SqliteDriver(), 'sqlite::memory:', null, null, array(
            $this->read('/files/schema_sqlite.sql'),
            'insert into user (username) values ("foo"), ("bar"), ("baz")',
        ));
    }

    protected function createInstance()
    {
        return new MapperRule($this->db);
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\RuleProvider::mapperValidateExists
     */
    public function testValidateExists($expected, $value)
    {
        $this->assertEquals($expected, $this->mapperRule->validate('exists', $value));
    }

    /**
     * @dataProvider Fal\Stick\TestSuite\Provider\Validation\RuleProvider::mapperValidateUnique
     */
    public function testValidateUnique($expected, $value)
    {
        $this->assertEquals($expected, $this->mapperRule->validate('unique', $value));
    }
}

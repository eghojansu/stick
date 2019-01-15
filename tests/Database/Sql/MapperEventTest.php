<?php

/*
 * This file is part of the eghojansu/stick library.
 *
 *  (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Database\Sql;

use Fal\Stick\Core;
use Fal\Stick\Database\Sql\Mapper;
use Fal\Stick\Database\Sql\MapperEvent;
use Fal\Stick\Database\Sql\Sql;
use PHPUnit\Framework\TestCase;

class MapperEventTest extends TestCase
{
    private $event;

    public function setup()
    {
        $core = new Core();
        $db = new Sql(
            $core,
            'sqlite::memory:',
            null,
            null,
            [file_get_contents(TEST_FIXTURE.'/files/schema.sql')]
        );
        $this->event = new MapperEvent(new Mapper($core, $db, 'user'));
    }

    public function testGetMapper()
    {
        $this->assertInstanceOf('Fal\\Stick\\Database\\Sql\\Mapper', $this->event->getMapper());
    }
}

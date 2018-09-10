<?php

/**
 * This file is part of the eghojansu/stick library.
 *
 * (c) Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fal\Stick\Test\Sql;

use Fal\Stick\App;
use Fal\Stick\Sql\Connection;
use Fal\Stick\Sql\Mapper;
use Fal\Stick\Sql\MapperEvent;
use PHPUnit\Framework\TestCase;

class MapperEventTest extends TestCase
{
    private $event;

    public function setUp()
    {
        $app = new App();
        $conn = new Connection($app, array(
            'dsn' => 'sqlite::memory:',
            'commands' => file_get_contents(FIXTURE.'files/schema.sql'),
        ));
        $mapper = new Mapper($app, $conn, 'user');
        $this->event = new MapperEvent($mapper);
    }

    public function testGetMapper()
    {
        $dt = new \DateTime();
        $this->event->getMapper()->set('foo', $dt);

        $this->assertSame($dt, $this->event->getMapper()->get('foo'));
    }
}

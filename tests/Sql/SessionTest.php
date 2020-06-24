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

namespace Ekok\Stick\Tests\Sql;

use Ekok\Stick\Fw;
use Ekok\Stick\Sql\QueryBuilder\SqliteQueryBuilder;
use Ekok\Stick\Sql\Session;
use Ekok\Stick\Sql\Sql;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @covers \Ekok\Stick\Sql\Session
 */
final class SessionTest extends TestCase
{
    private $qb;
    private $fw;
    private $sql;
    private $session;

    protected function setUp(): void
    {
        $this->qb = new SqliteQueryBuilder(array(
            'commands' => array(
                'create table sessions ('.
                    'id integer not null primary key autoincrement,'.
                    'session_id text not null,'.
                    'data text not null,'.
                    'ip text not null,'.
                    'agent text not null,'.
                    'stamp integer not null'.
                ')',
            ),
        ));
        $this->fw = new Fw();
        $this->fw->set('LOG.directory', TEST_TEMP.'/logs_session/');
        $this->sql = new Sql($this->fw, $this->qb);

        $this->session = new Session($this->fw, $this->sql);

        testRemoveTemp('logs_session');
    }

    public function testRegister()
    {
        $this->assertTrue(true, 'skipped');
    }

    public function testSid()
    {
        $this->assertNull($this->session->sid());
    }

    public function testClose()
    {
        $this->assertTrue($this->session->close());
    }

    public function testDestroy()
    {
        $this->assertEquals(0, $this->session->destroy('id'));
    }

    public function testGc()
    {
        $this->assertEquals(0, $this->session->gc(0));
    }

    public function testOpen()
    {
        $this->assertTrue($this->session->open('foo', 'id'));
    }

    public function testRead()
    {
        $session_id = 'foo';

        $this->assertEquals('', $this->session->read($session_id));
    }

    public function testWrite()
    {
        $session_id = 'foo';
        $session_data = serialize(array('id' => 'foo'));

        $this->assertTrue($this->session->write($session_id, $session_data));

        $session_data_old = $this->session->read($session_id);
        $session_data_new = serialize(array('id' => 'bar'));

        $this->assertEquals($session_data_old, $session_data);
        $this->assertTrue($this->session->write($session_id, $session_data_new));

        // attempt to read suspicious data
        $this->fw->set('IP', 'new-ip');
        $this->fw->on('session.suspect', function ($fw, $data) {
            return false;
        });

        $session_data_new = $this->session->read($session_id);
        $this->assertEquals('', $session_data_new);
    }
}

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
use Fal\Stick\Db\Pdo\Mapper;
use Fal\Stick\Db\Pdo\Session;
use Fal\Stick\TestSuite\MyTestCase;
use Fal\Stick\Db\Pdo\Driver\SqliteDriver;

class SessionTest extends MyTestCase
{
    private $fw;
    private $session;
    private $db;
    private $mapper;

    public function setup(): void
    {
        $this->fw = new Fw();
        $this->db = new Db($this->fw, new SqliteDriver(), 'sqlite::memory:', null, null, array(
            $this->read('/files/schema_sqlite.sql'),
        ));
        $this->mapper = new Mapper($this->db, 'session');
        $this->session = new Session($this->fw, $this->mapper, false);
    }

    private function insert($time = null, $sid = null, $data = null)
    {
        $this->mapper->reset()->fromArray(array(
            'session_id' => $sid ?? 'sid1',
            'data' => $data ?? 'foo',
            'ip' => $this->fw->IP,
            'agent' => $this->fw->AGENT,
            'stamp' => $time ?? time(),
        ))->save();
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
        $this->insert();

        $this->assertCount(1, $this->mapper->find());
        $this->assertTrue($this->session->destroy('sid1'));
        $this->assertCount(0, $this->mapper->find());
    }

    public function testGc()
    {
        // five seconds ago
        $this->insert(time() - 5);
        // ten seconds ago
        $this->insert(time() - 10, 'sid2');

        $this->assertCount(2, $this->mapper->find());
        $this->assertEquals(1, $this->session->gc(5));
        $this->assertCount(1, $this->mapper->find());
    }

    public function testOpen()
    {
        $this->assertTrue($this->session->open('foo', 'bar'));
    }

    public function testRead()
    {
        $this->insert();

        $this->assertEquals('foo', $this->session->read('sid1'));
        $this->assertEquals('', $this->session->read('sid2'));

        $this->fw->on('session.suspect', function ($session) {
            return false;
        });

        $this->fw->AGENT = 'foo';
        $this->assertEquals('', $this->session->read('sid1'));

        $this->assertEquals($this->response('error.txt', array(
            '%verb%' => 'GET',
            '%code%' => 403,
            '%path%' => '/',
            '%text%' => 'Forbidden',
        )), $this->fw->get('OUTPUT'));
    }

    public function testWrite()
    {
        $this->assertTrue($this->session->write('sid1', 'foo'));
        $this->assertEquals('foo', $this->mapper->findOne()->get('data'));
    }

    public function testConstruct()
    {
        $this->expectException('LogicException');
        $this->expectExceptionMessage('Invalid session mapper schema.');

        $mapper = new Mapper($this->db, 'nokey');
        new Session($this->fw, $mapper);
    }
}

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

namespace Ekok\Stick\Sql;

use Ekok\Stick\Fw;

/**
 * Sql based session handler.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Session implements \SessionHandlerInterface
{
    const EVENT_SUSPECT = 'session.suspect';

    /**
     * @var Fw
     */
    protected $fw;

    /**
     * @var Sql
     */
    protected $sql;

    /**
     * @var string
     */
    protected $table;

    /**
     * @var array
     */
    protected $data;

    public function __construct(Fw $fw, Sql $sql, string $table = 'sessions')
    {
        $this->fw = $fw;
        $this->sql = $sql;
        $this->table = $table;
    }

    /** @codeCoverageIgnore */
    public static function register(Fw $fw, Sql $sql, string $table = 'sessions'): Session
    {
        $self = new static($fw, $sql, $table);

        session_set_save_handler($self);

        return $self;
    }

    /**
     * Returns current session id.
     */
    public function sid(): ?string
    {
        return $this->data['session_id'] ?? null;
    }

    /**
     * {inheritdoc}.
     */
    public function close()
    {
        $this->data = null;

        return true;
    }

    /**
     * {inheritdoc}.
     */
    public function destroy($session_id)
    {
        return $this->sql->delete($this->table, array('session_id = ?', $session_id)) > 0;
    }

    /**
     * {inheritdoc}.
     */
    public function gc($maxlifetime)
    {
        return $this->sql->delete($this->table, array('stamp < ?', time() - $maxlifetime));
    }

    /**
     * {inheritdoc}.
     */
    public function open($save_path, $session_name)
    {
        return true;
    }

    /**
     * {inheritdoc}.
     */
    public function read($session_id)
    {
        $this->data = $this->sql->findOne($this->table, array('session_id = ?', $session_id));

        if (!$this->data) {
            return '';
        }

        $sessIP = $this->data['ip'];
        $sessAgent = $this->data['agent'];
        $userIP = $this->fw['IP'];
        $userAgent = $this->fw['AGENT'];

        $changed = $sessIP !== $userIP || $sessAgent !== $userAgent;

        // if changed, onsuspect should returns false to prevent further session handling
        if ($changed && $this->fw->dispatch(static::EVENT_SUSPECT, array($this->fw, $this->data), $continue) && !$continue) {
            $this->destroy($session_id);
            $this->close();
            $this->fw->rem('COOKIE.'.session_name());
            $this->fw->error(403);

            return '';
        }

        return $this->data['data'];
    }

    /**
     * {inheritdoc}.
     */
    public function write($session_id, $session_data)
    {
        if ($this->data) {
            $changed = $this->sql->update($this->table, array(
                'data' => $session_data,
            ), array('session_id = ?', $session_id));
        } else {
            $changed = $this->sql->insert($this->table, array(
                'session_id' => $session_id,
                'data' => $session_data,
                'ip' => $this->fw['IP'],
                'agent' => $this->fw['AGENT'],
                'stamp' => time(),
            ));
        }

        return $changed > 0;
    }
}

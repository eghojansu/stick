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

namespace Fal\Stick\Db\Pdo;

use Fal\Stick\Fw;

/**
 * Mapper based session handler.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 */
class Session implements \SessionHandlerInterface
{
    // Events
    const EVENT_SUSPECT = 'session.suspect';

    /**
     * @var Fw
     */
    public $fw;

    /**
     * @var Mapper
     */
    public $mapper;

    /**
     * @var string
     */
    protected $sid;

    /**
     * Class constructor.
     *
     * @param Fw     $fw
     * @param Mapper $mapper
     * @param bool   $register
     */
    public function __construct(Fw $fw, Mapper $mapper, bool $register = true)
    {
        $schema = $mapper->schema;

        if (!isset($schema['session_id'], $schema['data'], $schema['ip'], $schema['agent'], $schema['stamp'])) {
            throw new \LogicException('Invalid session mapper schema.');
        }

        $this->fw = $fw;
        $this->mapper = clone $mapper;

        !$register || session_set_save_handler($this);
    }

    /**
     * Returns current session id.
     *
     * @return string|null
     */
    public function sid(): ?string
    {
        return $this->sid;
    }

    /**
     * {inheritdoc}.
     */
    public function close()
    {
        $this->mapper->reset();
        $this->sid = null;

        return true;
    }

    /**
     * {inheritdoc}.
     */
    public function destroy($session_id)
    {
        return 0 < $this->mapper->deleteAll(compact('session_id'));
    }

    /**
     * {inheritdoc}.
     */
    public function gc($maxlifetime)
    {
        return $this->mapper->deleteAll(array('stamp <' => time() - $maxlifetime));
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
        $this->sid = $session_id;

        if ($this->mapper->findOne(compact('session_id'))->dry()) {
            return '';
        }

        $data = $this->mapper->data;
        $changed = $this->fw->IP !== $this->mapper->ip || $this->fw->AGENT !== $this->mapper->agent;

        // if changed, onsuspect should returns false to prevent further session handling
        if ($changed) {
            $dispatch = $this->fw->dispatch(self::EVENT_SUSPECT, $this);

            if (!$dispatch || false === $dispatch[0]) {
                $data = '';

                $this->destroy($session_id);
                $this->close();
                $this->fw->cookie(session_name());
                $this->fw->error(403);
            }
        }

        return $data;
    }

    /**
     * {inheritdoc}.
     */
    public function write($session_id, $session_data)
    {
        return $this->mapper->fromArray(array(
            'session_id' => $session_id,
            'data' => $session_data,
            'ip' => $this->fw->IP,
            'agent' => $this->fw->AGENT,
            'stamp' => time(),
        ))->save();
    }
}

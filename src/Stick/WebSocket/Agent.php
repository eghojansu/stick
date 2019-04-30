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

namespace Fal\Stick\WebSocket;

/**
 * RFC6455 remote socket.
 *
 * Ported from F3/Ws.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * @codeCoverageIgnore
 */
class Agent
{
    // Mask bits for first byte of header
    const MASK_TEXT = 0x01;
    const MASK_BINARY = 0x02;
    const MASK_CLOSE = 0x08;
    const MASK_PING = 0x09;
    const MASK_PONG = 0x0a;
    const MASK_OPCODE = 0x0f;
    const MASK_FINALE = 0x80;

    // Mask bits for second byte of header
    const MASK_LENGTH = 0x7f;

    // Events
    const EVENT_IDLE = 'agent.idle';
    const EVENT_CONNECT = 'agent.connect';
    const EVENT_DISCONNECT = 'agent.disconnect';
    const EVENT_SEND = 'agent.send';
    const EVENT_RECEIVE = 'agent.receive';

    /**
     * @var Server
     */
    public $server;

    /**
     * Socket name.
     *
     * @var string
     */
    protected $id;

    /**
     * @var resource
     */
    protected $socket;

    /**
     * @var string
     */
    protected $verb;

    /**
     * @var string
     */
    protected $uri;

    /**
     * @var array
     */
    protected $headers;

    /**
     * Class constructor.
     *
     * @param Server   $server
     * @param resource $socket
     * @param string   $verb
     * @param string   $uri
     * @param array    $headers
     */
    public function __construct(Server $server, $socket, string $verb, string $uri, array $headers)
    {
        $this->server = $server;
        $this->id = stream_socket_get_name($socket, true);
        $this->socket = $socket;
        $this->verb = $verb;
        $this->uri = $uri;
        $this->headers = $headers;

        $server->fw->dispatch(self::EVENT_CONNECT, $this);
    }

    /**
     * Destroy object.
     */
    public function __destruct()
    {
        $this->server->fw->dispatch(self::EVENT_DISCONNECT, $this);
    }

    /**
     * Return socket ID.
     *
     * @return string
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Return socket.
     *
     * @return resource
     */
    public function socket()
    {
        return $this->socket;
    }

    /**
     * Return request method.
     *
     * @return string
     */
    public function verb(): string
    {
        return $this->verb;
    }

    /**
     * Return request URI.
     *
     * @return string
     */
    public function uri(): string
    {
        return $this->uri;
    }

    /**
     * Return socket headers.
     *
     * @return array
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Frame and transmit payload.
     *
     * @param int    $op
     * @param string $data
     *
     * @return string
     */
    public function send(int $op, string $data = ''): string
    {
        $mask = self::MASK_FINALE | $op & self::MASK_OPCODE;
        $len = strlen($data);
        $buffer = '';

        if ($len > 0xffff) {
            $buffer = pack('CCNN', $mask, 0x7f, $len);
        } elseif ($len > 0x7d) {
            $buffer = pack('CCn', $mask, 0x7e, $len);
        } else {
            $buffer = pack('CC', $mask, $len);
        }

        $buffer .= $data;

        if ($this->server->write($this->socket, $buffer) < 0) {
            return '';
        }

        if (!in_array($op, array(self::MASK_PONG, self::MASK_CLOSE))) {
            $this->server->fw->dispatch(self::EVENT_SEND, $this, $op, $data);
        }

        return $data;
    }

    /**
     * Retrieve and unmask payload.
     *
     * @return array|null
     */
    public function fetch(): ?array
    {
        // Unmask payload
        if (null === $buffer = $this->server->read($this->socket)) {
            return null;
        }

        $op = ord($buffer[0]) & self::MASK_OPCODE;
        $len = ord($buffer[1]) & self::MASK_LENGTH;
        $pos = 2;

        if (0x7e == $len) {
            $len = ord($buffer[2]) * 256 + ord($buffer[3]);
            $pos += 2;
        } elseif (0x7f == $len) {
            for ($i = 0,$len = 0; $i < 8; ++$i) {
                $len = $len * 256 + ord($buffer[$i + 2]);
            }
            $pos += 8;
        }

        for ($i = 0,$mask = array(); $i < 4; ++$i) {
            $mask[$i] = ord($buffer[$pos + $i]);
        }

        $pos += 4;

        if (strlen($buffer) < $len + $pos) {
            return null;
        }

        for ($i = 0,$data = ''; $i < $len; ++$i) {
            $data .= chr(ord($buffer[$pos + $i]) ^ $mask[$i % 4]);
        }

        // Dispatch
        switch ($op & self::MASK_OPCODE) {
            case self::MASK_PING:
                $this->send(self::MASK_PONG);
                break;
            case self::MASK_CLOSE:
                $this->server->close($this->socket);
                break;
            case self::MASK_TEXT:
                $data = trim($data);
                // no break
            case self::MASK_BINARY:
                $this->server->fw->dispatch(self::EVENT_RECEIVE, $this, $op, $data);
                break;
        }

        return array($op, $data);
    }
}

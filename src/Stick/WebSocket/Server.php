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

use Fal\Stick\Fw;

/**
 * RFC6455 WebSocket server.
 *
 * Ported from F3/Ws.
 *
 * @author Eko Kurniawan <ekokurniawanbs@gmail.com>
 *
 * @codeCoverageIgnore
 */
class Server
{
    // UUID magic string
    const UUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    // Max packet size
    const MAX_PACKET = 65536;

    // Events
    const EVENT_ERROR = 'server.error';
    const EVENT_START = 'server.start';
    const EVENT_STOP = 'server.stop';

    /**
     * @var Fw
     */
    public $fw;

    /**
     * Server address.
     *
     * @var string
     */
    protected $address;

    /**
     * @var resource
     */
    protected $context;

    /**
     * Wait time.
     *
     * @var int
     */
    protected $wait;

    /**
     * @var array
     */
    protected $sockets = array();

    /**
     * @var array
     */
    protected $agents = array();

    /**
     * Class constructor.
     *
     * @param Fw            $fw
     * @param string        $address
     * @param resource|null $context
     * @param int           $wait
     */
    public function __construct(Fw $fw, string $address, $context = null, int $wait = 60)
    {
        $this->fw = $fw;
        $this->address = $address;
        $this->context = $context ?? stream_context_create();
        $this->wait = $wait;
    }

    /**
     * Allocate stream socket.
     *
     * @param resource $socket
     */
    public function alloc($socket): void
    {
        if (null === $buffer = $this->read($socket)) {
            return;
        }

        // Get WebSocket headers
        $headers = array();
        $eol = "\r\n";
        $verb = null;
        $uri = null;
        $sid = (int) $socket;

        foreach (explode($eol, trim($buffer)) as $line) {
            if (preg_match('/^(\w+)\s(.+)\sHTTP\/1\.\d$/', trim($line), $match)) {
                $verb = $match[1];
                $uri = $match[2];
            } elseif (preg_match('/^(.+): (.+)/', trim($line), $match)) {
                // Standardize header
                $headers[Fw::dashCase($match[1])] = $match[2];
            } else {
                $this->close($socket);

                return;
            }
        }

        if (empty($headers['Upgrade']) && empty($headers['Sec-Websocket-Key'])) {
            // Not a WebSocket request
            if ($verb && $uri) {
                $this->write($socket, 'HTTP/1.1 400 Bad Request'.$eol.'Connection: close'.$eol.$eol);
            }

            $this->close($socket);

            return;
        }

        // Handshake
        $buffer = 'HTTP/1.1 101 Switching Protocols'.$eol.
            'Upgrade: websocket'.$eol.
            'Connection: Upgrade'.$eol;

        if (isset($headers['Sec-Websocket-Protocol'])) {
            $buffer .= 'Sec-WebSocket-Protocol: '.$headers['Sec-Websocket-Protocol'].$eol;
        }

        $buffer .= 'Sec-WebSocket-Accept: '.base64_encode(sha1($headers['Sec-Websocket-Key'].self::UUID, true)).$eol.$eol;

        if ($this->write($socket, $buffer)) {
            // Connect agent to server
            $this->sockets[$sid] = $socket;
            $this->agents[$sid] = new Agent($this, $socket, $verb, $uri, $headers);
        }
    }

    /**
     * Close stream socket.
     *
     * @param resource $socket
     */
    public function close($socket): void
    {
        $sid = (int) $socket;

        if (isset($this->agents[$sid])) {
            unset($this->sockets[$sid], $this->agents[$sid]);
        }

        stream_socket_shutdown($socket, STREAM_SHUT_WR);
        fclose($socket);
    }

    /**
     * Read from stream socket.
     *
     * @param resource $socket
     *
     * @return string|null
     */
    public function read($socket): ?string
    {
        if (is_string($buffer = fread($socket, self::MAX_PACKET)) &&
            strlen($buffer) &&
            strlen($buffer) < self::MAX_PACKET) {
            return $buffer;
        }

        $this->fw->dispatch(self::EVENT_ERROR, $this);
        $this->close($socket);

        return null;
    }

    /**
     * Write to stream socket.
     *
     * @param resource $socket
     * @param string   $buffer
     *
     * @return int
     */
    public function write($socket, string $buffer): int
    {
        for ($i = 0,$bytes = 0; $i < strlen($buffer); $i += $bytes) {
            if (($bytes = fwrite($socket, substr($buffer, $i))) && fflush($socket)) {
                continue;
            }

            $this->fw->dispatch(self::EVENT_ERROR, $this);
            $this->close($socket);

            return -1;
        }

        return $bytes;
    }

    /**
     * Return socket agents.
     *
     * @param string|null $uri
     *
     * @return array
     */
    public function agents(string $uri = null): array
    {
        if (null === $uri) {
            return $this->agents;
        }

        return array_filter($this->agents, function ($agent) use ($uri) {
            return $agent->uri() == $uri;
        });
    }

    /**
     * Terminate server.
     *
     * @param int $signal
     */
    public function kill(int $signal): void
    {
        die;
    }

    /**
     * Execute the server process.
     *
     * @return Server
     */
    public function run(): Server
    {
        // Assign signal handlers
        declare(ticks=1);

        pcntl_signal(SIGINT, array($this, 'kill'));
        pcntl_signal(SIGTERM, array($this, 'kill'));
        gc_enable();

        // Activate WebSocket listener
        $listen = stream_socket_server($this->address, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $this->context);
        $socket = socket_import_stream($listen);

        register_shutdown_function(function () use ($listen) {
            foreach ($this->sockets as $socket) {
                if ($socket != $listen) {
                    $this->close($socket);
                }
            }

            $this->close($listen);
            $this->fw->dispatch(self::EVENT_STOP, $this);
        });

        if ($errstr) {
            throw new \LogicException($errstr);
        }

        $this->fw->dispatch(self::EVENT_START, $this);

        $empty = array();
        $wait = $this->wait;
        $this->sockets = array((int) $listen => $listen);

        while (true) {
            $active = $this->sockets;
            $mark = microtime(true);
            $count = stream_select($active, $empty, $empty, intval($wait), intval(round(1e6 * ($wait - (int) $wait))));

            if (is_bool($count) && $wait) {
                $this->fw->dispatch(self::EVENT_ERROR, $this);
                die;
            }

            if ($count) {
                // Process active connections
                foreach ($active as $socket) {
                    if (!is_resource($socket)) {
                        continue;
                    }

                    if ($socket == $listen) {
                        if ($socket = stream_socket_accept($listen, 0)) {
                            $this->alloc($socket);
                        } else {
                            $this->fw->dispatch(self::EVENT_ERROR, $this);
                        }
                    } else {
                        $id = (int) $socket;

                        if (isset($this->agents[$id])) {
                            $this->agents[$id]->fetch();
                        }
                    }
                }

                $wait -= microtime(true) - $mark;

                while ($wait < 1e-6) {
                    $wait += $this->wait;
                    $count = 0;
                }
            }

            if (!$count) {
                $mark = microtime(true);

                foreach ($this->sockets as $id => $socket) {
                    if (!is_resource($socket)) {
                        continue;
                    }

                    if ($socket != $listen && isset($this->agents[$id])) {
                        $this->fw->dispatch(Agent::EVENT_IDLE, $this->agents[$id]);
                    }
                }

                $wait = $this->wait - microtime(true) + $mark;
            }

            gc_collect_cycles();
        }
    }
}

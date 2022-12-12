<?php

namespace PHPWebsocket\Core;

use Ratchet\ConnectionInterface;
use Ratchet\RFC6455\Messaging\Frame;
use Ratchet\RFC6455\Messaging\Message;

class Socket
{
    /**
     * @var ConnectionInterface
     */
    protected $conn;

    /**
     * @var array
     */
    protected $events = [];

    /**
     * @var Broadcast
     */
    protected $broadcast;

    /**
     * @var RatchatClient
     */
    protected $client;

    /**
     * @var bool
     */
    protected $binary;

    /**
     * @var int
     */
    protected $count = 0;

    public function __construct(ConnectionInterface $conn, RatchatClient $client, bool $binary = false)
    {
        $this->conn = $conn;
        $this->broadcast = new Broadcast($client, $this);
        $this->client = $client;
        $this->binary = $binary;
    }

    public function id(): string
    {
        return $this->conn->socket_id;
    }

    public function on(string $name, callable $callback)
    {
        if (isset($this->events[$name])) {
            $this->events[$name] = [];
        }
        $this->events[$name][] = $callback;
    }

    public function broadcast(): Broadcast
    {
        return $this->broadcast;
    }

    public function to(string $toId): SocketTo
    {
        return $this->broadcast->to($toId);
    }

    protected function sendBinary($message)
    {
        $binaryMsg = new Message();
        $frame = new Frame($message, true, Frame::OP_BINARY);
        $binaryMsg->addFrame($frame);
        $this->conn->send($binaryMsg);
    }

    public function emit(string $name, $data)
    {
        if ($this->binary) {
            $this->sendBinary(json_encode([null, $name, $data]));
        } else {
            $this->conn->send(json_encode([null, $name, $data]));
        }
    }

    public function setCount($count)
    {
        $this->count = $count;
    }

    public function reply($data, $count = null)
    {
        if ($count === null) {
            $count = $this->count;
        }
        if ($this->binary) {
            $this->sendBinary(json_encode([$count, $data]));
        } else {
            $this->conn->send(json_encode([$count, $data]));
        }
    }

    public function events(): array
    {
        return $this->events;
    }

    public function getConnection()
    {
        return $this->conn;
    }

    public function client()
    {
        return $this->client;
    }
}

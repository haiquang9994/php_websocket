<?php

namespace PHPWebsocket\Core;

use Ratchet\ConnectionInterface;

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

    public function __construct(ConnectionInterface $conn, RatchatClient $client)
    {
        $this->conn = $conn;
        $this->broadcast = new Broadcast($client, $this);
        $this->client = $client;
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

    public function emit(string $name, $data)
    {
        $this->conn->send(json_encode([null, $name, $data]));
    }

    public function reply($length, $result)
    {
        $this->conn->send(json_encode([$length, $result]));
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

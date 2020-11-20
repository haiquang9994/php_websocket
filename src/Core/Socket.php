<?php

namespace MyWebsocket\Core;

use Ratchet\ConnectionInterface;

class Socket
{
    protected $conn;
    protected $client;
    protected $events = [];
    protected $broadcast;

    public function __construct(ConnectionInterface $conn, RatchatClient $client)
    {
        $this->conn = $conn;
        $this->client = $client;
        $this->broadcast = new Broadcast($client, $this);
    }

    public function id()
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

    public function broadcast()
    {
        return $this->broadcast;
    }

    public function to(string $toId)
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

    public function events()
    {
        return $this->events;
    }
}

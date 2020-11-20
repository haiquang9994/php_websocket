<?php

namespace MyWebsocket\Core;

use Ratchet\ConnectionInterface;

class Socket
{
    protected $id;
    protected $conn;
    protected $client;
    protected $events = [];
    protected $broadcast;

    public function __construct(ConnectionInterface $conn, RatchatClient $client, string $id)
    {
        $this->conn = $conn;
        $this->client = $client;
        $this->id = $id;
        $this->broadcast = new Broadcast($client, $this);
    }

    public function id()
    {
        return $this->id;
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
        $this->conn->send(json_encode([
            'name' => $name,
            'data' => $data,
        ]));
    }

    public function events()
    {
        return $this->events;
    }
}

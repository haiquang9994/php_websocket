<?php

namespace MyWebsocket\Core;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class RatchatClient implements MessageComponentInterface
{
    protected $callback;

    protected $sockets = [];

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        if ($id = $this->getSocketId($conn)) {
            $socket = new Socket($conn, $this, $id);
            $this->sockets[$id] = $socket;
            call_user_func($this->callback, $socket);
        }
    }

    protected function getSocketId($conn)
    {
        $query_string = $conn->httpRequest->getUri()->getQuery();
        $queries = array_map(function ($item) {
            return explode('=', $item);
        }, explode('&', $query_string));
        $sid = array_filter($queries, function ($item) {
            return $item[0] === 'sid';
        })[0] ?? null;
        return $sid ? $sid[1] : null;
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $message = json_decode($msg, true);
        $name = $message['name'] ?? null;
        $id = $this->getSocketId($from);
        if ($name && $id) {
            if ($socket = $this->sockets[$id]) {
                $events = $socket->events();
                $callbacks = $events[$name] ?? [];
                $data = $message['data'] ?? null;
                foreach ($callbacks as $callback) {
                    call_user_func($callback, $data, $socket);
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $id = $this->getSocketId($conn);
        unset($this->sockets[$id]);
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        $conn->close();
    }

    public function sockets()
    {
        return $this->sockets;
    }
}

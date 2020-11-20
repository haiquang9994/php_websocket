<?php

namespace PHPWebsocket\Core;

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
        $conn->socket_id = $this->getSocketId($conn);
        $socket = new Socket($conn, $this);
        $this->sockets[$conn->socket_id] = $socket;
        call_user_func($this->callback, $socket);
    }

    public function onMessage(ConnectionInterface $conn, $msg)
    {
        $message = json_decode($msg, true);
        list($count, $name, $data) = $message;
        $id = $conn->socket_id;
        if ($name && $id) {
            if ($socket = $this->sockets[$id]) {
                $events = $socket->events();
                $callbacks = $events[$name] ?? [];
                foreach ($callbacks as $callback) {
                    $result = call_user_func($callback, $data, $socket);
                    if ($result !== null) {
                        $socket->reply($count, $result);
                    }
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        if ($id = $conn->socket_id) {
            unset($this->sockets[$id]);
        }
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        $conn->close();
    }

    protected function getSocketId($conn)
    {
        $query_string = $conn->httpRequest->getUri()->getQuery();
        $queries = array_map(function ($item) {
            return explode('=', $item);
        }, explode('&', $query_string));
        $sid = array_values(array_filter($queries, function ($item) {
            return $item[0] === 'sid';
        }));
        return isset($sid[0][1]) ? $sid[0][1] : md5(date('d-m-Y-H-s-i') . rand() . rand());
    }

    public function sockets()
    {
        return $this->sockets;
    }
}

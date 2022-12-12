<?php

namespace PHPWebsocket\Core;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\RFC6455\Messaging\Frame;
use Ratchet\RFC6455\Messaging\Message;

class RatchatClient implements MessageComponentInterface
{
    /**
     * @var callable
     */
    protected $onConnect;

    /**
     * @var callable
     */
    protected $onClose;

    /**
     * @var array
     */
    protected $sockets = [];

    /**
     * @var bool
     */
    protected $binary;

    public function __construct(callable $onConnect, callable $onClose = null, callable $callback = null, bool $binary = false)
    {
        $this->onConnect = $onConnect;
        $this->onClose = $onClose;
        $this->binary = $binary;
        if (is_callable($callback)) {
            call_user_func($callback);
        }
    }

    protected function find($id): ?Socket
    {
        $socket = $this->sockets[$id] ?? null;
        if ($socket instanceof Socket) {
            return $socket;
        }
        return null;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $conn->socket_id = $this->getSocketId($conn);
        $socket = new Socket($conn, $this, $this->binary);
        $this->sockets[$conn->socket_id] = $socket;
        if (is_callable($this->onConnect)) {
            call_user_func($this->onConnect, $socket);
        }
    }

    public function onMessage(ConnectionInterface $conn, $msg)
    {
        $message = @json_decode($msg, true);
        if (!is_array($message)) {
            $this->handlePingMessage($conn, $message);
            return;
        }
        list($count, $name, $data) = $message;
        $this->handleMessage($conn, $count, $name, $data);
    }

    protected function handlePingMessage(ConnectionInterface $conn, $message)
    {
        if (is_numeric($message)) {
            if ($this->binary) {
                $binaryMsg = new Message();
                $frame = new Frame($message, true, Frame::OP_BINARY);
                $binaryMsg->addFrame($frame);
                $conn->send($binaryMsg);
            } else {
                $conn->send($message);
            }
        }
    }

    protected function handleMessage(ConnectionInterface $conn, $count, $name, $data)
    {
        if ($name && $conn->socket_id) {
            if ($socket = $this->find($conn->socket_id)) {
                $socket->setCount($count);
                $events = $socket->events();
                $callbacks = $events[$name] ?? [];
                foreach ($callbacks as $callback) {
                    $result = call_user_func($callback, $data, $socket);
                    if ($result !== null) {
                        $socket->reply($result, $count);
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
        if (is_callable($this->onClose)) {
            $socket = new Socket($conn, $this);
            call_user_func($this->onClose, $socket);
        }
    }

    public function onError(ConnectionInterface $conn, Exception $e)
    {
        $conn->close();
    }

    protected function getSocketId($conn): string
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

    public function sockets(): array
    {
        return $this->sockets;
    }
}

<?php

namespace PHPWebsocket\Core;

use Exception;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use Ratchet\RFC6455\Messaging\Frame;
use Ratchet\RFC6455\Messaging\Message;

class RatchatClientWithController implements MessageComponentInterface, RatchatClientInterface
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

    /**
     * @var Controller
     */
    protected $controller;

    public function __construct(Controller $controller, callable $onConnect, callable $onClose = null, callable $callback = null, bool $binary = false)
    {
        $this->controller = $controller;
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
        $wsMessageType = $conn->httpRequest->getHeaderLine('WSMessageType');
        if ($wsMessageType !== 'ControllerProcess' && is_callable($this->onConnect)) {
            call_user_func($this->onConnect, $socket);
        }
    }

    public function onMessage(ConnectionInterface $conn, $msg)
    {
        $message = @json_decode($msg, true);
        if (!is_array($message)) {
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
            return;
        }
        list($count, $name, $data) = $message;
        if ($name === '::controller:self:reply') {
            if (is_array($data)) {
                $socket_id = $data['socket_id'] ?? null;
                $count = $data['count'] ?? null;
                $result = $data['result'] ?? null;
                $socket = $this->find($socket_id);
                if ($count !== null && $socket) {
                    $socket->reply($count, $result);
                }
            }
            return;
        }
        if ($name === '::controller:broadcast:emit') {
            if (is_array($data)) {
                $socket_id = $data['socket_id'] ?? null;
                $name = $data['name'] ?? null;
                $data = $data['data'] ?? null;
                $socket = $this->find($socket_id);
                if ($name && $socket) {
                    $socket->broadcast()->emit($name, $data);
                }
            }
            return;
        }
        $socket_id = $conn->socket_id;
        $this->controller->process($socket_id, $count, $name, $data);
    }

    public function onClose(ConnectionInterface $conn)
    {
        if ($id = $conn->socket_id) {
            unset($this->sockets[$id]);
        }
        $wsMessageType = $conn->httpRequest->getHeaderLine('WSMessageType');
        if ($wsMessageType !== 'ControllerProcess' && is_callable($this->onClose)) {
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

<?php

namespace PHPWebsocket\Core;

use Ratchet\ConnectionInterface;

class RatchatClientWithController extends RatchatClient
{
    /**
     * @var Controller
     */
    protected $controller;

    public function __construct(Controller $controller, callable $onConnect, callable $onClose = null, callable $callback = null, bool $binary = false)
    {
        $this->controller = $controller;
        parent::__construct($onConnect, $onClose, $callback, $binary);
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $wsMessageType = $conn->httpRequest->getHeaderLine('WSMessageType');
        if ($wsMessageType === 'ControllerProcess') {
            $conn->socket_id = $this->getSocketId($conn);
            return;
        }
        parent::onOpen($conn);
    }

    protected function handleMessage(ConnectionInterface $conn, $count, $name, $data)
    {
        $wsMessageType = $conn->httpRequest->getHeaderLine('WSMessageType');
        if ($wsMessageType === 'ControllerProcess') {
            if ($name === '::controller:self:reply') {
                $socket_id = $data['socket_id'] ?? null;
                $count = $data['count'] ?? null;
                $result = $data['result'] ?? null;
                $socket = $this->find($socket_id);
                if ($count !== null && $socket) {
                    $socket->reply($result, $count);
                }
            } elseif ($name === '::controller:self:emit') {
                $socket_id = $data['socket_id'] ?? null;
                $to_ids = $data['to_ids'] ?? null;
                $name = $data['name'] ?? null;
                $data = $data['data'] ?? null;
                $socket = $this->find($socket_id);
                if ($name && $socket && is_array($to_ids)) {
                    foreach ($to_ids as $to) {
                        $socket->to($to)->emit($name, $data);
                    }
                }
            } elseif ($name === '::controller:broadcast:emit') {
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
        $this->controller->process($conn->socket_id, $count, $name, $data);
        parent::handleMessage($conn, $count, $name, $data);
    }

    public function onClose(ConnectionInterface $conn)
    {
        $wsMessageType = $conn->httpRequest->getHeaderLine('WSMessageType');
        if ($wsMessageType === 'ControllerProcess') {
            return;
        }
        parent::onClose($conn);
    }
}

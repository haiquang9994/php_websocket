<?php

namespace PHPWebsocket;

use PHPWebsocket\Core\RatchatClient;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;

class Server
{
    protected $address;

    protected $port;

    public function __construct($address = '0.0.0.0', $port = 8080)
    {
        $this->address = $address;
        $this->port = $port;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function onConnect(callable $runner, callable $callback = null)
    {
        return $this->run($runner, null, $callback);
    }

    public function run(callable $onConnect = null, callable $onClose = null, callable $callback = null)
    {
        IoServer::factory(
            new HttpServer(
                new WsServer(
                    new RatchatClient($onConnect, $onClose, $callback)
                )
            ),
            $this->port,
            $this->address,
        )->run();
    }
}

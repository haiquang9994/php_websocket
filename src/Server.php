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
    protected $binary;

    public function __construct($address = '127.0.0.1', $port = 8080, bool $binary = false)
    {
        $this->address = $address;
        $this->port = $port;
        $this->binary = $binary;
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
                    new RatchatClient($onConnect, $onClose, $callback, $this->binary)
                )
            ),
            $this->port,
            $this->address,
        )->run();
    }
}

<?php

namespace PHPWebsocket\Core;

class Broadcast
{
    /**
     * @var RatchatClientInterface
     */
    protected $client;

    /**
     * @var Socket
     */
    protected $socket;

    public function __construct(RatchatClientInterface $client, Socket $socket)
    {
        $this->client = $client;
        $this->socket = $socket;
    }

    public function emit(string $name, $data)
    {
        $sockets = $this->client->sockets();
        foreach ($sockets as $socket) {
            if ($socket->id() !== $this->socket->id()) {
                $socket->emit($name, $data);
            }
        }
    }

    public function to(string $toId): SocketTo
    {
        $sockets = $this->client->sockets();
        $socketTo = new SocketTo();
        $socketTo->setSocket($sockets[$toId] ?? null);
        return $socketTo;
    }
}

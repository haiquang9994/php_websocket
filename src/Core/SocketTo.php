<?php

namespace PHPWebsocket\Core;

class SocketTo
{
    /**
     * @var Socket|null
     */
    protected $socket;

    public function setSocket($socket)
    {
        $this->socket = $socket;
    }

    public function emit(string $name, $data)
    {
        if ($this->socket instanceof Socket) {
            $this->socket->emit($name, $data);
        }
    }
}

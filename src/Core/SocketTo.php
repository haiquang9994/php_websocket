<?php

namespace MyWebsocket\Core;

class SocketTo
{
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

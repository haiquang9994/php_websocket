<?php

namespace PHPWebsocket\Core;

use PHPWebsocket\Client;

class Handler
{
    protected $events;
    protected $host;
    protected $port;
    protected $socket_id;

    public function __construct(array $events)
    {
        $this->events = $events;
    }

    public function socketId()
    {
        return $this->socket_id;
    }

    public function broadcastEmit($name, $data)
    {
        $client = new Client($this->host, $this->port);
        $client->send("::controller:broadcast:emit", [
            'socket_id' => $this->socket_id,
            'name' => $name,
            'data' => $data,
        ], null, [], [
            'WSMessageType' => 'ControllerProcess',
        ]);
    }

    public function process()
    {
        global $argv;
        list($host, $port, $socket_id, $count, $name, $data) = @json_decode(@base64_decode($argv[1] ?? ""), true);
        if ($host && $port && $socket_id) {
            $this->host = $host;
            $this->port = $port;
            $this->socket_id = $socket_id;
            if ($fn = $this->events[$name] ?? null) {
                if ($result = call_user_func($fn, $data, $this)) {
                    $client = new Client($host, $port);
                    $client->send("::controller:self:reply", [
                        'socket_id' => $socket_id,
                        'count' => $count,
                        'result' => $result,
                    ], null, [], [
                        'WSMessageType' => 'ControllerProcess',
                    ]);
                }
            }
        }
    }
}

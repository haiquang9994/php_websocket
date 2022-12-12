<?php

namespace PHPWebsocket\Core;

use PHPWebsocket\Client;

class Handler
{
    /**
     * @var callable[]
     */
    protected $events;

    /**
     * @var string
     */
    protected $address;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var string
     */
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
        $client = new Client($this->address, $this->port);
        $client->send("::controller:broadcast:emit", [
            'socket_id' => $this->socket_id,
            'name' => $name,
            'data' => $data,
        ], null, [], [
            'WSMessageType' => 'ControllerProcess',
        ]);
    }

    public function emit($to, $name, $data)
    {
        $to_ids = is_array($to) ? $to : [$to];
        $client = new Client($this->address, $this->port);
        $client->send("::controller:self:emit", [
            'socket_id' => $this->socket_id,
            'to_ids' => $to_ids,
            'name' => $name,
            'data' => $data,
        ], null, [], [
            'WSMessageType' => 'ControllerProcess',
        ]);
    }

    public function process()
    {
        global $argv;
        list($address, $port, $socket_id, $count, $name, $data) = @json_decode(@base64_decode($argv[1] ?? ""), true);
        if ($address && $port && $socket_id) {
            $this->address = $address;
            $this->port = $port;
            $this->socket_id = $socket_id;
            if ($fn = $this->events[$name] ?? null) {
                if ($result = call_user_func($fn, $data, $this)) {
                    $client = new Client($address, $port);
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

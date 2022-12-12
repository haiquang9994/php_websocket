<?php

namespace PHPWebsocket;

use function Ratchet\Client\connect;

class Client
{
    /**
     * @var string
     */
    protected $address;

    /**
     * @var int
     */
    protected $port;

    /**
     * @var bool
     */
    protected $secure;

    public function __construct($address = '0.0.0.0', $port = 8080, $secure = false)
    {
        $this->address = $address;
        $this->port = $port;
        $this->secure = $secure;
    }

    public function getAddress()
    {
        return $this->address;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getSecure()
    {
        return $this->secure;
    }

    public function send(string $eventName, array $data = [], callable $callback = null, array $subProtocols = [], array $headers = [])
    {
        $url = sprintf("%s://%s:%s", $this->secure ? 'wss' : 'ws', $this->address, $this->port);
        connect($url, $subProtocols, $headers)->then(function ($conn) use ($eventName, $data, $callback) {
            $conn->on('message', function ($msg) use ($callback, $conn) {
                if (is_callable($callback)) {
                    $data = json_decode($msg, true);
                    if (count($data) === 2 && $data[0] === 0) {
                        call_user_func($callback, $data[1]);
                    }
                    $conn->close();
                }
            });
            $conn->send(json_encode([0, $eventName, $data]));
            if (!is_callable($callback)) {
                $conn->close();
            }
        }, function ($e) {
            throw $e;
        });
    }
}

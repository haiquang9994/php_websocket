<?php

namespace PHPWebsocket;

use PHPWebsocket\Core\RatchatClient;
use Ratchet\App;

class Server
{
    protected $app;

    protected $route;

    public function __construct(string $httpHost = 'localhost', $port = 8080, string $route = '/')
    {
        $this->app = new App($httpHost, $port);
        $this->route = $route;
    }

    public function onConnect(callable $runner)
    {
        $client = new RatchatClient($runner);
        $this->app->route($this->route, $client, ['*']);
        $this->app->run();
    }
}

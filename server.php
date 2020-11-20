<?php

use MyWebsocket\Core\Socket;
use MyWebsocket\Server;

require __DIR__ . '/vendor/autoload.php';


$app = new Server('localhost', 7508, '/');

$app->onConnect(function (Socket $socket) {
    $socket->on('chat', function ($data, Socket $socket) {
        $socket->broadcast()->emit('chat', ['message' => $data['message']]);
    });
});

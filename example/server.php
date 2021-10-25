<?php

use PHPWebsocket\Core\Socket;
use PHPWebsocket\Server;

require __DIR__ . '/../vendor/autoload.php';

$server = new Server('127.0.0.1', 7508);

$messages = [];

function getMessages()
{
    global $messages;
    return $messages;
}

function pushMessage($message)
{
    global $messages;
    $messages[] = $message;
}

$server->onConnect(function (Socket $socket) {
    $socket->on('chat', function ($data, Socket $socket) {
        pushMessage([
            'message' => $data['message'],
            'sender' => $socket->id(),
        ]);
        $socket->broadcast()->emit('chat', [
            'message' => $data['message'],
            'sender' => $socket->id(),
        ]);
        return ['status' => true];
    });
    $socket->on('messages', function ($data, Socket $socket) {
        return ['messages' => getMessages()];
    });
}, function (Server $server) {
    echo "Listening on {$server->getAddress()}:{$server->getPort()}\n";
});

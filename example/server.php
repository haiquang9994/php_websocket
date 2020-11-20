<?php

use PHPWebsocket\Core\Socket;
use PHPWebsocket\Server;

require __DIR__ . '/../vendor/autoload.php';


$app = new Server('localhost', 7508, '/');

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

$app->onConnect(function (Socket $socket) {
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
});

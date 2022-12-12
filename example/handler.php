<?php

use PHPWebsocket\Core\Handler;

require __DIR__ . '/../vendor/autoload.php';

$messages = @json_decode(@file_get_contents("messages.json"), true);
if (!is_array($messages)) {
    $messages = [];
}

function getMessages()
{
    global $messages;
    return $messages;
}

function pushMessage($message)
{
    global $messages;
    $messages[] = $message;
    file_put_contents("messages.json", json_encode($messages));
}

(new Handler([
    'messages' => function ($data) {
        return ['messages' => getMessages()];
    },
    'chat' => function ($data, Handler $handler) {
        pushMessage([
            'message' => $data['message'],
            'sender' => $handler->socketId(),
        ]);
        $handler->broadcastEmit('chat', [
            'message' => $data['message'],
            'sender' => $handler->socketId(),
        ]);
        return ['status' => true];
    },
]))->process();

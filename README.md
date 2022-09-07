# Install
```bash
composer require lpks/php-websocket
```

Script file in example folder.


# Description
WebSocket Server like SocketIO for PHP.

# Server
```php
use PHPWebsocket\Core\Socket;
use PHPWebsocket\Server;

require 'vendor/autoload.php';


$server = new Server('127.0.0.1', 7508, true);

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

$server->run(function (Socket $socket) {
    echo "Connected: {$socket->id()}\n";
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
}, function (Socket $socket) {
    echo "Disconnected: {$socket->id()}\n";
}, function () use ($server) {
    echo "Listening on {$server->getAddress()}:{$server->getPort()}\n";
});
```

# Client with js
```js
var socket = new WSClient('http://127.0.0.1:7508', true)

socket.emit('messages', {}, ({ messages }) => {
    console.log(messages)
})

socket.on('chat', ({ message, sender }) => {
    console.log(message, sender)
})
```

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
```

# Client with js
```js
var socket = new WSClient('http://localhost:7508')

socket.emit('messages', {}, ({ messages }) => {
    console.log(messages)
})

socket.on('chat', ({ message, sender }) => {
    console.log(message, sender)
})
```

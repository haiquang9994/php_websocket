<?php

use PHPWebsocket\Core\Controller;
use PHPWebsocket\Core\Socket;
use PHPWebsocket\Server;

require __DIR__ . '/../vendor/autoload.php';

$server = new Server('127.0.0.1', 7508, true);

$controller = new Controller("/opt/homebrew/opt/php@7.4/bin/php", __DIR__ . "/handler.php");

$server->runWithController(
    $controller,
    function (Socket $socket) {
        echo "Connected: {$socket->id()}\n";
    },
    function (Socket $socket) {
        echo "Disconnected: {$socket->id()}\n";
    },
    function () use ($server) {
        echo "Listening on {$server->getAddress()}:{$server->getPort()}\n";
    },
);

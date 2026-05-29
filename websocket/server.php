<?php

error_reporting(E_ALL & ~E_DEPRECATED);
require_once dirname(__DIR__) . '/config/app.php';
error_reporting(E_ALL & ~E_DEPRECATED);
require_once ROOT_PATH . '/vendor/autoload.php';

use App\WebSocket\AdminPusher;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Loop;
use React\Socket\SocketServer;

$wsHost     = '0.0.0.0';
$wsPort     = 8080; // WebSocket server for clients->app.js, user.js
$bridgeHost = '127.0.0.1';
$bridgePort = 8081;  // Internal bridge for HTTP->WS communication (not exposed to clients)

$loop   = Loop::get();
$pusher = new AdminPusher();

$wsSocket = new SocketServer($wsHost . ':' . $wsPort, [], $loop);
new IoServer(
    new HttpServer(new WsServer($pusher)),
    $wsSocket,
    $loop
);

$bridge = new SocketServer($bridgeHost . ':' . $bridgePort, [], $loop);
$bridge->on('connection', function ($conn) use ($pusher) {
    $buffer = '';
    $conn->on('data', function ($chunk) use (&$buffer, $conn, $pusher) {
        $buffer .= $chunk;
        while (($pos = strpos($buffer, "\n")) !== false) {
            $line   = trim(substr($buffer, 0, $pos));
            $buffer = substr($buffer, $pos + 1);
            if ($line === '') {
                continue;
            }
            $payload = json_decode($line, true);
            if (is_array($payload)) {
                $pusher->broadcastFromBridge($payload);
            }
        }
    });
    $conn->on('end',   fn() => $conn->close());
    $conn->on('error', fn() => $conn->close());
});

echo 'WS     listening on ws://' . $wsHost . ':' . $wsPort . PHP_EOL;
echo 'Bridge listening on tcp://' . $bridgeHost . ':' . $bridgePort . PHP_EOL;
echo 'Mode: RELAY-ONLY (clients fetch data over HTTP/JSON)' . PHP_EOL;

$loop->run();

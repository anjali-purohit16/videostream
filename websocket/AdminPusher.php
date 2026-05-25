<?php

namespace App\WebSocket;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use SplObjectStorage;

/**
 * Relay-only WebSocket component.
 *
 * The server does NOT touch the DB. Controllers push tiny event frames
 * via WsPublisher::push(); this class fans them out to the right clients.
 * Browsers receive the ping and fetch the real data over HTTP/JSON.
 */
class AdminPusher implements MessageComponentInterface
{
    /** @var SplObjectStorage<ConnectionInterface, array{role:string,uid:int}> */
    private SplObjectStorage $clients;

    public function __construct()
    {
        $this->clients = new SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn): void
    {
        [$role, $uid] = $this->resolveIdentity($conn);
        $this->clients->attach($conn, ['role' => $role, 'uid' => $uid]);
        $conn->send(json_encode(['event' => 'ready', 'role' => $role, 'uid' => $uid]));
        $this->log("+{$role} uid={$uid} (" . $this->clients->count() . ')');
    }

    public function onMessage(ConnectionInterface $from, $msg): void
    {
        // Clients are read-only consumers; ignore inbound frames.
    }

    public function onClose(ConnectionInterface $conn): void
    {
        $meta = $this->clients[$conn] ?? null;
        $this->clients->detach($conn);
        $tag = $meta ? "-{$meta['role']} uid={$meta['uid']}" : '-client';
        $this->log("{$tag} (" . $this->clients->count() . ')');
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void
    {
        $this->log('error: ' . $e->getMessage());
        $conn->close();
    }

    public function broadcastFromBridge(array $payload): void
    {
        $topic    = (string)($payload['topic'] ?? '');
        $audience = (string)($payload['audience'] ?? 'admin');
        $userId   = (int)($payload['user_id'] ?? 0);

        $frame = json_encode([
            'event'   => 'live',
            'topic'   => $topic,
            'audience' => $audience,
            'user_id' => $userId,
        ], JSON_UNESCAPED_SLASHES);

        $sent = 0;
        foreach ($this->clients as $client) {
            $meta = $this->clients[$client] ?? null;
            if (!$meta) {
                continue;
            }
            if (!$this->shouldDeliver($meta, $audience, $userId)) {
                continue;
            }
            $client->send($frame);
            $sent++;
        }
        $this->log("relay topic={$topic} audience={$audience} uid={$userId} → {$sent} client(s)");
    }

    private function shouldDeliver(array $meta, string $audience, int $userId): bool
    {
        $role = $meta['role'];
        return match ($audience) {
            'admin'     => $role === 'admin',
            'users'     => $role === 'user',
            'broadcast' => true,
            'user'      => $role === 'user' && (int)$meta['uid'] === $userId && $userId > 0,
            default     => $role === 'admin',
        };
    }

    private function resolveIdentity(ConnectionInterface $conn): array
    {
        $role = 'admin';
        $uid  = 0;
        try {
            $query = '';
            if (isset($conn->httpRequest)) {
                $uri = $conn->httpRequest->getUri();
                $query = method_exists($uri, 'getQuery') ? (string)$uri->getQuery() : '';
            }
            if ($query !== '') {
                parse_str($query, $params);
                if (($params['role'] ?? '') === 'user') {
                    $role = 'user';
                }
                $uid = (int)($params['uid'] ?? 0);
            }
        } catch (\Throwable) {}
        return [$role, $uid];
    }

    private function log(string $message): void
    {
        echo '[' . date('H:i:s') . '] ' . $message . PHP_EOL;
    }
}

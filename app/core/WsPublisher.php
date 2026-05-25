<?php

class WsPublisher
{
    private const BRIDGE_HOST = '127.0.0.1';
    private const BRIDGE_PORT = 8081;
    private const TIMEOUT     = 0.4;

    public static function push(string $topic, array $extra = []): void
    {
        $payload = json_encode(array_merge(['topic' => $topic], $extra), JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            return;
        }

        $errno = 0;
        $errstr = '';
        $sock = @stream_socket_client(
            'tcp://' . self::BRIDGE_HOST . ':' . self::BRIDGE_PORT,
            $errno,
            $errstr,
            self::TIMEOUT
        );
        if (!$sock) {
            // WS server not running — silently skip so the HTTP request still succeeds.
            return;
        }

        stream_set_timeout($sock, 0, (int)(self::TIMEOUT * 1_000_000));
        @fwrite($sock, $payload . "\n");
        @fclose($sock);
    }
}

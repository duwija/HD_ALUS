<?php

namespace App\Services;

use App\Olt;

/**
 * Issues tokens for the browser telnet terminal (telnet-bridge/server.js).
 *
 * The token is AES-256-GCM encrypted under a key derived from APP_KEY, so it
 * carries the OLT target and credentials without exposing them to the
 * browser, and the bridge only ever connects to an OLT taken from the DB.
 * Tokens expire after TOKEN_TTL seconds and the bridge accepts each once.
 */
class OltTelnetBridge
{
    const TOKEN_TTL = 30;
    const WS_PATH = '/olt-telnet-ws';

    public function issueToken(Olt $olt, $user, string $host): string
    {
        $payload = [
            'ip'       => (string) $olt->ip,
            'port'     => (int) ($olt->port ?: 23),
            'user'     => (string) ($olt->user ?? ''),
            'pass'     => (string) ($olt->password ?? ''),
            'olt_id'   => $olt->id,
            'olt_name' => (string) $olt->name,
            'uid'      => $user->id ?? null,
            'uname'    => $user->name ?? null,
            'tenant'   => function_exists('tenant_rescode') ? tenant_rescode() : null,
            'host'     => $host,
            'exp'      => time() + self::TOKEN_TTL,
            'nonce'    => bin2hex(random_bytes(16)),
        ];

        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt(json_encode($payload), 'aes-256-gcm', $this->key(), OPENSSL_RAW_DATA, $iv, $tag, '', 16);

        return rtrim(strtr(base64_encode($iv . $tag . $cipher), '+/', '-_'), '=');
    }

    // Must match KEY in telnet-bridge/server.js.
    private function key(): string
    {
        $appKey = config('app.key');
        if (strpos($appKey, 'base64:') === 0) {
            $appKey = base64_decode(substr($appKey, 7));
        }

        return hash_hmac('sha256', 'olt-telnet-bridge', $appKey, true);
    }
}

<?php

namespace App\Services;

/**
 * Decode + verify SSO tokens yang diterbitkan Holding App.
 *
 * Format token: {base64url-payload}.{base64url-signature}.
 * Signature: HMAC-SHA256(payloadB64, SSO_SECRET).
 * TTL: 5 menit (di-check dari payload['exp']).
 *
 * Logika harus sama persis dengan SsoSignedUrl di Holding
 * (lihat .guide/sso-subsidiary-guide.md).
 */
class SsoTokenVerifier
{
    /**
     * Decode token, verify signature & expiry. Return payload kalau valid, null kalau tidak.
     *
     * @return array<string,mixed>|null
     */
    public function decode(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 2) {
            return null;
        }
        [$payloadB64, $sigB64] = $parts;

        // 1. Verify signature (constant-time)
        $expected = $this->sign($payloadB64);
        $provided = $this->b64urlDecode($sigB64);
        if ($provided === null || ! hash_equals($expected, $provided)) {
            return null;
        }

        // 2. Decode payload
        $payloadJson = $this->b64urlDecode($payloadB64);
        if ($payloadJson === null) {
            return null;
        }

        try {
            $payload = json_decode($payloadJson, true, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (! is_array($payload)) {
            return null;
        }

        // 3. Expiry check
        if (! isset($payload['exp']) || time() > (int) $payload['exp']) {
            return null;
        }

        // 4. Required fields
        foreach (['uid', 'tid', 'lid', 'exp', 'email', 'role'] as $field) {
            if (! array_key_exists($field, $payload)) {
                return null;
            }
        }

        return $payload;
    }

    private function sign(string $payloadB64): string
    {
        $secret = (string) env('SSO_SECRET');

        return hash_hmac('sha256', $payloadB64, $secret, true);
    }

    private function b64urlDecode(string $b64): ?string
    {
        $padded = strtr($b64, '-_', '+/');
        $remainder = strlen($padded) % 4;
        if ($remainder !== 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode($padded, true);

        return $decoded === false ? null : $decoded;
    }
}

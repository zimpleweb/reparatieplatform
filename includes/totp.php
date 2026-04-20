<?php
/**
 * Minimal RFC 6238 TOTP implementation (Google Authenticator compatible).
 * No external dependencies.
 */

function totpGenerateSecret(int $bytes = 20): string {
    return base32Encode(random_bytes($bytes));
}

function totpVerify(string $secret, string $code, int $window = 1): bool {
    $code = preg_replace('/\s+/', '', $code);
    if (!preg_match('/^\d{6}$/', $code)) return false;
    $t = (int) floor(time() / 30);
    for ($i = -$window; $i <= $window; $i++) {
        if (hash_equals(totpCode($secret, $t + $i), $code)) return true;
    }
    return false;
}

function totpCode(string $secret, ?int $t = null): string {
    $t   = $t ?? (int) floor(time() / 30);
    $key = base32Decode($secret);
    $msg = pack('J', $t);
    $h   = hash_hmac('sha1', $msg, $key, true);
    $o   = ord($h[19]) & 0x0f;
    $v   = (ord($h[$o]) & 0x7f) << 24
         | (ord($h[$o+1]) & 0xff) << 16
         | (ord($h[$o+2]) & 0xff) << 8
         | (ord($h[$o+3]) & 0xff);
    return str_pad((string) ($v % 1000000), 6, '0', STR_PAD_LEFT);
}

function totpUri(string $secret, string $account, string $issuer = 'ReparatiePlatform'): string {
    return 'otpauth://totp/'
        . rawurlencode($issuer) . ':' . rawurlencode($account)
        . '?secret=' . $secret
        . '&issuer=' . rawurlencode($issuer)
        . '&algorithm=SHA1&digits=6&period=30';
}

function base32Encode(string $data): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $binary   = '';
    foreach (str_split($data) as $c) $binary .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
    $binary  .= str_repeat('0', (8 - strlen($binary) % 8) % 8);
    $result   = '';
    foreach (str_split($binary, 5) as $chunk) $result .= $alphabet[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
    return $result;
}

function base32Decode(string $data): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $data     = strtoupper(rtrim($data, '='));
    $binary   = '';
    foreach (str_split($data) as $c) {
        $pos = strpos($alphabet, $c);
        if ($pos === false) continue;
        $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $result = '';
    foreach (str_split($binary, 8) as $chunk) {
        if (strlen($chunk) === 8) $result .= chr(bindec($chunk));
    }
    return $result;
}

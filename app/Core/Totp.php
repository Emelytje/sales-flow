<?php
/**
 * RFC 6238 TOTP (Time-based One-Time Password) — compatible with Google
 * Authenticator, Microsoft Authenticator, Authy, etc. Pure PHP, no extensions.
 */

declare(strict_types=1);

namespace App\Core;

final class Totp
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public static function generateSecret(int $length = 20): string
    {
        $bytes = random_bytes($length);
        $secret = '';
        foreach (str_split($bytes) as $byte) {
            $secret .= self::ALPHABET[ord($byte) & 31];
        }
        return $secret;
    }

    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== 6) {
            return false;
        }
        $timeSlice = (int) floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::codeAt($secret, $timeSlice + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    public static function codeAt(string $secret, int $timeSlice): string
    {
        $key = self::base32Decode($secret);
        $binary = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $binary, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $value = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;
        return str_pad((string) $value, 6, '0', STR_PAD_LEFT);
    }

    public static function otpauthUri(string $secret, string $account, string $issuer): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            rawurlencode($issuer),
            rawurlencode($account),
            $secret,
            rawurlencode($issuer)
        );
    }

    private static function base32Decode(string $secret): string
    {
        $secret = strtoupper($secret);
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';
        foreach (str_split($secret) as $char) {
            $pos = strpos(self::ALPHABET, $char);
            if ($pos === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $pos;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        return $output;
    }
}

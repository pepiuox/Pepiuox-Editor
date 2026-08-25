<?php
namespace App\Services;

class TotpService
{
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const ALGO = 'sha1';

    /** Genera un secreto aleatorio (base32, 16 caracteres). */
    public static function generateSecret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        $bytes = random_bytes(10);
        for ($i = 0; $i < 16; $i++) $secret .= $chars[ord($bytes[$i]) & 31];
        return $secret;
    }

    /** Calcula el código TOTP para un secreto y timestamp dado. */
    public static function getCode(string $secret, ?int $time = null): string
    {
        $time = $time ?? time();
        $counter = intdiv($time, self::PERIOD);
        $binary = pack('N*', 0) . pack('N*', $counter);
        $secretBin = self::base32Decode($secret);
        $hash = hash_hmac(self::ALGO, $binary, $secretBin, true);
        $offset = ord($hash[19]) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset+1]) & 0xFF) << 16) |
            ((ord($hash[$offset+2]) & 0xFF) << 8) |
            (ord($hash[$offset+3]) & 0xFF)
        ) % (10 ** self::DIGITS);
        return str_pad((string)$code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /** Verifica un código con ventana de ±1 período. */
    public static function verify(string $secret, string $code): bool
    {
        $time = time();
        for ($i = -1; $i <= 1; $i++) {
            if (hash_equals(self::getCode($secret, $time + $i * self::PERIOD), $code)) return true;
        }
        return false;
    }

    /** URL otpauth:// para generar QR. */
    public static function getQrUrl(string $secret, string $account, string $issuer = 'PePiuoX Maker'): string
    {
        return 'otpauth://totp/' . rawurlencode($issuer) . ':' . rawurlencode($account)
             . '?secret=' . $secret
             . '&issuer=' . rawurlencode($issuer)
             . '&algorithm=SHA1&digits=' . self::DIGITS . '&period=' . self::PERIOD;
    }

    private static function base32Decode(string $input): string
    {
        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper(rtrim($input, '='));
        $buffer = 0; $bits = 0; $out = '';
        for ($i = 0; $i < strlen($input); $i++) {
            $v = strpos($map, $input[$i]);
            if ($v === false) continue;
            $buffer = ($buffer << 5) | $v;
            $bits += 5;
            if ($bits >= 8) {
                $bits -= 8;
                $out .= chr(($buffer >> $bits) & 0xFF);
            }
        }
        return $out;
    }
}
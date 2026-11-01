<?php
namespace App\Services;

use App\Core\Database;

class RateLimiter
{
    /**
     * Verifica si se han excedido los intentos permitidos.
     */
    public static function tooManyAttempts(string $key, int $maxAttempts, int $decaySeconds = 60): bool
    {
        $data = self::getRateLimitData($key);
        
        if (!$data) {
            return false;
        }
        
        // Si la ventana expiró, resetear
        if (time() > $data['window_end']) {
            self::clear($key);
            return false;
        }
        
        return $data['attempts'] >= $maxAttempts;
    }

    /**
     * Registra un intento y devuelve el estado actual.
     */
    public static function attempt(string $key, int $maxAttempts, int $decaySeconds = 60): array
    {
        $data = self::getRateLimitData($key);
        $now = time();
        
        // Si no existe o expiró, crear nuevo
        if (!$data || $now > $data['window_end']) {
            $windowEnd = $now + $decaySeconds;
            Database::execute(
                "INSERT INTO cache_settings (cache_key, cache_value, expires_at) 
                 VALUES (?, ?, FROM_UNIXTIME(?))
                 ON DUPLICATE KEY UPDATE 
                    cache_value = VALUES(cache_value),
                    expires_at = VALUES(expires_at)",
                [
                    'rate_limit:' . $key,
                    json_encode(['attempts' => 1, 'window_end' => $windowEnd]),
                    $windowEnd
                ]
            );
            
            return [
                'attempts' => 1,
                'remaining' => max(0, $maxAttempts - 1),
                'retry_after' => 0,
                'limited' => false,
            ];
        }
        
        // Incrementar intentos
        $attempts = $data['attempts'] + 1;
        $retryAfter = max(0, $data['window_end'] - $now);
        
        Database::execute(
            "UPDATE cache_settings SET cache_value = ? WHERE cache_key = ?",
            [
                json_encode(['attempts' => $attempts, 'window_end' => $data['window_end']]),
                'rate_limit:' . $key
            ]
        );
        
        return [
            'attempts' => $attempts,
            'remaining' => max(0, $maxAttempts - $attempts),
            'retry_after' => $retryAfter,
            'limited' => $attempts > $maxAttempts,
        ];
    }

    /**
     * Obtiene los intentos actuales sin incrementar.
     */
    public static function attempts(string $key): int
    {
        $data = self::getRateLimitData($key);
        return $data ? $data['attempts'] : 0;
    }

    /**
     * Limpia el contador de una clave.
     */
    public static function clear(string $key): void
    {
        Database::execute(
            "DELETE FROM cache_settings WHERE cache_key = ?",
            ['rate_limit:' . $key]
        );
    }

    /**
     * Obtiene el tiempo restante antes de poder intentar de nuevo.
     */
    public static function availableIn(string $key): int
    {
        $data = self::getRateLimitData($key);
        if (!$data) return 0;
        return max(0, $data['window_end'] - time());
    }

    /**
     * Obtiene los datos de rate limit de la BD.
     */
    private static function getRateLimitData(string $key): ?array
    {
        $row = Database::fetch(
            "SELECT cache_value, expires_at FROM cache_settings 
             WHERE cache_key = ? AND (expires_at IS NULL OR expires_at > NOW())",
            ['rate_limit:' . $key]
        );
        
        if (!$row) return null;
        
        $data = json_decode($row['cache_value'], true);
        if (!is_array($data)) return null;

        return $data;
    }

    /**
     * Borra las ventanas de limitación ya caducadas.
     *
     * ── Por qué existe ahora ────────────────────────────────────────────
     * La ruta `/cron/cleanup-rate-limits` llamaba a este método desde el primer
     * día y el método no existía: respondía 500. Nadie lo notó porque el
     * endpoint no era alcanzable (el token del cron no podía coincidir nunca).
     * Al arreglar el token, la llamada apareció.
     *
     * Solo toca filas con prefijo `rate_limit:` y caducadas: no es una limpieza
     * general de caché, y no borra nada que siga en vigor.
     *
     * @return int Filas eliminadas.
     * @since 2026-10-31
     */
    public static function cleanup(): int
    {
        return Database::execute(
            "DELETE FROM cache_settings
              WHERE cache_key LIKE 'rate_limit:%'
                AND expires_at IS NOT NULL
                AND expires_at < NOW()"
        );
    }
}
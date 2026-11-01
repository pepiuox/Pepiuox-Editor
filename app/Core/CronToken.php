<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\SettingService;

/**
 * Autorización de los endpoints de tareas programadas (`/cron/*`).
 *
 * ── Por qué existe ──────────────────────────────────────────────────
 * Había dos problemas, los dos medidos el 2026-10-31:
 *
 *  1. **El token no podía coincidir nunca.** `config/config.php` definía
 *     `cron_token` como `'...' . bin2hex(random_bytes(8))`, que se reevalúa en
 *     cada carga del archivo. El valor esperado cambiaba entre la comprobación
 *     y la siguiente lectura, así que los seis endpoints respondían 401 siempre.
 *  2. **Un endpoint no comprobaba nada.** `/cron/process-expired-bans` llamaba
 *     a `BanService::processExpiredBans()` sin mirar el token, y escribe en la
 *     base de datos. Cualquiera que conociera la URL lo disparaba.
 *
 * Este guardián centraliza la comprobación para que el próximo endpoint no
 * repita la lógica (ni la olvide).
 *
 * ── Reglas ─────────────────────────────────────────────────────────
 *  - Sin token configurado, **se rechaza todo**: cerrar por defecto es lo
 *    correcto cuando el secreto no existe.
 *  - La comparación es en tiempo constante (`hash_equals`) para no filtrar el
 *    token por el tiempo de respuesta.
 *  - Responde 401 en JSON y termina la petición.
 *
 * @package App\Core
 * @since   2026-10-31
 */
final class CronToken
{
    /**
     * Exige el token en la petición. Si no cuadra, responde 401 y termina.
     */
    public static function require(Request $request): void
    {
        if (self::isValid((string) $request->get('token'))) {
            return;
        }

        Response::json([
            'success' => false,
            'message' => 'Unauthorized',
        ], 401);
    }

    /**
     * ¿Es válido el token recibido?
     *
     * Público para poder probarlo sin levantar una petición HTTP.
     */
    public static function isValid(string $recibido): bool
    {
        $esperado = self::expected();

        if ($esperado === '' || $recibido === '') {
            return false;
        }

        return hash_equals($esperado, $recibido);
    }

    /**
     * Token esperado: primero el entorno, y como respaldo el ajuste
     * `general.cron_token` si alguien lo guardó desde el panel.
     *
     * El respaldo por base de datos existe para no atar el despliegue a un
     * archivo, pero el entorno manda: es lo que no se versiona.
     */
    private static function expected(): string
    {
        $cfg = require CONFIG_PATH . '/config.php';
        $desdeEntorno = (string) ($cfg['cron_token'] ?? '');

        if ($desdeEntorno !== '') {
            return $desdeEntorno;
        }

        try {
            $guardado = (string) (SettingService::get('general', 'cron_token', '') ?? '');
        } catch (\Throwable $e) {
            error_log('[Cron] No se pudo leer el token guardado: ' . $e->getMessage());
            return '';
        }

        return $guardado;
    }
}

<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\{Request, Response};

/**
 * Se coloca en lugar del limitador de peticiones cuando **no se pudo construir**.
 *
 * ── Qué problema resuelve ───────────────────────────────────────────
 * `config/routes.php` construye los limitadores dentro de un `try`. Si la
 * construcción falla, el `catch` los dejaba como arreglos vacíos: las rutas
 * sensibles —acceso, registro, recuperación de contraseña y segundo factor—
 * seguían funcionando **sin ningún límite**, y lo único que quedaba era una
 * línea en el log. Es decir, el sistema se protegía menos precisamente cuando
 * algo iba mal, y en silencio.
 *
 * Aquí se falla **en cerrado**: si no hay limitador, la ruta responde 503 con un
 * mensaje honesto en vez de aceptar intentos sin contar. La condición solo
 * aparece ante un error de construcción, que cualquier prueba detecta de
 * inmediato, así que no es un riesgo real de dejar el sitio sin servicio.
 *
 * Por qué no se deja pasar: en estas rutas, no limitar significa permitir
 * fuerza bruta sobre las contraseñas. Entre degradar la disponibilidad de un
 * formulario y abrir el acceso, se elige lo primero, y se dice en voz alta.
 *
 * @package App\Middleware
 * @since   2026-10-31
 */
final class RateLimitUnavailableMiddleware
{
    /**
     * Rechaza la petición con 503 y termina.
     */
    public function handle(Request $request): void
    {
        error_log(
            '[RateLimit] Protección no disponible: se rechaza la petición a '
            . $request->uri() . ' en lugar de aceptarla sin límite.'
        );

        if ($request->isAjax() || str_starts_with($request->uri(), '/api/')) {
            Response::json([
                'success' => false,
                'message' => 'La protección contra intentos repetidos no está disponible en este momento.',
                'code'    => 'RATE_LIMIT_UNAVAILABLE',
            ], 503);
        }

        Response::abort(
            503,
            'La protección contra intentos repetidos no está disponible en este momento. '
            . 'No se aceptan peticiones sin límite: vuelve a intentarlo en unos minutos.'
        );
    }
}

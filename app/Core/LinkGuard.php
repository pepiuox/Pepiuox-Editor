<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Reglas de validación de destinos (enlaces y fuentes de imagen).
 *
 * ── Por qué existe ──────────────────────────────────────────────────
 * El contenido editable del sitio incluye direcciones que se pintan en
 * `href` y `src`: los botones de la portada, los enlaces del pie y las
 * imágenes. Todas vienen de un formulario, así que hay que decidir qué se
 * acepta con **la misma regla** en todas partes. Antes vivía dentro de
 * `LandingContentService`; al añadir el pie se movió aquí para no tener dos
 * listas de esquemas permitidos que se desincronicen con el tiempo.
 *
 * ── Qué se acepta ───────────────────────────────────────────────────
 *  - Ruta del propio sitio que empieza por `/` (`/login`, `/funciones`).
 *    Se rechaza `//host`, que es una ruta protocolo-relativa hacia otro dominio.
 *  - Ancla dentro de la página (`#caracteristicas`).
 *  - URL absoluta `http` o `https`.
 *
 * ── Qué se rechaza ──────────────────────────────────────────────────
 * `javascript:`, `data:`, `vbscript:` y cualquier otro esquema. También el
 * vacío. La razón es simple: el contenido es editable desde el panel y no debe
 * poder convertirse en un inyector de scripts.
 *
 * @package App\Core
 * @since   2026-10-31
 */
final class LinkGuard
{
    /**
     * ¿Es un destino seguro para pintar en `href` o `src`?
     *
     * @param string $valor Ruta, ancla o URL.
     * @param int    $maxLargo Longitud máxima aceptada.
     */
    public static function isSafe(string $valor, int $maxLargo = 300): bool
    {
        if ($valor === '' || mb_strlen($valor) > $maxLargo) {
            return false;
        }

        $rutaInterna = str_starts_with($valor, '/') && !str_starts_with($valor, '//');
        $ancla       = str_starts_with($valor, '#');
        $externa     = (bool) preg_match('#^https?://[^\s]+$#i', $valor);

        return $rutaInterna || $ancla || $externa;
    }

    /**
     * ¿Es un destino válido para un enlace del sitio?
     *
     * Igual que `isSafe`, pero sin anclas sueltas: un enlace del pie que apunte
     * solo a `#` no lleva a ninguna parte y se considera un error de edición.
     */
    public static function isSafeLink(string $valor, int $maxLargo = 300): bool
    {
        return $valor !== '#'
            && !str_starts_with($valor, '#')
            && self::isSafe($valor, $maxLargo);
    }
}

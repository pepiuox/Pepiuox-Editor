<?php
/**
 * Carga mínima de variables de entorno.
 *
 * Sin dependencias externas: PHP plano, como pide el principio 3 de la constitución.
 *
 * Precedencia:
 *   1. Variables ya presentes en el proceso (getenv / $_ENV). Producción: Apache SetEnv,
 *      systemd, Docker.
 *   2. El archivo .env del proveedor en 01-TOOLS/<proveedor>/.env. Desarrollo local.
 *
 * El archivo .env nunca se confirma en git (principio 15).
 */

declare(strict_types=1);

/**
 * Lee un archivo .env y deja sus claves en el entorno del proceso.
 *
 * Formato: una clave por línea, `CLAVE=valor`. Las líneas vacías y las que empiezan
 * por `#` se ignoran. Las comillas simples o dobles alrededor del valor se quitan.
 * Una clave que ya existe en el proceso no se sobrescribe: el entorno real gana.
 *
 * No falla si el archivo no existe: puede que el entorno venga de fuera.
 */
function load_env_file(string $file): void
{
    if (!is_file($file) || !is_readable($file)) {
        return;
    }

    $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines ?: [] as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);

        if ($key === '' || getenv($key) !== false) {
            continue;
        }

        $value = trim(trim($value), "\"'");

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}

/**
 * Devuelve el valor de una clave del entorno, o el valor por defecto.
 *
 * Un valor vacío cuenta como ausente: es lo que hace útil a $default.
 */
function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);

    if ($value !== false && $value !== '') {
        return $value;
    }

    $value = $_ENV[$key] ?? null;

    if ($value !== null && $value !== '') {
        return (string) $value;
    }

    return $default;
}

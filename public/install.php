<?php
/**
 * Acceso al instalador desde /public/install.php.
 *
 * Por qué existe este archivo: la raíz del proyecto reescribe todo hacia
 * `public/` (.htaccess regla 6) y muchos servidores reales usan nginx, que
 * ignora .htaccess y manda las rutas al front controller. En los dos casos la
 * petición a /install.php acababa ejecutando la aplicación, que sin base de
 * datos responde 500 y hace imposible instalar.
 *
 * Con este archivo, /install.php resuelve tanto si el document root es la raíz
 * del proyecto como si es public/:
 *
 *   - nginx con raíz = proyecto  → sirve install.php de la raíz.
 *   - Apache con la regla 5.b    → sirve install.php de la raíz.
 *   - Apache sin la regla        → reescribe a public/install.php (este archivo).
 *   - nginx con raíz = public/   → sirve este archivo.
 *
 * Un único instalador, cuatro caminos. Toda la lógica (token, límite de
 * intentos, permiso remoto y autodesactivación) vive en el de la raíz.
 *
 * @package Installer
 */

declare(strict_types=1);

$installer = dirname(__DIR__) . '/install.php';

if (is_file($installer)) {
    require $installer;
    exit;
}

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Instalador no disponible</title>'
    . '<body style="font-family:system-ui;padding:2rem;max-width:680px;margin:auto">'
    . '<h1 style="font-size:1.2rem">Instalador no disponible</h1>'
    . '<p>No se encontró <code>install.php</code> en la raíz del proyecto. '
    . 'La instalación por consola sigue disponible: <code>docs/INSTALL.md</code>.</p>'
    . '</body></html>';

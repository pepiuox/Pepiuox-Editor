<?php
/**
 * Configuración de base de datos.
 *
 * Aquí NO hay credenciales. Se leen del entorno, en este orden:
 *   1. Variables del proceso (producción: Apache SetEnv, systemd, Docker, export).
 *   2. 01-TOOLS/mysql/.env (desarrollo local; git lo ignora).
 *
 * Constitución v1.0.0, principio 15: ningún secreto se confirma en git.
 * Si te falta el .env, cópialo de 01-TOOLS/mysql/.env.example.
 */

declare(strict_types=1);

require_once __DIR__ . '/env.php';

// Desarrollo local: las credenciales viven aquí. Si no existe, el entorno del proceso manda.
load_env_file(__DIR__ . '/../01-TOOLS/mysql/.env');

$dbname   = env('DB_NAME', '');
$username = env('DB_USER', '');
$password = env('DB_PASSWORD', '');

if ($dbname === '' || $username === '' || $password === '') {
    throw new RuntimeException(
        'Faltan credenciales de MySQL. Copia 01-TOOLS/mysql/.env.example a '
        . '01-TOOLS/mysql/.env y rellena DB_NAME, DB_USER y DB_PASSWORD, '
        . 'o defínelas como variables de entorno.'
    );
}

return [
    'driver'   => env('DB_DRIVER', 'mysql'),
    'host'     => env('DB_HOST', '127.0.0.1'),
    'port'     => (int) env('DB_PORT', '3306'),
    'dbname'   => $dbname,
    'username' => $username,
    'password' => $password,
    'charset'  => env('DB_CHARSET', 'utf8mb4'),
    'collation'=> env('DB_COLLATION', 'utf8mb4_uca1400_ai_ci'),
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
       // PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_uca1400_ai_ci",
    ],
];

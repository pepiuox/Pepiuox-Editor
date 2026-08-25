<?php
/**
 * Configuración de base de datos.
 * En producción, usa variables de entorno ($_ENV / getenv).
 */
return [
    'driver'   => 'mysql',
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'dbname'   => 'web_editor_db',
    'username' => 'pepiuox',
    'password' => 'Tru3L@ve',
    'charset'  => 'utf8mb4',
    'collation'=> 'utf8mb4_uca1400_ai_ci',
    'options'  => [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
       // PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_uca1400_ai_ci",
    ],
];
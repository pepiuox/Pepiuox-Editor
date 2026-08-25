# PePiuoX Maker — Instalación

## Requisitos
- PHP 8.1+ con extensiones: pdo_mysql, mbstring, json, openssl
- MariaDB 10.6+ / MySQL 8.0+
- Apache con mod_rewrite (o nginx equivalente)

## Pasos
1. Clona/copía el proyecto en tu servidor web.
2. Crea la base de datos:
   ```sql
   CREATE DATABASE web_editor_db CHARACTER SET utf8mb4 COLLATE utf8mb4_uca1400_ai_ci;
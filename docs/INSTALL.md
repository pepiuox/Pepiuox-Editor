# 🚀 Guía de Instalación — PePiuoX Editor

> **Versión:** 1.0.0  
> **Última actualización:** 25 de agosto de 2026  
> **Tiempo estimado de instalación:** 15-30 minutos

---

## 📋 Índice

1. [Requisitos del Sistema](#1-requisitos-del-sistema)
2. [Estructura del Proyecto](#2-estructura-del-proyecto)
3. [Instalación en Desarrollo Local](#3-instalación-en-desarrollo-local)
4. [Instalación en Producción](#4-instalación-en-producción)
5. [Configuración del Servidor Web](#5-configuración-del-servidor-web)
6. [Configuración de Cron Jobs](#6-configuración-de-cron-jobs)
7. [Primer Acceso](#7-primer-acceso)
8. [Verificación Post-Instalación](#8-verificación-post-instalación)
9. [Hardening para Producción](#9-hardening-para-producción)
10. [Troubleshooting](#10-troubleshooting)
11. [Comandos Útiles](#11-comandos-útiles)

---

## 1. Requisitos del Sistema

### 🔧 Requisitos mínimos

| Componente | Versión Mínima | Recomendado |
|------------|----------------|-------------|
| **PHP** | 8.1 | 8.2+ |
| **MySQL/MariaDB** | 8.0 / 10.5 | 10.6+ |
| **Apache** | 2.4 | 2.4+ con mod_rewrite |
| **Nginx** | 1.18 | 1.20+ |
| **Composer** | 2.0 | 2.5+ |

### 📦 Extensiones PHP requeridas

```bash
# Verificar extensiones instaladas
php -m

# Extensiones necesarias:
pdo_mysql       # Conexión a MySQL
mbstring        # Soporte multibyte
json            # Manejo de JSON
openssl         # Encriptación
curl            # Peticiones HTTP
gd              # Procesamiento de imágenes
zip             # Compresión (backups)
intl            # Internacionalización
fileinfo        # Validación MIME
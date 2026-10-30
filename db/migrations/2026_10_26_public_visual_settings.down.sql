-- ═══════════════════════════════════════════════════════════════════
-- Reversión de 2026_10_26_public_visual_settings.up.sql
--
-- NO-OP DELIBERADO. No hay nada que deshacer en el esquema: la migración
-- sólo inserta filas de configuración en una tabla que ya existía.
--
-- Por qué no se borran las filas: son configuración escrita por el
-- administrador (logos, colores, CSS). Un `down` que las eliminara
-- destruiría trabajo real y archivos referenciados en `uploads/`, que es
-- justo lo que el encargo prohíbe. Si de verdad se quiere volver al
-- estado inicial, la vía es el botón «Restablecer configuración» de la
-- interfaz, que es explícito y reversible.
--
-- La sentencia existe sólo para que el aplicador tenga algo que informar.
-- ═══════════════════════════════════════════════════════════════════

DO 0;

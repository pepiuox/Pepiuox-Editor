<?php
/**
 * Aviso de cookies del sitio público.
 *
 * Se incluye al final del `<body>` y **solo se pinta si hace falta**:
 * `AnalyticsProvidersService::necesitaAviso()` devuelve true únicamente cuando
 * hay algún proveedor configurado que instale cookies y el visitante todavía no
 * ha decidido. Si no hay medición con cookies, no aparece nada.
 *
 * Los scripts de esos proveedores llegan al navegador como `type="text/plain"`
 * y aquí se reactivan al aceptar. Rechazar no los carga en absoluto.
 *
 * @package PePiuoX\Editor\Views
 */

use App\Services\AnalyticsProvidersService;
use App\Services\SettingService;

if (!AnalyticsProvidersService::necesitaAviso()) {
    return;
}

$sitio = \App\Services\SiteSettings::name();
$politica = trim((string) SettingService::get('cookies', 'policy_url', ''));
?>

<style>
/* Aviso de cookies. Estilos propios y con ámbito: no dependen del tema activo. */
.cookie-banner {
    position: fixed;
    left: 1rem;
    right: 1rem;
    bottom: 1rem;
    z-index: 1080;
    margin: 0 auto;
    max-width: 780px;
    background: var(--bg-card, #161b22);
    color: var(--text-main, #e6edf3);
    border: 1px solid var(--border-color, #30363d);
    border-radius: 14px;
    box-shadow: 0 18px 40px rgba(0, 0, 0, .35);
    padding: 1.1rem 1.25rem;
    font-size: .925rem;
    line-height: 1.55;
}
.cookie-banner p { margin: 0 0 .85rem; }
.cookie-banner a { color: var(--color-link, #58a6ff); text-decoration: underline; }
.cookie-banner__actions { display: flex; flex-wrap: wrap; gap: .6rem; }
.cookie-banner button {
    font: inherit;
    font-weight: 600;
    border-radius: 999px;
    padding: .5rem 1.15rem;
    cursor: pointer;
    border: 1px solid var(--border-color, #30363d);
    background: transparent;
    color: var(--text-main, #e6edf3);
}
.cookie-banner button:hover { border-color: var(--color-link, #58a6ff); }
.cookie-banner button:focus-visible { outline: 2px solid var(--color-link, #58a6ff); outline-offset: 2px; }
.cookie-banner__accept {
    background: var(--color-button, #00d4aa);
    border-color: var(--color-button, #00d4aa);
    color: var(--color-button-text, #041a04);
}
@media (max-width: 575.98px) {
    .cookie-banner { left: .5rem; right: .5rem; bottom: .5rem; padding: 1rem; }
    .cookie-banner button { flex: 1 1 100%; }
}
</style>

<div class="cookie-banner" role="dialog" aria-live="polite" aria-label="Aviso de cookies" id="cookieBanner">
  <p>
    <strong><?= e($sitio) ?></strong> usa cookies para medir cómo se usa el sitio.
    Solo se activan si las aceptas; hasta entonces no se carga ninguna herramienta de medición
    que las instale.
    <?php if ($politica !== ''): ?>
      Puedes leer el detalle en <a href="<?= e($politica) ?>">la política de cookies</a>.
    <?php endif; ?>
  </p>
  <div class="cookie-banner__actions">
    <button type="button" class="cookie-banner__accept" id="cookieAccept">Aceptar la medición</button>
    <button type="button" id="cookieReject">Solo lo esencial</button>
  </div>
</div>

<script>
(function () {
  'use strict';

  var banner = document.getElementById('cookieBanner');
  if (!banner) { return; }

  /** Convierte los scripts en cuarentena en scripts reales, en orden. */
  function activarMedicion() {
    var pendientes = document.querySelectorAll('script[data-cookie-consent]');

    Array.prototype.forEach.call(pendientes, function (viejo) {
      var nuevo = document.createElement('script');

      Array.prototype.forEach.call(viejo.attributes, function (atributo) {
        if (atributo.name === 'type' || atributo.name === 'data-cookie-consent') { return; }
        nuevo.setAttribute(atributo.name, atributo.value);
      });

      if (viejo.src) {
        nuevo.src = viejo.src;
      } else {
        nuevo.textContent = viejo.textContent;
      }

      viejo.parentNode.replaceChild(nuevo, viejo);
    });
  }

  function decidir(valor) {
    document.cookie = 'pep_cookie_consent=' + valor + '; path=/; max-age=31536000; samesite=lax';
    banner.remove();
    if (valor === 'todas') { activarMedicion(); }
  }

  document.getElementById('cookieAccept').addEventListener('click', function () { decidir('todas'); });
  document.getElementById('cookieReject').addEventListener('click', function () { decidir('esenciales'); });
})();
</script>

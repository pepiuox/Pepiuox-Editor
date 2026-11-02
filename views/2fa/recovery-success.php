<?php
$pageTitle = '2FA Activado - Códigos de Recuperación';
?>
<div class="container" style="max-width:700px;margin:2rem auto;padding:1.5rem">
  <div class="card">
    <div class="card-header">
      <h3><i class="bi bi-shield-check text-success"></i> ¡2FA Activado Correctamente!</h3>
    </div>
    <div class="card-body">
      <div class="alert alert-success">
        <i class="bi bi-check-circle"></i>
        <strong>La autenticación en dos pasos está ahora activa en tu cuenta.</strong>
      </div>

      <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        <strong>¡IMPORTANTE!</strong> Estos códigos de recuperación se muestran <strong>UNA SOLA VEZ</strong>. 
        Guárdalos en un lugar seguro (gestor de contraseñas, caja fuerte, etc.). 
        Los necesitarás si pierdes acceso a tu app autenticadora.
      </div>

      <!-- ═══ CÓDIGOS ═══ -->
      <div class="recovery-codes-grid" style="display:grid;grid-template-columns:repeat(2,1fr);gap:.75rem;margin:1.5rem 0;padding:1.25rem;background:var(--bg-principal);border:1px solid var(--border);border-radius:var(--radius)">
        <?php foreach ($formattedCodes as $i => $code): ?>
          <div class="recovery-code-item" style="display:flex;align-items:center;gap:.5rem;padding:.6rem .9rem;background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-sm);font-family:var(--font-mono);font-size:1rem;letter-spacing:1px">
            <span style="color:var(--text-muted);font-size:.75rem;width:20px"><?= str_pad($i+1, 2, '0', STR_PAD_LEFT) ?></span>
            <code style="color:var(--accent);font-weight:600;flex:1"><?= e($code) ?></code>
            <button type="button" class="btn btn-ghost btn-sm" onclick="copyCode('<?= e($code) ?>')" title="Copiar" style="padding:2px 6px">
              <i class="bi bi-clipboard"></i>
            </button>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- ═══ ACCIONES ═══ -->
      <div class="d-flex gap-2 flex-wrap" style="margin-top:1.5rem">
        <a href="<?= url('admin/2fa/download-codes') ?>" class="btn btn-primary">
          <i class="bi bi-download"></i> Descargar como TXT
        </a>
        <button type="button" class="btn btn-ghost" onclick="copyAllCodes()">
          <i class="bi bi-clipboard-check"></i> Copiar todos
        </button>
        <a href="<?= url('admin/profile') ?>" class="btn btn-ghost ms-auto">
          <i class="bi bi-check"></i> He guardado los códigos
        </a>
      </div>

      <div class="alert alert-info" style="margin-top:1.5rem;font-size:.85rem">
        <i class="bi bi-info-circle"></i>
        <strong>Consejos de seguridad:</strong>
        <ul style="margin:.5rem 0 0 1.25rem;line-height:1.8">
          <li>Cada código es de <strong>un solo uso</strong></li>
          <li>Tienes <strong><?= count($formattedCodes) ?> códigos</strong> en total</li>
          <li>Cuando te queden pocos, el sistema te pedirá regenerarlos</li>
          <li>Puedes regenerarlos en cualquier momento desde tu perfil</li>
          <li>Al usar un código, recibirás una notificación por email</li>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Copia oculta para poder copiar la lista completa -->
<textarea id="allCodes" readonly tabindex="-1" aria-hidden="true"
          style="position:fixed;top:0;left:-9999px;width:1px;height:1px;opacity:0"><?= e(implode("\n", $formattedCodes)) ?></textarea>

<script>
(function () {
  'use strict';

  /**
   * Copia texto al portapapeles y dice si DE VERDAD se copió.
   *
   * `navigator.clipboard` solo existe en contexto seguro (HTTPS o localhost) y,
   * cuando existe, puede rechazar la promesa: sin foco en el documento, sin
   * permiso, o dentro de un iframe. Antes se llamaba a ciegas y sin `catch`, así
   * que fuera de HTTPS el botón no hacía nada y no avisaba de nada.
   *
   * @param {string} texto
   * @returns {Promise<boolean>}
   */
  function copiarTexto(texto) {
    return new Promise(function (resolve) {
      if (window.isSecureContext && navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(texto).then(
          function () { resolve(true); },
          function () { resolve(respaldo(texto)); }
        );
        return;
      }
      resolve(respaldo(texto));
    });
  }

  /**
   * Plan B sin permisos: un textarea real, seleccionado y copiado con
   * `execCommand`. Devuelve lo que el navegador responde, no lo que suponemos.
   *
   * @param {string} texto
   * @returns {boolean}
   */
  function respaldo(texto) {
    var area = document.createElement('textarea');
    area.value = texto;
    area.setAttribute('readonly', '');
    area.style.position = 'fixed';
    area.style.top = '0';
    area.style.left = '-9999px';
    document.body.appendChild(area);

    var copiado = false;
    try {
      area.focus();
      area.select();
      area.setSelectionRange(0, area.value.length);
      copiado = document.execCommand('copy');
    } catch (e) {
      copiado = false;
    }

    document.body.removeChild(area);
    return copiado === true;
  }

  /**
   * Aviso flotante. Usa las clases que sí existen en maker.css: `.toast` más
   * `.success` / `.error` / `.info`, con espacio. `toast-success` no está
   * definida en la hoja del panel.
   *
   * @param {string} mensaje
   * @param {string} tipo
   */
  function avisar(mensaje, tipo) {
    var contenedor = document.querySelector('.toast-container');
    if (!contenedor) {
      contenedor = document.createElement('div');
      contenedor.className = 'toast-container';
      document.body.appendChild(contenedor);
    }

    var aviso = document.createElement('div');
    aviso.className = 'toast ' + (tipo || 'info');
    aviso.textContent = mensaje;
    contenedor.appendChild(aviso);

    setTimeout(function () {
      aviso.style.opacity = '0';
      setTimeout(function () { aviso.remove(); }, 300);
    }, 4000);
  }

  /** Selecciona la lista visible para que el usuario copie con Ctrl+C. */
  function seleccionarLista() {
    var rejilla = document.querySelector('.recovery-codes-grid');
    if (!rejilla || !window.getSelection) {
      return;
    }
    var rango = document.createRange();
    rango.selectNodeContents(rejilla);
    var seleccion = window.getSelection();
    seleccion.removeAllRanges();
    seleccion.addRange(rango);
  }

  window.copyCode = function (codigo) {
    copiarTexto(codigo).then(function (ok) {
      if (ok) {
        avisar('Código copiado al portapapeles', 'success');
      } else {
        avisar('No se pudo copiar solo. Selecciona el código y pulsa Ctrl+C.', 'error');
      }
    });
  };

  window.copyAllCodes = function () {
    var area = document.getElementById('allCodes');
    var texto = area ? area.value : '';

    copiarTexto(texto).then(function (ok) {
      if (ok) {
        avisar('Los ' + texto.split('\n').length + ' códigos están en el portapapeles', 'success');
      } else {
        seleccionarLista();
        avisar('No se pudo copiar solo: la lista queda seleccionada, pulsa Ctrl+C.', 'error');
      }
    });
  };
})();
</script>
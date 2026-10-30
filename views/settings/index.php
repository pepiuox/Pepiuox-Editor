<?php
/**
 * Configuración del sistema + personalización visual de la web pública.
 *
 * Estructura: una lista de pestañas y, debajo, **formularios hermanos** (nunca
 * anidados: HTML no permite formularios dentro de formularios y los huecos de
 * subida necesitan `multipart/form-data`).
 *
 *  - Formulario A → `admin/settings/update`            (grupos del sistema)
 *  - Huecos de logo y favicon → un formulario por hueco
 *  - Formulario B → `admin/settings/brand`            (tema, colores, CSS)
 *
 * El panel no cambia de aspecto: se reutilizan `card`, `nav-tabs`, `form-grid`,
 * `form-control` y `btn` de `maker.css`, y los widgets nuevos usan clases con
 * prefijo `ppf-` definidas en el `<style>` del final, con ámbito local.
 *
 * @var array  $settings     Grupos del sistema (general, seo, cache, mail).
 * @var array  $public       Configuración pública (PublicThemeService::all()).
 * @var array  $logoSlots    Catálogo de huecos de logotipo.
 * @var array  $faviconSlots Catálogo de huecos de favicon.
 * @var array  $colorLabels  Las 16 claves de color con su etiqueta.
 * @var array  $presets      Temas predefinidos con su paleta.
 * @var array  $assetUrls    URL del archivo actual por hueco (null si no hay).
 * @var string $activeTab    Pestaña activa al cargar.
 */

$pageTitle = 'Configuración del Sistema';
$s       = $settings ?? [];
$general = $s['general'] ?? [];
$seo     = $s['seo'] ?? [];
$cache   = $s['cache'] ?? [];
$mail    = $s['mail'] ?? [];

$pub               = $public ?? [];
$logoSlots         = $logoSlots ?? [];
$faviconSlots      = $faviconSlots ?? [];
$colorLabels       = $colorLabels ?? [];
$surfaceTextLabels = $surfaceTextLabels ?? [];
$surfaceTextValues = $surfaceTextValues ?? [];
$presets           = $presets ?? [];
$assetUrls         = $assetUrls ?? [];

$activeTab   = $activeTab ?? 'general';
$currentTheme = (string) ($pub['theme'] ?? 'personalizado');
$logoAlt     = (string) ($pub['logo_alt'] ?? '');

/** Pestañas en orden de presentación: sistema primero, personalización después. */
$tabs = [
    'general'  => ['label' => 'General',            'icon' => 'bi-info-circle'],
    'seo'      => ['label' => 'SEO',                 'icon' => 'bi-search'],
    'cache'    => ['label' => 'Cache',               'icon' => 'bi-hdd'],
    'mail'     => ['label' => 'Correo',              'icon' => 'bi-envelope'],
    'identity' => ['label' => 'Identidad visual',    'icon' => 'bi-palette'],
    'logos'    => ['label' => 'Logotipos e iconos',  'icon' => 'bi-images'],
    'favicon'  => ['label' => 'Favicon',             'icon' => 'bi-bookmark-star'],
    'themes'   => ['label' => 'Temas',               'icon' => 'bi-circle-half'],
    'colors'   => ['label' => 'Colores',             'icon' => 'bi-droplet-half'],
    'css'      => ['label' => 'CSS personalizado',   'icon' => 'bi-code-slash'],
    'preview'  => ['label' => 'Vista previa',        'icon' => 'bi-eyeglasses'],
];
?>

<div class="card">
  <div class="card-header">
    <h3><i class="bi bi-gear"></i> Configuración del Sistema</h3>
    <p class="form-text mb-0">
      La personalización visual afecta <strong>solo a la web pública</strong>. El panel de administración conserva su diseño.
    </p>
  </div>
  <div class="card-body">

    <!-- Pestañas de Navegación -->
    <ul class="nav-tabs mb-4" role="tablist">
      <?php foreach ($tabs as $id => $tab): ?>
        <li role="presentation">
          <button type="button"
                  class="nav-link <?= $id === $activeTab ? 'active' : '' ?>"
                  id="tabbtn-<?= e($id) ?>"
                  data-tab="<?= e($id) ?>"
                  role="tab"
                  aria-selected="<?= $id === $activeTab ? 'true' : 'false' ?>"
                  aria-controls="tab-<?= e($id) ?>">
            <i class="bi <?= e($tab['icon']) ?>"></i> <?= e($tab['label']) ?>
          </button>
        </li>
      <?php endforeach; ?>
    </ul>

    <!-- ══════════════════════════════════════════════════════════
         A · AJUSTES DEL SISTEMA (grupos general, seo, cache, mail)
         ══════════════════════════════════════════════════════════ -->
    <form method="post" action="<?= url('admin/settings/update') ?>" id="settingsForm">
      <?= \App\Core\CSRF::field() ?>
      <input type="hidden" name="return_tab" value="general" data-return-tab>

      <!-- 1. GENERAL -->
      <div class="tab-content <?= $activeTab === 'general' ? 'active' : '' ?>" data-tab="general" id="tab-general" role="tabpanel" aria-labelledby="tabbtn-general">
        <div class="ppf-note ppf-note--info">
          <i class="bi bi-broadcast"></i>
          <span>
            Estos valores se aplican en la <strong>web pública</strong>: nombre en la cabecera,
            el pie y el título de la pestaña del navegador; descripción en la meta descripción
            cuando la página no trae la suya; idioma en <code>&lt;html lang&gt;</code>; zona horaria
            en las fechas del sitio.
          </span>
        </div>
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Nombre del Sitio</label>
            <input type="text" name="settings[general][site_name]" class="form-control" value="<?= e($general['site_name'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Descripción Corta</label>
            <input type="text" name="settings[general][site_description]" class="form-control" value="<?= e($general['site_description'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Idioma por defecto</label>
            <select name="settings[general][site_locale]" class="form-select">
              <option value="es" <?= ($general['site_locale'] ?? 'es') === 'es' ? 'selected' : '' ?>>🇪🇸 Español</option>
              <option value="en" <?= ($general['site_locale'] ?? '') === 'en' ? 'selected' : '' ?>>🇬🇧 English</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Zona Horaria</label>
            <select name="settings[general][site_timezone]" class="form-select">
              <?php foreach (timezone_identifiers_list() as $tz): ?>
                <option value="<?= e($tz) ?>" <?= ($general['site_timezone'] ?? 'Europe/Madrid') === $tz ? 'selected' : '' ?>><?= e($tz) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- 2. SEO -->
      <div class="tab-content <?= $activeTab === 'seo' ? 'active' : '' ?>" data-tab="seo" id="tab-seo" role="tabpanel" aria-labelledby="tabbtn-seo">
        <div class="form-grid">
          <div class="form-group full-width">
            <label class="form-label">Meta Title por defecto</label>
            <input type="text" name="settings[seo][default_meta_title]" class="form-control" value="<?= e($seo['default_meta_title'] ?? '') ?>">
          </div>
          <div class="form-group full-width">
            <label class="form-label">Meta Description por defecto</label>
            <textarea name="settings[seo][default_meta_description]" class="form-control" rows="3"><?= e($seo['default_meta_description'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Robots por defecto</label>
            <select name="settings[seo][default_robots]" class="form-select">
              <option value="index, follow" <?= ($seo['default_robots'] ?? '') === 'index, follow' ? 'selected' : '' ?>>Index, Follow</option>
              <option value="noindex, follow" <?= ($seo['default_robots'] ?? '') === 'noindex, follow' ? 'selected' : '' ?>>Noindex, Follow</option>
              <option value="noindex, nofollow" <?= ($seo['default_robots'] ?? '') === 'noindex, nofollow' ? 'selected' : '' ?>>Noindex, Nofollow</option>
            </select>
          </div>
        </div>
      </div>

      <!-- 3. CACHE -->
      <div class="tab-content <?= $activeTab === 'cache' ? 'active' : '' ?>" data-tab="cache" id="tab-cache" role="tabpanel" aria-labelledby="tabbtn-cache">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Estado del Cache</label>
            <div class="d-flex align-center gap-2 mt-1">
              <label class="form-switch">
                <input type="hidden" name="settings[cache][enabled]" value="0">
                <input type="checkbox" name="settings[cache][enabled]" value="1" class="form-check-input" <?= ($cache['enabled'] ?? false) ? 'checked' : '' ?>>
                <span class="form-check-label ms-2">Activar caché de páginas</span>
              </label>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">TTL (Tiempo de vida en segundos)</label>
            <input type="number" name="settings[cache][ttl]" class="form-control" value="<?= e((string) ($cache['ttl'] ?? 3600)) ?>" min="60" step="60">
            <small class="form-text">Ej: 3600 = 1 hora</small>
          </div>
        </div>
      </div>

      <!-- 4. CORREO -->
      <div class="tab-content <?= $activeTab === 'mail' ? 'active' : '' ?>" data-tab="mail" id="tab-mail" role="tabpanel" aria-labelledby="tabbtn-mail">
        <div class="form-grid">
          <div class="form-group">
            <label class="form-label">Servidor SMTP (Host)</label>
            <input type="text" name="settings[mail][smtp_host]" class="form-control" placeholder="smtp.gmail.com" value="<?= e($mail['smtp_host'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Puerto SMTP</label>
            <input type="number" name="settings[mail][smtp_port]" class="form-control" value="<?= e((string) ($mail['smtp_port'] ?? 587)) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Usuario SMTP</label>
            <input type="text" name="settings[mail][smtp_user]" class="form-control" value="<?= e($mail['smtp_user'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Contraseña SMTP</label>
            <input type="password" name="settings[mail][smtp_pass]" class="form-control" value="<?= e($mail['smtp_pass'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Email Remitente</label>
            <input type="email" name="settings[mail][from_email]" class="form-control" value="<?= e($mail['from_email'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Nombre Remitente</label>
            <input type="text" name="settings[mail][from_name]" class="form-control" value="<?= e($mail['from_name'] ?? '') ?>">
          </div>
        </div>
      </div>

      <!-- Barra de guardado del sistema: sólo visible en sus propias pestañas.
           Antes esta barra y la de personalización estaban siempre visibles a la
           vez, y pulsar la de arriba estando en «Temas» enviaba el formulario
           equivocado: el tema no se guardaba y parecía que la app lo ignoraba. -->
      <div class="d-flex justify-end gap-2 mt-4 pt-3 border-top ppf-actions"
           data-form-scope="general seo cache mail">
        <button type="button" class="btn btn-ghost" onclick="history.back()">Cancelar</button>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-circle"></i> Guardar Configuración
        </button>
      </div>
    </form>

    <!-- ══════════════════════════════════════════════════════════
         B · LOGOTIPOS (un formulario por hueco: multipart)
         ══════════════════════════════════════════════════════════ -->
    <div class="tab-content <?= $activeTab === 'logos' ? 'active' : '' ?>" data-tab="logos" id="tab-logos" role="tabpanel" aria-labelledby="tabbtn-logos">
      <p class="form-text">
        PNG, JPG, WEBP o SVG · máximo <?= e(\App\Services\SettingsAssetService::humanMaxUpload(false)) ?> · se conservan las proporciones originales.
        El logo de escritorio, tablet y móvil se sirve con <code>&lt;picture&gt;</code>, así que el navegador
        elige el adecuado en cada tamaño de pantalla.
      </p>
      <div class="ppf-asset-grid">
        <?php foreach ($logoSlots as $slot => $meta): ?>
          <?php $currentUrl = $assetUrls[$slot] ?? null; ?>
          <article class="ppf-asset-card">
            <header class="ppf-asset-head">
              <span class="ppf-asset-title"><?= e($meta['label']) ?></span>
              <span class="ppf-asset-hint"><?= e($meta['hint']) ?></span>
              <?php if (!empty($meta['where'])): ?>
                <span class="ppf-asset-where"><i class="bi bi-geo-alt"></i> <?= e($meta['where']) ?></span>
              <?php endif; ?>
              <?php if (!empty($meta['note'])): ?>
                <span class="ppf-asset-note"><i class="bi bi-exclamation-circle"></i> <?= e($meta['note']) ?></span>
              <?php endif; ?>
            </header>

            <div class="ppf-asset-preview <?= $currentUrl === null ? 'is-empty' : '' ?>">
              <?php if ($currentUrl !== null): ?>
                <img src="<?= e($currentUrl) ?>" alt="<?= e($meta['label']) ?>" loading="lazy">
              <?php else: ?>
                <span class="ppf-asset-empty"><i class="bi bi-image"></i> Sin imagen</span>
              <?php endif; ?>
            </div>

            <form method="post" action="<?= url('admin/settings/brand/asset') ?>"
                  enctype="multipart/form-data" class="ppf-asset-form">
              <?= \App\Core\CSRF::field() ?>
              <input type="hidden" name="slot" value="<?= e($slot) ?>">
              <input type="file" name="asset" class="form-control form-control-sm"
                     accept=".png,.jpg,.jpeg,.webp,.svg,image/png,image/jpeg,image/webp,image/svg+xml"
                     aria-label="Archivo para <?= e($meta['label']) ?>" required>
              <button type="submit" class="btn btn-primary btn-sm ppf-asset-submit">
                <i class="bi bi-upload"></i> <?= $currentUrl === null ? 'Subir' : 'Reemplazar' ?>
              </button>
            </form>

            <?php if ($currentUrl !== null): ?>
              <form method="post" action="<?= url('admin/settings/brand/asset/delete') ?>"
                    class="ppf-asset-delete" data-confirm="¿Eliminar «<?= e($meta['label']) ?>»? El sitio volverá a la marca textual.">
                <?= \App\Core\CSRF::field() ?>
                <input type="hidden" name="slot" value="<?= e($slot) ?>">
                <button type="submit" class="btn btn-ghost btn-sm ppf-danger">
                  <i class="bi bi-trash"></i> Eliminar
                </button>
              </form>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         C · FAVICON
         ══════════════════════════════════════════════════════════ -->
    <div class="tab-content <?= $activeTab === 'favicon' ? 'active' : '' ?>" data-tab="favicon" id="tab-favicon" role="tabpanel" aria-labelledby="tabbtn-favicon">
      <p class="form-text">
        ICO, PNG o SVG · máximo <?= e(\App\Services\SettingsAssetService::humanMaxUpload(true)) ?>. Solo se emiten en el <code>&lt;head&gt;</code> público las
        etiquetas de los archivos que existan, así que no quedan referencias rotas.
        El <code>site.webmanifest</code> se genera con los iconos disponibles.
      </p>
      <div class="ppf-asset-grid ppf-asset-grid--compact">
        <?php foreach ($faviconSlots as $slot => $meta): ?>
          <?php $currentUrl = $assetUrls[$slot] ?? null; ?>
          <article class="ppf-asset-card">
            <header class="ppf-asset-head">
              <span class="ppf-asset-title"><?= e($meta['label']) ?></span>
              <span class="ppf-asset-hint"><?= e($meta['hint']) ?></span>
              <?php if (!empty($meta['note'])): ?>
                <span class="ppf-asset-note"><i class="bi bi-exclamation-circle"></i> <?= e($meta['note']) ?></span>
              <?php endif; ?>
            </header>

            <div class="ppf-asset-preview ppf-asset-preview--icon <?= $currentUrl === null ? 'is-empty' : '' ?>">
              <?php if ($currentUrl !== null): ?>
                <img src="<?= e($currentUrl) ?>" alt="<?= e($meta['label']) ?>" loading="lazy">
              <?php else: ?>
                <span class="ppf-asset-empty"><i class="bi bi-image"></i> Sin imagen</span>
              <?php endif; ?>
            </div>

            <form method="post" action="<?= url('admin/settings/brand/asset') ?>"
                  enctype="multipart/form-data" class="ppf-asset-form">
              <?= \App\Core\CSRF::field() ?>
              <input type="hidden" name="slot" value="<?= e($slot) ?>">
              <input type="file" name="asset" class="form-control form-control-sm"
                     accept=".ico,.png,.svg,image/x-icon,image/png,image/svg+xml"
                     aria-label="Archivo para <?= e($meta['label']) ?>" required>
              <button type="submit" class="btn btn-primary btn-sm ppf-asset-submit">
                <i class="bi bi-upload"></i> <?= $currentUrl === null ? 'Subir' : 'Reemplazar' ?>
              </button>
            </form>

            <?php if ($currentUrl !== null): ?>
              <form method="post" action="<?= url('admin/settings/brand/asset/delete') ?>"
                    class="ppf-asset-delete" data-confirm="¿Eliminar <?= e($meta['label']) ?>?">
                <?= \App\Core\CSRF::field() ?>
                <input type="hidden" name="slot" value="<?= e($slot) ?>">
                <button type="submit" class="btn btn-ghost btn-sm ppf-danger">
                  <i class="bi bi-trash"></i> Eliminar
                </button>
              </form>
            <?php endif; ?>
          </article>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         D · IDENTIDAD · TEMAS · COLORES · CSS (un solo formulario)
         ══════════════════════════════════════════════════════════ -->
    <form method="post" action="<?= url('admin/settings/brand') ?>" id="publicSettingsForm">
      <?= \App\Core\CSRF::field() ?>
      <input type="hidden" name="return_tab" value="identity" data-return-tab>

      <!-- 5. IDENTIDAD -->
      <div class="tab-content <?= $activeTab === 'identity' ? 'active' : '' ?>" data-tab="identity" id="tab-identity" role="tabpanel" aria-labelledby="tabbtn-identity">
        <div class="form-grid">
          <div class="form-group full-width">
            <label class="form-label" for="logo_alt">Texto alternativo de los logotipos</label>
            <input type="text" id="logo_alt" name="logo_alt" class="form-control" maxlength="200"
                   value="<?= e($logoAlt) ?>" placeholder="Ej.: Logotipo de Mi Empresa">
            <small class="form-text">
              Lo leen los lectores de pantalla y se muestra si una imagen no carga.
              Si se deja vacío se usa el nombre de la aplicación.
            </small>
          </div>
        </div>
        <div class="ppf-note">
          <i class="bi bi-info-circle"></i>
          <span>
            El <strong>nombre</strong>, la <strong>descripción</strong>, el <strong>idioma</strong> y la
            <strong>zona horaria</strong> se editan en la pestaña General y se aplican en la cabecera,
            el pie, el título de la pestaña del navegador, la meta descripción y el manifiesto del
            sitio público.
            <button type="button" class="ppf-link-btn" data-goto-tab="general">Ir a General</button>
          </span>
        </div>
      </div>

      <!-- 6. TEMAS -->
      <div class="tab-content <?= $activeTab === 'themes' ? 'active' : '' ?>" data-tab="themes" id="tab-themes" role="tabpanel" aria-labelledby="tabbtn-themes">
        <p class="form-text">
          Elegir un tema rellena los 16 colores de la pestaña <strong>Colores</strong>. Puedes ajustarlos
          después: lo que se guarda son los colores, no el nombre del tema.
        </p>
        <div class="ppf-theme-grid" role="radiogroup" aria-label="Temas de la web pública">
          <?php foreach ($presets as $id => $preset): ?>
            <label class="ppf-theme-card">
              <input type="radio" name="theme" value="<?= e($id) ?>"
                     class="ppf-theme-input" <?= $currentTheme === $id ? 'checked' : '' ?>
                     data-preset="<?= e(json_encode($preset['colors'], JSON_UNESCAPED_SLASHES) ?: '{}') ?>">
              <span class="ppf-theme-body">
                <span class="ppf-theme-swatches" aria-hidden="true">
                  <?php foreach (['color_background', 'color_primary', 'color_accent', 'color_text'] as $swatch): ?>
                    <span style="background: <?= e($preset['colors'][$swatch] ?? '#000000') ?>"></span>
                  <?php endforeach; ?>
                </span>
                <strong><?= e($preset['label']) ?></strong>
                <small><?= e($preset['description']) ?></small>
              </span>
            </label>
          <?php endforeach; ?>

          <label class="ppf-theme-card">
            <input type="radio" name="theme" value="personalizado" class="ppf-theme-input"
                   data-preset="" <?= $currentTheme === 'personalizado' ? 'checked' : '' ?>>
            <span class="ppf-theme-body">
              <span class="ppf-theme-swatches" aria-hidden="true">
                <?php foreach (['color_background', 'color_primary', 'color_accent', 'color_text'] as $swatch): ?>
                  <span style="background: <?= e($pub[$swatch] ?? '#000000') ?>"></span>
                <?php endforeach; ?>
              </span>
              <strong>Personalizado</strong>
              <small>Los 16 colores tal y como están guardados ahora.</small>
            </span>
          </label>
        </div>
      </div>

      <!-- 7. COLORES -->
      <div class="tab-content <?= $activeTab === 'colors' ? 'active' : '' ?>" data-tab="colors" id="tab-colors" role="tabpanel" aria-labelledby="tabbtn-colors">
        <p class="form-text">
          Escribe el HEX o usa el selector. Cada fila indica el contraste sobre el fondo principal
          (WCAG 2.1 AA pide 4.5:1 para texto normal).
        </p>
        <div class="ppf-color-grid">
          <?php foreach ($colorLabels as $key => $label): ?>
            <?php $hex = (string) ($pub[$key] ?? '#000000'); ?>
            <div class="ppf-color-row">
              <label for="hex_<?= e($key) ?>"><?= e($label) ?></label>
              <input type="color" name="colors[<?= e($key) ?>]" id="picker_<?= e($key) ?>"
                     value="<?= e($hex) ?>" data-hex-target="hex_<?= e($key) ?>"
                     aria-label="Selector de <?= e($label) ?>">
              <input type="text" class="form-control ppf-hex" id="hex_<?= e($key) ?>"
                     name="colors[<?= e($key) ?>]"
                     value="<?= e($hex) ?>" maxlength="7" spellcheck="false"
                     pattern="#?[0-9a-fA-F]{3}([0-9a-fA-F]{3})?"
                     data-picker-target="picker_<?= e($key) ?>"
                     aria-label="Código HEX de <?= e($label) ?>">
              <span class="ppf-contrast" data-contrast="<?= e($key) ?>" role="status" title="Contraste sobre el fondo principal"></span>
            </div>
          <?php endforeach; ?>
        </div>

        <div class="d-flex justify-end mt-3">
          <button type="submit" form="ppfResetForm" class="btn btn-ghost btn-sm">
            <i class="bi bi-arrow-counterclockwise"></i> Restablecer configuración de colores
          </button>
        </div>

        <!-- Texto sobre cada superficie: el color del texto de la cabecera, el
             menú, los desplegables, el pie y los botones es editable. Vacío =
             automático (se calcula para cumplir AA sobre su fondo). -->
        <h4 class="ppf-subtitle">Texto sobre cada superficie</h4>
        <p class="form-text">
          Elige el color del texto que se pinta encima de cada fondo. Si lo dejas
          vacío se calcula automáticamente el que cumple AA sobre ese fondo; el
          indicador muestra el contraste real de lo que hay ahora.
        </p>
        <div class="ppf-color-grid">
          <?php foreach ($surfaceTextLabels as $key => $meta): ?>
            <?php
            $raw      = (string) ($pub[$key] ?? '');
            $effective = (string) ($surfaceTextValues[$key] ?? $raw);
            ?>
            <div class="ppf-color-row">
              <label for="hex_<?= e($key) ?>">
                <?= e($meta['label']) ?>
                <small class="ppf-row-hint"><?= e($meta['hint']) ?></small>
              </label>
              <input type="color" name="colors[<?= e($key) ?>]" id="picker_<?= e($key) ?>"
                     value="<?= e($effective !== '' ? $effective : '#ffffff') ?>"
                     data-hex-target="hex_<?= e($key) ?>"
                     aria-label="Selector de <?= e($meta['label']) ?>">
              <input type="text" class="form-control ppf-hex" id="hex_<?= e($key) ?>"
                     name="colors[<?= e($key) ?>]"
                     value="<?= e($raw) ?>" placeholder="auto" maxlength="7" spellcheck="false"
                     pattern="#?[0-9a-fA-F]{3}([0-9a-fA-F]{3})?"
                     data-picker-target="picker_<?= e($key) ?>"
                     aria-label="Código HEX de <?= e($meta['label']) ?> (vacío = automático)">
              <span class="ppf-contrast" data-contrast="<?= e($key) ?>"
                    data-contrast-against="<?= e($meta['surface']) ?>" role="status"></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- 8. CSS PERSONALIZADO -->
      <div class="tab-content <?= $activeTab === 'css' ? 'active' : '' ?>" data-tab="css" id="tab-css" role="tabpanel" aria-labelledby="tabbtn-css">
        <div class="d-flex justify-between align-center mb-3">
          <label class="form-switch mb-0">
            <input type="hidden" name="custom_css_enabled" value="0">
            <input type="checkbox" name="custom_css_enabled" value="1" class="form-check-input"
                   <?= (string) ($pub['custom_css_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
            <span class="form-check-label ms-2">Activar CSS personalizado</span>
          </label>
          <button type="button" class="btn btn-ghost btn-sm" id="ppfCssRestore">
            <i class="bi bi-arrow-counterclockwise"></i> Restaurar estilos predeterminados
          </button>
        </div>

        <label class="form-label" for="custom_css">CSS adicional para la web pública</label>
        <textarea id="custom_css" name="custom_css" class="form-control ppf-css-editor" rows="16"
                  spellcheck="false"
                  placeholder=".public-site .hero { border-radius: 24px; }"><?= e((string) ($pub['custom_css'] ?? '')) ?></textarea>
        <small class="form-text">
          Se carga <strong>solo</strong> en la web pública, después del tema. Tus reglas se acotan
          automáticamente bajo <code>body.public-site</code>: escribir <code>.hero { }</code> aquí
          produce <code>body.public-site .hero { }</code>, así que no pueden alcanzar al panel ni al
          resto del documento. No se permite <code>@import</code>, <code>expression()</code>,
          <code>javascript:</code> ni el carácter <code>&lt;</code>. Máximo 100 KB.
        </small>
        <p class="ppf-css-status" id="ppfCssStatus" role="status" aria-live="polite"></p>
      </div>

      <div class="d-flex justify-end gap-2 mt-4 pt-3 border-top ppf-actions"
           data-form-scope="identity themes colors css">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-circle"></i> Guardar personalización
        </button>
      </div>
    </form>

    <!-- Restablecer colores: formulario propio para no mezclarlo con el guardado -->
    <form method="post" action="<?= url('admin/settings/brand/reset') ?>" id="ppfResetForm"
          data-confirm="¿Restablecer los 16 colores al tema seleccionado? No se borran logos, favicon ni CSS.">
      <?= \App\Core\CSRF::field() ?>
      <input type="hidden" name="theme" value="<?= e($currentTheme === 'personalizado' ? 'oscuro' : $currentTheme) ?>" id="ppfResetTheme">
    </form>

    <!-- ══════════════════════════════════════════════════════════
         E · VISTA PREVIA
         ══════════════════════════════════════════════════════════ -->
    <div class="tab-content <?= $activeTab === 'preview' ? 'active' : '' ?>" data-tab="preview" id="tab-preview" role="tabpanel" aria-labelledby="tabbtn-preview">
      <div class="ppf-preview-layout">
        <section class="ppf-preview-pane">
          <h4 class="ppf-pane-title">Muestra en vivo</h4>
          <p class="form-text">Refleja los colores que tienes ahora en el formulario, sin guardar.</p>
          <div class="ppf-mock" id="ppfMock">
            <div class="ppf-mock-header">
              <span class="ppf-mock-logo">Marca</span>
              <span class="ppf-mock-nav">Inicio · Servicios · Contacto</span>
            </div>            <div class="ppf-mock-body">
              <strong>Título de ejemplo</strong>
              <p>Texto secundario de ejemplo sobre el fondo de sección.</p>
              <span class="ppf-mock-btn">Botón</span>
            </div>
            <div class="ppf-mock-footer">Pie de página</div>
          </div>
        </section>

        <section class="ppf-preview-pane">
          <div class="d-flex justify-between align-center mb-2">
            <h4 class="ppf-pane-title mb-0">Sitio público real</h4>
            <button type="button" class="btn btn-ghost btn-sm" id="ppfReloadPreview">
              <i class="bi bi-arrow-clockwise"></i> Recargar
            </button>
          </div>
          <p class="form-text">Lo que hay publicado ahora mismo. Guarda antes para ver los cambios.</p>
          <iframe id="ppfPreviewFrame" class="ppf-preview-frame" src="<?= e(url('/')) ?>"
                  sandbox="allow-same-origin allow-scripts" referrerpolicy="no-referrer"
                  title="Vista previa de la web pública" loading="lazy"></iframe>
        </section>
      </div>
    </div>

  </div>
</div>

<style>
/* Widgets de personalización. Ámbito `ppf-` y todo bajo .card: no altera el
   panel, solo esta pantalla. No se toca maker.css. */
.ppf-asset-grid{display:grid;gap:1rem;grid-template-columns:repeat(auto-fill,minmax(240px,1fr))}
.ppf-asset-grid--compact{grid-template-columns:repeat(auto-fill,minmax(200px,1fr))}
.ppf-asset-card{display:flex;flex-direction:column;gap:.6rem;padding:.9rem;border:1px solid var(--border-color,#30363d);border-radius:10px;background:var(--bg-card,#161b22)}
.ppf-asset-head{display:flex;flex-direction:column;gap:.15rem}
.ppf-asset-title{font-weight:600;font-size:.9rem}
.ppf-asset-hint{font-size:.75rem;opacity:.7}
.ppf-asset-where{font-size:.72rem;opacity:.85;display:flex;gap:.3rem;align-items:flex-start;margin-top:.15rem}
.ppf-asset-where i{opacity:.7;margin-top:.1rem}
.ppf-asset-note{font-size:.72rem;line-height:1.35;display:flex;gap:.35rem;align-items:flex-start;margin-top:.15rem;padding:.3rem .45rem;border-radius:6px;background:rgba(245,158,11,.12);border:1px solid rgba(245,158,11,.35);color:#f59e0b}
.ppf-asset-preview{display:flex;align-items:center;justify-content:center;min-height:88px;padding:.5rem;border-radius:8px;background:repeating-conic-gradient(rgba(139,148,158,.18) 0% 25%,transparent 0% 50%) 50%/14px 14px}
.ppf-asset-preview img{max-width:100%;max-height:80px;object-fit:contain}
.ppf-asset-preview--icon img{max-height:48px;max-width:48px}
.ppf-asset-empty{font-size:.78rem;opacity:.7;display:flex;gap:.35rem;align-items:center;background:var(--bg-principal,#0d1117);padding:.25rem .6rem;border-radius:6px}
.ppf-asset-form{display:flex;flex-direction:column;gap:.4rem}
.ppf-asset-submit{align-self:flex-start}
.ppf-asset-delete{margin-top:-.2rem}
.ppf-danger{color:#ff7b72}
.ppf-note{display:flex;gap:.5rem;align-items:flex-start;margin-top:1rem;padding:.7rem .9rem;border-radius:8px;background:rgba(0,212,170,.08);border:1px solid rgba(0,212,170,.25);font-size:.85rem}
/* Barra de guardado oculta: se usa una clase, no [hidden], porque .d-flex de
   Bootstrap tiene display:flex !important y ganaría. */
.ppf-actions.is-off{display:none !important}
.ppf-note--info{margin-top:0;margin-bottom:1rem;background:rgba(13,110,253,.10);border-color:rgba(13,110,253,.35)}
.ppf-link-btn{background:none;border:0;padding:0;margin-left:.35rem;color:inherit;font:inherit;font-weight:600;text-decoration:underline;cursor:pointer}
.ppf-link-btn:hover{opacity:.8}
.ppf-theme-grid{display:grid;gap:1rem;grid-template-columns:repeat(auto-fill,minmax(220px,1fr))}
.ppf-theme-card{position:relative;display:block;cursor:pointer}
.ppf-theme-input{position:absolute;opacity:0;width:1px;height:1px}
.ppf-theme-body{display:flex;flex-direction:column;gap:.4rem;padding:.85rem;border:2px solid var(--border-color,#30363d);border-radius:10px;transition:border-color .2s,box-shadow .2s;background:var(--bg-card,#161b22)}
.ppf-theme-card:hover .ppf-theme-body{border-color:var(--accent,#00d4aa)}
.ppf-theme-input:focus-visible + .ppf-theme-body{outline:2px solid var(--accent,#00d4aa);outline-offset:2px}
.ppf-theme-input:checked + .ppf-theme-body{border-color:var(--accent,#00d4aa);box-shadow:0 0 0 3px rgba(0,212,170,.18)}
.ppf-theme-body strong{font-size:.92rem}
.ppf-theme-body small{opacity:.75;line-height:1.35}
.ppf-theme-swatches{display:flex;gap:.25rem}
.ppf-theme-swatches span{width:26px;height:26px;border-radius:6px;border:1px solid rgba(139,148,158,.5)}
.ppf-color-grid{display:grid;gap:.55rem 1.2rem;grid-template-columns:repeat(auto-fill,minmax(320px,1fr))}
.ppf-color-row{display:grid;grid-template-columns:1fr 44px 104px 56px;gap:.5rem;align-items:center}
.ppf-color-row label{font-size:.86rem;margin:0}
.ppf-row-hint{display:block;font-size:.7rem;opacity:.65;font-weight:400}
.ppf-subtitle{margin:1.6rem 0 .3rem;font-size:.95rem;padding-top:1rem;border-top:1px solid var(--border-color,#30363d)}
.ppf-color-row input[type=color]{width:44px;height:34px;padding:0;border:1px solid var(--border-color,#30363d);border-radius:6px;background:none;cursor:pointer}
.ppf-hex{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.85rem;text-transform:lowercase}
.ppf-contrast{font-size:.72rem;text-align:center;padding:.15rem 0;border-radius:5px;opacity:.9}
.ppf-contrast.is-pass{background:rgba(0,212,170,.18);color:#00d4aa}
.ppf-contrast.is-fail{background:rgba(255,123,114,.18);color:#ff7b72}
.ppf-css-editor{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:.85rem;line-height:1.5;tab-size:2}
.ppf-css-status{font-size:.82rem;margin:.5rem 0 0;min-height:1.2em}
.ppf-css-status.is-error{color:#ff7b72}
.ppf-css-status.is-ok{color:#00d4aa}
.ppf-preview-layout{display:grid;gap:1.2rem;grid-template-columns:minmax(260px,1fr) minmax(320px,1.4fr)}
@media (max-width:900px){.ppf-preview-layout{grid-template-columns:1fr}}
.ppf-pane-title{font-size:.95rem;margin:0 0 .35rem}
.ppf-mock{border-radius:10px;overflow:hidden;border:1px solid var(--border-color,#30363d);font-size:.8rem}
.ppf-mock-header{display:flex;justify-content:space-between;gap:.5rem;padding:.55rem .7rem;background:var(--ppf-bg-header,#0d1117);color:var(--ppf-header-text,#e6edf3);font-weight:600}
.ppf-mock-nav{font-weight:400;opacity:.95;color:var(--ppf-menu-text,#e6edf3)}
.ppf-mock-body{padding:.9rem .7rem;background:var(--ppf-bg-section,#161b22);color:var(--ppf-text,#e6edf3)}
.ppf-mock-body p{opacity:.8;margin:.2rem 0 .6rem;color:var(--ppf-text-muted,#8b949e)}
.ppf-mock-btn{display:inline-block;padding:.25rem .7rem;border-radius:6px;background:var(--ppf-button,#00d4aa);color:var(--ppf-button-text,#0d1117);font-weight:600}
.ppf-mock-footer{padding:.5rem .7rem;background:var(--ppf-bg-footer,#161b22);color:var(--ppf-text-muted,#8b949e)}
.ppf-preview-frame{width:100%;height:420px;border:1px solid var(--border-color,#30363d);border-radius:10px;background:#fff}
</style>

<script src="<?= asset('js/settings-public.js') ?>" defer></script>

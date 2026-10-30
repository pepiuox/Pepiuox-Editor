<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Auth};
use App\Models\Setting;
use App\Services\{SettingService, AuditService, PublicThemeService, SettingsAssetService, SiteSettings};

/**
 * Módulo de configuración del sistema.
 *
 * Cubre dos familias de ajustes:
 *
 *  - **Sistema** (`general`, `seo`, `cache`, `mail`): nombre del sitio, zona
 *    horaria, SMTP, caché. Se guardan con `Setting::updateBatch()`.
 *  - **Web pública** (grupo `public`): logotipos, favicon, tema, 16 colores y
 *    CSS adicional. La lógica vive en `PublicThemeService`; este controlador
 *    sólo valida permisos, CSRF y traduce el resultado a un mensaje flash.
 *
 * Separación deliberada: nada de lo que se guarda aquí cambia el CSS, el logo
 * ni los componentes del panel. `PublicThemeService` sólo lo lee
 * `views/public/layout.php`.
 *
 * @package App\Controllers
 * @since   2026-10-26 (personalización visual de la web pública)
 */
class SettingsController
{
    /** Permiso necesario para modificar configuración. */
    private const PERMISSION = 'settings.manage';

    /**
     * Pestañas válidas, para devolver al usuario a la que estaba usando.
     *
     * @var array<int, string>
     */
    private const TABS = [
        'general', 'seo', 'cache', 'mail',
        'identity', 'logos', 'favicon', 'themes', 'colors', 'css', 'preview',
    ];

    /**
     * `GET /admin/settings` — pinta todas las pestañas.
     */
    public function index(Request $req): void
    {
        // Autorización de lectura: la página muestra la contraseña SMTP y el CSS
        // personalizado, así que no puede ser visible para cualquier sesión.
        // El enlace del menú ya se oculta con el mismo permiso (partials/sidebar).
        $this->guardManage();

        $settings = SettingService::all();

        // Estado actual de la personalización pública + catálogo para la vista.
        $public       = PublicThemeService::all();
        $assetUrls    = [];
        foreach (array_keys(PublicThemeService::LOGO_SLOTS) as $slot) {
            $assetUrls[$slot] = PublicThemeService::assetUrl($slot);
        }
        foreach (array_keys(PublicThemeService::FAVICON_SLOTS) as $slot) {
            $assetUrls[$slot] = PublicThemeService::assetUrl($slot);
        }

        Response::render(View::render('settings.index', [
            'settings'     => $settings,
            'public'       => $public,
            // Sólo los huecos que tienen dónde pintarse: los demás se ocultan
            // en la interfaz en lugar de ofrecer un control sin efecto.
            'logoSlots'    => PublicThemeService::visibleLogoSlots(),
            'faviconSlots' => PublicThemeService::FAVICON_SLOTS,
            'colorLabels'  => PublicThemeService::COLOR_LABELS,
            // Texto sobre cada superficie: el valor guardado puede estar vacío
            // («automático»); `surfaceTextValues` trae el color efectivo para
            // que el selector muestre lo que de verdad se pinta.
            'surfaceTextLabels' => PublicThemeService::SURFACE_TEXT_LABELS,
            'surfaceTextValues' => PublicThemeService::resolvedSurfaceTexts(),
            'presets'      => PublicThemeService::THEME_PRESETS,
            'assetUrls'    => $assetUrls,
            'activeTab'    => $this->requestedTab($req),
        ]));
    }

    /**
     * `POST /admin/settings/update` — guarda los grupos del sistema.
     *
     * Se conserva el contrato anterior (`settings[grupo][clave]`), incluido el
     * tipo inferido por el nombre de la clave.
     */
    public function update(Request $req): void
    {
        // Sin esta comprobación cualquier sesión autenticada podía sobrescribir
        // `mail.smtp_*`, `general.*` o el grupo `public` (mass assignment sobre
        // la tabla `settings`). Era el agujero más grave del módulo.
        $this->guardManage();
        CSRF::guard();

        $data = $req->post('settings') ?? [];
        if (empty($data) || !is_array($data)) {
            Session::flash('flash.error', 'No se recibieron datos.');
            Response::redirect(url('admin/settings'));
        }

        // Lista blanca de grupos: nada fuera de aquí se escribe desde este
        // formulario. El grupo `public` tiene su propio endpoint validado.
        $allowedGroups = ['general', 'seo', 'cache', 'mail'];

        $updateData = [];
        foreach ($data as $group => $fields) {
            if (!in_array($group, $allowedGroups, true) || !is_array($fields)) {
                continue;
            }
            foreach ($fields as $key => $value) {
                // Determinar el tipo basado en el nombre del campo o valor
                $type = 'string';
                if (str_starts_with((string) $key, 'is_') || str_ends_with((string) $key, '_enabled')) {
                    $type = 'bool';
                    $value = isset($fields[$key]) ? '1' : '0'; // Checkbox: si no está en POST, es 0
                } elseif (is_numeric($value)) {
                    $type = 'int';
                }

                $updateData[$group][$key] = [
                    'value' => is_array($value) ? json_encode($value) : trim((string) $value),
                    'type'  => $type,
                ];
            }
        }

        try {
            Setting::updateBatch($updateData);
            SettingService::clearCache(); // Limpiar caché para la siguiente petición
            // La identidad del sitio (nombre, descripción, idioma, zona horaria)
            // se cachea aparte: sin esto, el sitio público seguiría pintando el
            // nombre anterior durante el resto de la petición.
            SiteSettings::flush();
            PublicThemeService::flush();

            AuditService::log(Auth::id(), 'settings_updated', 'Configuración del sistema actualizada', 'settings');
            Session::flash('flash.success', 'Configuración guardada correctamente.');
        } catch (\Throwable $e) {
            AuditService::log(Auth::id(), 'settings_error', 'Error al guardar configuración: ' . $e->getMessage(), 'settings');
            Session::flash('flash.error', 'Error al guardar la configuración.');
        }

        Response::redirect($this->tabUrl($req, 'general'));
    }

    /**
     * `POST /admin/settings/brand` — guarda tema, colores, alt y CSS.
     *
     * Valida **todo** antes de escribir: si un color o el CSS no pasan, no se
     * persiste nada y se devuelven los errores juntos.
     */
    public function updatePublic(Request $req): void
    {
        $this->guardManage();
        CSRF::guard();

        $input = [];
        foreach (['theme', 'logo_alt', 'custom_css', 'custom_css_enabled'] as $key) {
            $value = $req->post($key);
            if ($value !== null) {
                $input[$key] = $value;
            }
        }
        $colors = $req->post('colors');
        if (is_array($colors)) {
            $input['colors'] = $colors;
        }

        try {
            $result = PublicThemeService::save($input);
        } catch (\Throwable $e) {
            error_log('[Settings] Error al guardar la personalización pública: ' . $e->getMessage());
            $this->respond($req, 'themes', false, 'Error al guardar la personalización.');
            return;
        }

        if (!$result['ok']) {
            Session::flash('flash.error', implode(' ', $result['errors']));
            AuditService::log(Auth::id(), 'public_theme_invalid', 'Personalización pública rechazada por validación', 'settings');
            $this->respond($req, (string) $req->post('return_tab') ?: 'themes', false, implode(' ', $result['errors']));
            return;
        }

        AuditService::log(Auth::id(), 'public_theme_updated', 'Personalización visual de la web pública actualizada', 'settings');
        $this->respond($req, (string) $req->post('return_tab') ?: 'themes', true, 'Personalización de la web pública guardada.');
    }

    /**
     * `POST /admin/settings/brand/asset` — sube un logo o un favicon.
     *
     * Espera `multipart/form-data` con `slot` y `asset`.
     */
    public function uploadAsset(Request $req): void
    {
        $this->guardManage();
        CSRF::guard();

        $slot  = (string) $req->post('slot');
        $file  = $_FILES['asset'] ?? null;
        $tab   = array_key_exists($slot, PublicThemeService::FAVICON_SLOTS) ? 'favicon' : 'logos';

        $service = new SettingsAssetService();
        try {
            $result = $service->store($slot, is_array($file) ? $file : null);
        } catch (\Throwable $e) {
            error_log('[Settings] Error al subir imagen: ' . $e->getMessage());
            $result = ['ok' => false, 'message' => 'Error inesperado al subir la imagen.', 'warnings' => [], 'url' => null];
        }

        if ($result['ok']) {
            $message = $result['message'];
            if ($result['warnings'] !== []) {
                $message .= ' ' . implode(' ', $result['warnings']);
            }
            AuditService::log(Auth::id(), 'public_asset_uploaded', 'Imagen pública actualizada: ' . $slot, 'settings');
            $this->respond($req, $tab, true, $message);
            return;
        }

        AuditService::log(Auth::id(), 'public_asset_rejected', 'Subida rechazada en ' . $slot . ': ' . $result['message'], 'settings');
        $this->respond($req, $tab, false, $result['message']);
    }

    /**
     * `POST /admin/settings/brand/asset/delete` — elimina el archivo del hueco.
     */
    public function deleteAsset(Request $req): void
    {
        $this->guardManage();
        CSRF::guard();

        $slot = (string) $req->post('slot');
        $tab  = array_key_exists($slot, PublicThemeService::FAVICON_SLOTS) ? 'favicon' : 'logos';

        $service = new SettingsAssetService();
        try {
            $result = $service->delete($slot);
        } catch (\Throwable $e) {
            error_log('[Settings] Error al eliminar imagen: ' . $e->getMessage());
            $result = ['ok' => false, 'message' => 'Error inesperado al eliminar la imagen.', 'warnings' => [], 'url' => null];
        }

        if ($result['ok']) {
            AuditService::log(Auth::id(), 'public_asset_deleted', 'Imagen pública eliminada: ' . $slot, 'settings');
        }

        $this->respond($req, $tab, $result['ok'], $result['message']);
    }

    /**
     * `POST /admin/settings/brand/reset` — vuelve al preset indicado.
     *
     * Reversible sin riesgo: sólo reescribe las 16 claves de color. No borra
     * logotipos, favicon ni CSS personalizado.
     */
    public function resetPublic(Request $req): void
    {
        $this->guardManage();
        CSRF::guard();

        $theme = (string) ($req->post('theme') ?: 'oscuro');

        try {
            $result = PublicThemeService::resetColors($theme);
        } catch (\Throwable $e) {
            error_log('[Settings] Error al restablecer colores: ' . $e->getMessage());
            $this->respond($req, 'colors', false, 'Error al restablecer la configuración.');
            return;
        }

        if (!$result['ok']) {
            $this->respond($req, 'colors', false, implode(' ', $result['errors']));
            return;
        }

        AuditService::log(Auth::id(), 'public_theme_reset', 'Colores restablecidos al tema ' . $theme, 'settings');
        $this->respond($req, 'colors', true, 'Colores restablecidos al tema «' . $theme . '».');
    }

    // ────────────────────────────────────────────────────────────────
    //  Apoyo interno
    // ────────────────────────────────────────────────────────────────

    /**
     * Exige el permiso `settings.manage`; si falta, 403 y fin.
     *
     * El middleware de sesión ya garantiza que hay usuario; esto añade la
     * autorización por rol, que es lo que impide que un editor sin permiso
     * cambie la identidad del sitio.
     */
    private function guardManage(): void
    {
        if (Auth::can(self::PERMISSION)) {
            return;
        }
        if ((new Request())->isAjax()) {
            Response::json(['success' => false, 'message' => 'No tienes permiso para modificar la configuración.'], 403);
        }
        Response::abort(403, 'No tienes permiso para modificar la configuración.');
    }

    /**
     * Responde a una acción: JSON si es AJAX, redirect con flash si es formulario.
     */
    private function respond(Request $req, string $tab, bool $ok, string $message): void
    {
        Session::flash($ok ? 'flash.success' : 'flash.error', $message);

        if ($req->isAjax()) {
            Response::json([
                'success' => $ok,
                'message' => $message,
                'tab'     => $tab,
            ], $ok ? 200 : 422);
            return;
        }

        Response::redirect($this->tabUrl($req, $tab));
    }

    /**
     * URL de vuelta a la pestaña indicada.
     */
    private function tabUrl(Request $req, string $tab): string
    {
        $tab = in_array($tab, self::TABS, true) ? $tab : 'general';
        return url('admin/settings') . '?tab=' . $tab . '#tab-' . $tab;
    }

    /**
     * Pestaña pedida en la URL, validada contra la lista blanca.
     */
    private function requestedTab(Request $req): string
    {
        $tab = (string) ($req->get('tab') ?? '');
        return in_array($tab, self::TABS, true) ? $tab : 'general';
    }
}

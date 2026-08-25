<?php
namespace App\Controllers;
use App\Core\{Request, Response, View, Session, CSRF};
use App\Models\Setting;
use App\Services\{SettingService, AuditService};

class SettingsController
{
    public function index(Request $req): void
    {
        $settings = SettingService::all();
        Response::render(View::render('settings.index', compact('settings')));
    }

    public function update(Request $req): void
    {
        CSRF::guard();
        
        $data = $req->post('settings') ?? [];
        if (empty($data)) {
            Session::flash('flash.error', 'No se recibieron datos.');
            Response::redirect(url('admin/settings'));
        }

        $updateData = [];
        foreach ($data as $group => $fields) {
            foreach ($fields as $key => $value) {
                // Determinar el tipo basado en el nombre del campo o valor
                $type = 'string';
                if (str_starts_with($key, 'is_') || str_ends_with($key, '_enabled')) {
                    $type = 'bool';
                    $value = isset($fields[$key]) ? '1' : '0'; // Checkbox: si no está en POST, es 0
                } elseif (is_numeric($value)) {
                    $type = 'int';
                }

                $updateData[$group][$key] = [
                    'value' => is_array($value) ? json_encode($value) : trim((string)$value),
                    'type'  => $type
                ];
            }
        }

        try {
            Setting::updateBatch($updateData);
            SettingService::clearCache(); // Limpiar caché para la siguiente petición
            
            AuditService::log('settings_updated', 'Configuración del sistema actualizada', 'settings');
            Session::flash('flash.success', 'Configuración guardada correctamente.');
        } catch (\Throwable $e) {
            AuditService::log('settings_error', 'Error al guardar configuración: ' . $e->getMessage(), 'settings');
            Session::flash('flash.error', 'Error al guardar la configuración.');
        }

        Response::redirect(url('admin/settings'));
    }
}
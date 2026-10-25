<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Auth};
use App\Services\TemplatesService;
use Throwable;

/**
 * Controlador de gestión de templates
 *
 * Responsabilidades:
 *   - Recibir peticiones HTTP y validar permisos
 *   - Delegar lógica de negocio a TemplatesService
 *   - Gestionar respuestas (HTML/JSON/redirect)
 *
 * NO contiene lógica de negocio ni queries SQL directas.
 *
 * @package App\Controllers
 */
class TemplatesController
{
    private TemplatesService $templateService;

    /**
     * Constructor con inyección de dependencias
     *
     * @param TemplatesService $templateService Servicio de templates (contiene AuditService interno)
     */
    public function __construct(TemplatesService $templateService)
    {
        $this->templateService = $templateService;
    }

    // ═══════════════════════════════════════════════════════════════
    //  LISTADO Y FORMULARIOS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Lista todos los templates
     * Ruta: GET /admin/templates
     */
    public function index(Request $request): void
    {
        if (!Auth::can('templates.view')) {
            Response::abort(403, 'No tienes permisos para ver templates');
        }

        try {
            $templates = $this->templateService->getAll();

            // Vista: templates.index → views/templates/index.php
            Response::render(View::render('templates.index', compact('templates')));
        } catch (Throwable $e) {
            error_log('[TemplatesController] Error en index: ' . $e->getMessage());
            Response::render(View::render('templates.index', ['templates' => []]));
        }
    }

    /**
     * Muestra formulario de creación
     * Ruta: GET /admin/templates/create
     */
    public function create(Request $request): void
    {
        if (!Auth::can('templates.create')) {
            Response::abort(403, 'No tienes permisos para crear templates');
        }

        // La vista es un documento HTML completo: se renderiza sin layout.
        Response::render(View::renderStandalone('templates.edit', ['template' => null]));
    }

    /**
     * Guarda un nuevo template
     * Ruta: POST /admin/templates
     */
    public function store(Request $request): void
    {
        CSRF::guard();

        if (!Auth::can('templates.create')) {
            Response::abort(403, 'No tienes permisos para crear templates');
        }

        $data = $this->extractTemplateData($request);

        try {
            $result = $this->templateService->create($data);

            if ($result['success']) {
                Session::flash('flash.success', 'Template creado correctamente');
                Response::redirect(url("admin/templates/{$result['id']}/edit"));
            } else {
                Session::flash('flash.error', $result['error']);
                Response::redirect(url('admin/templates/create'));
            }
        } catch (Throwable $e) {
            error_log('[TemplatesController] Error en store: ' . $e->getMessage());
            Session::flash('flash.error', 'Error al crear el template');
            Response::redirect(url('admin/templates/create'));
        }
    }

    /**
     * Muestra formulario de edición
     * Ruta: GET /admin/templates/{id}/edit
     */
    public function edit(Request $request, array $params): void
    {
        if (!Auth::can('templates.edit')) {
            Response::abort(403, 'No tienes permisos para editar templates');
        }

        $id = (int)$params['id'];
        $template = $this->templateService->find($id);

        if (!$template) {
            Response::abort(404, 'Template no encontrado');
        }

        // Vista: templates.edit → views/templates/edit.php (documento completo, sin layout)
        Response::render(View::renderStandalone('templates.edit', compact('template')));
    }

    /**
     * Actualiza un template con auto-versionado
     * Ruta: POST /admin/templates/{id}
     */
    public function update(Request $request, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('templates.edit')) {
            Response::abort(403, 'No tienes permisos para editar templates');
        }

        $id   = (int)$params['id'];
        $data = $this->extractTemplateData($request);

        try {
            // ✅ Auto-versionado si cambió el contenido Y el usuario lo solicitó
            $createVersion = (bool)$request->post('create_version', true);

            if ($createVersion && $this->templateService->hasContentChanged($id, $data)) {
                $this->templateService->createVersion($id);
            }

            $result = $this->templateService->update($id, $data);

            // El editor (views/templates/edit.php) guarda por fetch, igual que el
            // builder de páginas: en peticiones AJAX la respuesta es JSON y no
            // hay recarga de página.
            if ($request->isAjax()) {
                Response::json(
                    $result['success']
                        ? ['success' => true, 'message' => 'Plantilla guardada']
                        : ['success' => false, 'message' => $result['error'] ?? 'Error al guardar'],
                    $result['success'] ? 200 : 422
                );
            }

            if ($result['success']) {
                Session::flash('flash.success', 'Template actualizado correctamente');
                Response::redirect(url('admin/templates'));
            } else {
                Session::flash('flash.error', $result['error']);
                Response::redirect(url("admin/templates/{$id}/edit"));
            }
        } catch (Throwable $e) {
            error_log('[TemplatesController] Error en update: ' . $e->getMessage());
            if ($request->isAjax()) {
                Response::json(['success' => false, 'message' => 'Error al actualizar la plantilla'], 500);
            }
            Session::flash('flash.error', 'Error al actualizar el template');
            Response::redirect(url("admin/templates/{$id}/edit"));
        }
    }

    /**
     * Elimina un template
     * Ruta: POST /admin/templates/{id}/delete
     */
    public function delete(Request $request, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('templates.delete')) {
            Response::abort(403, 'No tienes permisos para eliminar templates');
        }

        $id     = (int)$params['id'];
        $result = $this->templateService->delete($id);

        Session::flash(
            $result['success'] ? 'flash.success' : 'flash.error',
            $result['message'] ?? $result['error']
        );
        Response::redirect(url('admin/templates'));
    }

    /**
     * Duplica un template
     * Ruta: POST /admin/templates/{id}/duplicate
     */
    public function duplicate(Request $request, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('templates.create')) {
            Response::abort(403, 'No tienes permisos para duplicar templates');
        }

        $id     = (int)$params['id'];
        $result = $this->templateService->duplicate($id);

        Session::flash(
            $result['success'] ? 'flash.success' : 'flash.error',
            $result['message'] ?? $result['error']
        );
        Response::redirect(url('admin/templates'));
    }

    // ═══════════════════════════════════════════════════════════════
    //  GESTIÓN DE VERSIONES
    // ═══════════════════════════════════════════════════════════════

    /**
     * Lista versiones de un template
     * Ruta: GET /admin/templates/{id}/versions
     */
    public function versions(Request $request, array $params): void
    {
        if (!Auth::can('templates.view')) {
            Response::abort(403, 'No tienes permisos para ver versiones');
        }

        $id = (int)$params['id'];
        $template = $this->templateService->find($id);

        if (!$template) {
            Response::abort(404, 'Template no encontrado');
        }

        $versions = $this->templateService->getVersions($id);

        // Vista: templates.versions → views/templates/versions.php
        Response::render(View::render('templates.versions', compact('template', 'versions')));
    }

    /**
     * Crea una versión vía AJAX (desde el editor)
     * Ruta: POST /admin/templates/{id}/versions
     */
    public function createVersionAjax(Request $request, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('templates.edit')) {
            if ($request->isAjax()) {
                Response::json(['success' => false, 'error' => 'Sin permisos'], 403);
                return;
            }
            Response::abort(403, 'No tienes permisos para crear versiones');
        }

        $id = (int)$params['id'];

        // Datos pueden venir de POST form o JSON body
        $data = [
            'content_html' => $request->post('content_html'),
            'content_css'  => $request->post('content_css'),
            'content_js'   => $request->post('content_js'),
            'content_php'  => Auth::can('templates.edit_php') ? $request->post('content_php') : '',
        ];

        try {
            $result = $this->templateService->createVersion($id, $data);

            if ($request->isAjax()) {
                Response::json($result);
            } else {
                Session::flash(
                    $result['success'] ? 'flash.success' : 'flash.error',
                    $result['message'] ?? $result['error']
                );
                Response::redirect(url("admin/templates/{$id}/versions"));
            }
        } catch (Throwable $e) {
            error_log('[TemplatesController] Error en createVersionAjax: ' . $e->getMessage());
            if ($request->isAjax()) {
                Response::json(['success' => false, 'error' => 'Error al crear versión'], 500);
            } else {
                Session::flash('flash.error', 'Error al crear la versión');
                Response::redirect(url("admin/templates/{$id}/versions"));
            }
        }
    }

    /**
     * Restaura una versión (con rollback safety)
     * Ruta: POST /admin/templates/{id}/versions/{vid}/restore
     */
    public function restoreVersion(Request $request, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('templates.edit')) {
            Response::abort(403, 'No tienes permisos para restaurar versiones');
        }

        $templateId = (int)$params['id'];
        $versionId  = (int)$params['vid'];

        try {
            $result = $this->templateService->restoreVersion($templateId, $versionId);

            Session::flash(
                $result['success'] ? 'flash.success' : 'flash.error',
                $result['message'] ?? $result['error']
            );
            Response::redirect(url("admin/templates/{$templateId}/edit"));
        } catch (Throwable $e) {
            error_log('[TemplatesController] Error en restoreVersion: ' . $e->getMessage());
            Session::flash('flash.error', 'Error al restaurar la versión');
            Response::redirect(url("admin/templates/{$templateId}/versions"));
        }
    }

    /**
     * Elimina una versión
     * Ruta: POST /admin/templates/{id}/versions/{vid}/delete
     */
    public function deleteVersion(Request $request, array $params): void
    {
        CSRF::guard();

        if (!Auth::can('templates.edit')) {
            Response::abort(403, 'No tienes permisos para eliminar versiones');
        }

        $templateId = (int)$params['id'];
        $versionId  = (int)$params['vid'];

        try {
            $result = $this->templateService->deleteVersion($templateId, $versionId);

            if ($request->isAjax()) {
                Response::json($result);
            } else {
                Session::flash(
                    $result['success'] ? 'flash.success' : 'flash.error',
                    $result['message'] ?? $result['error']
                );
                Response::redirect(url("admin/templates/{$templateId}/versions"));
            }
        } catch (Throwable $e) {
            error_log('[TemplatesController] Error en deleteVersion: ' . $e->getMessage());
            if ($request->isAjax()) {
                Response::json(['success' => false, 'error' => 'Error al eliminar'], 500);
            } else {
                Session::flash('flash.error', 'Error al eliminar la versión');
                Response::redirect(url("admin/templates/{$templateId}/versions"));
            }
        }
    }

    // ═══════════════════════════════════════════════════════════════
    //  HELPERS PRIVADOS
    // ═══════════════════════════════════════════════════════════════

    /**
     * Extrae y valida datos del template desde el request
     *
     * Centraliza la extracción para evitar duplicación entre store() y update()
     *
     * @param Request $request
     * @return array Datos del template listos para el servicio
     */
    private function extractTemplateData(Request $request): array
    {
        return [
            'name'          => $request->post('name'),
            'description'   => $request->post('description'),
            'content_html'  => $request->post('content_html', ''),
            'content_css'   => $request->post('content_css', ''),
            'content_js'    => $request->post('content_js', ''),
            // ✅ Solo permitir edición PHP si tiene permiso específico
            'content_php'   => Auth::can('templates.edit_php')
                ? $request->post('content_php', '')
                : '',
            'grapesjs_data' => $request->post('grapesjs_data'),
            'is_active'     => (int)$request->post('is_active', 1),
            'is_default'    => (int)$request->post('is_default', 0),
        ];
    }
}
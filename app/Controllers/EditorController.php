<?php
namespace App\Controllers;

use App\Core\{Request, Response, View, Session, CSRF, Database, Auth};
use App\Models\{Page, Template, MetaTag};
use App\Services\{PageService, PagePublisher, AuditService};

class EditorController
{
    /**
     * Muestra el editor visual para una página
     */
    public function edit(Request $req, array $params): void
    {
        if (!Auth::can('pages.edit')) {
            Response::abort(403, 'No tienes permisos para editar páginas');
        }

        $pageId = (int)($params['id'] ?? 0);
        
        // Si es una página nueva
        if ($pageId === 0) {
            $page = [
                'id' => 0,
                'title' => 'Nueva página',
                'slug' => '',
                'description' => '',
                'content_html' => '<div style="padding:40px;text-align:center;"><h1>Mi Nueva Página</h1><p>Comienza a editar tu contenido aquí.</p></div>',
                'content_css' => "body {\n  font-family: 'Inter', sans-serif;\n  margin: 0;\n  padding: 0;\n  background: #ffffff;\n  color: #333333;\n}",
                'content_php' => '',
                'content_js' => '',
                'status' => 'draft',
                'startpage' => 0,
                'created_at' => null,
                'updated_at' => null,
            ];
        } else {
            $page = Page::find($pageId);
            if (!$page) {
                Session::flash('flash.error', 'Página no encontrada');
                Response::redirect(url('admin/pages'));
            }
            
            // Verificar que el usuario sea el propietario o tenga permisos de admin
            if ((int)$page['user_id'] !== Auth::id() && !Auth::can('pages.edit')) {
                Response::abort(403, 'No tienes permisos para editar esta página');
            }
        }

        // Cargar assets existentes para el Asset Manager
        $existingAssets = $this->getExistingAssets();

        // Codificar contenido para JavaScript
        $jsonHtml = json_encode($page['content_html'] ?? '', JSON_UNESCAPED_UNICODE);
        $jsonCss = json_encode($page['content_css'] ?? '', JSON_UNESCAPED_UNICODE);
        $jsonPhp = json_encode($page['content_php'] ?? '', JSON_UNESCAPED_UNICODE);
        $jsonJs = json_encode($page['content_js'] ?? '', JSON_UNESCAPED_UNICODE);
        $jsonAssets = json_encode($existingAssets, JSON_UNESCAPED_SLASHES);

        // La vista es un documento HTML completo: se renderiza sin layout.
        Response::render(View::renderStandalone('pages.editor', compact(
            'page', 'jsonHtml', 'jsonCss', 'jsonPhp', 'jsonJs', 'jsonAssets'
        )));
    }

    /**
     * Guarda los cambios de la página
     */
    public function save(Request $req): void
    {
        CSRF::guard();

        if (!Auth::can('pages.edit')) {
            Response::abort(403, 'No tienes permisos para guardar páginas');
        }

        $pageId = (int)$req->post('page_id', 0);
        $title = trim($req->post('title', 'Sin título'));
        $slug = trim($req->post('slug', '')) ?: Page::slugify($title);
        $slug = Page::slugify($slug);
        $description = trim($req->post('description', ''));
        $contentHtml = $req->post('content_html', '');
        $contentCss = $req->post('content_css', '');
        $contentPhp = $req->post('content_php', '');
        $contentJs = $req->post('content_js', '');
        $status = in_array($req->post('status', ''), ['draft', 'published', 'archived'])
            ? $req->post('status') : 'draft';
        $isPublished = ($status === 'published') ? 1 : 0;
        $startpage = (int)$req->post('startpage', 0);
        $userId = Auth::id();

        // Verificar slug único
        $existingSlug = Database::fetch(
            "SELECT id FROM pages WHERE slug = ? AND id != ? AND user_id = ?",
            [$slug, $pageId, $userId]
        );
        if ($existingSlug) {
            $slug .= '-' . time();
        }

        // Capturar slug anterior para invalidar caché
        $oldSlug = null;
        if ($pageId > 0) {
            $oldPage = Page::find($pageId);
            $oldSlug = $oldPage['slug'] ?? null;
        }

        try {
            Database::begin();

            // Si es página de inicio, desactivar las demás
            if ($startpage) {
                Database::execute(
                    "UPDATE pages SET startpage = 0 WHERE user_id = ? AND id != ?",
                    [$userId, $pageId]
                );
            }

            if ($pageId > 0) {
                // Actualizar página existente
                Database::execute(
                    "UPDATE pages 
                     SET title=?, slug=?, description=?, content_html=?, content_css=?,
                         content_php=?, content_js=?, status=?, is_published=?, startpage=?
                     WHERE id=? AND user_id=?",
                    [
                        $title, $slug, $description, $contentHtml, $contentCss,
                        $contentPhp, $contentJs, $status, $isPublished, $startpage,
                        $pageId, $userId
                    ]
                );
            } else {
                // Crear nueva página
                $pageId = Database::insert(
                    "INSERT INTO pages 
                     (user_id, title, slug, description, content_html, content_css,
                      content_php, content_js, status, is_published, startpage)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                    [
                        $userId, $title, $slug, $description, $contentHtml, $contentCss,
                        $contentPhp, $contentJs, $status, $isPublished, $startpage
                    ]
                );
            }

            // Obtener página completa recién guardada
            $page = Page::find($pageId);
            if (!$page) {
                throw new \RuntimeException('No se pudo recuperar la página guardada');
            }

            // Publicación automática si está publicada
            if ($status === 'published') {
                $publisher = new PagePublisher(Database::connection());
                $publisher->publish($pageId, $page, $userId);
            } else {
                $publisher = new PagePublisher(Database::connection());
                $publisher->unpublish($pageId, $page);
            }

            // Invalidar caché del slug anterior si cambió
            if ($oldSlug !== null && $oldSlug !== $slug) {
                $publisher->invalidateCache($oldSlug);
            }

            Database::commit();

            $msg = $status === 'published' 
                ? 'Página publicada correctamente' 
                : "Página guardada como $status";

            // Firma real: log(?int $userId, string $action, ?string $details, ?string $context).
            AuditService::log(Auth::id(), 'page_saved', "Página guardada: $title (ID $pageId)", 'pages');
            Session::flash('flash.success', $msg);

            if ($req->isAjax()) {
                Response::json(['success' => true, 'id' => $pageId, 'message' => $msg]);
            } else {
                Response::redirect(url("admin/pages/$pageId/edit"));
            }

        } catch (\Throwable $e) {
            Database::rollback();
            error_log('Error al guardar página: ' . $e->getMessage());
            
            if ($req->isAjax()) {
                Response::json(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()]);
            } else {
                Session::flash('flash.error', 'Error al guardar la página: ' . $e->getMessage());
                Response::redirect(url("admin/pages/$pageId/edit"));
            }
        }
    }

    /**
     * Obtiene la lista de imágenes subidas
     */
    private function getExistingAssets(): array
    {
        $uploadDir = UPLOADS_PATH;
        $uploadUrl = url('uploads/');
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $assets = [];

        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
            return $assets;
        }

        $files = @scandir($uploadDir);
        if ($files === false) {
            return $assets;
        }

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $filePath = $uploadDir . '/' . $file;
            if (!is_file($filePath) || !is_readable($filePath)) continue;

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt)) continue;

            $width = 0;
            $height = 0;

            if ($ext === 'svg') {
                $svgContent = @file_get_contents($filePath);
                if ($svgContent !== false) {
                    if (preg_match('/viewBox=["\']([^"\']+)["\']/', $svgContent, $matches)) {
                        $viewBox = explode(' ', $matches[1]);
                        if (count($viewBox) === 4) {
                            $width = (int)$viewBox[2];
                            $height = (int)$viewBox[3];
                        }
                    }
                }
            } else {
                $imgInfo = @getimagesize($filePath);
                if ($imgInfo !== false) {
                    $width = $imgInfo[0];
                    $height = $imgInfo[1];
                }
            }

            $assets[] = [
                'src' => $uploadUrl . $file,
                'name' => $file,
                'type' => 'image',
                'width' => $width,
                'height' => $height,
                'size' => filesize($filePath),
                'uploaded_at' => date('Y-m-d H:i:s', filemtime($filePath))
            ];
        }

        usort($assets, fn($a, $b) => strtotime($b['uploaded_at']) - strtotime($a['uploaded_at']));
        return $assets;
    }
}
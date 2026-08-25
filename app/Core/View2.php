<?php
namespace App\Core;

class View
{
    /**
     * Renderiza una vista con layout.
     * @param string $view   ruta relativa a /views (sin .php), ej: 'dashboard.index'
     * @param array  $data   variables disponibles como $data['x'] y como $x
     * @param string $layout layout a usar (ej: 'layouts.admin')
     */

    public static function render(string $view, array $data = [], string $layout = 'layouts.admin'): string
    {
        // Extraer variables para que estén disponibles en la vista (ej: $user, $roles)
        extract($data, EXTR_SKIP);
        
        $viewFile = self::path($view);
        if (!is_file($viewFile)) {
            Response::abort(500, "Vista no encontrada: {$viewFile}");
        }
        
        // 1. Renderizar el contenido de la vista
        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        // 2. Renderizar el layout envolvente
        $layoutFile = self::path($layout);
        if (is_file($layoutFile)) {
            ob_start();
            include $layoutFile;
            return ob_get_clean();
        }
        
        // Si no hay layout, devolver solo el contenido
        return $content;
    }

    public static function partial(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $file = self::path($view);
        if (is_file($file)) include $file;
    }

    private static function path(string $view): string
    {
        // __DIR__ es app/Core. dirname(__DIR__, 2) sube dos niveles hasta la raíz del proyecto.
        $rootPath = dirname(__DIR__, 2);
        return $rootPath . '/views/' . str_replace('.', '/', $view) . '.php';
    }
}
<?php
namespace App\Core;

class View
{
    /**
     * Renderiza una vista con layout
     * 
     * @param string $view Ruta de la vista (ej: 'public.layout')
     * @param array $data Datos para la vista
     * @param string $layout Layout a usar (ej: 'layouts.admin')
     * @return string HTML renderizado
     */
    public static function render(string $view, array $data = [], string $layout = 'layouts.admin'): string
    {
        // Extraer variables para que estén disponibles en la vista
        extract($data, EXTR_SKIP);
        
        // Construir ruta del archivo de vista
        $viewFile = self::path($view);
        if (!is_file($viewFile)) {
            Response::abort(500, "Vista no encontrada: {$view}");
        }
        
        // Renderizar contenido de la vista
        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        // Renderizar layout envolvente
        $layoutFile = self::path($layout);
        if (is_file($layoutFile)) {
            ob_start();
            include $layoutFile;
            return ob_get_clean();
        }
        
        // Si no hay layout, devolver solo el contenido
        return $content;
    }

    /**
     * Renderiza una vista que YA es un documento HTML completo.
     *
     * A diferencia de render(), no la envuelve en ningún layout: la vista trae
     * su propio <!DOCTYPE>, <html>, <head> y <body>. Envolverla produciría un
     * documento anidado y el navegador descartaría el <body> interior con sus
     * estilos y su altura.
     */
    public static function renderStandalone(string $view, array $data = []): string
    {
        extract($data, EXTR_SKIP);

        $viewFile = self::path($view);
        if (!is_file($viewFile)) {
            Response::abort(500, "Vista no encontrada: {$view}");
        }

        ob_start();
        include $viewFile;
        return (string) ob_get_clean();
    }

    /**
     * Renderiza un partial (sin layout)
     */
    public static function partial(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $file = self::path($view);
        if (is_file($file)) include $file;
    }

    /**
     * Convierte nombre de vista a ruta de archivo
     */
    private static function path(string $view): string
    {
        // __DIR__ es app/Core, subimos 2 niveles hasta la raíz del proyecto
        $rootPath = dirname(__DIR__, 2);
        return $rootPath . '/views/' . str_replace('.', '/', $view) . '.php';
    }
}
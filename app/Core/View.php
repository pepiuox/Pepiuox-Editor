<?php
namespace App\Core;

class View
{
    public static function render(string $view, array $data = [], string $layout = 'layouts.admin'): string
    {
        extract($data, EXTR_SKIP);
        
        $viewFile = self::path($view);
        if (!is_file($viewFile)) {
            Response::abort(500, "Vista no encontrada: {$viewFile}");
        }
        
        ob_start();
        include $viewFile;
        $content = ob_get_clean();

        $layoutFile = self::path($layout);
        if (is_file($layoutFile)) {
            ob_start();
            include $layoutFile;
            return ob_get_clean();
        }
        
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
        // Usa VIEWS_PATH que apunta a la raíz absoluta /views/
        return VIEWS_PATH . '/' . str_replace('.', '/', $view) . '.php';
    }
}
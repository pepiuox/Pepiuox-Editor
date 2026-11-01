<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Servicio de subida de imágenes de los editores visuales.
 *
 * Antes vivía dentro de PagesController::uploadAsset() con sus dos helpers
 * privados; el editor de plantillas no podía subir imágenes porque el endpoint
 * estaba atado a una página. Aquí queda la lógica en un solo sitio y cada
 * controlador pone sus permisos y su ruta.
 *
 * Reglas (defensa en profundidad):
 *  - Solo extensiones de imagen de la lista blanca.
 *  - Tamaño máximo 5 MB por archivo.
 *  - El MIME real (finfo) debe empezar por `image/`.
 *  - El nombre se normaliza (ASCII, sin rutas) y se le añade sufijo ante colisión.
 *  - Se conserva el fichero fuera del document root, en UPLOADS_PATH/<año>/<mes>.
 *
 * @package App\Services
 */
final class AssetUploadService
{
    /** Extensiones permitidas para los editores visuales. */
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    /** Tamaño máximo por archivo (5 MB). */
    private const MAX_BYTES = 5 * 1024 * 1024;

    /**
     * @param string $uploadsPath Directorio raíz de subidas (por defecto UPLOADS_PATH)
     */
    public function __construct(
        private readonly string $uploadsPath = UPLOADS_PATH
    ) {
    }

    /**
     * Guarda los ficheros recibidos en $_FILES.
     *
     * Acepta la estructura de un input múltiple (`files[]`) y también la de un
     * único fichero.
     *
     * @param array<string, mixed>|null $files Entrada de $_FILES
     * @return array{received: bool, uploaded: array<int, array<string, mixed>>, errors: array<int, string>}
     */
    public function store(?array $files): array
    {
        if (!$files || !isset($files['name'])) {
            return ['received' => false, 'uploaded' => [], 'errors' => ['No se recibieron archivos']];
        }

        $uploaded = [];
        $errors = [];
        $names = (array) $files['name'];

        for ($i = 0, $count = count($names); $i < $count; $i++) {
            $originalName = (string) $names[$i];
            $error = is_array($files['error']) ? $files['error'][$i] : $files['error'];
            $tmpName = is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'];
            $size = (int) (is_array($files['size']) ? $files['size'][$i] : $files['size']);
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if ($error !== UPLOAD_ERR_OK) {
                $errors[] = 'Error al subir: ' . $originalName;
                continue;
            }
            if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                $errors[] = 'Tipo no permitido: ' . $originalName;
                continue;
            }
            if ($size > self::MAX_BYTES) {
                $errors[] = 'Archivo demasiado grande (máx 5MB): ' . $originalName;
                continue;
            }

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = (string) $finfo->file((string) $tmpName);
            if (!str_starts_with($mime, 'image/')) {
                $errors[] = 'MIME inválido: ' . $originalName;
                continue;
            }

            $yearMonth = date('Y/m');
            $directory = rtrim($this->uploadsPath, '/\\') . '/' . $yearMonth;
            if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
                $errors[] = 'No se pudo crear el directorio de subida';
                break;
            }

            $filename = $this->uniqueName($directory, $originalName);
            $destination = $directory . '/' . $filename;

            if (!move_uploaded_file((string) $tmpName, $destination)) {
                $errors[] = 'No se pudo guardar: ' . $originalName;
                continue;
            }
            @chmod($destination, 0644);

            [$width, $height] = $this->dimensionsOf($destination, $extension);
            $relativePath = $yearMonth . '/' . $filename;

            $uploaded[] = [
                'src' => url('uploads/' . $relativePath),
                'name' => $filename,
                'original_name' => $originalName,
                'type' => 'image',
                'width' => $width,
                'height' => $height,
                'size' => $size,
                'uploaded_at' => date('Y-m-d H:i:s'),
            ];
        }

        return ['received' => true, 'uploaded' => $uploaded, 'errors' => $errors];
    }

    /**
     * Nombre de archivo seguro para URL que conserva el original.
     * Si ya existe en el directorio, añade un sufijo numérico.
     */
    private function uniqueName(string $directory, string $originalName): string
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $base = pathinfo($originalName, PATHINFO_FILENAME);

        // Sin acentos, sin separadores de ruta y con espacios convertidos en guiones.
        if (function_exists('iconv')) {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base);
            if ($ascii !== false) {
                $base = $ascii;
            }
        }
        $base = preg_replace('/[^A-Za-z0-9._-]+/', '-', $base) ?? '';
        $base = trim(str_replace(['..', '/', '\\'], '', $base), '-._');

        if ($base === '') {
            $base = 'imagen';
        }
        $base = substr($base, 0, 80);

        $candidate = $base . '.' . $extension;
        $suffix = 1;
        while (is_file($directory . '/' . $candidate)) {
            $candidate = $base . '-' . $suffix . '.' . $extension;
            $suffix++;
            if ($suffix > 999) {
                $candidate = $base . '-' . bin2hex(random_bytes(4)) . '.' . $extension;
                break;
            }
        }

        return $candidate;
    }

    /**
     * Dimensiones de una imagen o de un SVG (por viewBox).
     *
     * Pública porque el listado de assets de una página también las necesita
     * para las imágenes que ya están en disco.
     *
     * @return array{0:int,1:int}
     */
    /**
     * Imágenes ya subidas, en el formato que espera GrapesJS.
     *
     * ── Por qué vive aquí ───────────────────────────────────────────────
     * El constructor de páginas tenía esta lógica privada y el del pie no tenía
     * ninguna, así que en el pie no se podía elegir un logo ya subido. Se movió
     * al servicio que ya es dueño de las subidas: una sola forma de recorrer
     * `uploads/` y de construir la URL pública.
     *
     * Cada entrada lleva `src`, `name`, `type`, `width`, `height`, `size` y
     * `uploaded_at`, ordenadas de la más reciente a la más antigua.
     *
     * @return list<array{src:string,name:string,type:string,width:int,height:int,size:int,uploaded_at:string}>
     */
    public function existing(): array
    {
        $raiz = rtrim($this->uploadsPath, '/\\');
        if (!is_dir($raiz)) {
            return [];
        }

        $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        $raizNormal = str_replace('\\', '/', $raiz);
        $assets = [];

        $iterador = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($raiz, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterador as $archivo) {
            if ($archivo->isDir()) {
                continue;
            }

            $ext = strtolower($archivo->getExtension());
            if (!in_array($ext, $permitidas, true)) {
                continue;
            }

            // getPathname() usa separadores nativos; sin normalizar, la URL salía
            // como «uploads/C:\...» y las miniaturas se veían rotas.
            $absoluta = str_replace('\\', '/', $archivo->getPathname());
            $relativa = ltrim(substr($absoluta, strlen($raizNormal)), '/');
            [$ancho, $alto] = $this->dimensionsOf($archivo->getPathname(), $ext);

            $assets[] = [
                'src'         => url('uploads/' . $relativa),
                'name'        => $archivo->getFilename(),
                'type'        => 'image',
                'width'       => $ancho,
                'height'      => $alto,
                'size'        => $archivo->getSize(),
                'uploaded_at' => date('Y-m-d H:i:s', $archivo->getMTime()),
            ];
        }

        usort($assets, static fn (array $a, array $b): int => strtotime($b['uploaded_at']) <=> strtotime($a['uploaded_at']));

        return $assets;
    }

    /**
     * Calcula las dimensiones de una imagen.
     *
     * @return array{0:int,1:int}
     */
    public function dimensionsOf(string $path, string $extension): array
    {
        if ($extension === 'svg') {
            $svg = @file_get_contents($path);
            if ($svg && preg_match('/viewBox=["\']([^"\']+)["\']/', $svg, $matches)) {
                $viewBox = preg_split('/[\s,]+/', trim($matches[1])) ?: [];
                if (count($viewBox) === 4) {
                    return [(int) $viewBox[2], (int) $viewBox[3]];
                }
            }
            return [0, 0];
        }

        $info = @getimagesize($path);
        return $info ? [(int) $info[0], (int) $info[1]] : [0, 0];
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Sanitizador de SVG.
 *
 * Un SVG es XML: puede llevar `<script>`, manejadores `on*`, entidades
 * externas o `<foreignObject>` con HTML dentro. Publicado tal cual en el sitio
 * público sería XSS almacenado con nombre de imagen.
 *
 * Estrategia: **lista blanca**. Se parsea el XML sin red, se recorren todos los
 * nodos, se elimina cualquier elemento fuera de la lista y cualquier atributo
 * fuera de la lista; después se vuelve a serializar. Lo que no está permitido
 * desaparece, y el llamante recibe la lista de lo eliminado para informar al
 * administrador.
 *
 * Nota: no se usa `LIBXML_NOENT`, así que no se expanden entidades externas
 * (XXE) ni bombas de expansión. Un `<!DOCTYPE` se rechaza de plano.
 *
 * @package App\Services
 * @since   2026-10-26
 */
final class SvgSanitizer
{
    /**
     * Elementos que sí pueden quedarse.
     *
     * `<style>` **no** está: su contenido es CSS y no se puede sanear de forma
     * fiable con esta estrategia, así que se elimina entero. Un logotipo no
     * necesita hojas de estilo embebidas; si se elimina alguna, el administrador
     * lo ve en el aviso de la subida.
     */
    private const ALLOWED_ELEMENTS = [
        'svg', 'g', 'defs', 'symbol', 'use', 'title', 'desc',
        'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
        'text', 'tspan', 'textPath',
        'linearGradient', 'radialGradient', 'stop', 'pattern', 'clipPath', 'mask',
        'filter', 'feGaussianBlur', 'feOffset', 'feBlend', 'feMerge', 'feMergeNode',
        'feColorMatrix', 'feComposite', 'feFlood', 'feDropShadow',
    ];

    /** Atributos permitidos (además de los `data-*` y `aria-*`). */
    private const ALLOWED_ATTRIBUTES = [
        'version', 'id', 'class', 'style', 'transform', 'viewBox', 'width', 'height',
        'x', 'y', 'x1', 'y1', 'x2', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'd', 'points',
        'fill', 'fill-opacity', 'fill-rule', 'stroke', 'stroke-width', 'stroke-opacity',
        'stroke-linecap', 'stroke-linejoin', 'stroke-dasharray', 'stroke-dashoffset',
        'opacity', 'clip-path', 'clip-rule', 'mask', 'filter', 'offset', 'stop-color',
        'stop-opacity', 'gradientUnits', 'gradientTransform', 'patternUnits',
        'preserveAspectRatio', 'xmlns', 'xmlns:xlink', 'xml:space', 'font-family',
        'font-size', 'font-weight', 'text-anchor', 'dominant-baseline', 'letter-spacing',
        'role', 'focusable', 'data-icon', 'display',
        // Referencias: se admiten, pero sólo internas (`#id`); lo comprueba
        // `cleanAttributes()`. Sin esto se rompen los SVG con `<use>`.
        'href', 'xlink:href',
    ];

    /**
     * Sanitiza el contenido de un archivo SVG.
     *
     * @param string $svg Contenido XML original.
     * @return array{ok: bool, svg: string, removed: array<int, string>, error: ?string}
     */
    public function sanitize(string $svg): array
    {
        $out = ['ok' => false, 'svg' => '', 'removed' => [], 'error' => null];

        if (trim($svg) === '') {
            $out['error'] = 'El SVG está vacío.';
            return $out;
        }
        if (strlen($svg) > 2097152) {
            $out['error'] = 'El SVG supera 2 MB.';
            return $out;
        }
        if (stripos($svg, '<!DOCTYPE') !== false || stripos($svg, '<!ENTITY') !== false) {
            $out['error'] = 'El SVG contiene una declaración DOCTYPE o ENTITY, que no está permitida.';
            return $out;
        }

        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded   = $document->loadXML($svg, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded || !$document->documentElement) {
            $out['error'] = 'El SVG no es XML válido.';
            return $out;
        }
        if (strtolower($document->documentElement->nodeName) !== 'svg') {
            $out['error'] = 'El elemento raíz del archivo no es <svg>.';
            return $out;
        }

        $removed = [];
        $this->cleanNode($document->documentElement, $removed);

        $clean = $document->saveXML($document->documentElement);
        if ($clean === false || trim($clean) === '') {
            $out['error'] = 'No se pudo serializar el SVG saneado.';
            return $out;
        }

        $out['ok']      = true;
        $out['svg']     = $clean;
        $out['removed'] = array_values(array_unique($removed));
        return $out;
    }

    /**
     * Recorre un nodo y sus descendientes eliminando lo no permitido.
     *
     * @param array<int, string> $removed Acumulador de motivos (por referencia).
     */
    private function cleanNode(\DOMNode $node, array &$removed): void
    {
        if (!$node->hasChildNodes()) {
            $this->cleanAttributes($node, $removed);
            return;
        }

        // Copia de la lista: se va a modificar el DOM mientras se recorre.
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child instanceof \DOMElement) {
                $name = $child->nodeName;
                $lower = strtolower($name);

                if (!in_array($name, self::ALLOWED_ELEMENTS, true) && !in_array($lower, self::ALLOWED_ELEMENTS, true)) {
                    $removed[] = 'elemento <' . $name . '>';
                    $node->removeChild($child);
                    continue;
                }
                $this->cleanNode($child, $removed);
                continue;
            }

            if ($child instanceof \DOMComment) {
                $node->removeChild($child);
                continue;
            }

            if ($child instanceof \DOMProcessingInstruction) {
                $removed[] = 'instrucción de proceso <' . $child->nodeName . '>';
                $node->removeChild($child);
            }
        }

        $this->cleanAttributes($node, $removed);
    }

    /**
     * Elimina atributos no permitidos o con contenido peligroso.
     *
     * @param array<int, string> $removed Acumulador de motivos (por referencia).
     */
    private function cleanAttributes(\DOMNode $node, array &$removed): void
    {
        if (!$node->attributes instanceof \DOMNamedNodeMap) {
            return;
        }

        $toRemove = [];
        foreach ($node->attributes as $attribute) {
            $name  = $attribute->nodeName;
            $lower = strtolower($name);
            $value = strtolower(trim($attribute->nodeValue ?? ''));

            if (str_starts_with($lower, 'on')) {
                $toRemove[] = [$name, 'atributo de evento ' . $name];
                continue;
            }
            if (!in_array($name, self::ALLOWED_ATTRIBUTES, true)
                && !str_starts_with($lower, 'data-')
                && !str_starts_with($lower, 'aria-')) {
                $toRemove[] = [$name, 'atributo ' . $name];
                continue;
            }
            if (str_contains($value, 'javascript:') || str_contains($value, 'vbscript:') || str_contains($value, 'data:text/html')) {
                $toRemove[] = [$name, 'valor peligroso en ' . $name];
                continue;
            }
            if (in_array($lower, ['href', 'xlink:href', 'src'], true) && !str_starts_with($value, '#')) {
                $toRemove[] = [$name, 'referencia externa en ' . $name];
                continue;
            }
            if ($lower === 'style' && (
                str_contains($value, 'expression(')
                || str_contains($value, 'javascript:')
                || str_contains($value, 'url(')
                || str_contains($value, '@import')
                || str_contains($value, 'data:')
            )) {
                $toRemove[] = [$name, 'style con contenido no permitido'];
            }
        }

        foreach ($toRemove as [$name, $reason]) {
            $node->removeAttribute($name);
            $removed[] = $reason;
        }
    }
}

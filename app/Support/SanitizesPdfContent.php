<?php

declare(strict_types=1);

namespace App\Support;

trait SanitizesPdfContent
{
    /**
     * Limpia recursivamente un array para que todos los strings sean UTF-8 válido.
     */
    protected function sanitizeArrayForPdf(array $data): array
    {
        array_walk_recursive($data, function (&$item): void {
            if (! is_string($item)) {
                return;
            }

            // Si ya es UTF-8 válido, lo dejamos.
            if (mb_check_encoding($item, 'UTF-8')) {
                return;
            }

            // Intentamos convertir asumiendo ISO-8859-1 / Windows-1252.
            $converted = @mb_convert_encoding($item, 'UTF-8', 'ISO-8859-1,Windows-1252');

            if ($converted === false || ! mb_check_encoding($converted, 'UTF-8')) {
                // Último recurso: eliminar bytes inválidos.
                $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $item) ?: '';
            }

            $item = $converted;
        });

        return $data;
    }

    /**
     * Asegura que un string individual sea UTF-8 válido.
     */
    protected function sanitizeStringForPdf(?string $text): string
    {
        $text ??= '';

        if (mb_check_encoding($text, 'UTF-8')) {
            return $text;
        }

        $converted = @mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1,Windows-1252');

        if ($converted === false || ! mb_check_encoding($converted, 'UTF-8')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $text) ?: '';
        }

        return $converted;
    }

    /**
     * Asegura que el HTML completo esté en UTF-8 válido.
     */
    protected function sanitizeHtmlForPdf(string $html): string
    {
        if (mb_check_encoding($html, 'UTF-8')) {
            return $html;
        }

        $clean = @iconv('UTF-8', 'UTF-8//IGNORE', $html);

        if ($clean === false) {
            $clean = mb_convert_encoding($html, 'UTF-8', 'UTF-8');
        }

        return $clean;
    }
}

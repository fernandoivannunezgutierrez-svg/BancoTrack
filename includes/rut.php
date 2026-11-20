<?php
declare(strict_types=1);

/**
 * Utilidades para normalización y validación de RUT chileno (máx. 12 chars: XX.XXX.XXX-Y).
 */

function normalizar_rut(string $rawRut): ?string
{
    $clean = strtoupper(preg_replace('/[^0-9kK]/', '', trim($rawRut)) ?? '');
    if (strlen($clean) < 7 || strlen($clean) > 9) {
        return null;
    }

    $body = substr($clean, 0, -1);
    $dv = substr($clean, -1);

    if (!ctype_digit($body) || (!ctype_digit($dv) && $dv !== 'K')) {
        return null;
    }

    $formattedBody = number_format((int) $body, 0, '', '.');
    $formatted = $formattedBody . '-' . $dv;

    if (strlen($formatted) > 12) {
        return null;
    }

    return $formatted;
}

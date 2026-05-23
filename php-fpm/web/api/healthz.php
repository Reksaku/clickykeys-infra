<?php
// Endpoint sprawdzający czy aplikacja PHP działa (liveness check).

if (!function_exists('healthz_payload')) {
    function healthz_payload(): array {
        return [
            'status'  => 'ok',
            'service' => 'php-fpm',
        ];
    }
}

// Wykonujemy odpowiedź HTTP tylko gdy plik jest wywołany przez serwer,
// nie podczas include w testach jednostkowych.
if (PHP_SAPI !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    echo json_encode(healthz_payload());
}

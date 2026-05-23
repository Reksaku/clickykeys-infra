<?php
// Healthz endpoint - liveness check

if (!function_exists('healthz_payload')) {
    function healthz_payload(): array {
        return [
            'status'  => 'ok',
            'service' => 'php-fpm',
        ];
    }
}


if (PHP_SAPI !== 'cli') {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(200);
    echo json_encode(healthz_payload());
}

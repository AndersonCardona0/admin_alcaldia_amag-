<?php

declare(strict_types=1);

/**
 * router.php — Script de enrutamiento para el servidor de desarrollo PHP integrado.
 *
 * Uso:
 *   php -S localhost:8000 router.php
 *
 * Comportamiento:
 *  · Archivos estáticos (css, js, imágenes, fuentes): se sirven directamente.
 *  · Cualquier otra ruta (incluidas peticiones directas a /views/*.php,
 *    /controllers/*.php, etc.): pasa por index.php (front controller).
 *
 * En producción (Apache/Nginx) este archivo no se usa; el equivalente
 * es la directiva RewriteRule o try_files en la configuración del servidor.
 */

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// Activos estáticos: dejar que el servidor los sirva directamente
if (preg_match('/\.(css|js|map|png|jpg|jpeg|gif|ico|svg|webp|woff2?|ttf|eot)$/i', (string) $requestPath)) {
    return false;
}

// Todo lo demás pasa por el front controller
require __DIR__ . '/index.php';

<?php

/**
 * RbacGuard.php — Control de Acceso Basado en Roles (RBAC).
 * Cargado desde index.php antes del despachador de rutas.
 * Requiere que session_start() haya sido invocado previamente.
 *
 * Roles disponibles: 'ADMINISTRADOR' | 'AUDITOR'
 */

/** Retorna true si el usuario autenticado tiene rol ADMINISTRADOR. */
function esAdministrador(): bool
{
    return ($_SESSION['usuario_rol'] ?? '') === 'ADMINISTRADOR';
}

/**
 * Detiene la ejecución si el usuario no tiene rol ADMINISTRADOR.
 * Inyecta un flash de acceso denegado y redirige a la ruta indicada.
 *
 * Llamar al inicio de cualquier controlador o método que exija permisos
 * elevados, como segunda capa de defensa después del guard de rutas en index.php.
 */
function requiereAdministrador(string $redirigirA = '/?page=dashboard'): void
{
    if (!esAdministrador()) {
        $_SESSION['flash_error'] = 'Acceso denegado. Esta operación requiere perfil de Administrador.';
        header("Location: {$redirigirA}");
        exit;
    }
}

<?php

/**
 * index.php — Front Controller (Punto de entrada único)
 * Intercepta todas las peticiones HTTP, sanitiza el parámetro 'page'
 * y despacha al controlador o vista correspondiente.
 */

declare(strict_types=1);

// Las sesiones deben iniciarse antes de cualquier salida al buffer
session_start();

// Configuración de errores (desactivar display_errors en producción real)
ini_set('display_errors', '1');
error_reporting(E_ALL);

date_default_timezone_set('America/Bogota');

// ── Núcleo de conexión: siempre disponible para todos los controladores ────────
require_once __DIR__ . '/config/Conexion.php';

// ── Sanitización del parámetro de ruta ───────────────────────────────────────
$paginaSolicitada = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'dashboard';
$paginaSolicitada = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $paginaSolicitada));
$paginaSolicitada = $paginaSolicitada ?: 'dashboard';

// ── Despachador de rutas ──────────────────────────────────────────────────────
try {
    switch ($paginaSolicitada) {

        case 'dashboard':
        default:
            // Carga modelos y controlador del panel principal
            require_once __DIR__ . '/models/EquipoModel.php';
            require_once __DIR__ . '/models/ZonaModel.php';
            require_once __DIR__ . '/controllers/DashboardController.php';
            (new DashboardController())->mostrar();
            break;

        case 'inventario':
            // Delega al EquipoController para búsqueda dinámica y filtros relacionales
            require_once __DIR__ . '/models/EquipoModel.php';
            require_once __DIR__ . '/models/ZonaModel.php';
            require_once __DIR__ . '/controllers/EquipoController.php';
            (new EquipoController())->inventario();
            break;

        case 'registrar':
            // Carga todos los modelos necesarios y delega al controlador
            // que maneja GET (formulario) y POST (procesamiento + redirect)
            require_once __DIR__ . '/models/EquipoModel.php';
            require_once __DIR__ . '/models/ZonaModel.php';
            require_once __DIR__ . '/models/FuncionarioModel.php';
            require_once __DIR__ . '/controllers/EquipoController.php';
            (new EquipoController())->registrar();
            break;

        case 'editar':
            // Ruta de edición: GET renderiza el form pre-poblado,
            // POST ejecuta la transacción y redirige con mensaje flash
            require_once __DIR__ . '/models/EquipoModel.php';
            require_once __DIR__ . '/models/ZonaModel.php';
            require_once __DIR__ . '/models/FuncionarioModel.php';
            require_once __DIR__ . '/controllers/EquipoController.php';
            (new EquipoController())->editar();
            break;

        case 'detalle':
            // Vista de perfil del activo (solo lectura): datos + timeline de auditoría
            require_once __DIR__ . '/models/EquipoModel.php';
            require_once __DIR__ . '/models/ZonaModel.php';
            require_once __DIR__ . '/models/HistorialModel.php';
            require_once __DIR__ . '/controllers/EquipoController.php';
            (new EquipoController())->ver();
            break;

        case 'exportar_pdf':
            // Genera y fuerza la descarga del Acta de Asignación en PDF.
            // No produce salida HTML; envía directamente los headers del PDF.
            require_once __DIR__ . '/models/EquipoModel.php';
            require_once __DIR__ . '/models/ZonaModel.php';
            require_once __DIR__ . '/controllers/EquipoController.php';
            (new EquipoController())->exportarPdf();
            break;
    }
} catch (Throwable $e) {
    // Captura cualquier error no manejado (PDOException, RuntimeException, etc.)
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
          <script src="https://cdn.tailwindcss.com"></script></head>
          <body class="flex items-center justify-center h-screen bg-slate-100">
          <div class="bg-white rounded-xl border border-red-200 p-8 max-w-lg shadow text-center">
            <p class="text-red-600 font-bold text-lg mb-2">Error interno del servidor</p>
            <p class="text-slate-600 text-sm font-mono bg-slate-50 rounded p-3 mt-3 text-left break-all">'
          . htmlspecialchars($e->getMessage()) .
          '</p></div></body></html>';
}

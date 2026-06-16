<?php

/**
 * index.php — Front Controller (Punto de entrada único)
 * Intercepta todas las peticiones HTTP, sanitiza el parámetro 'page'
 * y despacha al controlador o vista correspondiente.
 */

declare(strict_types=1);

// ══════════════════════════════════════════════════════════════════════════════
// PILAR 1 — Detección de entorno
// Cualquier host distinto de localhost / 127.0.0.1 / ::1 se trata como
// producción. Esta constante dirige todos los controles de seguridad subsiguientes.
// ══════════════════════════════════════════════════════════════════════════════
// Extrae solo el hostname, descartando el puerto (ej: "localhost:8000" → "localhost")
$_detectedHost = strtolower(explode(':', $_SERVER['HTTP_HOST'] ?? 'cli')[0]);
define('APP_ENV',   in_array($_detectedHost, ['localhost', '127.0.0.1', '::1'], true)
    ? 'development'
    : 'production'
);
define('APP_DEBUG', APP_ENV === 'development');
unset($_detectedHost);

// ══════════════════════════════════════════════════════════════════════════════
// PILAR 2 — Cabeceras de seguridad HTTP
// Se envían ANTES de session_start() y de cualquier salida HTML.
// ══════════════════════════════════════════════════════════════════════════════
header('X-Frame-Options: DENY');                        // anti-Clickjacking
header('X-Content-Type-Options: nosniff');              // anti-MIME sniffing
header('Referrer-Policy: no-referrer-when-downgrade');  // privacidad de referrer
header('X-XSS-Protection: 1; mode=block');              // filtro XSS legacy

// ══════════════════════════════════════════════════════════════════════════════
// PILAR 3 — Errores: silencio total en pantalla, todo al log del servidor
// ══════════════════════════════════════════════════════════════════════════════
ini_set('display_errors',         APP_DEBUG ? '1' : '0');
ini_set('display_startup_errors', APP_DEBUG ? '1' : '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');

// Configura la cookie de sesión antes de session_start():
//  · httponly  → JavaScript no puede leer ni robar el ID de sesión
//  · samesite  → el navegador no envía la cookie en peticiones cross-site (anti-CSRF)
//  · secure    → cambiar a true en producción con HTTPS habilitado
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    // Producción (HTTPS): true — desactivar solo si el servidor aún no tiene SSL.
    'secure'   => !APP_DEBUG,
    'httponly' => true,
    'samesite' => 'Strict',
]);

// Las sesiones deben iniciarse antes de cualquier salida al buffer
session_start();

// Genera el token CSRF una vez por sesión; persiste hasta que se regenere o expire
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

date_default_timezone_set('America/Bogota');

// ── Núcleo de conexión: siempre disponible para todos los controladores ────────
require_once __DIR__ . '/config/Conexion.php';

// ── Control de acceso basado en roles: funciones esAdministrador() / requiereAdministrador() ─
require_once __DIR__ . '/config/RbacGuard.php';

// ── Clase base de controladores protegidos (segunda capa de auth) ─────────────
require_once __DIR__ . '/controllers/BaseController.php';

// ── Sanitización del parámetro de ruta ───────────────────────────────────────
$paginaSolicitada = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'dashboard';
$paginaSolicitada = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $paginaSolicitada));
$paginaSolicitada = $paginaSolicitada ?: 'dashboard';

// ── Auth Guard: Middleware de autenticación ───────────────────────────────────
// Rutas accesibles sin sesión activa
$rutasPublicas = ['login', 'logout'];

if (!in_array($paginaSolicitada, $rutasPublicas, true)) {

    // Verifica presencia de los campos mínimos que establece procesarLogin()
    $sesionValida = !empty($_SESSION['usuario_id']) && !empty($_SESSION['usuario_rol']);

    // Expiración server-side por inactividad: 30 minutos sin actividad = sesión muerta
    if ($sesionValida && isset($_SESSION['_last_activity'])) {
        if ((time() - $_SESSION['_last_activity']) > 1800) {
            $sesionValida = false;
        }
    }

    if (!$sesionValida) {
        // Limpia los datos de usuario pero conserva el CSRF token.
        // La rotación del session ID ocurre SOLO en procesarLogin() tras
        // autenticarse (límite de autenticación correcto). Si se llama a
        // session_regenerate_id() aquí, el navegador recibe una cookie nueva
        // mientras el formulario de login ya renderizado tiene el token de la
        // sesión anterior → mismatch de CSRF garantizado en cada intento.
        $csrfActual = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
        $_SESSION   = ['csrf_token' => $csrfActual];

        header('Location: /?page=login');
        exit;
    }

    // Renueva el timestamp en cada petición autenticada para sliding expiration
    $_SESSION['_last_activity'] = time();
}

// ── RBAC Guard: rutas restringidas al rol ADMINISTRADOR ───────────────────────
// Segunda capa de defensa; la primera está en cada método de controlador afectado.
// `ajustes` NO está aquí: AUDITOR también accede (solo ve Panel B — cambio de clave propia).
// El RBAC interno del controlador bloquea las acciones admin-only (crear/toggle usuario).
$rutasSoloAdmin = ['dar_de_baja', 'exportar_acta_baja', 'zonas', 'funcionarios'];

if (
    in_array($paginaSolicitada, $rutasSoloAdmin, true) &&
    !esAdministrador()
) {
    $_SESSION['flash_error'] = 'Acceso denegado. Esta sección requiere perfil de Administrador.';
    header('Location: /?page=inventario');
    exit;
}

// ── Despachador de rutas ──────────────────────────────────────────────────────
try {
    switch ($paginaSolicitada) {

        case 'login':
            // GET → mostrar formulario | POST → procesar credenciales
            require_once __DIR__ . '/models/AuthModel.php';
            require_once __DIR__ . '/controllers/AuthController.php';
            $ctrlAuth = new AuthController();
            ($_SERVER['REQUEST_METHOD'] === 'POST')
                ? $ctrlAuth->procesarLogin()
                : $ctrlAuth->mostrarLogin();
            break;

        case 'logout':
            // POST exclusivo: destruye la sesión y redirige al login
            require_once __DIR__ . '/models/AuthModel.php';
            require_once __DIR__ . '/controllers/AuthController.php';
            (new AuthController())->logout();
            break;

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

        case 'dar_de_baja':
            // POST exclusivo: ejecuta la baja lógica del equipo con justificación.
            require_once __DIR__ . '/models/EquipoModel.php';
            require_once __DIR__ . '/models/ZonaModel.php';
            require_once __DIR__ . '/models/HistorialModel.php';
            require_once __DIR__ . '/controllers/EquipoController.php';
            (new EquipoController())->darDeBaja();
            break;

        case 'exportar_acta_baja':
            // Genera y fuerza la descarga del Acta de Baja Institucional en PDF.
            require_once __DIR__ . '/models/EquipoModel.php';
            require_once __DIR__ . '/models/ZonaModel.php';
            require_once __DIR__ . '/models/HistorialModel.php';
            require_once __DIR__ . '/controllers/EquipoController.php';
            (new EquipoController())->exportarActaBaja();
            break;

        case 'funcionarios':
            // GET → panel con listado y formulario de registro.
            // POST → procesa el alta de un nuevo funcionario (PRG).
            require_once __DIR__ . '/models/FuncionarioModel.php';
            require_once __DIR__ . '/controllers/FuncionarioController.php';
            $ctrl = new FuncionarioController();
            ($_SERVER['REQUEST_METHOD'] === 'POST')
                ? $ctrl->guardar()
                : $ctrl->mostrar();
            break;

        case 'zonas':
            // GET  (sin action)       → panel listado + formulario de alta
            // POST (sin action)       → registrar zona nueva (PRG)
            // GET  action=editar&id=X → formulario de edición pre-poblado
            // POST action=editar&id=X → guardar cambios de la zona (PRG)
            // POST action=eliminar    → baja lógica de la zona (PRG)
            require_once __DIR__ . '/models/ZonaModel.php';
            require_once __DIR__ . '/models/SedeModel.php';
            require_once __DIR__ . '/models/FuncionarioModel.php';
            require_once __DIR__ . '/controllers/ZonaController.php';
            $ctrlZona   = new ZonaController();
            $zonaAction = trim(filter_input(INPUT_GET, 'action', FILTER_UNSAFE_RAW) ?? '');
            if ($zonaAction === 'editar') {
                ($_SERVER['REQUEST_METHOD'] === 'POST')
                    ? $ctrlZona->guardarEdicion()
                    : $ctrlZona->mostrarEditar();
            } elseif ($zonaAction === 'eliminar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
                $ctrlZona->desactivar();
            } else {
                ($_SERVER['REQUEST_METHOD'] === 'POST')
                    ? $ctrlZona->guardar()
                    : $ctrlZona->mostrar();
            }
            break;

        case 'ajustes':
            // GET → panel de ajustes con gestión de usuarios y cambio de clave.
            // POST → dispatch interno por campo `action` (crear/toggle/password).
            require_once __DIR__ . '/models/UsuarioModel.php';
            require_once __DIR__ . '/controllers/AjustesController.php';
            $ctrlAjustes = new AjustesController();
            ($_SERVER['REQUEST_METHOD'] === 'POST')
                ? $ctrlAjustes->procesarPost()
                : $ctrlAjustes->mostrarPanel();
            break;

        case 'reportes':
            // Panel de reportes estadísticos y controles de exportación.
            require_once __DIR__ . '/models/ReporteModel.php';
            require_once __DIR__ . '/models/ZonaModel.php';
            require_once __DIR__ . '/controllers/ReporteController.php';
            (new ReporteController())->mostrar();
            break;

        case 'exportar_reporte_pdf':
            // Descarga el listado filtrado como PDF horizontal con FPDF.
            require_once __DIR__ . '/models/ReporteModel.php';
            require_once __DIR__ . '/controllers/ReporteController.php';
            (new ReporteController())->exportarPdf();
            break;

        case 'exportar_reporte_csv':
            // Descarga el listado filtrado como CSV con BOM UTF-8 (compatible Excel).
            require_once __DIR__ . '/models/ReporteModel.php';
            require_once __DIR__ . '/controllers/ReporteController.php';
            (new ReporteController())->exportarCsv();
            break;
    }
} catch (Throwable $e) {
    // Registra el error completo en el log del servidor; NUNCA lo expone al cliente.
    error_log(
        '[Sistema Alcaldía] ' . get_class($e) . ': ' . $e->getMessage()
        . ' en ' . $e->getFile() . ':' . $e->getLine()
    );
    $ref = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    http_response_code(500);
    echo '<!DOCTYPE html><html lang="es"><head><meta charset="UTF-8">
          <script src="https://cdn.tailwindcss.com"></script></head>
          <body class="flex items-center justify-center h-screen bg-slate-100">
          <div class="bg-white rounded-xl border border-red-200 p-8 max-w-lg shadow text-center">
            <p class="text-red-600 font-bold text-lg mb-2">Error interno del servidor</p>
            <p class="text-slate-500 text-sm mt-3">El sistema encontró un error inesperado.<br>
               Comuníquese con el administrador indicando la referencia:</p>
            <p class="font-mono text-sm font-bold text-slate-800 bg-slate-100 px-4 py-2 rounded mt-3">'
          . htmlspecialchars($ref, ENT_QUOTES, 'UTF-8') .
          '</p></div></body></html>';
}

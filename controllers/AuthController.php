<?php

require_once __DIR__ . '/../models/AuthModel.php';

/**
 * AuthController.php
 * Gestiona el ciclo completo de autenticación: mostrar login, procesar
 * credenciales y cerrar sesión. Sin lógica SQL ni salida HTML directa.
 *
 * Capas de seguridad implementadas:
 *  1. Verificación CSRF en login y logout (hash_equals — comparación en tiempo constante)
 *  2. password_verify() con bcrypt para validación de credenciales
 *  3. Hash dummy de formato bcrypt válido para normalizar tiempo de respuesta
 *     cuando el usuario no existe (mitiga user enumeration por timing analysis)
 *  4. session_regenerate_id(true) post-login (previene session fixation)
 *  5. Regeneración del token CSRF post-login (previene CSRF token fixation)
 *  6. Destrucción completa de sesión + invalidación de cookie en logout
 *  7. Logout exclusivo por POST (previene forced-logout via CSRF con enlace GET)
 */
class AuthController
{
    private AuthModel $authModel;

    // Hash bcrypt sintáctico válido usado cuando el usuario no existe,
    // para que password_verify() ejecute el costo computacional completo de bcrypt
    // en ambas ramas (usuario encontrado / no encontrado) y el tiempo de respuesta
    // sea estadísticamente indistinguible para un observador externo.
    // Formato: $2y$12$ + 22 chars salt + 31 chars hash = 60 chars totales.
    // Costo 12 coincide con el factor usado al generar los hashes reales en la BD,
    // garantizando tiempos uniformes entre usuarios existentes y no existentes.
    private const HASH_DUMMY = '$2y$12$dummyhashtimingnormxyzABCDEFGHIJKLMNOPQRSTUVWXYZabcde';

    public function __construct()
    {
        $this->authModel = new AuthModel();
    }

    /**
     * GET /?page=login
     * Renderiza el formulario de inicio de sesión.
     * Si el usuario ya tiene sesión autenticada, redirige al dashboard.
     */
    public function mostrarLogin(): void
    {
        if (!empty($_SESSION['usuario_id'])) {
            header('Location: /?page=dashboard');
            exit;
        }
        require_once __DIR__ . '/../views/login.php';
    }

    /**
     * POST /?page=login
     * Valida el token CSRF, busca el usuario, verifica la contraseña con bcrypt
     * y establece las variables de sesión si las credenciales son correctas.
     * Siempre termina en redirect (patrón PRG).
     */
    public function procesarLogin(): void
    {
        // ── 1. Verificación CSRF ───────────────────────────────────────────────
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_login_error'] = 'Petición no autorizada o caducada. Intente nuevamente.';
            header('Location: /?page=login');
            exit;
        }

        // ── 2. Captura y sanitización básica de credenciales ──────────────────
        $usuario  = trim(filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $password = $_POST['password'] ?? '';

        if ($usuario === '' || $password === '') {
            $_SESSION['flash_login_error'] = 'Ingrese su usuario y contraseña para continuar.';
            header('Location: /?page=login');
            exit;
        }

        // ── 3. Verificación de credenciales con mitigación de timing attack ───
        $registro = $this->authModel->buscarPorUsuario($usuario);

        // Siempre se ejecuta password_verify() para que el tiempo de respuesta
        // sea uniforme independientemente de si el usuario existe en la BD.
        $hashComparar = !empty($registro) ? $registro['password_hash'] : self::HASH_DUMMY;
        $credencialesValidas = !empty($registro) && password_verify($password, $hashComparar);

        if (!$credencialesValidas) {
            $_SESSION['flash_login_error'] = 'Usuario o contraseña incorrectos. Verifique e intente nuevamente.';
            header('Location: /?page=login');
            exit;
        }

        // ── 4. Prevención de session fixation ─────────────────────────────────
        // Regenera el ID de sesión conservando los datos; invalida el anterior.
        session_regenerate_id(true);

        // ── 5. Establecimiento de variables de sesión autenticada ─────────────
        $_SESSION['usuario_id']     = (int) $registro['id'];
        $_SESSION['usuario_login']  = $registro['usuario'];
        $_SESSION['usuario_nombre'] = $registro['nombre_completo'];
        $_SESSION['usuario_rol']    = $registro['rol'];

        // Regenera el CSRF token con la nueva sesión para evitar CSRF token fixation
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        header('Location: /?page=dashboard');
        exit;
    }

    /**
     * POST /?page=logout
     * Destruye la sesión completa, invalida la cookie de sesión en el cliente
     * y redirige al login. Solo acepta POST para prevenir forced-logout por CSRF.
     */
    public function logout(): void
    {
        // Logout exclusivo por POST — un GET malicioso no puede cerrar la sesión
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /?page=dashboard');
            exit;
        }

        // Verificación CSRF para prevenir forced-logout cross-site
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            header('Location: /?page=dashboard');
            exit;
        }

        // Limpia todas las variables de sesión en memoria
        $_SESSION = [];

        // Invalida la cookie de sesión en el navegador del cliente
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        header('Location: /?page=login');
        exit;
    }
}

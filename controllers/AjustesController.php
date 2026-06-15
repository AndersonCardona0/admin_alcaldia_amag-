<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/UsuarioModel.php';

/**
 * AjustesController.php
 * Gestiona el panel de ajustes del sistema y la administración de cuentas.
 *
 * Rutas manejadas (todas bajo /?page=ajustes):
 *   GET  → mostrarPanel()          — panel según rol (ADMIN: todo | AUDITOR: solo clave)
 *   POST → procesarPost()          — dispatcher interno por campo `action`:
 *            action=crear_usuario  → guardarUsuario()       [solo ADMINISTRADOR]
 *            action=toggle_estado  → toggleEstado()         [solo ADMINISTRADOR]
 *            action=cambiar_pass   → cambiarPasswordPropia() [ambos roles]
 *
 * Capas de seguridad implementadas:
 *  1. Auth Guard en constructor (sesión obligatoria para todo el módulo)
 *  2. RBAC Guard en guardarUsuario() y toggleEstado() (solo ADMINISTRADOR)
 *  3. CSRF con hash_equals() en todas las acciones POST
 *  4. password_verify() antes de cualquier cambio de contraseña
 *  5. Prevención de auto-bloqueo: el admin no puede desactivarse a sí mismo
 *  6. Aislamiento de PDOException con referencia hex única, sin exposición al cliente
 *  7. Patrón PRG: todos los métodos POST finalizan con header() + exit
 */
class AjustesController
{
    private UsuarioModel $model;

    public function __construct()
    {
        // Auth Guard: sesión obligatoria para acceder a cualquier acción del módulo
        if (empty($_SESSION['usuario_id'])) {
            header('Location: /?page=login');
            exit;
        }

        $this->model = new UsuarioModel();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET — Renderizado del panel
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Carga y renderiza el panel de ajustes.
     * Los usuarios solo se cargan para el rol ADMINISTRADOR; el AUDITOR
     * recibe un array vacío (defensa en profundidad sobre el control de la vista).
     */
    public function mostrarPanel(): void
    {
        // Solo ADMINISTRADOR recibe el listado; el AUDITOR ve [] y la vista
        // oculta la sección completa de gestión de usuarios.
        $usuarios  = esAdministrador() ? $this->model->obtenerTodos() : [];
        $tabActiva = $this->resolverTabActiva();

        require_once __DIR__ . '/../views/ajustes.php';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST — Dispatcher de acciones
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Dispatcher central de peticiones POST.
     * Lee el campo `action` del formulario y delega al método correspondiente.
     * Acción desconocida → redirección silenciosa (no expone las rutas disponibles).
     */
    public function procesarPost(): void
    {
        $action = trim(filter_input(INPUT_POST, 'action', FILTER_UNSAFE_RAW) ?? '');

        switch ($action) {
            case 'crear_usuario':
                $this->guardarUsuario();
                break;

            case 'toggle_estado':
                $this->toggleEstado();
                break;

            case 'cambiar_pass':
                $this->cambiarPasswordPropia();
                break;

            default:
                header('Location: /?page=ajustes');
                exit;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Acción: crear usuario (solo ADMINISTRADOR)
    // ─────────────────────────────────────────────────────────────────────────

    public function guardarUsuario(): void
    {
        // Capa RBAC: solo ADMINISTRADOR
        requiereAdministrador('/?page=ajustes');

        // ── 1. CSRF ───────────────────────────────────────────────────────────
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Petición no autorizada o caducada. Intente nuevamente.';
            header('Location: /?page=ajustes&tab=usuarios');
            exit;
        }

        // ── 2. Sanitización ───────────────────────────────────────────────────
        $usuario  = trim(filter_input(INPUT_POST, 'usuario',  FILTER_UNSAFE_RAW) ?? '');
        $nombre   = trim(filter_input(INPUT_POST, 'nombre',   FILTER_UNSAFE_RAW) ?? '');
        $password = $_POST['password'] ?? '';
        $rol      = trim(filter_input(INPUT_POST, 'rol',      FILTER_UNSAFE_RAW) ?? '');

        // ── 3. Validación ─────────────────────────────────────────────────────
        $errores = [];

        if ($usuario === '') {
            $errores[] = 'El nombre de usuario es obligatorio.';
        } elseif (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $usuario)) {
            $errores[] = 'El usuario debe tener entre 3 y 50 caracteres (letras, números, puntos, guiones).';
        }

        if ($nombre === '') {
            $errores[] = 'El nombre completo es obligatorio.';
        }

        if (strlen($password) < 8) {
            $errores[] = 'La contraseña debe tener mínimo 8 caracteres.';
        }

        if (!in_array($rol, UsuarioModel::ROLES_VALIDOS, true)) {
            $errores[] = 'Seleccione un rol válido (ADMINISTRADOR o AUDITOR).';
        }

        if (!empty($errores)) {
            $_SESSION['flash_error'] = implode(' ', $errores);
            header('Location: /?page=ajustes&tab=usuarios');
            exit;
        }

        // ── 4. Persistencia ───────────────────────────────────────────────────
        try {
            $this->model->registrar($usuario, $password, $nombre, $rol);
            $_SESSION['flash_success'] = "Cuenta \"{$usuario}\" creada exitosamente con rol {$rol}.";

        } catch (PDOException $e) {
            $ref = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            error_log(
                '[Sistema Alcaldía] [Ref:' . $ref . '] PDOException en AjustesController::guardarUsuario(): '
                . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine()
            );

            // Detecta violación de unicidad (código MySQL 1062, SQLSTATE 23000)
            $_SESSION['flash_error'] = (($e->errorInfo[1] ?? 0) === 1062)
                ? "El nombre de usuario \"{$usuario}\" ya está registrado. Elija uno diferente."
                : 'No se pudo crear el usuario. Referencia de error: ' . $ref;
        }

        // ── 5. PRG ────────────────────────────────────────────────────────────
        header('Location: /?page=ajustes&tab=usuarios');
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Acción: activar / desactivar usuario (solo ADMINISTRADOR)
    // ─────────────────────────────────────────────────────────────────────────

    public function toggleEstado(): void
    {
        // Capa RBAC: solo ADMINISTRADOR
        requiereAdministrador('/?page=ajustes');

        // ── 1. CSRF ───────────────────────────────────────────────────────────
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Petición no autorizada o caducada.';
            header('Location: /?page=ajustes&tab=usuarios');
            exit;
        }

        // ── 2. Extracción y validación de IDs ─────────────────────────────────
        $idStr     = trim(filter_input(INPUT_POST, 'usuario_id', FILTER_UNSAFE_RAW) ?? '');
        $activoStr = trim(filter_input(INPUT_POST, 'activo',     FILTER_UNSAFE_RAW) ?? '');

        $id     = (ctype_digit($idStr)     && (int) $idStr > 0) ? (int) $idStr : 0;
        // Estrictamente binario: cualquier valor distinto de '1' se interpreta como desactivar
        $activo = ($activoStr === '1') ? 1 : 0;

        if ($id === 0) {
            $_SESSION['flash_error'] = 'Identificador de usuario no válido.';
            header('Location: /?page=ajustes&tab=usuarios');
            exit;
        }

        // ── 3. REGLA CRÍTICA: prevención de auto-bloqueo ──────────────────────
        // El administrador activo no puede desactivar su propia cuenta.
        if ($activo === 0 && $id === (int) $_SESSION['usuario_id']) {
            $_SESSION['flash_error'] = 'No puede desactivar su propia cuenta. '
                . 'Solicite a otro administrador que realice esta acción.';
            header('Location: /?page=ajustes&tab=usuarios');
            exit;
        }

        // ── 4. Persistencia ───────────────────────────────────────────────────
        try {
            $this->model->modificarEstado($id, $activo);
            $_SESSION['flash_success'] = $activo === 1
                ? 'Cuenta de usuario activada correctamente.'
                : 'Cuenta de usuario desactivada. El usuario no podrá iniciar sesión.';

        } catch (PDOException $e) {
            $ref = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            error_log(
                '[Sistema Alcaldía] [Ref:' . $ref . '] PDOException en AjustesController::toggleEstado(): '
                . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine()
            );
            $_SESSION['flash_error'] = 'No se pudo modificar el estado del usuario. Referencia: ' . $ref;
        }

        // ── 5. PRG ────────────────────────────────────────────────────────────
        header('Location: /?page=ajustes&tab=usuarios');
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Acción: cambiar contraseña propia (ambos roles)
    // ─────────────────────────────────────────────────────────────────────────

    public function cambiarPasswordPropia(): void
    {
        // ── 1. CSRF ───────────────────────────────────────────────────────────
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_pwd_error'] = 'Petición no autorizada o caducada. Intente nuevamente.';
            header('Location: /?page=ajustes&tab=seguridad');
            exit;
        }

        // ── 2. Captura de campos de contraseña (nunca pasar por filtros de string) ──
        $passwordActual  = $_POST['password_actual']    ?? '';
        $nuevaPassword   = $_POST['nueva_password']     ?? '';
        $confirmarPass   = $_POST['confirmar_password'] ?? '';

        // ── 3. Validación estructural ─────────────────────────────────────────
        $errores = [];

        if ($passwordActual === '') {
            $errores[] = 'Debe ingresar su contraseña actual para continuar.';
        }

        if (strlen($nuevaPassword) < 8) {
            $errores[] = 'La nueva contraseña debe tener mínimo 8 caracteres.';
        }

        if ($nuevaPassword !== $confirmarPass) {
            $errores[] = 'La nueva contraseña y su confirmación no coinciden.';
        }

        if (!empty($errores)) {
            $_SESSION['flash_pwd_error'] = implode(' ', $errores);
            header('Location: /?page=ajustes&tab=seguridad');
            exit;
        }

        // ── 4. Verificación de contraseña actual contra la BD ─────────────────
        // password_verify() ejecuta el costo computacional de bcrypt completo;
        // el tiempo de respuesta es uniforme independientemente del resultado.
        $hashActual = $this->model->obtenerHashPorId((int) $_SESSION['usuario_id']);

        if ($hashActual === '' || !password_verify($passwordActual, $hashActual)) {
            $_SESSION['flash_pwd_error'] = 'La contraseña actual es incorrecta. '
                . 'Verifique e intente nuevamente.';
            header('Location: /?page=ajustes&tab=seguridad');
            exit;
        }

        // ── 5. Persistencia del nuevo hash ────────────────────────────────────
        try {
            $this->model->actualizarPassword((int) $_SESSION['usuario_id'], $nuevaPassword);
            $_SESSION['flash_pwd_success'] = 'Su contraseña ha sido actualizada exitosamente.';

        } catch (PDOException $e) {
            $ref = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            error_log(
                '[Sistema Alcaldía] [Ref:' . $ref . '] PDOException en AjustesController::cambiarPasswordPropia(): '
                . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine()
            );
            $_SESSION['flash_pwd_error'] = 'No se pudo actualizar la contraseña. '
                . 'Referencia de error: ' . $ref;
        }

        // ── 6. PRG ────────────────────────────────────────────────────────────
        header('Location: /?page=ajustes&tab=seguridad');
        exit;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Determina qué pestaña mostrar al renderizar el panel.
     * Usa el parámetro GET `tab`; AUDITOR siempre ve 'seguridad'.
     */
    private function resolverTabActiva(): string
    {
        if (!esAdministrador()) {
            return 'seguridad';
        }

        $tab = trim(filter_input(INPUT_GET, 'tab', FILTER_UNSAFE_RAW) ?? '');
        return $tab === 'seguridad' ? 'seguridad' : 'usuarios';
    }
}

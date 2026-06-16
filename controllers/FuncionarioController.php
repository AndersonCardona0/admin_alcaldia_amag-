<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/FuncionarioModel.php';
require_once __DIR__ . '/../models/ZonaModel.php';

/**
 * FuncionarioController.php
 * Gestiona el panel de administración de funcionarios.
 *
 * Rutas manejadas:
 *   GET  /?page=funcionarios → mostrar()  — listado + formulario de alta
 *   POST /?page=funcionarios → guardar()  — procesa el registro (PRG)
 *
 * Ambas rutas exigen rol ADMINISTRADOR (verificado en index.php + constructor).
 */
class FuncionarioController extends BaseController
{
    private FuncionarioModel $model;
    private ZonaModel        $zonaModel;

    public function __construct()
    {
        parent::__construct();
        requiereAdministrador('/?page=dashboard');

        $this->model     = new FuncionarioModel();
        $this->zonaModel = new ZonaModel();
    }

    /**
     * Renderiza el panel con la tabla de funcionarios registrados
     * y el formulario de alta lateral con el selector de zonas.
     */
    public function mostrar(): void
    {
        $funcionarios = $this->model->obtenerTodos();
        $total        = count($funcionarios);
        $zonas        = $this->zonaModel->obtenerActivas();

        require_once __DIR__ . '/../views/funcionarios.php';
    }

    /**
     * Procesa el formulario de registro de un nuevo funcionario.
     * Valida CSRF, sanitiza entradas, delega al modelo y redirige (PRG).
     * Los errores de BD se registran en el log sin exponerse al cliente.
     */
    public function guardar(): void
    {
        // ── 1. Verificación CSRF ───────────────────────────────────────────────
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Petición no autorizada o caducada. Intente nuevamente.';
            header('Location: /?page=funcionarios');
            exit;
        }

        // ── 2. Sanitización de entradas ────────────────────────────────────────
        $nombre    = trim(filter_input(INPUT_POST, 'nombre', FILTER_UNSAFE_RAW) ?? '');
        $cargo     = trim(filter_input(INPUT_POST, 'cargo',  FILTER_UNSAFE_RAW) ?? '');
        $zonaIdStr = trim(filter_input(INPUT_POST, 'zona_id', FILTER_UNSAFE_RAW) ?? '');
        $emailRaw  = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '');

        // zona_id debe ser un entero positivo; cualquier otro valor → 0 (inválido)
        $zonaId = (ctype_digit($zonaIdStr) && (int) $zonaIdStr > 0) ? (int) $zonaIdStr : 0;

        // ── 3. Validación ──────────────────────────────────────────────────────
        $errores = [];
        if ($nombre === '') $errores[] = 'El nombre completo es obligatorio.';
        if ($cargo === '')  $errores[] = 'El cargo es obligatorio.';
        if ($zonaId === 0)  $errores[] = 'Debe seleccionar una zona válida.';

        // Email: opcional pero debe tener formato válido si se proporciona
        $email = null;
        if ($emailRaw !== '') {
            if (filter_var($emailRaw, FILTER_VALIDATE_EMAIL) === false) {
                $errores[] = 'El correo electrónico no tiene un formato válido.';
            } else {
                $email = $emailRaw;
            }
        }

        if (!empty($errores)) {
            $_SESSION['flash_error'] = implode(' ', $errores);
            header('Location: /?page=funcionarios');
            exit;
        }

        // ── 4. Defensa en profundidad: verificar que la zona sigue activa ──────
        // Rechaza peticiones forjadas que envíen un zona_id de zona inactiva.
        $zonaRegistro = $this->zonaModel->obtenerPorId($zonaId);
        if ($zonaRegistro === null || $zonaRegistro['estado'] === 'INACTIVO') {
            $ref = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            error_log(
                '[Sistema Alcaldía] [Ref:' . $ref . '] Intento de asignar funcionario a zona inactiva '
                . 'o inexistente. zona_id=' . $zonaId
                . ', usuario_id=' . ($_SESSION['usuario_id'] ?? 'N/A')
            );
            $_SESSION['flash_error'] = 'La zona seleccionada no está disponible para nuevas asignaciones. '
                . 'Recargue la página e intente nuevamente.';
            header('Location: /?page=funcionarios');
            exit;
        }

        // ── 5. Persistencia con manejo de excepciones ──────────────────────────
        try {
            $this->model->registrar($nombre, $cargo, $zonaId, $email);
            $_SESSION['flash_success'] = "Funcionario \"{$nombre}\" registrado exitosamente.";

        } catch (PDOException $e) {
            $ref = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            error_log(
                '[Sistema Alcaldía] [Ref:' . $ref . '] PDOException en FuncionarioController::guardar(): '
                . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine()
            );
            // Violación de FK (zona inexistente): SQLSTATE 23000 / MySQL error 1452
            $_SESSION['flash_error'] = (($e->errorInfo[1] ?? 0) === 1452)
                ? 'La zona seleccionada no existe. Por favor recargue la página e intente nuevamente.'
                : 'No se pudo registrar el funcionario. Comuníquese con el administrador indicando la referencia: ' . $ref;
        }

        // ── 6. PRG: siempre redirige para evitar reenvío del formulario ────────
        header('Location: /?page=funcionarios');
        exit;
    }
}

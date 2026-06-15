<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/FuncionarioModel.php';

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
class FuncionarioController
{
    private FuncionarioModel $model;

    public function __construct()
    {
        // Capa 1: auth guard (defensa en profundidad respecto al guard de index.php)
        if (empty($_SESSION['usuario_id'])) {
            header('Location: /?page=login');
            exit;
        }
        // Capa 2: RBAC — solo ADMINISTRADOR puede gestionar funcionarios
        requiereAdministrador('/?page=dashboard');

        $this->model = new FuncionarioModel();
    }

    /**
     * Renderiza el panel con la tabla de funcionarios registrados
     * y el formulario de alta lateral.
     */
    public function mostrar(): void
    {
        $funcionarios = $this->model->obtenerTodos();
        $total        = count($funcionarios);

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
        $nombre      = trim(filter_input(INPUT_POST, 'nombre',      FILTER_UNSAFE_RAW) ?? '');
        $cargo       = trim(filter_input(INPUT_POST, 'cargo',       FILTER_UNSAFE_RAW) ?? '');
        $dependencia = trim(filter_input(INPUT_POST, 'dependencia', FILTER_UNSAFE_RAW) ?? '');
        $emailRaw    = trim(filter_input(INPUT_POST, 'email',       FILTER_SANITIZE_EMAIL)         ?? '');

        // ── 3. Validación ──────────────────────────────────────────────────────
        $errores = [];
        if ($nombre === '')      $errores[] = 'El nombre completo es obligatorio.';
        if ($cargo === '')       $errores[] = 'El cargo es obligatorio.';
        if ($dependencia === '') $errores[] = 'La dependencia es obligatoria.';

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

        // ── 4. Persistencia con manejo de excepciones ──────────────────────────
        try {
            $this->model->registrar($nombre, $cargo, $dependencia, $email);
            $_SESSION['flash_success'] = "Funcionario \"{$nombre}\" registrado exitosamente.";

        } catch (PDOException $e) {
            $ref = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            error_log(
                '[Sistema Alcaldía] [Ref:' . $ref . '] PDOException en FuncionarioController::guardar(): '
                . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine()
            );
            $_SESSION['flash_error'] = 'No se pudo registrar el funcionario. '
                . 'Comuníquese con el administrador indicando la referencia: ' . $ref;
        }

        // ── 5. PRG: siempre redirige para evitar reenvío del formulario ────────
        header('Location: /?page=funcionarios');
        exit;
    }
}

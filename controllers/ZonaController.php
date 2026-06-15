<?php

declare(strict_types=1);

require_once __DIR__ . '/../models/ZonaModel.php';
require_once __DIR__ . '/../models/SedeModel.php';
require_once __DIR__ . '/../models/FuncionarioModel.php';

/**
 * ZonaController.php
 * Gestiona el panel de administración de zonas físicas.
 *
 * Rutas manejadas:
 *   GET  /?page=zonas → mostrar()  — listado con tabla + formulario de alta
 *   POST /?page=zonas → guardar()  — procesa el registro de la zona (PRG)
 *
 * Ambas rutas exigen rol ADMINISTRADOR (index.php + constructor).
 */
class ZonaController
{
    private ZonaModel        $zonaModel;
    private SedeModel        $sedeModel;
    private FuncionarioModel $funcionarioModel;

    public function __construct()
    {
        // Capa 1: auth guard — defensa en profundidad sobre el guard de index.php
        if (empty($_SESSION['usuario_id'])) {
            header('Location: /?page=login');
            exit;
        }
        // Capa 2: RBAC — solo ADMINISTRADOR gestiona zonas
        requiereAdministrador('/?page=dashboard');

        $this->zonaModel        = new ZonaModel();
        $this->sedeModel        = new SedeModel();
        $this->funcionarioModel = new FuncionarioModel();
    }

    /**
     * Renderiza el panel con la tabla de zonas existentes y el formulario de alta.
     * Inyecta los catálogos de sedes y funcionarios para los selectores dinámicos.
     */
    public function mostrar(): void
    {
        $zonas        = $this->zonaModel->obtenerTodasConEncargado();
        $sedes        = $this->sedeModel->obtenerTodas();
        $funcionarios = $this->funcionarioModel->obtenerTodos();
        $total        = count($zonas);

        require_once __DIR__ . '/../views/zonas.php';
    }

    /**
     * Procesa el formulario POST de registro de zona.
     * Valida CSRF, sanitiza y valida entradas, persiste y redirige (PRG).
     */
    public function guardar(): void
    {
        // ── 1. Verificación CSRF ───────────────────────────────────────────────
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
            $_SESSION['flash_error'] = 'Petición no autorizada o caducada. Intente nuevamente.';
            header('Location: /?page=zonas');
            exit;
        }

        // ── 2. Sanitización ────────────────────────────────────────────────────
        $nombre       = trim(filter_input(INPUT_POST, 'nombre',  FILTER_UNSAFE_RAW) ?? '');
        $sedeIdStr    = trim(filter_input(INPUT_POST, 'sede_id',      FILTER_UNSAFE_RAW) ?? '');
        $encargadoStr = trim(filter_input(INPUT_POST, 'encargado_id', FILTER_UNSAFE_RAW) ?? '');
        $estado       = trim(filter_input(INPUT_POST, 'estado',       FILTER_UNSAFE_RAW) ?? '');

        // IDs: deben ser enteros positivos; encargado es opcional (puede ser vacío → null)
        $sedeId      = (ctype_digit($sedeIdStr)    && (int) $sedeIdStr > 0)    ? (int) $sedeIdStr    : 0;
        $encargadoId = (ctype_digit($encargadoStr) && (int) $encargadoStr > 0) ? (int) $encargadoStr : null;

        // ── 3. Validación ──────────────────────────────────────────────────────
        $estadosValidos = ['SIN INICIAR', 'EN PROCESO', 'EN PAUSA', 'COMPLETADO'];

        $errores = [];
        if ($nombre === '')                                                $errores[] = 'El nombre de la zona es obligatorio.';
        if ($sedeId === 0)                                                 $errores[] = 'Debe seleccionar una sede válida.';
        if (!in_array($estado, $estadosValidos, strict: true))            $errores[] = 'Seleccione un estado de zona válido.';

        if (!empty($errores)) {
            $_SESSION['flash_error'] = implode(' ', $errores);
            header('Location: /?page=zonas');
            exit;
        }

        // ── 4. Persistencia con aislamiento de excepciones ────────────────────
        try {
            $this->zonaModel->registrar($nombre, $sedeId, $encargadoId, $estado);
            $_SESSION['flash_success'] = "Zona \"{$nombre}\" registrada exitosamente.";

        } catch (PDOException $e) {
            $ref = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
            error_log(
                '[Sistema Alcaldía] [Ref:' . $ref . '] PDOException en ZonaController::guardar(): '
                . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine()
            );
            $_SESSION['flash_error'] = 'No se pudo registrar la zona. '
                . 'Comuníquese con el administrador indicando la referencia: ' . $ref;
        }

        // ── 5. PRG: siempre redirige ───────────────────────────────────────────
        header('Location: /?page=zonas');
        exit;
    }
}

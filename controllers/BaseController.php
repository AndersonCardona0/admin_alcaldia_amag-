<?php

declare(strict_types=1);

/**
 * BaseController.php — Segunda capa de defensa de autenticación (failsafe).
 *
 * La primera capa es el Auth Guard de index.php, que intercepta antes del
 * despacho. Este constructor actúa como barrera adicional: si un controlador
 * protegido se instanciara fuera del flujo normal (CLI, include directo, etc.),
 * la sesión sería igualmente validada y el estado residual destruido antes de
 * que se ejecute cualquier lógica de negocio.
 *
 * USO: Todo controlador que requiera sesión activa debe extender esta clase
 * y llamar parent::__construct() como primera instrucción de su propio constructor.
 * AuthController NO debe extender esta clase (sus rutas son públicas).
 */
abstract class BaseController
{
    public function __construct()
    {
        if (empty($_SESSION['usuario_id']) || empty($_SESSION['usuario_rol'])) {
            $this->destruirSesionResidual();
            header('Location: /?page=login');
            exit;
        }
    }

    /**
     * Limpia los datos de usuario preservando el CSRF token.
     * La rotación de ID de sesión ocurre SOLO en procesarLogin() (límite
     * de autenticación real). Llamar session_regenerate_id() aquí provoca
     * que el navegador reciba una cookie nueva mientras el formulario de
     * login ya renderizado tiene el token de la sesión anterior, causando
     * un mismatch de CSRF en cada intento de login subsiguiente.
     */
    protected function destruirSesionResidual(): void
    {
        $csrf     = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
        $_SESSION = ['csrf_token' => $csrf];
    }
}

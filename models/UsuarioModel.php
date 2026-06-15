<?php

declare(strict_types=1);

/**
 * UsuarioModel.php
 * Encapsula todas las operaciones de persistencia sobre la tabla `usuarios`.
 * NUNCA selecciona la columna `password_hash` en listados públicos —
 * solo la expone de forma aislada en obtenerHashPorId() para uso de password_verify().
 */
class UsuarioModel
{
    private PDO $db;

    /** Costo bcrypt compartido con AuthController::HASH_DUMMY (costo 12). */
    private const BCRYPT_COST = 12;

    /** Roles disponibles en el sistema; debe coincidir con RbacGuard. */
    public const ROLES_VALIDOS = ['ADMINISTRADOR', 'AUDITOR'];

    public function __construct()
    {
        $this->db = Conexion::conectar();
    }

    /**
     * Retorna todos los usuarios ordenados alfabéticamente.
     * Excluye deliberadamente `password_hash` del SELECT.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(): array
    {
        $stmt = $this->db->query(
            "SELECT id, usuario, nombre_completo, rol, activo
             FROM   usuarios
             ORDER  BY nombre_completo ASC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Inserta una nueva cuenta con contraseña hasheada (bcrypt, costo 12).
     * La columna `usuario` debe tener restricción UNIQUE en la BD.
     *
     * @throws PDOException Código SQLSTATE 23000 si el nombre de usuario ya existe.
     */
    public function registrar(
        string $usuario,
        string $password,
        string $nombre,
        string $rol
    ): void {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]);

        $stmt = $this->db->prepare(
            "INSERT INTO usuarios (usuario, password_hash, nombre_completo, rol, activo)
             VALUES (:usuario, :password_hash, :nombre_completo, :rol, 1)"
        );
        $stmt->execute([
            ':usuario'         => $usuario,
            ':password_hash'   => $hash,
            ':nombre_completo' => $nombre,
            ':rol'             => $rol,
        ]);
    }

    /**
     * Modifica el estado lógico de una cuenta (activo/inactivo).
     * Baja LÓGICA exclusiva — nunca ejecuta DELETE.
     *
     * @param int $activo 1 = activar  |  0 = desactivar
     * @throws PDOException Si la operación de actualización falla.
     */
    public function modificarEstado(int $id, int $activo): void
    {
        $stmt = $this->db->prepare(
            "UPDATE usuarios SET activo = :activo WHERE id = :id"
        );
        $stmt->execute([':activo' => $activo, ':id' => $id]);
    }

    /**
     * Reemplaza la contraseña del usuario con un nuevo hash bcrypt (costo 12).
     * Llamar ÚNICAMENTE tras verificar la contraseña actual con password_verify().
     *
     * @throws PDOException Si la operación de actualización falla.
     */
    public function actualizarPassword(int $id, string $nuevaPassword): void
    {
        $hash = password_hash($nuevaPassword, PASSWORD_BCRYPT, ['cost' => self::BCRYPT_COST]);

        $stmt = $this->db->prepare(
            "UPDATE usuarios SET password_hash = :hash WHERE id = :id"
        );
        $stmt->execute([':hash' => $hash, ':id' => $id]);
    }

    /**
     * Recupera el hash de contraseña de un usuario por su ID.
     * Usado exclusivamente por AjustesController::cambiarPasswordPropia()
     * para la verificación previa con password_verify().
     * Retorna cadena vacía si el ID no existe (manejo seguro en el controlador).
     */
    public function obtenerHashPorId(int $id): string
    {
        $stmt = $this->db->prepare(
            "SELECT password_hash FROM usuarios WHERE id = :id LIMIT 1"
        );
        $stmt->execute([':id' => $id]);
        return (string) ($stmt->fetchColumn() ?: '');
    }
}

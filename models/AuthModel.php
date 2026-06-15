<?php

/**
 * AuthModel.php
 * Acceso a datos exclusivo del sistema de autenticación.
 * Solo expone lecturas — nunca escribe credenciales en caliente.
 */
class AuthModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::conectar();
    }

    /**
     * Busca un registro de usuario por nombre de usuario (lookup exacto).
     * Retorna array vacío si no existe; nunca lanza excepción por "no encontrado".
     * El controlador usa este método para obtener el hash y validar con password_verify().
     *
     * @return array<string, mixed>
     */
    public function buscarPorUsuario(string $usuario): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, usuario, password_hash, nombre_completo, rol
             FROM   usuarios
             WHERE  usuario = ?
               AND  activo  = 1
             LIMIT  1"
        );
        $stmt->execute([$usuario]);
        return $stmt->fetch() ?: [];
    }
}

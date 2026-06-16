<?php

declare(strict_types=1);

/**
 * FuncionarioModel.php
 * Encapsula las consultas sobre la tabla `funcionarios`.
 * Provee listados para selectores en formularios de equipo
 * y operaciones de alta para el módulo de gestión de funcionarios.
 */
class FuncionarioModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::conectar();
    }

    /**
     * Retorna todos los funcionarios con el nombre de su zona asignada.
     * Usado en la tabla del panel de gestión y en selectores de equipo.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(): array
    {
        $stmt = $this->db->query(
            "SELECT   f.id,
                      f.nombre,
                      f.cargo,
                      f.email,
                      COALESCE(z.nombre, '—') AS zona_nombre
             FROM     funcionarios f
             LEFT JOIN zonas z ON z.id = f.zona_id
             ORDER BY f.nombre ASC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Inserta un nuevo funcionario vinculado a una zona existente.
     * El email es opcional (NULL si se omite).
     *
     * @param int     $zonaId FK obligatoria hacia `zonas.id`.
     * @throws PDOException Si la FK de zona no existe (SQLSTATE 23000) u otro fallo de BD.
     */
    public function registrar(
        string  $nombre,
        string  $cargo,
        int     $zonaId,
        ?string $email = null
    ): void {
        $stmt = $this->db->prepare(
            "INSERT INTO funcionarios (nombre, cargo, zona_id, email)
             VALUES (:nombre, :cargo, :zona_id, :email)"
        );
        $stmt->execute([
            ':nombre'  => $nombre,
            ':cargo'   => $cargo,
            ':zona_id' => $zonaId,
            ':email'   => $email,
        ]);
    }

    /**
     * Cuenta el total de funcionarios registrados.
     * Utilizado por la vista para mostrar el badge del encabezado de tabla.
     */
    public function contarTodos(): int
    {
        return (int) $this->db->query("SELECT COUNT(*) FROM funcionarios")->fetchColumn();
    }
}

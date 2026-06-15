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
     * Retorna todos los funcionarios ordenados alfabéticamente.
     * Usado tanto en los selectores de registrar/editar equipo
     * como en la tabla del panel de gestión de funcionarios.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(): array
    {
        $stmt = $this->db->query(
            "SELECT id, nombre, cargo, dependencia, email
             FROM funcionarios
             ORDER BY nombre ASC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Inserta un nuevo funcionario en la tabla.
     * El email es opcional (NULL si se omite).
     *
     * @throws PDOException Si la inserción falla a nivel de base de datos.
     */
    public function registrar(
        string  $nombre,
        string  $cargo,
        string  $dependencia,
        ?string $email = null
    ): void {
        $stmt = $this->db->prepare(
            "INSERT INTO funcionarios (nombre, cargo, dependencia, email)
             VALUES (:nombre, :cargo, :dependencia, :email)"
        );
        $stmt->execute([
            ':nombre'      => $nombre,
            ':cargo'       => $cargo,
            ':dependencia' => $dependencia,
            ':email'       => $email,
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

<?php

/**
 * FuncionarioModel.php
 * Encapsula las consultas sobre la tabla `funcionarios`.
 * Provee listados para poblar selectores en vistas de formulario.
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
     * Se utiliza para poblar el <select> de responsable en el formulario de registro.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodos(): array
    {
        $stmt = $this->db->query(
            "SELECT id, nombre, cargo, dependencia
             FROM funcionarios
             ORDER BY nombre ASC"
        );
        return $stmt->fetchAll();
    }
}

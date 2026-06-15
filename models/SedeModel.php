<?php

declare(strict_types=1);

/**
 * SedeModel.php
 * Encapsula las consultas sobre la tabla `sedes`.
 * Provee el catálogo de sedes para los selectores del módulo de Zonas.
 */
class SedeModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::conectar();
    }

    /**
     * Retorna todas las sedes ordenadas alfabéticamente.
     * Usado para poblar el <select> de sede en el formulario de Zonas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodas(): array
    {
        $stmt = $this->db->query(
            "SELECT id, nombre FROM sedes ORDER BY nombre ASC"
        );
        return $stmt->fetchAll();
    }
}

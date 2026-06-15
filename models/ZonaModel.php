<?php

/**
 * ZonaModel.php
 * Encapsula las consultas sobre la tabla `zonas` con sus relaciones
 * hacia `sedes` y `funcionarios` (encargado asignado).
 */
class ZonaModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::conectar();
    }

    /**
     * Devuelve todas las zonas con el nombre de su sede y el nombre del
     * funcionario encargado. Si una zona no tiene encargado asignado,
     * el campo `encargado_nombre` retorna 'Sin asignar'.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodasConEncargado(): array
    {
        $sql = "
            SELECT
                z.id,
                z.nombre                              AS zona_nombre,
                z.estado,
                s.nombre                              AS sede_nombre,
                COALESCE(f.nombre, 'Sin asignar')     AS encargado_nombre,
                f.cargo                               AS encargado_cargo
            FROM zonas z
            LEFT JOIN sedes        s ON z.sede_id      = s.id
            LEFT JOIN funcionarios f ON z.encargado_id = f.id
            ORDER BY s.nombre ASC, z.nombre ASC
        ";

        // Sin parámetros variables; query() es suficiente y más eficiente aquí
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}

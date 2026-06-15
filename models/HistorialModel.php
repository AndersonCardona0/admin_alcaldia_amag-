<?php

/**
 * HistorialModel.php
 * Encapsula las consultas de solo lectura sobre `historial_cambios`.
 * Ningún método de este modelo modifica datos; es estrictamente de extracción.
 */
class HistorialModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::conectar();
    }

    /**
     * Retorna todos los eventos de auditoría asociados a un equipo,
     * ordenados del más reciente al más antiguo (DESC por fecha).
     *
     * @param int $equipoId ID del equipo a consultar.
     * @return array<int, array<string, mixed>>
     */
    public function obtenerHistorialPorEquipo(int $equipoId): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, equipo_id, fecha, accion, detalle, usuario
             FROM   historial_cambios
             WHERE  equipo_id = ?
             ORDER BY fecha DESC"
        );
        $stmt->execute([$equipoId]);
        return $stmt->fetchAll();
    }
}

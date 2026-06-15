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

    /**
     * Retorna el registro de auditoría más reciente correspondiente a la acción
     * de baja ('BAJA') para el equipo indicado.
     * Devuelve array vacío si no existe ningún evento de baja registrado.
     *
     * @return array<string, mixed>
     */
    public function obtenerUltimaBaja(int $equipoId): array
    {
        $stmt = $this->db->prepare(
            "SELECT detalle, fecha, usuario
             FROM   historial_cambios
             WHERE  equipo_id = ? AND accion = 'BAJA'
             ORDER BY fecha DESC
             LIMIT 1"
        );
        $stmt->execute([$equipoId]);
        return $stmt->fetch() ?: [];
    }
}

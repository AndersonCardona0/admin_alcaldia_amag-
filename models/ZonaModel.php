<?php

declare(strict_types=1);

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
     * Devuelve todas las zonas con nombre de sede y encargado.
     * Usado por: DashboardController, EquipoController, ReporteController,
     * ZonaController. No romper firma ni nombre — tiene múltiples callers.
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

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Inserta una nueva zona en la tabla.
     * El encargado es opcional (null si no se selecciona).
     * La sede es obligatoria (restricción de FK garantizada en el controlador).
     *
     * @throws PDOException Si la inserción falla a nivel de base de datos.
     */
    public function registrar(
        string $nombre,
        int    $sedeId,
        ?int   $encargadoId,
        string $estado
    ): void {
        $stmt = $this->db->prepare(
            "INSERT INTO zonas (nombre, sede_id, encargado_id, estado)
             VALUES (:nombre, :sede_id, :encargado_id, :estado)"
        );
        $stmt->execute([
            ':nombre'       => $nombre,
            ':sede_id'      => $sedeId,
            ':encargado_id' => $encargadoId, // PDO inserta NULL correctamente
            ':estado'       => $estado,
        ]);
    }
}

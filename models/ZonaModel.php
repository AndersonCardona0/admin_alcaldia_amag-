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
     * Retorna id y nombre de todas las zonas, ordenadas alfabéticamente.
     * Incluye zonas INACTIVAS. Usar únicamente en paneles de administración
     * donde el administrador necesita visibilidad completa.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerTodas(): array
    {
        $stmt = $this->db->query(
            "SELECT id, nombre FROM zonas ORDER BY nombre ASC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Retorna id y nombre exclusivamente de las zonas operativas
     * (cualquier estado distinto de 'INACTIVO').
     * Usar en todo selector de zona expuesto al usuario final para
     * evitar asignaciones a áreas ya desactivadas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function obtenerActivas(): array
    {
        $stmt = $this->db->query(
            "SELECT id, nombre FROM zonas WHERE estado <> 'INACTIVO' ORDER BY nombre ASC"
        );
        return $stmt->fetchAll();
    }

    /**
     * Recupera una zona por su ID para pre-poblar el formulario de edición.
     * Retorna null si el ID no existe — el controlador redirige con flash de error.
     *
     * @return array<string, mixed>|null
     */
    public function obtenerPorId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nombre, sede_id, encargado_id, estado
             FROM   zonas
             WHERE  id = :id
             LIMIT  1"
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Actualiza los datos editables de una zona existente.
     * El encargado es opcional; se acepta null para desasignar.
     *
     * @throws PDOException Si la actualización falla (FK inválida, etc.).
     */
    public function actualizar(
        int    $id,
        string $nombre,
        int    $sedeId,
        ?int   $encargadoId,
        string $estado
    ): void {
        $stmt = $this->db->prepare(
            "UPDATE zonas
             SET    nombre       = :nombre,
                    sede_id      = :sede_id,
                    encargado_id = :encargado_id,
                    estado       = :estado
             WHERE  id = :id"
        );
        $stmt->execute([
            ':nombre'       => $nombre,
            ':sede_id'      => $sedeId,
            ':encargado_id' => $encargadoId,
            ':estado'       => $estado,
            ':id'           => $id,
        ]);
    }

    /**
     * Baja lógica de la zona: establece estado = 'INACTIVO'.
     * DELETE sobre esta tabla está terminantemente prohibido.
     *
     * @throws PDOException Si la operación de actualización falla.
     */
    public function desactivar(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE zonas SET estado = 'INACTIVO' WHERE id = :id"
        );
        $stmt->execute([':id' => $id]);
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

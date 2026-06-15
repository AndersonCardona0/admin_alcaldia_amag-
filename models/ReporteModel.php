<?php

/**
 * ReporteModel.php
 * Encapsula las consultas estadísticas y de listado completo para el módulo
 * de reportes. Provee resúmenes cuantitativos y datasets sin paginación
 * para la generación de PDF y CSV.
 */
class ReporteModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::conectar();
    }

    /**
     * Devuelve el conteo de equipos agrupado por estado, aplicando los mismos
     * filtros opcionales que obtenerListadoCompleto() para garantizar consistencia
     * entre los KPI visualizados en el panel y los datos que se exportarán.
     *
     * Sin filtros → estadísticas globales del inventario completo.
     * Con filtros → estadísticas del subconjunto que coincide con la selección activa.
     *
     * @param string $estado Valor exacto del ENUM; vacío = todos los estados.
     * @param string $zonaId ID numérico de zona; vacío = todas las zonas.
     * @return array{total: int, operativos: int, en_mantenimiento: int, de_baja: int}
     */
    public function resumenPorEstado(string $estado = '', string $zonaId = ''): array
    {
        $sql = "
            SELECT
                COUNT(*)                                     AS total,
                SUM(e.estado = 'OPERATIVO')                  AS operativos,
                SUM(e.estado = 'EN MANTENIMIENTO')           AS en_mantenimiento,
                SUM(e.estado = 'DE BAJA')                    AS de_baja
            FROM equipos e
            LEFT JOIN zonas z ON e.zona_id = z.id
            WHERE 1=1
        ";

        $params = [];

        if ($estado !== '') {
            $sql .= " AND e.estado = :estado";
            $params[':estado'] = $estado;
        }

        if ($zonaId !== '') {
            $sql .= " AND e.zona_id = :zona_id";
            $params[':zona_id'] = (int) $zonaId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $fila = $stmt->fetch();

        return [
            'total'            => (int) ($fila['total']            ?? 0),
            'operativos'       => (int) ($fila['operativos']       ?? 0),
            'en_mantenimiento' => (int) ($fila['en_mantenimiento'] ?? 0),
            'de_baja'          => (int) ($fila['de_baja']          ?? 0),
        ];
    }

    /**
     * Conteo de equipos agrupado por zona, con desglose por estado.
     * Solo incluye zonas que tengan al menos un equipo asignado (INNER JOIN).
     * Ordenado por total descendente para mostrar primero las zonas más densas.
     *
     * @return array<int, array<string, mixed>>
     */
    public function resumenPorZona(): array
    {
        $stmt = $this->db->query("
            SELECT
                z.id,
                z.nombre                             AS zona_nombre,
                s.nombre                             AS sede_nombre,
                COUNT(e.id)                          AS total,
                SUM(e.estado = 'OPERATIVO')          AS operativos,
                SUM(e.estado = 'EN MANTENIMIENTO')   AS en_mantenimiento,
                SUM(e.estado = 'DE BAJA')            AS de_baja
            FROM equipos e
            INNER JOIN zonas z ON e.zona_id = z.id
            LEFT  JOIN sedes s ON z.sede_id  = s.id
            GROUP BY z.id, z.nombre, s.nombre
            ORDER BY total DESC, z.nombre ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Listado completo de activos sin paginación, con filtros opcionales.
     * Diseñado para exportación masiva: retorna todas las filas coincidentes
     * en una sola consulta y las carga en memoria (adecuado para volúmenes
     * municipales de hasta varios miles de registros).
     *
     * @param string $estado  Valor exacto del ENUM; vacío = todos los estados.
     * @param string $zonaId  ID numérico de zona; vacío = todas las zonas.
     * @param string $sedeId  ID numérico de sede; vacío = todas las sedes.
     * @return array<int, array<string, mixed>>
     */
    public function obtenerListadoCompleto(
        string $estado = '',
        string $zonaId = '',
        string $sedeId = ''
    ): array {
        $sql = "
            SELECT
                e.id,
                e.tipo,
                e.marca,
                e.modelo,
                e.numero_serie,
                e.estado,
                e.fecha_registro,
                z.nombre                              AS zona_nombre,
                s.id                                  AS sede_id,
                s.nombre                              AS sede_nombre,
                COALESCE(f.nombre, 'Sin asignar')     AS funcionario_nombre
            FROM equipos e
            LEFT JOIN zonas        z ON e.zona_id       = z.id
            LEFT JOIN sedes        s ON z.sede_id        = s.id
            LEFT JOIN funcionarios f ON e.funcionario_id = f.id
            WHERE 1=1
        ";

        $params = [];

        if ($estado !== '') {
            $sql .= " AND e.estado = :estado";
            $params[':estado'] = $estado;
        }

        if ($zonaId !== '') {
            $sql .= " AND e.zona_id = :zona_id";
            $params[':zona_id'] = (int) $zonaId;
        }

        if ($sedeId !== '') {
            $sql .= " AND s.id = :sede_id";
            $params[':sede_id'] = (int) $sedeId;
        }

        $sql .= " ORDER BY e.estado ASC, z.nombre ASC, e.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}

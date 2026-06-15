<?php

/**
 * EquipoModel.php
 * Encapsula las consultas estadísticas sobre la tabla `equipos`.
 * Toda interacción SQL ocurre aquí; las vistas nunca tocan la BD directamente.
 */
class EquipoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Conexion::conectar();
    }

    /** Cuenta el total de registros en la tabla, sin filtro de estado. */
    public function totalEquipos(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM equipos");
        return (int) $stmt->fetchColumn();
    }

    /** Cuenta equipos cuyo estado es exactamente 'OPERATIVO'. */
    public function totalOperativos(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM equipos WHERE estado = ?");
        $stmt->execute(['OPERATIVO']);
        return (int) $stmt->fetchColumn();
    }

    /** Cuenta equipos cuyo estado es exactamente 'EN MANTENIMIENTO'. */
    public function totalEnMantenimiento(): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM equipos WHERE estado = ?");
        $stmt->execute(['EN MANTENIMIENTO']);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Retorna equipos con sus datos relacionales, aplicando filtros opcionales.
     * La consulta se construye dinámicamente pero NUNCA concatena valores crudos:
     * cada condición agrega un marcador nombrado que se enlaza via PDO::execute().
     *
     * @param string $busqueda Término libre buscado en tipo, marca, modelo, serie y funcionario.
     * @param string $estado   Valor exacto del ENUM ('OPERATIVO', 'EN MANTENIMIENTO', 'DE BAJA').
     * @param string $zonaId   ID numérico de la zona a filtrar.
     * @return array<int, array<string, mixed>>
     */
    public function obtenerEquiposFiltrados(
        string $busqueda = '',
        string $estado   = '',
        string $zonaId   = ''
    ): array {
        // Base de la consulta: LEFT JOINs para reunir toda la información en una sola pasada
        $sql = "
            SELECT
                e.id,
                e.tipo,
                e.marca,
                e.modelo,
                e.numero_serie,
                e.estado,
                e.fecha_registro,
                z.id               AS zona_id,
                z.nombre           AS zona_nombre,
                s.nombre           AS sede_nombre,
                COALESCE(f.nombre, 'Sin asignar') AS funcionario_nombre,
                f.cargo            AS funcionario_cargo
            FROM equipos e
            LEFT JOIN zonas        z ON e.zona_id       = z.id
            LEFT JOIN sedes        s ON z.sede_id        = s.id
            LEFT JOIN funcionarios f ON e.funcionario_id = f.id
            WHERE 1=1
        ";

        $params = [];

        // Búsqueda libre: se generan marcadores únicos por columna para evitar
        // conflictos con drivers PDO que no permiten reutilizar el mismo nombre
        if ($busqueda !== '') {
            $termino = '%' . $busqueda . '%';
            $sql .= " AND (
                e.tipo         LIKE :b_tipo
                OR e.marca     LIKE :b_marca
                OR e.modelo    LIKE :b_modelo
                OR e.numero_serie LIKE :b_serie
                OR f.nombre    LIKE :b_func
            )";
            $params[':b_tipo']   = $termino;
            $params[':b_marca']  = $termino;
            $params[':b_modelo'] = $termino;
            $params[':b_serie']  = $termino;
            $params[':b_func']   = $termino;
        }

        // Filtro exacto por estado del ENUM
        if ($estado !== '') {
            $sql .= " AND e.estado = :estado";
            $params[':estado'] = $estado;
        }

        // Filtro exacto por zona (ya validado como numérico en el controlador)
        if ($zonaId !== '') {
            $sql .= " AND e.zona_id = :zona_id";
            $params[':zona_id'] = (int) $zonaId;
        }

        $sql .= " ORDER BY e.fecha_registro DESC, e.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Devuelve un arreglo asociativo único con todos los datos del equipo,
     * sus especificaciones técnicas y sus relaciones (zona, sede, funcionario).
     * Retorna array vacío si el ID no existe, sin lanzar excepción.
     *
     * @return array<string, mixed>
     */
    public function obtenerEquipoCompletoPorId(int $id): array
    {
        $stmt = $this->db->prepare("
            SELECT
                e.id,
                e.zona_id,
                e.funcionario_id,
                e.tipo,
                e.marca,
                e.modelo,
                e.numero_serie,
                e.estado,
                e.fecha_registro,
                z.nombre           AS zona_nombre,
                s.nombre           AS sede_nombre,
                COALESCE(f.nombre, 'Sin asignar') AS funcionario_nombre,
                f.cargo            AS funcionario_cargo,
                esp.id             AS especificaciones_id,
                esp.procesador,
                esp.ram,
                esp.almacenamiento
            FROM equipos e
            LEFT JOIN zonas           z   ON e.zona_id       = z.id
            LEFT JOIN sedes           s   ON z.sede_id        = s.id
            LEFT JOIN funcionarios    f   ON e.funcionario_id = f.id
            LEFT JOIN especificaciones esp ON e.id            = esp.equipo_id
            WHERE e.id = ?
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: [];
    }

    /**
     * Actualiza un equipo y sus especificaciones en una transacción atómica,
     * registrando el cambio en la tabla de auditoría `historial_cambios`.
     * Usa UPSERT (INSERT … ON DUPLICATE KEY UPDATE) para especificaciones,
     * cubriendo el caso de equipos que aún no tengan fila en esa tabla.
     *
     * @param int                 $id                    ID del equipo a modificar.
     * @param array<string,mixed> $datosEquipo           Parámetros nombrados para UPDATE equipos.
     * @param array<string,mixed> $datosEspecificaciones Parámetros para UPSERT especificaciones.
     * @param array<string,mixed> $datosAuditoria        Parámetros para INSERT historial_cambios.
     * @throws PDOException Relanza tras el rollback para que el controlador la gestione.
     */
    public function actualizarEquipoCompleto(
        int   $id,
        array $datosEquipo,
        array $datosEspecificaciones,
        array $datosAuditoria
    ): void {
        try {
            $this->db->beginTransaction();

            // ── 1. Actualización de datos base del activo ──────────────────────
            $stmt1 = $this->db->prepare("
                UPDATE equipos SET
                    zona_id        = :zona_id,
                    funcionario_id = :funcionario_id,
                    tipo           = :tipo,
                    marca          = :marca,
                    modelo         = :modelo,
                    numero_serie   = :numero_serie,
                    estado         = :estado
                WHERE id = :id
            ");
            $stmt1->execute($datosEquipo);

            // ── 2. UPSERT de especificaciones (cubre la relación 1:1) ──────────
            // ON DUPLICATE KEY UPDATE garantiza que si ya existe el registro se
            // actualiza; si no existe (equipo legado sin specs), se inserta.
            $stmt2 = $this->db->prepare("
                INSERT INTO especificaciones (equipo_id, procesador, ram, almacenamiento)
                VALUES (:equipo_id, :procesador, :ram, :almacenamiento)
                ON DUPLICATE KEY UPDATE
                    procesador     = VALUES(procesador),
                    ram            = VALUES(ram),
                    almacenamiento = VALUES(almacenamiento)
            ");
            $stmt2->execute($datosEspecificaciones);

            // ── 3. Registro de auditoría en historial_cambios ─────────────────
            // `fecha` se omite: la columna tiene DEFAULT CURRENT_TIMESTAMP
            $stmt3 = $this->db->prepare("
                INSERT INTO historial_cambios (equipo_id, accion, detalle, usuario)
                VALUES (:equipo_id, :accion, :detalle, :usuario)
            ");
            $stmt3->execute($datosAuditoria);

            $this->db->commit();

        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Comprueba si un número de serie ya existe en la tabla.
     * Se llama ANTES de intentar la inserción para evitar que la restricción
     * UNIQUE de MySQL genere una excepción no controlada.
     */
    public function existeNumeroSerie(string $serie): bool
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM equipos WHERE numero_serie = ?"
        );
        $stmt->execute([$serie]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Registra un equipo y sus especificaciones técnicas en una transacción
     * atómica: si cualquiera de las dos inserciones falla, ambas se revierten.
     *
     * @param array<string,mixed> $datosEquipo         Parámetros nombrados para `equipos`.
     * @param array<string,mixed> $datosEspecificaciones Parámetros para `especificaciones` (sin equipo_id).
     * @return int ID autoincremental del equipo recién insertado.
     * @throws PDOException Relanza la excepción tras el rollback para que el controlador la capture.
     */
    public function registrarEquipoCompleto(
        array $datosEquipo,
        array $datosEspecificaciones
    ): int {
        try {
            $this->db->beginTransaction();

            // ── Primera inserción: registro base del activo ────────────────────
            $stmt1 = $this->db->prepare("
                INSERT INTO equipos
                    (zona_id, funcionario_id, tipo, marca, modelo, numero_serie, estado)
                VALUES
                    (:zona_id, :funcionario_id, :tipo, :marca, :modelo, :numero_serie, :estado)
            ");
            $stmt1->execute($datosEquipo);
            $equipoId = (int) $this->db->lastInsertId();

            // ── Segunda inserción: especificaciones técnicas vinculadas ─────────
            $datosEspecificaciones[':equipo_id'] = $equipoId;
            $stmt2 = $this->db->prepare("
                INSERT INTO especificaciones
                    (equipo_id, procesador, ram, almacenamiento)
                VALUES
                    (:equipo_id, :procesador, :ram, :almacenamiento)
            ");
            $stmt2->execute($datosEspecificaciones);

            $this->db->commit();
            return $equipoId;

        } catch (PDOException $e) {
            // Revierte ambas inserciones si cualquiera falla
            $this->db->rollBack();
            throw $e;
        }
    }
}

<?php

/**
 * EquipoController.php
 * Gestiona las peticiones relacionadas con el módulo de Inventario.
 * Responsabilidad única: sanitizar entradas, delegar al modelo y proveer
 * las variables necesarias a la vista. Sin lógica SQL ni salidas HTML.
 */
class EquipoController
{
    private EquipoModel $equipoModel;
    private ZonaModel   $zonaModel;

    public function __construct()
    {
        $this->equipoModel = new EquipoModel();
        $this->zonaModel   = new ZonaModel();
    }

    /**
     * Renderiza la pantalla de Inventario Detallado.
     * Captura los parámetros GET, los valida, ejecuta las consultas filtradas
     * y pasa las variables a la vista correspondiente.
     */
    public function inventario(): void
    {
        // ── Captura y sanitización de parámetros GET ──────────────────────────
        $busqueda = trim(
            filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? ''
        );

        $estado = trim(
            filter_input(INPUT_GET, 'estado', FILTER_SANITIZE_SPECIAL_CHARS) ?? ''
        );

        $zonaId = trim(
            filter_input(INPUT_GET, 'zona_id', FILTER_SANITIZE_SPECIAL_CHARS) ?? ''
        );

        // ── Validación de whitelist para estado (previene valores fuera del ENUM) ─
        $estadosPermitidos = ['OPERATIVO', 'EN MANTENIMIENTO', 'DE BAJA'];
        if ($estado !== '' && !in_array($estado, $estadosPermitidos, strict: true)) {
            $estado = '';
        }

        // ── Validación de zona_id: debe ser estrictamente numérico ─────────────
        if ($zonaId !== '' && !ctype_digit($zonaId)) {
            $zonaId = '';
        }

        // ── Consultas al modelo ────────────────────────────────────────────────
        $equipos = $this->equipoModel->obtenerEquiposFiltrados($busqueda, $estado, $zonaId);

        // Lista completa de zonas para poblar el <select> de filtros en la vista
        $zonas = $this->zonaModel->obtenerTodasConEncargado();

        // Agrupa los valores activos para que la vista pueda repoblar el formulario
        $filtros = [
            'search'  => $busqueda,
            'estado'  => $estado,
            'zona_id' => $zonaId,
        ];

        // ── Delegación a la vista ──────────────────────────────────────────────
        // Las variables $equipos, $zonas y $filtros quedan en el scope del include
        require_once __DIR__ . '/../views/inventario.php';
    }

    /**
     * Muestra el perfil de solo lectura de un equipo: datos cruzados + timeline de auditoría.
     * GET /?page=detalle&id=X — no acepta POST; cualquier intento lo ignora.
     */
    public function ver(): void
    {
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$id || $id <= 0) {
            $_SESSION['flash_error'] = 'ID de equipo no válido.';
            header('Location: /?page=inventario');
            exit;
        }

        // Reutiliza el método de la Fase 5 para obtener todos los datos cruzados
        $equipo = $this->equipoModel->obtenerEquipoCompletoPorId($id);

        if (empty($equipo)) {
            $_SESSION['flash_error'] = 'El equipo solicitado no existe en el sistema.';
            header('Location: /?page=inventario');
            exit;
        }

        // Historial de auditoría ordenado del más reciente al más antiguo
        $historialModel = new HistorialModel();
        $historial      = $historialModel->obtenerHistorialPorEquipo($id);

        require_once __DIR__ . '/../views/detalle.php';
    }

    /**
     * Genera y fuerza la descarga del "Acta de Asignación de Equipo Tecnológico" en PDF.
     * Solo acepta GET con un ?id= válido. No produce ninguna salida HTML.
     *
     * Librería: FPDF 1.9 (fuentes Helvetica/Arial en ISO-8859-1 via font/*.json).
     * mb_convert_encoding() convierte cada campo UTF-8 → ISO-8859-1 antes de
     * pasarlo a FPDF, garantizando tildes y eñes sin corrupción binaria.
     */
    public function exportarPdf(): void
    {
        // ── Captura total de output desde el primer instante ───────────────────
        // Garantiza que cualquier warning/notice de display_errors, BOM o
        // whitespace no llegue al navegador antes que los headers del PDF,
        // independientemente del valor de output_buffering en php.ini.
        ob_start();

        // ── Validación de ID ───────────────────────────────────────────────────
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

        if (!$id || $id <= 0) {
            $_SESSION['flash_error'] = 'ID de equipo no válido para generar el acta.';
            header('Location: /?page=inventario');
            exit;
        }

        $equipo = $this->equipoModel->obtenerEquipoCompletoPorId($id);

        if (empty($equipo)) {
            $_SESSION['flash_error'] = 'El equipo solicitado no existe; no se puede generar el acta.';
            header('Location: /?page=inventario');
            exit;
        }

        // ── Incluir FPDF ───────────────────────────────────────────────────────
        require_once __DIR__ . '/../libs/fpdf/fpdf.php';

        // ── Helpers de codificación y valores ─────────────────────────────────
        // $t  → convierte UTF-8 a ISO-8859-1 vía mb_convert_encoding, que es más
        //       portable que iconv('UTF-8','ISO-8859-1//TRANSLIT') en Windows:
        //       //TRANSLIT puede devolver false en algunas builds, dejando el PDF
        //       con todo el texto vacío. mb_string siempre está disponible.
        // $v  → garantiza que un campo nullable muestre '-' en lugar de quedar vacío.
        $t = fn(mixed $s): string =>
            mb_convert_encoding((string) ($s ?? ''), 'ISO-8859-1', 'UTF-8');
        $v = fn(mixed $s): string =>
            trim((string) ($s ?? '')) !== '' ? trim((string) ($s ?? '')) : '-';

        // ── Traducción de meses al español (date() devuelve inglés) ───────────
        static $meses = [
            'January'   => 'enero',   'February' => 'febrero', 'March'    => 'marzo',
            'April'     => 'abril',   'May'      => 'mayo',    'June'     => 'junio',
            'July'      => 'julio',   'August'   => 'agosto',  'September'=> 'septiembre',
            'October'   => 'octubre', 'November' => 'noviembre','December' => 'diciembre',
        ];
        $fechaActual = strtr(date('d \d\e F \d\e Y'), $meses);

        // ── Instanciar FPDF · paleta gris institucional ───────────────────────
        // Clase anónima para sobreescribir Footer() sin contaminar el namespace.
        // Todos los colores del documento siguen el esquema monocromático:
        //   Texto principal   → SetTextColor(34, 34, 34)   antracita
        //   Texto secundario  → SetTextColor(75, 85, 99)   gris medio
        //   Líneas / bordes   → SetDrawColor(209, 213, 221) gris claro
        //   Fondos de sección → SetFillColor(243, 244, 246) gris muy suave
        $pdf = new class('P', 'mm', 'A4') extends FPDF {
            public string $fechaPie = '';

            public function Footer(): void
            {
                $this->SetY(-12);
                $this->SetDrawColor(209, 213, 221);
                $this->SetLineWidth(0.3);
                $this->Line(20, $this->GetY(), 190, $this->GetY());
                $this->Ln(1.5);
                $this->SetFont('Arial', 'I', 7);
                $this->SetTextColor(150, 150, 150);
                $texto = mb_convert_encoding(
                    'Alcaldía Amagá, Antioquia - ' . $this->fechaPie,
                    'ISO-8859-1', 'UTF-8'
                );
                $this->Cell(0, 4, $texto, 0, 0, 'C');
            }
        };

        $pdf->fechaPie = $fechaActual;
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(true, 25);
        $pdf->AddPage();

        // ════════════════════════════════════════════════════════════════════════
        // BLOQUE 1 · CABECERA INSTITUCIONAL
        // ════════════════════════════════════════════════════════════════════════

        // Nombre de la entidad
        $pdf->SetFont('Arial', 'B', 13);
        $pdf->SetTextColor(34, 34, 34);
        $pdf->Cell(0, 7, $t('ALCALDÍA MUNICIPAL'), 0, 1, 'C');

        // Unidad responsable
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(75, 85, 99);
        $pdf->Cell(0, 5, $t('Unidad de Gestión Tecnológica e Informática'), 0, 1, 'C');

        // Separador fino bajo nombre de entidad
        $pdf->SetDrawColor(209, 213, 221);
        $pdf->SetLineWidth(0.4);
        $pdf->Line(20, $pdf->GetY() + 3, 190, $pdf->GetY() + 3);
        $pdf->Ln(9);

        // Título principal del acta
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->SetTextColor(34, 34, 34);
        $pdf->Cell(0, 8, $t('ACTA DE ASIGNACIÓN DE EQUIPO TECNOLÓGICO'), 0, 1, 'C');

        // Metadatos en una sola línea: fecha + ID activo
        $pdf->SetFont('Arial', '', 8.5);
        $pdf->SetTextColor(75, 85, 99);
        $pdf->Cell(0, 5, $t("Fecha de generación: {$fechaActual}     ·     Activo N.º #{$id}"), 0, 1, 'C');

        // Separador más grueso que cierra el bloque de cabecera
        $pdf->SetDrawColor(34, 34, 34);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(20, $pdf->GetY() + 3, 190, $pdf->GetY() + 3);
        $pdf->Ln(9);

        // ── Helper: barra de título de sección ───────────────────────────────
        // Fondo gris muy suave + borde inferior gris claro + texto antracita.
        // Sin uso de ningún color primario; paleta estrictamente monocromática.
        $seccion = function(string $titulo) use ($pdf, $t): void {
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(243, 244, 246);
            $pdf->SetTextColor(34, 34, 34);
            $pdf->SetDrawColor(209, 213, 221);
            $pdf->SetLineWidth(0.3);
            $pdf->Cell(0, 7, $t('  ' . $titulo), 'B', 1, 'L', true);
            $pdf->Ln(2);
        };

        // ── Helper: fila key-value con línea inferior sutil ───────────────────
        // Etiqueta (negrita, gris medio, $w mm) | Valor (regular, antracita).
        // El borde inferior gris claro traza la cuadrícula sin bordes verticales.
        $fila = function(string $etiqueta, string $valor, int $w = 55) use ($pdf, $t): void {
            $pdf->SetDrawColor(209, 213, 221);
            $pdf->SetLineWidth(0.2);
            $pdf->SetFont('Arial', 'B', 8.5);
            $pdf->SetTextColor(75, 85, 99);
            $pdf->Cell($w, 6, $t($etiqueta . ':'), 'B', 0, 'L');
            $pdf->SetFont('Arial', '', 8.5);
            $pdf->SetTextColor(34, 34, 34);
            $pdf->Cell(0, 6, $t($valor), 'B', 1, 'L');
        };

        // ════════════════════════════════════════════════════════════════════════
        // BLOQUE 2 · DATOS DEL FUNCIONARIO RESPONSABLE
        // ════════════════════════════════════════════════════════════════════════
        $seccion('Datos del Funcionario Responsable');

        $fila('Nombre completo',    $v($equipo['funcionario_nombre']));
        $fila('Cargo',              $v($equipo['funcionario_cargo']));
        $fila('Dependencia / Zona', $v($equipo['zona_nombre']));
        $fila('Sede',               $v($equipo['sede_nombre']));
        $pdf->Ln(5);

        // ════════════════════════════════════════════════════════════════════════
        // BLOQUE 3 · ESPECIFICACIONES DEL BIEN TECNOLÓGICO ASIGNADO
        // ════════════════════════════════════════════════════════════════════════
        $seccion('Especificaciones del Bien Tecnológico Asignado');

        $fila('Tipo de equipo',    $v($equipo['tipo']));
        $fila('Marca',             $v($equipo['marca']));
        $fila('Modelo',            $v($equipo['modelo']));
        $fila('Número de serie',   $v($equipo['numero_serie']));
        $fila('Procesador',        $v($equipo['procesador']));
        $fila('Memoria RAM',       $v($equipo['ram']));
        $fila('Almacenamiento',    $v($equipo['almacenamiento']));
        $fila('Estado del activo', $v($equipo['estado']));
        $pdf->Ln(5);

        // ════════════════════════════════════════════════════════════════════════
        // BLOQUE 4 · CLÁUSULA DE RESPONSABILIDAD
        // Para modificar el texto legal editar únicamente $clausula.
        // ════════════════════════════════════════════════════════════════════════
        $seccion('Cláusula de Responsabilidad');

        $nombreFunc = $v($equipo['funcionario_nombre']);
        $clausula =
            "Yo, {$nombreFunc}, funcionario(a) de la entidad identificado(a) en el presente "
          . "documento, declaro haber recibido a plena satisfacción el bien tecnológico descrito "
          . "en este instrumento. Me comprometo formalmente a: (i) utilizar el equipo "
          . "exclusivamente para el cumplimiento de las funciones propias de mi cargo; "
          . "(ii) mantenerlo en óptimo estado de conservación y funcionamiento; "
          . "(iii) reportar de manera inmediata cualquier daño, pérdida o sustracción a la "
          . "Unidad de Gestión Tecnológica e Informática. El incumplimiento de las obligaciones "
          . "aquí contraídas dará lugar a las responsabilidades disciplinarias, civiles y penales "
          . "que correspondan conforme a la normativa vigente aplicable.";

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(34, 34, 34);
        $pdf->MultiCell(0, 5.5, $t($clausula), 0, 'J');

        // ════════════════════════════════════════════════════════════════════════
        // BLOQUE 5 · FIRMAS DE CONFORMIDAD
        // Ln(25) provee espacio físico cómodo para la firma manuscrita.
        // Las líneas se anclan a $yFirma para que un salto de página automático
        // no desplace el bloque de firmas al centro de la hoja.
        // ════════════════════════════════════════════════════════════════════════
        $pdf->Ln(25);
        $yFirma = $pdf->GetY();

        $pdf->SetDrawColor(34, 34, 34);
        $pdf->SetLineWidth(0.3);

        // Ancho y posición de cada columna de firma
        $xIzq = 25;
        $xDer = 115;
        $wCol = 70;

        // Líneas horizontales de firma
        $pdf->Line($xIzq, $yFirma, $xIzq + $wCol, $yFirma);
        $pdf->Line($xDer, $yFirma, $xDer + $wCol, $yFirma);

        // — Columna izquierda: funcionario responsable —
        $pdf->SetXY($xIzq, $yFirma + 2);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(34, 34, 34);
        $pdf->Cell($wCol, 5, $t($v($equipo['funcionario_nombre'])), 0, 0, 'C');

        $pdf->SetXY($xIzq, $yFirma + 7);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(75, 85, 99);
        $pdf->Cell($wCol, 4, $t($v($equipo['funcionario_cargo'])), 0, 0, 'C');

        $pdf->SetXY($xIzq, $yFirma + 12);
        $pdf->SetFont('Arial', 'I', 7.5);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell($wCol, 4, $t('Firma del Funcionario Responsable'), 0, 0, 'C');

        // — Columna derecha: líder de sistemas —
        $pdf->SetXY($xDer, $yFirma + 2);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(34, 34, 34);
        $pdf->Cell($wCol, 5, $t('Unidad de Gestión Tecnológica'), 0, 0, 'C');

        $pdf->SetXY($xDer, $yFirma + 7);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetTextColor(75, 85, 99);
        $pdf->Cell($wCol, 4, $t('e Informática'), 0, 0, 'C');

        $pdf->SetXY($xDer, $yFirma + 12);
        $pdf->SetFont('Arial', 'I', 7.5);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell($wCol, 4, $t('Firma del Líder de Sistemas'), 0, 0, 'C');

        $pdf->SetTextColor(0, 0, 0);

        // ── Nombre del archivo: serie sanitizada o ID como fallback ───────────
        $serie   = trim((string) ($equipo['numero_serie'] ?? ''));
        $sufijo  = $serie !== ''
            ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $serie)
            : "ID_{$id}";
        $archivo = "Acta_Asignacion_{$sufijo}.pdf";

        // ── Vaciar TODOS los niveles de buffer antes de enviar el PDF ────────
        // ob_end_clean() solo cierra un nivel; con buffering anidado el nivel
        // exterior seguiría teniendo contenido y FPDF lanzaría error. El while
        // garantiza que headers_sent() sea false y ob_get_length() sea 0/false
        // sin importar cuántos niveles abrió PHP internamente.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $pdf->Output('D', $archivo);
        exit;
    }

    /**
     * Gestiona la pantalla y el procesamiento del formulario de registro.
     *
     * GET  → carga listas de zonas y funcionarios, renderiza el formulario vacío.
     * POST → valida los campos, verifica unicidad del número de serie y, si todo
     *        es correcto, ejecuta la transacción atómica. En caso de éxito aplica
     *        el patrón PRG (Post-Redirect-Get) hacia el inventario con un mensaje
     *        flash de sesión. Si falla la validación, re-renderiza el formulario
     *        con los valores anteriores y los mensajes de error.
     */
    public function registrar(): void
    {
        // FuncionarioModel solo se instancia en este flujo, no en inventario()
        $funcionarioModel = new FuncionarioModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarRegistro($funcionarioModel);
        } else {
            $this->mostrarFormulario($funcionarioModel);
        }
    }

    /**
     * Gestiona la pantalla y el procesamiento del formulario de edición.
     *
     * GET  → valida que el ID exista, carga el equipo con sus datos relacionales
     *        y renderiza el formulario pre-poblado.
     * POST → sanitiza, valida, detecta cambios para la auditoría y ejecuta la
     *        transacción de actualización. Redirige al inventario con mensaje flash.
     */
    public function editar(): void
    {
        $funcionarioModel = new FuncionarioModel();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->procesarEdicion($funcionarioModel);
        } else {
            $this->mostrarFormularioEdicion($funcionarioModel);
        }
    }

    // ── Métodos privados de apoyo ─────────────────────────────────────────────

    /** Renderiza el formulario de registro con los selectores cargados desde la BD. */
    private function mostrarFormulario(
        FuncionarioModel $funcionarioModel,
        array $errores = [],
        array $datos   = []
    ): void {
        $zonas        = $this->zonaModel->obtenerTodasConEncargado();
        $funcionarios = $funcionarioModel->obtenerTodos();
        require_once __DIR__ . '/../views/registrar.php';
    }

    /** Valida, sanitiza y persiste los datos del formulario POST. */
    private function procesarRegistro(FuncionarioModel $funcionarioModel): void
    {
        // ── Captura y sanitización de todos los campos POST ───────────────────
        $datos = [
            'tipo'           => trim(filter_input(INPUT_POST, 'tipo',           FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
            'marca'          => trim(filter_input(INPUT_POST, 'marca',          FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
            'modelo'         => trim(filter_input(INPUT_POST, 'modelo',         FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
            'numero_serie'   => trim(filter_input(INPUT_POST, 'numero_serie',   FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
            'zona_id'        => trim(filter_input(INPUT_POST, 'zona_id',        FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
            'funcionario_id' => trim(filter_input(INPUT_POST, 'funcionario_id', FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
            'estado'         => trim(filter_input(INPUT_POST, 'estado',         FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
            'procesador'     => trim(filter_input(INPUT_POST, 'procesador',     FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
            'ram'            => trim(filter_input(INPUT_POST, 'ram',            FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
            'almacenamiento' => trim(filter_input(INPUT_POST, 'almacenamiento', FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
        ];

        // ── Validaciones de campo obligatorio y whitelist ─────────────────────
        $errores = [];

        $tiposValidos = ['Desktop', 'Laptop','All in One', 'Servidor', 'Impresora', 'Switch', 'Otro'];
        if (empty($datos['tipo']) || !in_array($datos['tipo'], $tiposValidos, strict: true)) {
            $errores[] = 'Seleccione un tipo de equipo válido.';
        }

        if (empty($datos['marca'])) {
            $errores[] = 'La marca del equipo es obligatoria.';
        }

        if (empty($datos['modelo'])) {
            $errores[] = 'El modelo del equipo es obligatorio.';
        }

        if (empty($datos['zona_id']) || !ctype_digit($datos['zona_id'])) {
            $errores[] = 'Debe seleccionar una zona válida.';
        }

        // Funcionario es opcional; si viene, debe ser numérico
        if ($datos['funcionario_id'] !== '' && !ctype_digit($datos['funcionario_id'])) {
            $datos['funcionario_id'] = '';
        }

        $estadosValidos = ['OPERATIVO', 'EN MANTENIMIENTO', 'DE BAJA'];
        if (empty($datos['estado']) || !in_array($datos['estado'], $estadosValidos, strict: true)) {
            $errores[] = 'Seleccione un estado válido para el equipo.';
        }

        // Verificación de unicidad del número de serie antes de intentar insertar
        if ($datos['numero_serie'] !== '' && $this->equipoModel->existeNumeroSerie($datos['numero_serie'])) {
            $errores[] = 'El número de serie "' . htmlspecialchars($datos['numero_serie'], ENT_QUOTES, 'UTF-8')
                       . '" ya se encuentra registrado en el sistema.';
        }

        // Si hay errores, re-renderiza el formulario conservando los valores ingresados
        if (!empty($errores)) {
            $this->mostrarFormulario($funcionarioModel, $errores, $datos);
            return;
        }

        // ── Preparación de parámetros para el modelo ──────────────────────────
        $datosEquipo = [
            ':zona_id'        => (int) $datos['zona_id'],
            ':funcionario_id' => $datos['funcionario_id'] !== '' ? (int) $datos['funcionario_id'] : null,
            ':tipo'           => $datos['tipo'],
            ':marca'          => $datos['marca'],
            ':modelo'         => $datos['modelo'],
            ':numero_serie'   => $datos['numero_serie'] !== '' ? $datos['numero_serie'] : null,
            ':estado'         => $datos['estado'],
        ];

        $datosEspecificaciones = [
            ':procesador'     => $datos['procesador']     !== '' ? $datos['procesador']     : null,
            ':ram'            => $datos['ram']            !== '' ? $datos['ram']            : null,
            ':almacenamiento' => $datos['almacenamiento'] !== '' ? $datos['almacenamiento'] : null,
        ];

        // ── Ejecución de la transacción atómica ───────────────────────────────
        try {
            $equipoId = $this->equipoModel->registrarEquipoCompleto($datosEquipo, $datosEspecificaciones);

            // Mensaje flash de éxito: sobrevive a la redirección via sesión
            $_SESSION['flash_success'] = "Equipo registrado exitosamente (ID #{$equipoId}).";
            header('Location: /?page=inventario');
            exit;

        } catch (PDOException $e) {
            // Error inesperado de BD: muestra al usuario sin exponer detalles técnicos
            $errores[] = 'Ocurrió un error al guardar el equipo. Intente nuevamente o contacte al administrador.';
            $this->mostrarFormulario($funcionarioModel, $errores, $datos);
        }
    }

    /**
     * GET handler para edición: valida el ID, carga el equipo y renderiza el form.
     * Si el equipo no existe, redirige al inventario con mensaje de error.
     */
    private function mostrarFormularioEdicion(
        FuncionarioModel $funcionarioModel,
        array $equipoOverride = [],
        array $errores        = []
    ): void {
        // Obtiene el ID desde GET (primera carga) o desde el array override (re-render en error)
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT)
           ?? (int) ($equipoOverride['id'] ?? 0);

        if (!$id || $id <= 0) {
            $_SESSION['flash_error'] = 'ID de equipo no válido.';
            header('Location: /?page=inventario');
            exit;
        }

        $equipo = $this->equipoModel->obtenerEquipoCompletoPorId($id);

        if (empty($equipo)) {
            $_SESSION['flash_error'] = 'El equipo solicitado no existe en el sistema.';
            header('Location: /?page=inventario');
            exit;
        }

        // Superpone los valores enviados por POST sobre los de la BD para
        // repoblar el formulario con la entrada del usuario tras un error
        if (!empty($equipoOverride)) {
            foreach (['tipo','marca','modelo','numero_serie','zona_id',
                      'funcionario_id','estado','procesador','ram','almacenamiento'] as $campo) {
                if (array_key_exists($campo, $equipoOverride)) {
                    $equipo[$campo] = $equipoOverride[$campo];
                }
            }
        }

        $zonas        = $this->zonaModel->obtenerTodasConEncargado();
        $funcionarios = $funcionarioModel->obtenerTodos();
        require_once __DIR__ . '/../views/editar.php';
    }

    /**
     * POST handler para edición: sanitiza, valida, detecta cambios para la
     * auditoría y ejecuta la transacción de actualización.
     */
    private function procesarEdicion(FuncionarioModel $funcionarioModel): void
    {
        // El ID viaja en el form como campo oculto
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if (!$id || $id <= 0) {
            $_SESSION['flash_error'] = 'ID de equipo no válido.';
            header('Location: /?page=inventario');
            exit;
        }

        // Carga los datos originales para la comparación de cambios y la
        // validación del número de serie (evitar falso duplicado sobre sí mismo)
        $equipoOriginal = $this->equipoModel->obtenerEquipoCompletoPorId($id);
        if (empty($equipoOriginal)) {
            $_SESSION['flash_error'] = 'El equipo solicitado no existe en el sistema.';
            header('Location: /?page=inventario');
            exit;
        }

        // ── Captura y sanitización de campos POST ─────────────────────────────
        $datos = [
            'tipo'           => trim(filter_input(INPUT_POST, 'tipo',           FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
            'marca'          => trim(filter_input(INPUT_POST, 'marca',          FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
            'modelo'         => trim(filter_input(INPUT_POST, 'modelo',         FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
            'numero_serie'   => trim(filter_input(INPUT_POST, 'numero_serie',   FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
            'zona_id'        => trim(filter_input(INPUT_POST, 'zona_id',        FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
            'funcionario_id' => trim(filter_input(INPUT_POST, 'funcionario_id', FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
            'estado'         => trim(filter_input(INPUT_POST, 'estado',         FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
            'procesador'     => trim(filter_input(INPUT_POST, 'procesador',     FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
            'ram'            => trim(filter_input(INPUT_POST, 'ram',            FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
            'almacenamiento' => trim(filter_input(INPUT_POST, 'almacenamiento', FILTER_SANITIZE_SPECIAL_CHARS) ?? ''),
        ];

        // ── Validaciones ──────────────────────────────────────────────────────
        $errores = [];

        $tiposValidos = ['Desktop', 'Laptop', 'All in One', 'Servidor', 'Impresora', 'Switch', 'Otro'];
        if (empty($datos['tipo']) || !in_array($datos['tipo'], $tiposValidos, strict: true)) {
            $errores[] = 'Seleccione un tipo de equipo válido.';
        }

        if (empty($datos['marca'])) {
            $errores[] = 'La marca del equipo es obligatoria.';
        }

        if (empty($datos['modelo'])) {
            $errores[] = 'El modelo del equipo es obligatorio.';
        }

        if (empty($datos['zona_id']) || !ctype_digit($datos['zona_id'])) {
            $errores[] = 'Debe seleccionar una zona válida.';
        }

        if ($datos['funcionario_id'] !== '' && !ctype_digit($datos['funcionario_id'])) {
            $datos['funcionario_id'] = '';
        }

        $estadosValidos = ['OPERATIVO', 'EN MANTENIMIENTO', 'DE BAJA'];
        if (empty($datos['estado']) || !in_array($datos['estado'], $estadosValidos, strict: true)) {
            $errores[] = 'Seleccione un estado válido para el equipo.';
        }

        // Unicidad del número de serie: solo valida si cambió respecto al original
        $serieOriginal = $equipoOriginal['numero_serie'] ?? '';
        if ($datos['numero_serie'] !== '' && $datos['numero_serie'] !== $serieOriginal) {
            if ($this->equipoModel->existeNumeroSerie($datos['numero_serie'])) {
                $errores[] = 'El número de serie "' . htmlspecialchars($datos['numero_serie'], ENT_QUOTES, 'UTF-8')
                           . '" ya está en uso por otro equipo.';
            }
        }

        if (!empty($errores)) {
            // Incluye el ID en el override para que mostrarFormularioEdicion lo encuentre
            $datos['id'] = $id;
            $this->mostrarFormularioEdicion($funcionarioModel, $datos, $errores);
            return;
        }

        // ── Detección granular de cambios para auditoría ──────────────────────
        //
        // $norm  → equipara null y '' (BD vs POST) para evitar falsos positivos
        //          al comparar campos opcionales que el usuario dejó en blanco.
        // $label → devuelve el valor o el centinela '(Sin registro)' si está vacío,
        //          garantizando que el auditor siempre vea texto autoexplicativo.
        $norm  = fn(mixed $v): string => trim((string) ($v ?? ''));
        $label = fn(string $v): string => $v !== '' ? $v : '(Sin registro)';

        $cambiosDetectados = [];

        // Estado operativo
        if ($datos['estado'] !== $norm($equipoOriginal['estado'])) {
            $cambiosDetectados[] = "Estado de '{$label($norm($equipoOriginal['estado']))}'"
                                 . " a '{$label($datos['estado'])}'";
        }

        // Tipo de equipo
        if ($datos['tipo'] !== $norm($equipoOriginal['tipo'])) {
            $cambiosDetectados[] = "Tipo de '{$label($norm($equipoOriginal['tipo']))}'"
                                 . " a '{$label($datos['tipo'])}'";
        }

        // Zona de asignación — incluye nombre anterior para legibilidad inmediata
        if ($datos['zona_id'] !== $norm($equipoOriginal['zona_id'])) {
            $zonaNombreAnterior = $label($norm($equipoOriginal['zona_nombre']));
            $cambiosDetectados[] = "Zona de '{$zonaNombreAnterior}' (ID {$norm($equipoOriginal['zona_id'])})"
                                 . " a Zona ID {$datos['zona_id']}";
        }

        // Responsable — incluye nombre anterior; refleja asignación o remoción
        if ($datos['funcionario_id'] !== $norm($equipoOriginal['funcionario_id'])) {
            $funcNombreAnterior = $label($norm($equipoOriginal['funcionario_nombre']));
            $nuevoFunc = $datos['funcionario_id'] !== '' ? "ID {$datos['funcionario_id']}" : '(Sin asignar)';
            $cambiosDetectados[] = "Responsable de '{$funcNombreAnterior}' a '{$nuevoFunc}'";
        }

        // Número de serie
        if ($datos['numero_serie'] !== $norm($serieOriginal)) {
            $cambiosDetectados[] = "N° Serie de '{$label($norm($serieOriginal))}'"
                                 . " a '{$label($datos['numero_serie'])}'";
        }

        // ── Especificaciones de hardware ──────────────────────────────────────
        if ($datos['procesador'] !== $norm($equipoOriginal['procesador'])) {
            $cambiosDetectados[] = "Procesador de '{$label($norm($equipoOriginal['procesador']))}'"
                                 . " a '{$label($datos['procesador'])}'";
        }

        if ($datos['ram'] !== $norm($equipoOriginal['ram'])) {
            $cambiosDetectados[] = "RAM de '{$label($norm($equipoOriginal['ram']))}'"
                                 . " a '{$label($datos['ram'])}'";
        }

        if ($datos['almacenamiento'] !== $norm($equipoOriginal['almacenamiento'])) {
            $cambiosDetectados[] = "Almacenamiento de '{$label($norm($equipoOriginal['almacenamiento']))}'"
                                 . " a '{$label($datos['almacenamiento'])}'";
        }
        // ── Fin detección granular ────────────────────────────────────────────

        $detalle = !empty($cambiosDetectados)
            ? implode(', ', $cambiosDetectados)
            : 'Edición de datos menores (sin cambios en campos críticos)';

        // ── Preparación de parámetros PDO ─────────────────────────────────────
        $datosEquipo = [
            ':id'            => $id,
            ':zona_id'       => (int) $datos['zona_id'],
            ':funcionario_id'=> $datos['funcionario_id'] !== '' ? (int) $datos['funcionario_id'] : null,
            ':tipo'          => $datos['tipo'],
            ':marca'         => $datos['marca'],
            ':modelo'        => $datos['modelo'],
            ':numero_serie'  => $datos['numero_serie'] !== '' ? $datos['numero_serie'] : null,
            ':estado'        => $datos['estado'],
        ];

        $datosEspecificaciones = [
            ':equipo_id'     => $id,
            ':procesador'    => $datos['procesador']     !== '' ? $datos['procesador']     : null,
            ':ram'           => $datos['ram']            !== '' ? $datos['ram']            : null,
            ':almacenamiento'=> $datos['almacenamiento'] !== '' ? $datos['almacenamiento'] : null,
        ];

        $datosAuditoria = [
            ':equipo_id' => $id,
            ':accion'    => 'ACTUALIZACIÓN',
            ':detalle'   => $detalle,
            ':usuario'   => 'Auditor Sistema',
        ];

        // ── Ejecución de la transacción atómica ───────────────────────────────
        try {
            $this->equipoModel->actualizarEquipoCompleto(
                $id,
                $datosEquipo,
                $datosEspecificaciones,
                $datosAuditoria
            );
            $_SESSION['flash_success'] = "Equipo #{$id} actualizado correctamente.";
            header('Location: /?page=inventario');
            exit;

        } catch (PDOException $e) {
            $errores[] = 'Error al actualizar el equipo. Intente nuevamente o contacte al administrador.';
            $datos['id'] = $id;
            $this->mostrarFormularioEdicion($funcionarioModel, $datos, $errores);
        }
    }
}

<?php

require_once __DIR__ . '/../models/ReporteModel.php';
require_once __DIR__ . '/../models/ZonaModel.php';

/**
 * ReporteController.php
 * Gestiona el panel de reportes estadísticos y la generación de exportaciones.
 *
 * Rutas manejadas:
 *   GET /?page=reportes              → mostrar()       — panel con métricas
 *   GET /?page=exportar_reporte_pdf  → exportarPdf()   — descarga PDF landscape
 *   GET /?page=exportar_reporte_csv  → exportarCsv()   — descarga CSV UTF-8 con BOM
 *
 * Las tres rutas exigen sesión activa (verificada en index.php antes del dispatch).
 */
class ReporteController
{
    private ReporteModel $reporteModel;
    private ZonaModel    $zonaModel;

    public function __construct()
    {
        // Defensa en profundidad: verifica sesión activa incluso si el controlador
        // se instancia fuera del flujo de index.php (que ya aplica el auth guard).
        if (empty($_SESSION['usuario_id'])) {
            header('Location: /?page=login');
            exit;
        }
        $this->reporteModel = new ReporteModel();
        $this->zonaModel    = new ZonaModel();
    }

    /**
     * Renderiza el panel de reportes: métricas por estado, distribución
     * por zona y controles de exportación con filtros aplicables.
     */
    public function mostrar(): void
    {
        // $filtros debe capturarse ANTES de $resumen para que los KPI reflejen
        // exactamente el mismo universo de datos que se exportará.
        $filtros     = $this->capturarFiltros();
        $resumen     = $this->reporteModel->resumenPorEstado($filtros['estado'], $filtros['zona_id']);
        $zonaResumen = $this->reporteModel->resumenPorZona();
        $zonas       = $this->zonaModel->obtenerTodasConEncargado();

        require_once __DIR__ . '/../views/reportes.php';
    }

    /**
     * Genera y fuerza la descarga del reporte completo en PDF horizontal (landscape).
     * Encabezado institucional dinámico + pie de página con paginación {nb}.
     * Columnas: ID · Tipo · Marca · Modelo · N.° Serie · Zona · Funcionario · Estado
     */
    public function exportarPdf(): void
    {
        ob_start();

        $filtros = $this->capturarFiltros();
        $equipos = $this->reporteModel->obtenerListadoCompleto(
            $filtros['estado'],
            $filtros['zona_id']
        );

        require_once __DIR__ . '/../libs/fpdf/fpdf.php';

        // ── Helpers de codificación y valor ──────────────────────────────────
        $t = fn(mixed $s): string =>
            mb_convert_encoding((string) ($s ?? ''), 'ISO-8859-1', 'UTF-8');

        $v = fn(mixed $s): string =>
            trim((string) ($s ?? '')) !== '' ? trim((string) ($s ?? '')) : '-';

        // ── Fecha en español ──────────────────────────────────────────────────
        static $meses = [
            'January'  => 'enero',    'February' => 'febrero',  'March'    => 'marzo',
            'April'    => 'abril',    'May'      => 'mayo',     'June'     => 'junio',
            'July'     => 'julio',    'August'   => 'agosto',   'September'=> 'septiembre',
            'October'  => 'octubre',  'November' => 'noviembre','December' => 'diciembre',
        ];
        $fechaActual  = strtr(date('d \d\e F \d\e Y'), $meses);
        $filtroLabel  = $this->construirEtiquetaFiltros($filtros['estado'], $filtros['zona_id']);
        $subtituloStr = 'Alcaldía Municipal de Amagá, Antioquia - ' . $fechaActual
                      . ($filtroLabel !== '' ? '  ·  Filtros: ' . $filtroLabel : '');

        // ── Instancia FPDF con encabezado y pie dinámicos ─────────────────────
        $pdf = new class('L', 'mm', 'A4') extends FPDF {
            public string $tituloDoc   = '';
            public string $subtituloDoc = '';
            public string $fechaPie    = '';

            public function Header(): void
            {
                $lm = 10;
                $rm = 10;
                $this->SetFont('Arial', 'B', 11);
                $this->SetTextColor(30, 30, 30);
                $this->Cell(0, 7, $this->tituloDoc, 0, 1, 'C');

                $this->SetFont('Arial', '', 8);
                $this->SetTextColor(100, 100, 100);
                $this->Cell(0, 5, $this->subtituloDoc, 0, 1, 'C');

                $this->SetDrawColor(190, 190, 190);
                $this->SetLineWidth(0.4);
                $this->Line($lm, $this->GetY() + 2, $this->GetPageWidth() - $rm, $this->GetY() + 2);
                $this->Ln(6);
            }

            public function Footer(): void
            {
                $this->SetY(-13);
                $lm = 10;
                $rm = 10;
                $uw = $this->GetPageWidth() - $lm - $rm; // ancho útil dinámico
                $this->SetDrawColor(190, 190, 190);
                $this->SetLineWidth(0.3);
                $this->Line($lm, $this->GetY(), $this->GetPageWidth() - $rm, $this->GetY());
                $this->Ln(1.5);
                $this->SetFont('Arial', 'I', 7);
                $this->SetTextColor(140, 140, 140);
                $izq = mb_convert_encoding(
                    'Alcaldía Municipal de Amagá, Antioquia — ' . $this->fechaPie,
                    'ISO-8859-1', 'UTF-8'
                );
                $der = mb_convert_encoding('Página ' . $this->PageNo() . ' de {nb}', 'ISO-8859-1', 'UTF-8');
                $izqAncho = (int) round($uw * 0.55); // ~60% para la leyenda institucional
                $this->Cell($izqAncho, 4, $izq, 0, 0, 'L');
                $this->Cell($uw - $izqAncho, 4, $der, 0, 0, 'R'); // resto exacto
            }
        };

        $pdf->AliasNbPages();
        $pdf->tituloDoc    = $t('REPORTE DE INVENTARIO TECNOLÓGICO');
        $pdf->subtituloDoc = $t($subtituloStr);
        $pdf->fechaPie     = $t($fechaActual);
        // Margins: left=10, top=22 (header ~16mm + padding), right=10
        $pdf->SetMargins(10, 22, 10);
        $pdf->SetAutoPageBreak(true, 16);
        $pdf->AddPage();

        // ── Anchos de columna derivados dinámicamente del ancho real de la página ─
        // Proporciones base (enteros cuya suma = 277, valor de referencia para A4).
        // Se escalan al anchoUtil real: factor=1.0 para A4 landscape −20mm, sin
        // redondeo acumulado porque $anchoTotal se toma de $anchoUtil directamente.
        $margenLateral = 10;
        $anchoUtil = $pdf->GetPageWidth() - ($margenLateral * 2);

        $proporcionBase = [
            'ID'          => 15,
            'Tipo'        => 25,
            'Marca'       => 28,
            'Modelo'      => 38,
            'N. Serie'    => 42,
            'Zona'        => 42,
            'Funcionario' => 57,
            'Estado'      => 30,
        ];
        $sumaBase = array_sum($proporcionBase); // 277
        $escala   = $anchoUtil / $sumaBase;
        $cols     = array_map(fn(int $w): float => round($w * $escala, 2), $proporcionBase);
        $anchoTotal = $anchoUtil; // exacto: evita acumulación de errores de redondeo

        // ── Fila de encabezados de columna ────────────────────────────────────
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(235, 235, 235);
        $pdf->SetTextColor(30, 30, 30);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.2);

        foreach ($cols as $etiqueta => $ancho) {
            $pdf->Cell($ancho, 7, $t($etiqueta), 1, 0, 'C', true);
        }
        $pdf->Ln();

        // ── Filas de datos con bandas alternas ────────────────────────────────
        $pdf->SetFont('Arial', '', 7.5);
        $pdf->SetTextColor(30, 30, 30);

        if (empty($equipos)) {
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->SetTextColor(120, 120, 120);
            $pdf->Cell($anchoTotal, 8, $t('No se encontraron registros con los filtros aplicados.'), 1, 1, 'C');
        } else {
            $alterno = false;
            foreach ($equipos as $fila) {
                $r = $alterno ? 248 : 255;
                $pdf->SetFillColor($r, $r, $r);

                $pdf->Cell($cols['ID'],          7, $t((string) ($fila['id'] ?? '')),                'LR', 0, 'C', $alterno);
                $pdf->Cell($cols['Tipo'],        7, $t($v($fila['tipo'])),                           'LR', 0, 'L', $alterno);
                $pdf->Cell($cols['Marca'],       7, $t($v($fila['marca'])),                          'LR', 0, 'L', $alterno);
                $pdf->Cell($cols['Modelo'],      7, $t($v($fila['modelo'])),                         'LR', 0, 'L', $alterno);
                $pdf->Cell($cols['N. Serie'],    7, $t($v($fila['numero_serie'])),                   'LR', 0, 'L', $alterno);
                $pdf->Cell($cols['Zona'],        7, $t($v($fila['zona_nombre'])),                    'LR', 0, 'L', $alterno);
                $pdf->Cell($cols['Funcionario'], 7, $t($v($fila['funcionario_nombre'])),             'LR', 0, 'L', $alterno);
                $pdf->Cell($cols['Estado'],      7, $t($v($fila['estado'])),                        'LR', 0, 'C', $alterno);
                $pdf->Ln();
                $alterno = !$alterno;
            }
        }

        // ── Línea de cierre inferior de la tabla ──────────────────────────────
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->SetLineWidth(0.3);
        $pdf->Cell($anchoTotal, 0, '', 'T');
        $pdf->Ln(4);

        // ── Nota de total de registros ─────────────────────────────────────────
        $pdf->SetFont('Arial', 'I', 7.5);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(0, 5, $t('Total de registros: ' . count($equipos)), 0, 0, 'R');

        // ── Vaciar buffers y despachar el PDF ─────────────────────────────────
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $pdf->Output('D', 'Reporte_Inventario_' . date('Ymd_His') . '.pdf');
        exit;
    }

    /**
     * Genera y fuerza la descarga del listado completo como CSV con BOM UTF-8.
     * El BOM (\xEF\xBB\xBF) garantiza que Microsoft Excel en Windows interprete
     * correctamente tildes y eñes sin configuración adicional del usuario.
     * Los datos se escriben línea a línea vía php://output (streaming nativo).
     */
    public function exportarCsv(): void
    {
        // ob_start() captura cualquier aviso PHP (display_errors=1) producido durante
        // la consulta, impidiendo que contamine la respuesta antes de los headers.
        // Simétrico al ob_start() que exportarPdf() ya aplica en su primera línea.
        ob_start();

        $filtros = $this->capturarFiltros();
        $equipos = $this->reporteModel->obtenerListadoCompleto(
            $filtros['estado'],
            $filtros['zona_id']
        );

        // Vaciar buffers acumulados antes de enviar headers HTTP
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $nombreArchivo = 'Reporte_Inventario_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: max-age=0, no-cache, no-store');
        header('Pragma: no-cache');

        $salida = fopen('php://output', 'w');

        // BOM UTF-8 — obligatorio para compatibilidad con Excel en Windows
        fwrite($salida, "\xEF\xBB\xBF");

        // Fila de encabezados
        fputcsv($salida, [
            'ID',
            'Tipo',
            'Marca',
            'Modelo',
            'N° Serie',
            'Zona',
            'Sede',
            'Funcionario',
            'Estado',
            'Fecha Registro',
        ], separator: ',', enclosure: '"', escape: '');

        // Filas de datos — escritura directa al stream, sin acumular en memoria
        foreach ($equipos as $fila) {
            fputcsv($salida, [
                $fila['id']                    ?? '',
                $fila['tipo']                  ?? '',
                $fila['marca']                 ?? '',
                $fila['modelo']                ?? '',
                $fila['numero_serie']          ?? '',
                $fila['zona_nombre']           ?? '',
                $fila['sede_nombre']           ?? '',
                $fila['funcionario_nombre']    ?? '',
                $fila['estado']                ?? '',
                $fila['fecha_registro']        ?? '',
            ], separator: ',', enclosure: '"', escape: '');
        }

        fclose($salida);
        exit;
    }

    // ── Helpers privados ──────────────────────────────────────────────────────

    /**
     * Captura, sanitiza y valida los parámetros GET de filtro.
     * Retorna un array con las claves 'estado' y 'zona_id' siempre presentes.
     *
     * @return array{estado: string, zona_id: string}
     */
    private function capturarFiltros(): array
    {
        $estado  = trim(filter_input(INPUT_GET, 'estado',  FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
        $zonaId  = trim(filter_input(INPUT_GET, 'zona_id', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

        $estadosPermitidos = ['OPERATIVO', 'EN MANTENIMIENTO', 'DE BAJA'];
        if ($estado !== '' && !in_array($estado, $estadosPermitidos, strict: true)) {
            $estado = '';
        }

        if ($zonaId !== '' && !ctype_digit($zonaId)) {
            $zonaId = '';
        }

        return ['estado' => $estado, 'zona_id' => $zonaId];
    }

    /**
     * Construye la cadena legible de filtros activos para el subtítulo del PDF.
     */
    private function construirEtiquetaFiltros(string $estado, string $zonaId): string
    {
        $partes = [];
        if ($estado !== '') {
            $partes[] = 'Estado: ' . $estado;
        }
        if ($zonaId !== '') {
            $partes[] = 'Zona ID: ' . $zonaId;
        }
        return implode(' · ', $partes);
    }
}

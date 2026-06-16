<?php
/**
 * reportes.php — Vista del módulo de Reportes y Exportaciones.
 * Recibe del ReporteController::mostrar():
 *   $resumen     → array{total, operativos, en_mantenimiento, de_baja}
 *   $zonaResumen → array de zonas con conteo por estado
 *   $zonas       → array de zonas para el selector de filtro
 *   $filtros     → array{estado, zona_id} con los filtros activos
 */

// Alias de escape corto
$e = fn(mixed $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

// URLs de descarga con los filtros activos preservados
$exportBase = array_filter([
    'estado'  => $filtros['estado'],
    'zona_id' => $filtros['zona_id'],
], fn(string $v): bool => $v !== '');

$urlPdf = $e('/?' . http_build_query(array_merge(['page' => 'exportar_reporte_pdf'], $exportBase)));
$urlCsv = $e('/?' . http_build_query(array_merge(['page' => 'exportar_reporte_csv'], $exportBase)));

$hayFiltros = $filtros['estado'] !== '' || $filtros['zona_id'] !== '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reportes y Exportaciones — Alcaldía Municipal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex h-screen bg-slate-100 overflow-hidden font-sans antialiased">

    <!-- ── Sidebar ──────────────────────────────────────────────────────────── -->
    <?php include __DIR__ . '/modules/sidebar.php'; ?>

    <!-- ── Columna principal ────────────────────────────────────────────────── -->
    <div class="flex-1 min-w-0 overflow-y-auto">

        <main class="p-6">

            <!-- ── Encabezado de sección ─────────────────────────────────────── -->
            <div class="mb-6">
                <h1 class="text-xl font-bold text-slate-800">Reportes y Exportaciones</h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    Métricas consolidadas del parque tecnológico y descarga masiva de activos
                </p>
            </div>

            <!-- ── Mensaje flash de error ────────────────────────────────────── -->
            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5 flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-red-700 font-medium text-sm">
                        <?= $e($_SESSION['flash_error']) ?>
                    </p>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

            <!-- ══════════════════════════════════════════════════════════════════
                 SECCIÓN 1 — Tarjetas de métricas por estado
            ════════════════════════════════════════════════════════════════════ -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">

                <!-- Total inventario -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
                    <div class="w-11 h-11 bg-slate-100 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-2xl font-extrabold text-slate-800 leading-none">
                            <?= $e((string) $resumen['total']) ?>
                        </p>
                        <p class="text-xs text-slate-500 mt-1 font-medium">Total activos</p>
                    </div>
                </div>

                <!-- Operativos -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
                    <div class="w-11 h-11 bg-emerald-50 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-2xl font-extrabold text-emerald-600 leading-none">
                            <?= $e((string) $resumen['operativos']) ?>
                        </p>
                        <p class="text-xs text-slate-500 mt-1 font-medium">Operativos</p>
                    </div>
                </div>

                <!-- En mantenimiento -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
                    <div class="w-11 h-11 bg-amber-50 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-2xl font-extrabold text-amber-500 leading-none">
                            <?= $e((string) $resumen['en_mantenimiento']) ?>
                        </p>
                        <p class="text-xs text-slate-500 mt-1 font-medium">En mantenimiento</p>
                    </div>
                </div>

                <!-- De baja -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 flex items-center gap-4">
                    <div class="w-11 h-11 bg-red-50 rounded-xl flex items-center justify-center flex-shrink-0">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-2xl font-extrabold text-red-500 leading-none">
                            <?= $e((string) $resumen['de_baja']) ?>
                        </p>
                        <p class="text-xs text-slate-500 mt-1 font-medium">De baja</p>
                    </div>
                </div>

            </div><!-- /grid métricas -->

            <!-- ══════════════════════════════════════════════════════════════════
                 SECCIÓN 2 — Distribución por zona
            ════════════════════════════════════════════════════════════════════ -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm mb-6">

                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-700">Distribución por Zona</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Desglose de activos por área asignada</p>
                    </div>
                    <span class="text-xs font-semibold text-slate-500 bg-slate-100 px-2.5 py-1 rounded-full">
                        <?= $e((string) count($zonaResumen)) ?> zonas
                    </span>
                </div>

                <?php if (empty($zonaResumen)): ?>
                    <div class="p-10 text-center text-slate-400 text-sm">
                        No hay equipos registrados en ninguna zona.
                    </div>
                <?php else: ?>
                    <div class="overflow-x-auto max-h-64 overflow-y-auto">
                        <table class="w-full text-sm">
                            <thead class="sticky top-0 bg-slate-50 z-10">
                                <tr class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    <th class="px-5 py-3 border-b border-slate-200">Zona</th>
                                    <th class="px-4 py-3 border-b border-slate-200">Sede</th>
                                    <th class="px-4 py-3 border-b border-slate-200 text-right">Total</th>
                                    <th class="px-4 py-3 border-b border-slate-200 text-right text-emerald-600">Operativos</th>
                                    <th class="px-4 py-3 border-b border-slate-200 text-right text-amber-500">Mant.</th>
                                    <th class="px-4 py-3 border-b border-slate-200 text-right text-red-500">Baja</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($zonaResumen as $zona): ?>
                                    <tr class="hover:bg-slate-50 transition-colors duration-100">
                                        <td class="px-5 py-3 font-medium text-slate-700">
                                            <?= $e($zona['zona_nombre']) ?>
                                        </td>
                                        <td class="px-4 py-3 text-slate-500">
                                            <?= $e($zona['sede_nombre'] ?? '—') ?>
                                        </td>
                                        <td class="px-4 py-3 text-right font-bold text-slate-800">
                                            <?= $e((string) $zona['total']) ?>
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold text-emerald-600">
                                            <?= $e((string) ($zona['operativos'] ?? 0)) ?>
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold text-amber-500">
                                            <?= $e((string) ($zona['en_mantenimiento'] ?? 0)) ?>
                                        </td>
                                        <td class="px-4 py-3 text-right font-semibold text-red-500">
                                            <?= $e((string) ($zona['de_baja'] ?? 0)) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

            </div><!-- /zona resumen -->

            <!-- ══════════════════════════════════════════════════════════════════
                 SECCIÓN 3 — Filtros y botones de exportación
            ════════════════════════════════════════════════════════════════════ -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">

                <div class="mb-4">
                    <h2 class="text-sm font-bold text-slate-700">Exportación Masiva</h2>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Aplique filtros opcionales y descargue el listado completo en el formato deseado
                    </p>
                </div>

                <!-- Formulario de filtros -->
                <form method="GET" action="/" class="flex flex-wrap gap-3 items-end mb-5">
                    <input type="hidden" name="page" value="reportes">

                    <!-- Filtro de estado -->
                    <div class="flex flex-col gap-1 min-w-[180px]">
                        <label for="filtroEstado" class="text-xs font-semibold text-slate-600 uppercase tracking-wide">
                            Estado
                        </label>
                        <select id="filtroEstado" name="estado"
                                class="px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white
                                       focus:outline-none focus:ring-2 focus:ring-slate-400 text-slate-700 cursor-pointer">
                            <option value="">Todos los estados</option>
                            <option value="OPERATIVO"        <?= $filtros['estado'] === 'OPERATIVO'        ? 'selected' : '' ?>>
                                Operativo
                            </option>
                            <option value="EN MANTENIMIENTO" <?= $filtros['estado'] === 'EN MANTENIMIENTO' ? 'selected' : '' ?>>
                                En mantenimiento
                            </option>
                            <option value="DE BAJA"          <?= $filtros['estado'] === 'DE BAJA'          ? 'selected' : '' ?>>
                                De baja
                            </option>
                        </select>
                    </div>

                    <!-- Filtro de zona -->
                    <div class="flex flex-col gap-1 min-w-[220px]">
                        <label for="filtroZona" class="text-xs font-semibold text-slate-600 uppercase tracking-wide">
                            Zona
                        </label>
                        <select id="filtroZona" name="zona_id"
                                class="px-3 py-2 text-sm border border-slate-200 rounded-lg bg-white
                                       focus:outline-none focus:ring-2 focus:ring-slate-400 text-slate-700 cursor-pointer">
                            <option value="">Todas las zonas</option>
                            <?php foreach ($zonas as $zona): ?>
                                <option value="<?= $e((string) $zona['id']) ?>"
                                        <?= (string) $zona['id'] === $filtros['zona_id'] ? 'selected' : '' ?>>
                                    <?= $e($zona['zona_nombre']) ?>
                                    <?php if (!empty($zona['sede_nombre'])): ?>
                                        — <?= $e($zona['sede_nombre']) ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Botón aplicar filtros -->
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold
                                   text-white bg-slate-700 hover:bg-slate-600 active:bg-slate-800
                                   rounded-lg transition-colors duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                        </svg>
                        Aplicar filtros
                    </button>

                    <!-- Limpiar filtros -->
                    <?php if ($hayFiltros): ?>
                        <a href="/?page=reportes"
                           class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold
                                  text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg
                                  transition-colors duration-150">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                            Limpiar
                        </a>
                    <?php endif; ?>

                </form>

                <!-- Indicador de filtros activos -->
                <?php if ($hayFiltros): ?>
                    <div class="flex items-center gap-2 mb-4 p-3 bg-slate-50 border border-slate-200
                                rounded-lg text-xs text-slate-600">
                        <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>
                            La exportación incluirá únicamente los registros que coincidan con los filtros activos.
                            <?php if ($filtros['estado'] !== ''): ?>
                                <strong>Estado:</strong> <?= $e($filtros['estado']) ?>.
                            <?php endif; ?>
                            <?php if ($filtros['zona_id'] !== ''): ?>
                                <strong>Zona ID:</strong> <?= $e($filtros['zona_id']) ?>.
                            <?php endif; ?>
                        </span>
                    </div>
                <?php endif; ?>

                <!-- Separador -->
                <div class="border-t border-slate-100 my-4"></div>

                <!-- Botones de descarga masiva -->
                <div class="flex flex-wrap gap-3">

                    <!-- Exportar PDF -->
                    <a href="<?= $urlPdf ?>"
                       class="inline-flex items-center gap-2.5 px-5 py-3 text-sm font-semibold
                              text-slate-800 bg-slate-100 hover:bg-slate-200 active:bg-slate-300
                              border border-slate-300 rounded-xl transition-colors duration-150 shadow-sm">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        <span>
                            Exportar PDF
                            <span class="block text-xs font-normal text-slate-500 leading-none mt-0.5">
                                Horizontal A4 · Paginado
                            </span>
                        </span>
                    </a>

                    <!-- Exportar Excel (CSV) -->
                    <a href="<?= $urlCsv ?>"
                       class="inline-flex items-center gap-2.5 px-5 py-3 text-sm font-semibold
                              text-slate-800 bg-slate-800 hover:bg-slate-700 active:bg-slate-900
                              border border-slate-800 rounded-xl transition-colors duration-150 shadow-sm text-white">
                        <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <span>
                            Exportar Excel
                            <span class="block text-xs font-normal text-slate-400 leading-none mt-0.5">
                                CSV · UTF-8 · Compatible Windows
                            </span>
                        </span>
                    </a>

                </div><!-- /botones descarga -->

            </div><!-- /panel exportación -->

        </main>
    </div>

</body>
</html>

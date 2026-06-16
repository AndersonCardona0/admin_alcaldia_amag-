<?php
/**
 * inventario.php — Vista del módulo de Inventario Detallado.
 * Recibe del EquipoController:
 *   $equipos  → array de filas con datos relacionales de hardware.
 *   $zonas    → array de zonas para el selector de filtros.
 *   $filtros  → array con los valores activos de búsqueda y filtrado.
 * No ejecuta SQL ni procesa datos: solo renderiza la información recibida.
 */

// Función local: retorna las clases Tailwind del badge según el estado del equipo
function badgeEquipo(string $estado): string
{
    return match ($estado) {
        'OPERATIVO'        => 'bg-green-100  text-green-800',
        'EN MANTENIMIENTO' => 'bg-yellow-100 text-yellow-800',
        'DE BAJA'          => 'bg-red-100    text-red-800',
        default            => 'bg-gray-100   text-gray-600',
    };
}

// Función local: retorna el icono SVG del estado (círculo de color)
function iconoEstado(string $estado): string
{
    $color = match ($estado) {
        'OPERATIVO'        => 'text-green-500',
        'EN MANTENIMIENTO' => 'text-yellow-500',
        'DE BAJA'          => 'text-red-500',
        default            => 'text-gray-400',
    };
    return "<span class=\"inline-block w-1.5 h-1.5 rounded-full mr-1.5 align-middle
                          {$color} bg-current\"></span>";
}

// Alias de escape corto para uso en la vista
$e = fn(mixed $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventario de Equipos — Alcaldía Municipal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex h-screen bg-slate-100 overflow-hidden font-sans antialiased">

    <!-- ── Sidebar ──────────────────────────────────────────────────────────── -->
    <?php include __DIR__ . '/modules/sidebar.php'; ?>

    <!-- ── Columna principal ────────────────────────────────────────────────── -->
    <div class="flex-1 min-w-0 overflow-y-auto">

        <main class="p-6">

            <!-- ── Encabezado de página ──────────────────────────────────────── -->
            <div class="mb-6">
                <h1 class="text-xl font-bold text-slate-800">Inventario de Equipos</h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    Consulta y filtra el parque tecnológico registrado en todas las sedes
                </p>
            </div>

            <!-- ── Mensaje flash de error (redirect desde editar/registrar) ───── -->
            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5 flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-red-700 font-medium text-sm">
                        <?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

            <!-- ── Mensaje flash de éxito (POST-Redirect-GET desde registrar) ─── -->
            <?php if (!empty($_SESSION['flash_success'])): ?>
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-5 flex items-start gap-3">
                    <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-emerald-700 font-medium text-sm">
                        <?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <!-- ── Barra de filtros ───────────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 mb-5">
                <form method="GET" action="/" class="flex flex-wrap gap-3 items-end">

                    <!-- Página destino (mantiene el enrutador activo) -->
                    <input type="hidden" name="page" value="inventario">

                    <!-- Buscador libre -->
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wide">
                            Buscar equipo
                        </label>
                        <div class="relative">
                            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            <input type="search"
                                   name="search"
                                   value="<?= $e($filtros['search']) ?>"
                                   placeholder="Tipo, marca, modelo, serie o responsable..."
                                   class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg
                                          bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500
                                          focus:border-transparent placeholder-slate-400 text-slate-700">
                        </div>
                    </div>

                    <!-- Filtro por estado -->
                    <div class="min-w-[180px]">
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wide">
                            Estado
                        </label>
                        <select name="estado"
                                class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-slate-50
                                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                       text-slate-700 cursor-pointer">
                            <option value="">Todos los estados</option>
                            <?php foreach (['OPERATIVO', 'EN MANTENIMIENTO', 'DE BAJA'] as $opcion): ?>
                                <option value="<?= $e($opcion) ?>"
                                    <?= $filtros['estado'] === $opcion ? 'selected' : '' ?>>
                                    <?= $e($opcion) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Filtro por zona -->
                    <div class="min-w-[200px]">
                        <label class="block text-xs font-semibold text-slate-500 mb-1.5 uppercase tracking-wide">
                            Zona
                        </label>
                        <select name="zona_id"
                                class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg bg-slate-50
                                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                                       text-slate-700 cursor-pointer">
                            <option value="">Todas las zonas</option>
                            <?php foreach ($zonas as $zona): ?>
                                <option value="<?= (int) $zona['id'] ?>"
                                    <?= (string) $filtros['zona_id'] === (string) $zona['id'] ? 'selected' : '' ?>>
                                    <?= $e($zona['zona_nombre']) ?>
                                    <?php if (!empty($zona['sede_nombre'])): ?>
                                        — <?= $e($zona['sede_nombre']) ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Botones de acción del formulario -->
                    <div class="flex gap-2">
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold
                                       bg-blue-600 hover:bg-blue-700 text-white rounded-lg
                                       transition-colors duration-150 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                            </svg>
                            Filtrar
                        </button>

                        <?php
                        // Solo muestra "Limpiar" si hay algún filtro activo
                        $hayFiltros = $filtros['search'] !== '' || $filtros['estado'] !== '' || $filtros['zona_id'] !== '';
                        ?>
                        <?php if ($hayFiltros): ?>
                            <a href="/?page=inventario"
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
                    </div>

                </form>
            </div>

            <!-- ── Badge de filtro activo por zona ───────────────────────────── -->
            <?php if ($filtros['zona_id'] !== ''): ?>
                <?php
                // Busca el nombre de la zona activa dentro del array ya cargado
                $zonaNombreActiva = '';
                foreach ($zonas as $z) {
                    if ((string) $z['id'] === (string) $filtros['zona_id']) {
                        $zonaNombreActiva = $z['zona_nombre'];
                        break;
                    }
                }
                ?>
                <div class="flex items-center gap-2 mb-4">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold
                                 text-blue-700 bg-blue-50 border border-blue-200 rounded-full">
                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Filtrando por Zona:
                        <span class="font-bold">
                            <?= htmlspecialchars($zonaNombreActiva ?: 'ID ' . $filtros['zona_id'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </span>
                    <a href="/?page=inventario"
                       class="text-xs text-slate-400 hover:text-slate-600 transition-colors duration-150">
                        × Quitar filtro
                    </a>
                </div>
            <?php endif; ?>

            <!-- ── Tabla de resultados o estado vacío ─────────────────────────── -->
            <?php if (empty($equipos)): ?>

                <!-- Estado vacío: ningún equipo coincide con los filtros aplicados -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-12 text-center">
                    <div class="flex flex-col items-center gap-4 max-w-sm mx-auto">
                        <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-slate-700 font-semibold text-base">
                                <?php if ($filtros['zona_id'] !== '' && $filtros['search'] === '' && $filtros['estado'] === ''): ?>
                                    No se encontraron PCs registrados en esta zona
                                <?php else: ?>
                                    No se encontraron equipos informáticos
                                <?php endif; ?>
                            </p>
                            <p class="text-slate-500 text-sm mt-1">
                                que coincidan con los criterios de búsqueda seleccionados.
                            </p>
                        </div>
                        <?php if ($hayFiltros): ?>
                            <a href="/?page=inventario"
                               class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold
                                      text-blue-600 bg-blue-50 border border-blue-200 rounded-lg
                                      hover:bg-blue-100 transition-colors duration-150">
                                Limpiar todos los filtros
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

            <?php else: ?>

                <!-- Contador de resultados (total del universo filtrado, no solo la página) -->
                <p class="text-xs text-slate-500 mb-3 px-1">
                    <?= $paginacion['registros'] ?> equipo<?= $paginacion['registros'] !== 1 ? 's' : '' ?> encontrado<?= $paginacion['registros'] !== 1 ? 's' : '' ?>
                    <?php if ($hayFiltros): ?>
                        <span class="text-slate-400">— con filtros aplicados</span>
                    <?php endif; ?>
                </p>

                <!-- Tabla de inventario -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-8">
                                        #
                                    </th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        Equipo / Modelo
                                    </th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        N° de Serie
                                    </th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        Zona / Sede
                                    </th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        Responsable
                                    </th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        Estado
                                    </th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">

                                <?php foreach ($equipos as $equipo): ?>
                                    <tr class="hover:bg-slate-50 transition-colors duration-100 group">

                                        <!-- ID -->
                                        <td class="px-5 py-3.5 text-slate-400 text-xs font-mono">
                                            <?= (int) $equipo['id'] ?>
                                        </td>

                                        <!-- Tipo · Marca · Modelo -->
                                        <td class="px-5 py-3.5">
                                            <p class="font-semibold text-slate-800">
                                                <?= $e($equipo['tipo']) ?>
                                            </p>
                                            <p class="text-xs text-slate-500 mt-0.5">
                                                <?= $e($equipo['marca']) ?>
                                                <?php if (!empty($equipo['modelo'])): ?>
                                                    · <?= $e($equipo['modelo']) ?>
                                                <?php endif; ?>
                                            </p>
                                        </td>

                                        <!-- Número de serie -->
                                        <td class="px-5 py-3.5">
                                            <span class="font-mono text-xs text-slate-600 bg-slate-100
                                                         px-2 py-0.5 rounded">
                                                <?= $e($equipo['numero_serie'] ?? '—') ?>
                                            </span>
                                        </td>

                                        <!-- Zona + Sede -->
                                        <td class="px-5 py-3.5">
                                            <p class="text-slate-700 font-medium">
                                                <?= $e($equipo['zona_nombre'] ?? '—') ?>
                                            </p>
                                            <p class="text-xs text-slate-400 mt-0.5">
                                                <?= $e($equipo['sede_nombre'] ?? '—') ?>
                                            </p>
                                        </td>

                                        <!-- Funcionario responsable -->
                                        <td class="px-5 py-3.5">
                                            <p class="text-slate-700">
                                                <?= $e($equipo['funcionario_nombre']) ?>
                                            </p>
                                            <?php if (!empty($equipo['funcionario_cargo'])): ?>
                                                <p class="text-xs text-slate-400 mt-0.5">
                                                    <?= $e($equipo['funcionario_cargo']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Badge de estado -->
                                        <td class="px-5 py-3.5">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full
                                                         text-xs font-semibold <?= badgeEquipo($equipo['estado']) ?>">
                                                <?= iconoEstado($equipo['estado']) ?>
                                                <?= $e($equipo['estado']) ?>
                                            </span>
                                        </td>

                                        <!-- Acciones -->
                                        <td class="px-5 py-3.5 text-right">
                                            <div class="flex items-center justify-end gap-1.5
                                                        opacity-0 group-hover:opacity-100 transition-opacity duration-150">
                                                <!-- Ver detalle -->
                                                <a href="/?page=detalle&id=<?= (int) $equipo['id'] ?>"
                                                   title="Ver ficha completa"
                                                   class="p-1.5 text-slate-500 hover:text-blue-600
                                                          hover:bg-blue-50 rounded-lg transition-colors duration-150">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </a>
                                                <!-- Editar (placeholder fase futura) -->
                                                <a href="/?page=editar&id=<?= (int) $equipo['id'] ?>"
                                                   title="Editar equipo"
                                                   class="p-1.5 text-slate-500 hover:text-emerald-600
                                                          hover:bg-emerald-50 rounded-lg transition-colors duration-150">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                              d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>

                    <!-- Pie de la tabla: resumen + paginación + acción registrar -->
                    <div class="px-5 py-3 bg-slate-50 border-t border-slate-100">
                        <div class="flex flex-wrap items-center justify-between gap-3">

                            <!-- Resumen de registros en la página actual -->
                            <p class="text-xs text-slate-400 flex-shrink-0">
                                <?php
                                $desde = ($paginacion['actual'] - 1) * $paginacion['limite'] + 1;
                                $hasta = min($paginacion['actual'] * $paginacion['limite'], $paginacion['registros']);
                                ?>
                                Mostrando
                                <span class="font-semibold text-slate-600"><?= $desde ?>–<?= $hasta ?></span>
                                de
                                <span class="font-semibold text-slate-600"><?= $paginacion['registros'] ?></span>
                                registro<?= $paginacion['registros'] !== 1 ? 's' : '' ?>
                            </p>

                            <!-- Controles de paginación (ocultos si hay una sola página) -->
                            <?php if ($paginacion['total'] > 1): ?>
                            <nav class="flex items-center gap-1" aria-label="Paginación de inventario">
                                <?php
                                // URL base preservando todos los filtros activos; 'p' se sobreescribe en cada enlace
                                $urlPagina = fn(int $n): string =>
                                    '/?' . http_build_query(array_merge($_GET, ['p' => $n]));

                                $totalPags = $paginacion['total'];
                                $pagActual = $paginacion['actual'];
                                ?>

                                <!-- Botón Anterior -->
                                <?php if ($pagActual > 1): ?>
                                    <a href="<?= htmlspecialchars($urlPagina($pagActual - 1), ENT_QUOTES, 'UTF-8') ?>"
                                       title="Página anterior"
                                       class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium
                                              text-slate-600 bg-white border border-slate-200 rounded-lg
                                              hover:bg-slate-50 transition-colors duration-150">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </a>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium
                                                 text-slate-300 bg-slate-50 border border-slate-200 rounded-lg
                                                 opacity-50 cursor-not-allowed" aria-disabled="true">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                        </svg>
                                    </span>
                                <?php endif; ?>

                                <!-- Números de página con elipsis -->
                                <?php
                                // Rango visible: primera, última, actual ±2; resto como elipsis
                                $rango    = [];
                                for ($i = 1; $i <= $totalPags; $i++) {
                                    if ($i === 1 || $i === $totalPags || ($i >= $pagActual - 2 && $i <= $pagActual + 2)) {
                                        $rango[] = $i;
                                    }
                                }
                                $anterior = null;
                                foreach ($rango as $pag):
                                    if ($anterior !== null && $pag - $anterior > 1):
                                ?>
                                    <span class="px-1 py-1.5 text-xs text-slate-400 select-none">…</span>
                                <?php
                                    endif;
                                    $anterior = $pag;
                                ?>
                                    <?php if ($pag === $pagActual): ?>
                                        <span class="inline-flex items-center justify-center w-8 h-7
                                                     text-xs font-bold text-slate-800 bg-slate-200
                                                     border border-slate-300 rounded-lg cursor-default"
                                              aria-current="page">
                                            <?= $pag ?>
                                        </span>
                                    <?php else: ?>
                                        <a href="<?= htmlspecialchars($urlPagina($pag), ENT_QUOTES, 'UTF-8') ?>"
                                           class="inline-flex items-center justify-center w-8 h-7
                                                  text-xs font-medium text-slate-600 bg-white
                                                  border border-slate-200 rounded-lg
                                                  hover:bg-slate-50 transition-colors duration-150">
                                            <?= $pag ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>

                                <!-- Botón Siguiente -->
                                <?php if ($pagActual < $totalPags): ?>
                                    <a href="<?= htmlspecialchars($urlPagina($pagActual + 1), ENT_QUOTES, 'UTF-8') ?>"
                                       title="Página siguiente"
                                       class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium
                                              text-slate-600 bg-white border border-slate-200 rounded-lg
                                              hover:bg-slate-50 transition-colors duration-150">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                <?php else: ?>
                                    <span class="inline-flex items-center px-2.5 py-1.5 text-xs font-medium
                                                 text-slate-300 bg-slate-50 border border-slate-200 rounded-lg
                                                 opacity-50 cursor-not-allowed" aria-disabled="true">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </span>
                                <?php endif; ?>

                            </nav>
                            <?php endif; ?>

                            <!-- Acceso rápido a registrar un nuevo equipo -->
                            <a href="/?page=registrar"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold
                                      text-white bg-emerald-500 hover:bg-emerald-600 rounded-lg
                                      transition-colors duration-150 flex-shrink-0">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                                </svg>
                                Registrar equipo
                            </a>

                        </div>
                    </div>

                </div><!-- /tabla -->

            <?php endif; ?>

        </main><!-- /contenido desplazable -->

    </div><!-- /columna principal -->

</body>
</html>

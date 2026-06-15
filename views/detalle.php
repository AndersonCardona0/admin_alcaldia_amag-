<?php
/**
 * detalle.php — Vista de Perfil del Activo (solo lectura).
 * Recibe del EquipoController::ver():
 *   $equipo    → fila con todos los datos relacionales del equipo y sus specs.
 *   $historial → array de eventos de historial_cambios ordenados DESC por fecha.
 * No ejecuta SQL ni modifica datos.
 */

// Alias de escape para prevenir XSS en todos los outputs
$e = fn(mixed $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

// Formatea un timestamp de BD en texto legible para el usuario final
$fecha = fn(mixed $ts): string => $ts
    ? date('d M Y, g:i A', strtotime((string) $ts))
    : '—';

// Badge de estado: clases Tailwind según el valor del ENUM
function badgeDetalle(string $estado): string
{
    return match ($estado) {
        'OPERATIVO'        => 'bg-green-100  text-green-800  ring-green-200',
        'EN MANTENIMIENTO' => 'bg-yellow-100 text-yellow-800 ring-yellow-200',
        'DE BAJA'          => 'bg-red-100    text-red-800    ring-red-200',
        default            => 'bg-slate-100  text-slate-600  ring-slate-200',
    };
}

// Punto de color del timeline según el tipo de acción registrada
function colorTimeline(string $accion): string
{
    return match (true) {
        str_contains($accion, 'ACTUALIZACIÓN') => 'bg-blue-500',
        str_contains($accion, 'REGISTRO')      => 'bg-emerald-500',
        str_contains($accion, 'BAJA')          => 'bg-red-500',
        str_contains($accion, 'ACTIVACIÓN')    => 'bg-emerald-500',
        default                                 => 'bg-slate-400',
    };
}

// Icono SVG del timeline según tipo de acción
function iconoTimeline(string $accion): string
{
    if (str_contains($accion, 'ACTUALIZACIÓN')) {
        return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5
                         m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>';
    }
    if (str_contains($accion, 'BAJA')) {
        return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0
                         01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1
                         1 0 00-1 1v3M4 7h16"/>';
    }
    // Registro / default: check
    return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>';
}

$equipoId = (int) ($equipo['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil del Activo #<?= $equipoId ?> — Alcaldía Municipal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex h-screen bg-slate-100 overflow-hidden font-sans antialiased">

    <!-- ── Sidebar ──────────────────────────────────────────────────────────── -->
    <?php include __DIR__ . '/modules/sidebar.php'; ?>

    <!-- ── Columna principal ────────────────────────────────────────────────── -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        <?php include __DIR__ . '/modules/header.php'; ?>

        <main class="flex-1 overflow-y-auto p-6">

            <!-- ── Encabezado de página ──────────────────────────────────────── -->
            <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
                <div>
                    <!-- Chip identificador -->
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold
                                     bg-slate-200 text-slate-600 tracking-wide font-mono">
                            #<?= $equipoId ?>
                        </span>
                        <span class="text-slate-400 text-xs">
                            Registrado el <?= $e($fecha($equipo['fecha_registro'] ?? null)) ?>
                        </span>
                    </div>
                    <h1 class="text-xl font-bold text-slate-800">
                        <?= $e($equipo['tipo'] ?? '—') ?> ·
                        <span class="text-slate-600">
                            <?= $e($equipo['marca'] ?? '') ?> <?= $e($equipo['modelo'] ?? '') ?>
                        </span>
                    </h1>
                    <div class="flex items-center gap-2 mt-1.5">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs
                                     font-semibold ring-1 <?= badgeDetalle($equipo['estado'] ?? '') ?>">
                            <?= $e($equipo['estado'] ?? '—') ?>
                        </span>
                        <?php if (!empty($equipo['numero_serie'])): ?>
                            <span class="font-mono text-xs text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                                S/N: <?= $e($equipo['numero_serie']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Acciones superiores -->
                <div class="flex items-center gap-2 flex-shrink-0">
                    <!-- Genera y descarga el Acta de Asignación en PDF -->
                    <a href="/?page=exportar_pdf&id=<?= $equipoId ?>"
                       title="Descargar Acta de Asignación en formato PDF"
                       class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-semibold
                              text-rose-700 bg-rose-50 border border-rose-200 rounded-lg
                              hover:bg-rose-100 transition-colors duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414
                                     A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 13h6m-3-3v6"/>
                        </svg>
                        Generar Acta PDF
                    </a>
                    <a href="/?page=editar&id=<?= $equipoId ?>"
                       class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-semibold
                              text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-lg
                              hover:bg-indigo-100 transition-colors duration-150">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0
                                     002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828
                                     15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar equipo
                    </a>
                    <a href="/?page=inventario"
                       class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium
                              text-slate-600 bg-white border border-slate-200 rounded-lg
                              hover:bg-slate-50 transition-colors duration-150 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Regresar al inventario
                    </a>
                </div>
            </div>

            <!-- ── Grid principal: datos (2 cols) | timeline (3 cols) ────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">

                <!-- ════════════════════════════════════════════════════════════
                     COLUMNA IZQUIERDA (2/5) — Tarjetas de datos estáticos
                     ════════════════════════════════════════════════════════════ -->
                <div class="lg:col-span-2 flex flex-col gap-5">

                    <!-- Tarjeta: Información General -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center gap-2">
                            <div class="w-6 h-6 bg-blue-100 rounded-md flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <h2 class="text-sm font-semibold text-slate-700">Información General</h2>
                        </div>

                        <dl class="divide-y divide-slate-100">
                            <?php
                            // Pares label → valor para la tarjeta de información
                            $infoItems = [
                                'Tipo'           => $equipo['tipo']             ?? '—',
                                'Marca'          => $equipo['marca']            ?? '—',
                                'Modelo'         => $equipo['modelo']           ?? '—',
                                'N° de serie'    => $equipo['numero_serie']     ?? 'No registrado',
                                'Zona'           => $equipo['zona_nombre']      ?? '—',
                                'Sede'           => $equipo['sede_nombre']      ?? '—',
                                'Responsable'    => $equipo['funcionario_nombre'] ?? 'Sin asignar',
                                'Cargo'          => $equipo['funcionario_cargo'] ?? '—',
                            ];
                            foreach ($infoItems as $label => $valor):
                            ?>
                                <div class="px-5 py-3 flex items-start justify-between gap-3">
                                    <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide
                                               flex-shrink-0 w-24">
                                        <?= $e($label) ?>
                                    </dt>
                                    <dd class="text-sm text-slate-800 text-right
                                               <?= $label === 'N° de serie' ? 'font-mono' : '' ?>">
                                        <?= $e($valor) ?>
                                    </dd>
                                </div>
                            <?php endforeach; ?>

                            <!-- Estado con badge -->
                            <div class="px-5 py-3 flex items-center justify-between gap-3">
                                <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide">Estado</dt>
                                <dd>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full
                                                 text-xs font-semibold ring-1
                                                 <?= badgeDetalle($equipo['estado'] ?? '') ?>">
                                        <?= $e($equipo['estado'] ?? '—') ?>
                                    </span>
                                </dd>
                            </div>
                        </dl>
                    </div><!-- /tarjeta info general -->

                    <!-- Tarjeta: Especificaciones Técnicas -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200 flex items-center gap-2">
                            <div class="w-6 h-6 bg-emerald-100 rounded-md flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0
                                             0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                                </svg>
                            </div>
                            <h2 class="text-sm font-semibold text-slate-700">Especificaciones Técnicas</h2>
                        </div>

                        <dl class="divide-y divide-slate-100">
                            <?php
                            $specs = [
                                'Procesador'      => $equipo['procesador']     ?? null,
                                'Memoria RAM'     => $equipo['ram']            ?? null,
                                'Almacenamiento'  => $equipo['almacenamiento'] ?? null,
                            ];
                            foreach ($specs as $label => $valor):
                            ?>
                                <div class="px-5 py-3.5 flex items-start justify-between gap-3">
                                    <dt class="text-xs font-semibold text-slate-500 uppercase tracking-wide
                                               flex-shrink-0">
                                        <?= $e($label) ?>
                                    </dt>
                                    <dd class="text-sm text-right
                                               <?= $valor ? 'text-slate-800' : 'text-slate-400 italic' ?>">
                                        <?= $valor ? $e($valor) : 'No especificado' ?>
                                    </dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    </div><!-- /tarjeta especificaciones -->

                </div><!-- /columna izquierda -->


                <!-- ════════════════════════════════════════════════════════════
                     COLUMNA DERECHA (3/5) — Timeline de auditoría
                     ════════════════════════════════════════════════════════════ -->
                <div class="lg:col-span-3">
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

                        <!-- Cabecera del timeline -->
                        <div class="px-5 py-3.5 bg-slate-50 border-b border-slate-200
                                    flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 bg-violet-100 rounded-md flex items-center justify-center">
                                    <svg class="w-3.5 h-3.5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <h2 class="text-sm font-semibold text-slate-700">Historial de Cambios</h2>
                            </div>
                            <span class="text-xs font-semibold text-slate-500 bg-slate-100
                                         px-2 py-0.5 rounded-full">
                                <?= count($historial) ?>
                                evento<?= count($historial) !== 1 ? 's' : '' ?>
                            </span>
                        </div>

                        <!-- Cuerpo del timeline -->
                        <div class="p-5">

                            <?php if (empty($historial)): ?>
                                <!-- Estado vacío: el activo no tiene eventos aún -->
                                <div class="flex flex-col items-center justify-center py-10 text-center gap-3">
                                    <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                  d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-slate-600 font-semibold text-sm">Sin eventos registrados</p>
                                        <p class="text-slate-400 text-xs mt-0.5">
                                            No hay eventos registrados para este activo.
                                        </p>
                                    </div>
                                </div>

                            <?php else: ?>

                                <!-- Línea de tiempo vertical con border-l -->
                                <div class="relative pl-6 border-l-2 border-slate-200 space-y-0">

                                    <?php foreach ($historial as $i => $evento): ?>

                                        <?php
                                        $colorDot  = colorTimeline($evento['accion']);
                                        $svgPath   = iconoTimeline($evento['accion']);
                                        $esUltimo  = $i === count($historial) - 1;
                                        ?>

                                        <div class="relative <?= $esUltimo ? '' : 'pb-6' ?>">

                                            <!-- Círculo indicador sobre la línea vertical -->
                                            <div class="absolute -left-[2.05rem] top-0.5 flex items-center
                                                        justify-center w-8 h-8 rounded-full
                                                        <?= $colorDot ?> shadow-sm ring-2 ring-white">
                                                <svg class="w-3.5 h-3.5 text-white" fill="none"
                                                     stroke="currentColor" viewBox="0 0 24 24">
                                                    <?= $svgPath ?>
                                                </svg>
                                            </div>

                                            <!-- Tarjeta del evento -->
                                            <div class="ml-2 bg-slate-50 border border-slate-200
                                                        rounded-lg p-4 hover:border-slate-300
                                                        transition-colors duration-150">

                                                <!-- Metadatos del evento -->
                                                <div class="flex flex-wrap items-center justify-between
                                                            gap-1 mb-2">
                                                    <span class="text-xs font-bold text-slate-700 uppercase
                                                                 tracking-wide">
                                                        <?= $e($evento['accion']) ?>
                                                    </span>
                                                    <span class="text-xs text-slate-400 font-mono">
                                                        <?= $e($fecha($evento['fecha'])) ?>
                                                    </span>
                                                </div>

                                                <!-- Detalle del cambio -->
                                                <?php if (!empty($evento['detalle'])): ?>
                                                    <p class="text-sm text-slate-600 leading-relaxed">
                                                        <?= $e($evento['detalle']) ?>
                                                    </p>
                                                <?php endif; ?>

                                                <!-- Usuario que realizó el cambio -->
                                                <div class="flex items-center gap-1.5 mt-2.5 pt-2.5
                                                            border-t border-slate-200">
                                                    <svg class="w-3.5 h-3.5 text-slate-400 flex-shrink-0"
                                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                              stroke-width="2"
                                                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12
                                                                 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                                    </svg>
                                                    <span class="text-xs text-slate-500">
                                                        <?= $e($evento['usuario']) ?>
                                                    </span>
                                                </div>

                                            </div><!-- /tarjeta evento -->
                                        </div><!-- /item timeline -->

                                    <?php endforeach; ?>

                                </div><!-- /timeline vertical -->

                            <?php endif; ?>

                        </div><!-- /cuerpo timeline -->
                    </div><!-- /panel timeline -->
                </div><!-- /columna derecha -->

            </div><!-- /grid principal -->

        </main>

    </div><!-- /columna principal -->

</body>
</html>

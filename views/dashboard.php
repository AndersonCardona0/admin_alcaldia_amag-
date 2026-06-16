<?php
/**
 * dashboard.php — Vista ensambladora del Panel de Control principal.
 * Recibe $stats y $zonas inyectadas por DashboardController::mostrar().
 * Incluye los módulos de sidebar y header, luego renderiza el contenido propio.
 */

// Función local: retorna clases Tailwind del badge según el estado de sondeo
function badgeEstado(string $estado): string
{
    return match ($estado) {
        'EN PROCESO'  => 'bg-blue-100 text-blue-700 ring-blue-200',
        'EN PAUSA'    => 'bg-amber-100 text-amber-700 ring-amber-200',
        'COMPLETADO'  => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
        default       => 'bg-slate-100 text-slate-600 ring-slate-200',   // 'SIN INICIAR'
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Alcaldía Municipal</title>
    <!-- Tailwind CSS CDN (producción via Play CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex h-screen bg-slate-100 overflow-hidden font-sans antialiased">

    <!-- ── Sidebar ──────────────────────────────────────────────────────────── -->
    <?php include __DIR__ . '/modules/sidebar.php'; ?>

    <!-- ── Columna principal (header + contenido) ───────────────────────────── -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        <!-- Barra superior -->
        <?php include __DIR__ . '/modules/header.php'; ?>

        <!-- Área de contenido desplazable -->
        <main class="flex-1 overflow-y-auto p-6">

            <!-- Encabezado de sección -->
            <div class="mb-6">
                <h1 class="text-xl font-bold text-slate-800">Panel de Control</h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    Resumen general del inventario tecnológico de la alcaldía
                </p>
            </div>

            <!-- ── Tarjetas de métricas ──────────────────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">

                <!-- Total PCs en Servicio -->
                <div class="bg-white rounded-xl border border-slate-200 p-5
                            flex items-center gap-4 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-blue-50 rounded-xl flex items-center
                                justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-3xl font-extrabold text-slate-800 leading-none">
                            <?= htmlspecialchars((string) $stats['total']) ?>
                        </p>
                        <p class="text-sm text-slate-500 mt-1">Total PCs en Servicio</p>
                    </div>
                </div>

                <!-- PCs Operativos -->
                <div class="bg-white rounded-xl border border-slate-200 p-5
                            flex items-center gap-4 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-emerald-50 rounded-xl flex items-center
                                justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-3xl font-extrabold text-emerald-600 leading-none">
                            <?= htmlspecialchars((string) $stats['operativos']) ?>
                        </p>
                        <p class="text-sm text-slate-500 mt-1">PCs Operativos</p>
                    </div>
                </div>

                <!-- PCs en Mantenimiento -->
                <div class="bg-white rounded-xl border border-slate-200 p-5
                            flex items-center gap-4 shadow-sm hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-amber-50 rounded-xl flex items-center
                                justify-center flex-shrink-0">
                        <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-3xl font-extrabold text-amber-600 leading-none">
                            <?= htmlspecialchars((string) $stats['en_mantenimiento']) ?>
                        </p>
                        <p class="text-sm text-slate-500 mt-1">PCs en Mantenimiento</p>
                    </div>
                </div>

            </div><!-- /tarjetas -->

            <!-- ── Tabla: Zonas en Sondeo ────────────────────────────────────── -->
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

                <!-- Cabecera de la tabla -->
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-slate-800">Zonas en Sondeo</h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            <?= count($zonas) ?> zona<?= count($zonas) !== 1 ? 's' : '' ?> registrada<?= count($zonas) !== 1 ? 's' : '' ?>
                        </p>
                    </div>
                    <a href="/?page=registrar"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold
                              bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nuevo Equipo
                    </a>
                </div>

                <!-- Tabla de datos -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 text-left">
                                <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Zona / Sede
                                </th>
                                <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Encargado
                                </th>
                                <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                    Estado del Sondeo
                                </th>
                                <th class="px-5 py-3 text-xs font-semibold text-slate-500 uppercase tracking-wider text-right">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">

                            <?php if (empty($zonas)): ?>
                                <!-- Estado vacío: se muestra si no hay zonas en la BD -->
                                <tr>
                                    <td colspan="4" class="px-5 py-14 text-center">
                                        <div class="flex flex-col items-center gap-3">
                                            <div class="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center">
                                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                                </svg>
                                            </div>
                                            <p class="text-slate-500 font-medium">No hay zonas registradas para el sondeo</p>
                                            <p class="text-slate-400 text-xs">Registre equipos y asigne zonas para que aparezcan aquí.</p>
                                        </div>
                                    </td>
                                </tr>

                            <?php else: ?>

                                <?php foreach ($zonas as $zona): ?>
                                    <tr class="hover:bg-slate-50 transition-colors duration-100">

                                        <!-- Zona + Sede -->
                                        <td class="px-5 py-3.5">
                                            <p class="font-medium text-slate-800">
                                                <?= htmlspecialchars($zona['zona_nombre']) ?>
                                            </p>
                                            <p class="text-xs text-slate-400 mt-0.5">
                                                <?= htmlspecialchars($zona['sede_nombre'] ?? '—') ?>
                                            </p>
                                        </td>

                                        <!-- Encargado -->
                                        <td class="px-5 py-3.5">
                                            <p class="text-slate-700">
                                                <?= htmlspecialchars($zona['encargado_nombre']) ?>
                                            </p>
                                            <?php if (!empty($zona['encargado_cargo'])): ?>
                                                <p class="text-xs text-slate-400 mt-0.5">
                                                    <?= htmlspecialchars($zona['encargado_cargo']) ?>
                                                </p>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Badge de estado -->
                                        <td class="px-5 py-3.5">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs
                                                         font-semibold ring-1 <?= badgeEstado($zona['estado']) ?>">
                                                <?= htmlspecialchars($zona['estado']) ?>
                                            </span>
                                        </td>

                                        <!-- Acciones -->
                                        <td class="px-5 py-3.5 text-right">
                                            <a href="/?page=inventario&zona_id=<?= (int) $zona['id'] ?>"
                                               class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium
                                                      text-slate-600 bg-slate-100 hover:bg-slate-200
                                                      rounded-lg transition-colors duration-150">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                Ver detalle
                                            </a>
                                        </td>

                                    </tr>
                                <?php endforeach; ?>

                            <?php endif; ?>

                        </tbody>
                    </table>
                </div>

            </div><!-- /tabla -->

        </main><!-- /contenido desplazable -->

    </div><!-- /columna principal -->

</body>
</html>

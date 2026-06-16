<?php

declare(strict_types=1);

/**
 * zonas.php — Panel de gestión de zonas físicas.
 * Recibe de ZonaController::mostrar():
 *   $zonas        → array con todos los registros (id, zona_nombre, estado,
 *                   sede_nombre, encargado_nombre, encargado_cargo)
 *   $sedes        → array para el <select> de sede (id, nombre)
 *   $funcionarios → array para el <select> de encargado (id, nombre, cargo)
 *   $total        → int conteo total de zonas
 */

// Alias de escape seguro
$e = fn(mixed $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

// Badge de color según el estado de la zona (paleta slate/blue/amber/emerald)
$badgeZona = static function(string $estado): string {
    return match ($estado) {
        'EN PROCESO'  => 'bg-blue-100   text-blue-700   ring-1 ring-blue-200',
        'EN PAUSA'    => 'bg-amber-100  text-amber-700  ring-1 ring-amber-200',
        'COMPLETADO'  => 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200',
        'INACTIVO'    => 'bg-slate-200  text-slate-500  ring-1 ring-slate-300',
        default       => 'bg-slate-100  text-slate-600  ring-1 ring-slate-200',
    };
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zonas — Alcaldía Municipal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex h-screen bg-slate-100 overflow-hidden font-sans antialiased">

    <!-- ── Sidebar ──────────────────────────────────────────────────────────── -->
    <?php include __DIR__ . '/modules/sidebar.php'; ?>

    <!-- ── Columna principal ────────────────────────────────────────────────── -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        <?php include __DIR__ . '/modules/header.php'; ?>

        <main class="flex-1 overflow-y-auto p-6">

            <!-- ── Encabezado de sección ─────────────────────────────────────── -->
            <div class="mb-6">
                <h1 class="text-xl font-bold text-slate-800">Gestión de Zonas</h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    Administre las áreas físicas de la alcaldía y sus encargados asignados
                </p>
            </div>

            <!-- ── Mensajes Flash ────────────────────────────────────────────── -->
            <?php if (!empty($_SESSION['flash_success'])): ?>
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-5
                            flex items-start gap-3">
                    <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none"
                         stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-emerald-700 font-medium text-sm">
                        <?= $e($_SESSION['flash_success']) ?>
                    </p>
                </div>
                <?php unset($_SESSION['flash_success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['flash_error'])): ?>
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5
                            flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none"
                         stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-red-700 font-medium text-sm">
                        <?= $e($_SESSION['flash_error']) ?>
                    </p>
                </div>
                <?php unset($_SESSION['flash_error']); ?>
            <?php endif; ?>

            <!-- ── Cuadrícula principal: formulario + tabla ──────────────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

                <!-- ══════════════════════════════════════════════════════════════
                     COLUMNA IZQUIERDA — Formulario de registro
                ════════════════════════════════════════════════════════════════ -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">

                    <div class="mb-5">
                        <h2 class="text-sm font-bold text-slate-700">Nueva Zona</h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Complete los campos para registrar un área física
                        </p>
                    </div>

                    <form method="POST" action="/?page=zonas" novalidate>
                        <input type="hidden" name="csrf_token"
                               value="<?= $e($_SESSION['csrf_token'] ?? '') ?>">

                        <!-- Nombre de la zona -->
                        <div class="mb-4">
                            <label for="nombre"
                                   class="block text-xs font-semibold text-slate-600
                                          uppercase tracking-wide mb-1.5">
                                Nombre de la zona <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="nombre"
                                   name="nombre"
                                   maxlength="120"
                                   required
                                   placeholder="Ej: Oficina de Sistemas"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                                          bg-white text-slate-800 placeholder-slate-400
                                          focus:outline-none focus:ring-2 focus:ring-slate-400
                                          transition-shadow duration-150">
                        </div>

                        <!-- Sede (FK obligatoria) -->
                        <div class="mb-4">
                            <label for="sede_id"
                                   class="block text-xs font-semibold text-slate-600
                                          uppercase tracking-wide mb-1.5">
                                Sede <span class="text-red-500">*</span>
                            </label>
                            <select id="sede_id"
                                    name="sede_id"
                                    required
                                    class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                                           bg-white text-slate-700 cursor-pointer
                                           focus:outline-none focus:ring-2 focus:ring-slate-400
                                           transition-shadow duration-150">
                                <option value="">— Seleccione una sede —</option>
                                <?php foreach ($sedes as $sede): ?>
                                    <option value="<?= $e((string) $sede['id']) ?>">
                                        <?= $e($sede['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($sedes)): ?>
                                <p class="text-xs text-amber-600 mt-1">
                                    No hay sedes registradas. Agregue una sede antes de crear zonas.
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- Encargado (FK opcional) -->
                        <div class="mb-4">
                            <label for="encargado_id"
                                   class="block text-xs font-semibold text-slate-600
                                          uppercase tracking-wide mb-1.5">
                                Encargado
                                <span class="font-normal text-slate-400 normal-case
                                             tracking-normal ml-1">(opcional)</span>
                            </label>
                            <select id="encargado_id"
                                    name="encargado_id"
                                    class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                                           bg-white text-slate-700 cursor-pointer
                                           focus:outline-none focus:ring-2 focus:ring-slate-400
                                           transition-shadow duration-150">
                                <option value="">— Sin encargado asignado —</option>
                                <?php foreach ($funcionarios as $f): ?>
                                    <option value="<?= $e((string) $f['id']) ?>">
                                        <?= $e($f['nombre']) ?>
                                        <?php if (!empty($f['cargo'])): ?>
                                            — <?= $e($f['cargo']) ?>
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Estado de la zona -->
                        <div class="mb-5">
                            <label for="estado"
                                   class="block text-xs font-semibold text-slate-600
                                          uppercase tracking-wide mb-1.5">
                                Estado <span class="text-red-500">*</span>
                            </label>
                            <select id="estado"
                                    name="estado"
                                    required
                                    class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                                           bg-white text-slate-700 cursor-pointer
                                           focus:outline-none focus:ring-2 focus:ring-slate-400
                                           transition-shadow duration-150">
                                <option value="SIN INICIAR" selected>Sin iniciar</option>
                                <option value="EN PROCESO">En proceso</option>
                                <option value="EN PAUSA">En pausa</option>
                                <option value="COMPLETADO">Completado</option>
                            </select>
                        </div>

                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2
                                       px-4 py-2.5 text-sm font-semibold text-white
                                       bg-slate-800 hover:bg-slate-700 active:bg-slate-900
                                       rounded-lg transition-colors duration-150 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                      d="M12 4v16m8-8H4"/>
                            </svg>
                            Registrar Zona
                        </button>
                    </form>

                </div><!-- /formulario -->

                <!-- ══════════════════════════════════════════════════════════════
                     COLUMNA DERECHA (×2) — Tabla de zonas
                ════════════════════════════════════════════════════════════════ -->
                <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm">

                    <!-- Encabezado de tabla -->
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center
                                justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-slate-700">Zonas Registradas</h2>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Áreas físicas disponibles para asignar a equipos del inventario
                            </p>
                        </div>
                        <span class="text-xs font-semibold text-slate-500 bg-slate-100
                                     px-2.5 py-1 rounded-full">
                            <?= $e((string) $total) ?>
                            <?= $total === 1 ? 'zona' : 'zonas' ?>
                        </span>
                    </div>

                    <?php if (empty($zonas)): ?>
                        <!-- Estado vacío -->
                        <div class="p-10 flex flex-col items-center justify-center text-center gap-3">
                            <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center
                                        justify-center">
                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-600">Sin zonas registradas</p>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    Use el formulario para registrar la primera área física
                                </p>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="overflow-x-auto overflow-y-auto max-h-[calc(100vh-20rem)]">
                            <table class="w-full text-sm">
                                <thead class="sticky top-0 bg-slate-50 z-10">
                                    <tr class="text-left text-xs font-semibold text-slate-500
                                               uppercase tracking-wider">
                                        <th class="px-5 py-3 border-b border-slate-200 w-8">#</th>
                                        <th class="px-4 py-3 border-b border-slate-200">Zona</th>
                                        <th class="px-4 py-3 border-b border-slate-200">Sede</th>
                                        <th class="px-4 py-3 border-b border-slate-200">Encargado</th>
                                        <th class="px-4 py-3 border-b border-slate-200">Estado</th>
                                        <th class="px-4 py-3 border-b border-slate-200 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($zonas as $idx => $zona): ?>
                                        <tr class="hover:bg-slate-50 transition-colors duration-100">
                                            <td class="px-5 py-3 text-slate-400 text-xs font-mono">
                                                <?= $e((string) ($idx + 1)) ?>
                                            </td>
                                            <td class="px-4 py-3 font-semibold text-slate-800">
                                                <?= $e($zona['zona_nombre']) ?>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600">
                                                <?= $e($zona['sede_nombre'] ?? '—') ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="text-slate-700 font-medium">
                                                    <?= $e($zona['encargado_nombre']) ?>
                                                </span>
                                                <?php if (!empty($zona['encargado_cargo'])): ?>
                                                    <span class="block text-xs text-slate-400 mt-0.5">
                                                        <?= $e($zona['encargado_cargo']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <span class="inline-flex items-center px-2.5 py-0.5
                                                             rounded-full text-xs font-semibold
                                                             <?= $e($badgeZona($zona['estado'] ?? '')) ?>">
                                                    <?= $e($zona['estado'] ?? '—') ?>
                                                </span>
                                            </td>

                                            <!-- Acciones: Editar + Desactivar -->
                                            <td class="px-4 py-3 text-right">
                                                <div class="inline-flex items-center gap-2">

                                                    <!-- Editar (siempre visible) -->
                                                    <a href="/?page=zonas&action=editar&id=<?= (int) $zona['id'] ?>"
                                                       class="inline-flex items-center gap-1 px-2.5 py-1 text-xs
                                                              font-medium text-slate-600 bg-slate-100
                                                              hover:bg-slate-200 rounded-lg transition-colors
                                                              duration-150">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                             viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                  stroke-width="2"
                                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0
                                                                     002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828
                                                                     15H9v-2.828l8.586-8.586z"/>
                                                        </svg>
                                                        Editar
                                                    </a>

                                                    <!-- Desactivar (solo si NO está INACTIVO) -->
                                                    <?php if (($zona['estado'] ?? '') !== 'INACTIVO'): ?>
                                                        <form method="POST"
                                                              action="/?page=zonas&action=eliminar"
                                                              onsubmit="return confirm('¿Confirma que desea desactivar la zona «<?= htmlspecialchars(addslashes($zona['zona_nombre']), ENT_QUOTES, 'UTF-8') ?>»?\nEsta acción es reversible desde edición.');">
                                                            <input type="hidden" name="csrf_token"
                                                                   value="<?= $e($_SESSION['csrf_token'] ?? '') ?>">
                                                            <input type="hidden" name="id"
                                                                   value="<?= (int) $zona['id'] ?>">
                                                            <button type="submit"
                                                                    class="inline-flex items-center gap-1 px-2.5 py-1
                                                                           text-xs font-medium text-red-600
                                                                           bg-red-50 hover:bg-red-100 rounded-lg
                                                                           transition-colors duration-150">
                                                                <svg class="w-3.5 h-3.5" fill="none"
                                                                     stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round"
                                                                          stroke-linejoin="round" stroke-width="2"
                                                                          d="M18.364 18.364A9 9 0 005.636 5.636m12.728
                                                                             12.728A9 9 0 015.636 5.636m12.728
                                                                             12.728L5.636 5.636"/>
                                                                </svg>
                                                                Desactivar
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>

                                                </div>
                                            </td>

                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                </div><!-- /tabla -->

            </div><!-- /grid -->

        </main>
    </div>

</body>
</html>

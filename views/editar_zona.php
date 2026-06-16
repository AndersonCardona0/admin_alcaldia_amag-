<?php

declare(strict_types=1);

/**
 * editar_zona.php — Formulario de edición de zona física.
 * Recibe de ZonaController::mostrarEditar():
 *   $zona         → array con id, nombre, sede_id, encargado_id, estado
 *   $sedes        → array para <select> de sede (id, nombre)
 *   $funcionarios → array para <select> de encargado (id, nombre, cargo)
 */

// Alias de escape seguro
$e = fn(mixed $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Zona — Alcaldía Municipal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex h-screen bg-slate-100 overflow-hidden font-sans antialiased">

    <!-- ── Sidebar ──────────────────────────────────────────────────────────── -->
    <?php include __DIR__ . '/modules/sidebar.php'; ?>

    <!-- ── Columna principal ────────────────────────────────────────────────── -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

        <?php include __DIR__ . '/modules/header.php'; ?>

        <main class="flex-1 overflow-y-auto p-6">

            <!-- ── Encabezado con breadcrumb ─────────────────────────────────── -->
            <div class="mb-6">
                <nav class="text-xs text-slate-400 mb-1 flex items-center gap-1.5">
                    <a href="/?page=zonas"
                       class="hover:text-slate-600 transition-colors">Zonas</a>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5l7 7-7 7"/>
                    </svg>
                    <span class="text-slate-600">Editar</span>
                </nav>
                <h1 class="text-xl font-bold text-slate-800">Editar Zona</h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    Modifique los datos de la zona y guarde los cambios
                </p>
            </div>

            <!-- ── Mensajes Flash ────────────────────────────────────────────── -->
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

            <!-- ── Formulario de edición ─────────────────────────────────────── -->
            <div class="max-w-xl bg-white rounded-xl border border-slate-200 shadow-sm p-6">

                <form method="POST" action="/?page=zonas&action=editar" novalidate>

                    <!-- Campos de control (ocultos) -->
                    <input type="hidden" name="csrf_token"
                           value="<?= $e($_SESSION['csrf_token'] ?? '') ?>">
                    <input type="hidden" name="id"
                           value="<?= (int) $zona['id'] ?>">

                    <!-- Nombre de la zona -->
                    <div class="mb-5">
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
                               value="<?= $e($zona['nombre'] ?? '') ?>"
                               class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                                      bg-white text-slate-800 placeholder-slate-400
                                      focus:outline-none focus:ring-2 focus:ring-slate-400
                                      transition-shadow duration-150">
                    </div>

                    <!-- Sede (FK obligatoria) -->
                    <div class="mb-5">
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
                                <option value="<?= $e((string) $sede['id']) ?>"
                                    <?= ((int) $sede['id'] === (int) ($zona['sede_id'] ?? 0)) ? 'selected' : '' ?>>
                                    <?= $e($sede['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Encargado (FK opcional) -->
                    <div class="mb-5">
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
                                <option value="<?= $e((string) $f['id']) ?>"
                                    <?= ((int) $f['id'] === (int) ($zona['encargado_id'] ?? 0)) ? 'selected' : '' ?>>
                                    <?= $e($f['nombre']) ?>
                                    <?php if (!empty($f['cargo'])): ?>
                                        — <?= $e($f['cargo']) ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Estado de sondeo -->
                    <div class="mb-6">
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
                            <?php
                            $estadosOpciones = [
                                'SIN INICIAR' => 'Sin iniciar',
                                'EN PROCESO'  => 'En proceso',
                                'EN PAUSA'    => 'En pausa',
                                'COMPLETADO'  => 'Completado',
                            ];
                            foreach ($estadosOpciones as $val => $label):
                            ?>
                                <option value="<?= $e($val) ?>"
                                    <?= ($zona['estado'] === $val) ? 'selected' : '' ?>>
                                    <?= $e($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-slate-400 mt-1">
                            Para desactivar la zona use el botón "Desactivar" en el listado.
                        </p>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex items-center gap-3 pt-2 border-t border-slate-100">
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm
                                       font-semibold text-white bg-slate-800 hover:bg-slate-700
                                       active:bg-slate-900 rounded-lg transition-colors
                                       duration-150 shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M5 13l4 4L19 7"/>
                            </svg>
                            Guardar Cambios
                        </button>
                        <a href="/?page=zonas"
                           class="inline-flex items-center gap-2 px-4 py-2.5 text-sm
                                  font-medium text-slate-600 bg-slate-100 hover:bg-slate-200
                                  rounded-lg transition-colors duration-150">
                            Cancelar
                        </a>
                    </div>

                </form>

            </div><!-- /formulario -->

        </main>
    </div>

</body>
</html>

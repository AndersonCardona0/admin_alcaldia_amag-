<?php
/**
 * editar.php — Vista del formulario de edición de equipo existente.
 * Recibe del EquipoController::mostrarFormularioEdicion():
 *   $equipo       → datos actuales del equipo (DB + override de POST si hubo error).
 *   $zonas        → array de zonas para el <select>.
 *   $funcionarios → array de funcionarios para el <select>.
 *   $errores      → mensajes de validación (vacío en GET exitoso).
 * No ejecuta SQL ni lógica de negocio.
 */

// Alias de escape: ENT_QUOTES previene XSS en atributos HTML value=""
$e = fn(mixed $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

// Retorna 'selected' si el valor del <option> coincide con el campo del equipo
$sel = fn(string $campo, string $valor): string =>
    (string) ($equipo[$campo] ?? '') === $valor ? 'selected' : '';

$equipoId = (int) ($equipo['id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Equipo #<?= $equipoId ?> — Alcaldía Municipal</title>
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
            <div class="flex items-center justify-between mb-6">
                <div>
                    <div class="flex items-center gap-2 mb-0.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs
                                     font-bold bg-indigo-100 text-indigo-700 tracking-wide">
                            ID #<?= $equipoId ?>
                        </span>
                        <span class="text-slate-400 text-xs">
                            Registrado el <?= $e(
                                isset($equipo['fecha_registro'])
                                    ? date('d/m/Y H:i', strtotime($equipo['fecha_registro']))
                                    : '—'
                            ) ?>
                        </span>
                    </div>
                    <h1 class="text-xl font-bold text-slate-800">
                        Editar Equipo ·
                        <span class="text-indigo-600"><?= $e($equipo['marca'] ?? '') ?>
                            <?= $e($equipo['modelo'] ?? '') ?></span>
                    </h1>
                    <p class="text-sm text-slate-500 mt-0.5">
                        Modifique los datos del activo. Los cambios quedarán registrados en el historial.
                    </p>
                </div>
                <a href="/?page=inventario"
                   class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium
                          text-slate-600 bg-white border border-slate-200 rounded-lg
                          hover:bg-slate-50 transition-colors duration-150 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver al inventario
                </a>
            </div>

            <!-- ── Alertas de error de validación ────────────────────────────── -->
            <?php if (!empty($errores)): ?>
                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6 flex gap-3">
                    <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-red-700 font-semibold text-sm mb-1">
                            Corrija los siguientes errores para continuar:
                        </p>
                        <ul class="list-disc list-inside space-y-0.5">
                            <?php foreach ($errores as $error): ?>
                                <li class="text-red-600 text-sm"><?= $e($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <!-- ── Formulario de dos columnas ────────────────────────────────── -->
            <form method="POST" action="/?page=editar" novalidate>

                <!-- ID oculto: identifica el equipo que se modifica -->
                <input type="hidden" name="id" value="<?= $equipoId ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <!-- ════════════════════════════════════════════════════════
                         COLUMNA IZQUIERDA — Información General del Activo
                         (acento índigo para distinguir edición de creación)
                         ════════════════════════════════════════════════════════ -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

                        <div class="px-6 py-4 bg-indigo-50 border-b border-indigo-100 flex items-center gap-2.5">
                            <div class="w-7 h-7 bg-indigo-100 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </div>
                            <h2 class="text-sm font-semibold text-indigo-800">Información General del Activo</h2>
                        </div>

                        <div class="p-6 space-y-4">

                            <!-- Tipo de equipo -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide"
                                       for="tipo">
                                    Tipo de equipo <span class="text-red-500">*</span>
                                </label>
                                <select id="tipo" name="tipo" required
                                        class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg
                                               bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500
                                               focus:border-transparent text-slate-700 cursor-pointer">
                                    <option value="">— Seleccione un tipo —</option>
                                    <?php foreach (['Desktop', 'Laptop', 'All in One', 'Servidor', 'Impresora', 'Switch', 'Otro'] as $tipo): ?>
                                        <option value="<?= $e($tipo) ?>" <?= $sel('tipo', $tipo) ?>>
                                            <?= $e($tipo) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Marca -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide"
                                       for="marca">
                                    Marca <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="marca" name="marca"
                                       value="<?= $e($equipo['marca'] ?? '') ?>"
                                       maxlength="100"
                                       placeholder="Ej: Dell, HP, Lenovo..."
                                       class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg
                                              bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500
                                              focus:border-transparent placeholder-slate-400 text-slate-700">
                            </div>

                            <!-- Modelo -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide"
                                       for="modelo">
                                    Modelo <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="modelo" name="modelo"
                                       value="<?= $e($equipo['modelo'] ?? '') ?>"
                                       maxlength="100"
                                       placeholder="Ej: OptiPlex 7090..."
                                       class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg
                                              bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500
                                              focus:border-transparent placeholder-slate-400 text-slate-700">
                            </div>

                            <!-- Número de serie -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide"
                                       for="numero_serie">
                                    Número de serie
                                    <span class="text-slate-400 font-normal normal-case ml-1">(único)</span>
                                </label>
                                <input type="text" id="numero_serie" name="numero_serie"
                                       value="<?= $e($equipo['numero_serie'] ?? '') ?>"
                                       maxlength="100"
                                       placeholder="Ej: MXL1234ABCD"
                                       class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg
                                              bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500
                                              focus:border-transparent placeholder-slate-400
                                              text-slate-700 font-mono">
                            </div>

                            <!-- Zona -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide"
                                       for="zona_id">
                                    Zona asignada <span class="text-red-500">*</span>
                                </label>
                                <select id="zona_id" name="zona_id" required
                                        class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg
                                               bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500
                                               focus:border-transparent text-slate-700 cursor-pointer">
                                    <option value="">— Seleccione una zona —</option>
                                    <?php foreach ($zonas as $zona): ?>
                                        <option value="<?= (int) $zona['id'] ?>"
                                            <?= (string) ($equipo['zona_id'] ?? '') === (string) $zona['id'] ? 'selected' : '' ?>>
                                            <?= $e($zona['zona_nombre']) ?>
                                            <?php if (!empty($zona['sede_nombre'])): ?>
                                                — <?= $e($zona['sede_nombre']) ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Funcionario responsable -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide"
                                       for="funcionario_id">
                                    Funcionario responsable
                                    <span class="text-slate-400 font-normal normal-case ml-1">(opcional)</span>
                                </label>
                                <select id="funcionario_id" name="funcionario_id"
                                        class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg
                                               bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500
                                               focus:border-transparent text-slate-700 cursor-pointer">
                                    <option value="">— Sin asignar —</option>
                                    <?php foreach ($funcionarios as $func): ?>
                                        <option value="<?= (int) $func['id'] ?>"
                                            <?= (string) ($equipo['funcionario_id'] ?? '') === (string) $func['id'] ? 'selected' : '' ?>>
                                            <?= $e($func['nombre']) ?>
                                            <?php if (!empty($func['cargo'])): ?>
                                                · <?= $e($func['cargo']) ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Estado -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide"
                                       for="estado">
                                    Estado del equipo <span class="text-red-500">*</span>
                                </label>
                                <select id="estado" name="estado" required
                                        class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg
                                               bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500
                                               focus:border-transparent text-slate-700 cursor-pointer">
                                    <option value="OPERATIVO"        <?= $sel('estado', 'OPERATIVO') ?>>
                                        Operativo
                                    </option>
                                    <option value="EN MANTENIMIENTO" <?= $sel('estado', 'EN MANTENIMIENTO') ?>>
                                        En mantenimiento
                                    </option>
                                </select>
                            </div>

                        </div><!-- /campos izquierda -->
                    </div><!-- /columna izquierda -->


                    <!-- ════════════════════════════════════════════════════════
                         COLUMNA DERECHA — Especificaciones Técnicas de Hardware
                         ════════════════════════════════════════════════════════ -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

                        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center gap-2.5">
                            <div class="w-7 h-7 bg-emerald-100 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                                </svg>
                            </div>
                            <h2 class="text-sm font-semibold text-slate-700">Especificaciones Técnicas de Hardware</h2>
                        </div>

                        <div class="p-6 space-y-4">

                            <!-- Procesador -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide"
                                       for="procesador">
                                    Procesador (CPU)
                                </label>
                                <input type="text" id="procesador" name="procesador"
                                       value="<?= $e($equipo['procesador'] ?? '') ?>"
                                       maxlength="100"
                                       placeholder="Ej: Intel Core i5-10400 @ 2.90GHz"
                                       class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg
                                              bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500
                                              focus:border-transparent placeholder-slate-400 text-slate-700">
                            </div>

                            <!-- Memoria RAM -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide"
                                       for="ram">
                                    Memoria RAM
                                </label>
                                <input type="text" id="ram" name="ram"
                                       value="<?= $e($equipo['ram'] ?? '') ?>"
                                       maxlength="50"
                                       placeholder="Ej: 8 GB DDR4 2666 MHz"
                                       class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg
                                              bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500
                                              focus:border-transparent placeholder-slate-400 text-slate-700">
                            </div>

                            <!-- Almacenamiento -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide"
                                       for="almacenamiento">
                                    Almacenamiento
                                </label>
                                <input type="text" id="almacenamiento" name="almacenamiento"
                                       value="<?= $e($equipo['almacenamiento'] ?? '') ?>"
                                       maxlength="100"
                                       placeholder="Ej: SSD 256 GB NVMe + HDD 1 TB"
                                       class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg
                                              bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500
                                              focus:border-transparent placeholder-slate-400 text-slate-700">
                            </div>

                            <div class="border-t border-slate-100 my-2"></div>

                            <!-- Bloque informativo de auditoría -->
                            <div class="rounded-lg bg-indigo-50 border border-indigo-100 p-4">
                                <p class="text-xs font-semibold text-indigo-700 mb-2 uppercase tracking-wide">
                                    Al guardar se registrará:
                                </p>
                                <ul class="text-xs text-indigo-600 space-y-1">
                                    <li class="flex items-center gap-1.5">
                                        <svg class="w-3 h-3 text-indigo-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        UPDATE en <strong>equipos</strong> y <strong>especificaciones</strong>
                                    </li>
                                    <li class="flex items-center gap-1.5">
                                        <svg class="w-3 h-3 text-indigo-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        INSERT en <strong>historial_cambios</strong> (auditoría)
                                    </li>
                                    <li class="flex items-center gap-1.5">
                                        <svg class="w-3 h-3 text-indigo-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Todo en una <strong>transacción atómica</strong>
                                    </li>
                                </ul>
                            </div>

                        </div><!-- /campos derecha -->
                    </div><!-- /columna derecha -->

                </div><!-- /grid -->

                <!-- ── Acciones del formulario ────────────────────────────────── -->
                <div class="flex items-center gap-3 mt-6 pt-6 border-t border-slate-200">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold
                                   bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white
                                   rounded-lg transition-colors duration-150 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M5 13l4 4L19 7"/>
                        </svg>
                        Guardar cambios
                    </button>
                    <a href="/?page=inventario"
                       class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold
                              text-slate-600 bg-white border border-slate-200 rounded-lg
                              hover:bg-slate-50 transition-colors duration-150 shadow-sm">
                        Cancelar
                    </a>
                    <p class="ml-auto text-xs text-slate-400">
                        Los campos marcados con <span class="text-red-500">*</span> son obligatorios
                    </p>
                </div>

            </form>

        </main>

    </div><!-- /columna principal -->

</body>
</html>

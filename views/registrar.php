<?php
/**
 * registrar.php — Vista del formulario de registro de nuevo equipo.
 * Recibe del EquipoController::mostrarFormulario():
 *   $zonas        → array de zonas para el <select>.
 *   $funcionarios → array de funcionarios para el <select>.
 *   $errores      → array de mensajes de error de validación (vacío en GET).
 *   $datos        → array con los valores previos del POST fallido (vacío en GET).
 * No ejecuta SQL ni lógica de negocio: solo renderiza.
 */

// Alias de escape seguro: ENT_QUOTES protege contra XSS en atributos value=""
$e = fn(mixed $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

// Función: retorna 'selected' si el valor del <option> coincide con el dato previo
$sel = fn(string $campo, string $valor): string =>
    ($datos[$campo] ?? '') === $valor ? 'selected' : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Equipo — Alcaldía Municipal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex h-screen bg-slate-100 overflow-hidden font-sans antialiased">

    <!-- ── Sidebar ──────────────────────────────────────────────────────────── -->
    <?php include __DIR__ . '/modules/sidebar.php'; ?>

    <!-- ── Columna principal ────────────────────────────────────────────────── -->
    <div class="flex-1 min-w-0 overflow-y-auto">

        <main class="p-6">

            <!-- ── Encabezado de página ──────────────────────────────────────── -->
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-xl font-bold text-slate-800">Registrar Nuevo Equipo</h1>
                    <p class="text-sm text-slate-500 mt-0.5">
                        Complete la información del activo y sus especificaciones técnicas
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
                    <div class="flex-shrink-0 mt-0.5">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
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
            <form method="POST" action="/?page=registrar" novalidate>

                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                    <!-- ════════════════════════════════════════════════════════
                         COLUMNA IZQUIERDA — Información General del Activo
                         ════════════════════════════════════════════════════════ -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">

                        <!-- Cabecera de sección -->
                        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center gap-2.5">
                            <div class="w-7 h-7 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            <h2 class="text-sm font-semibold text-slate-700">Información General del Activo</h2>
                        </div>

                        <!-- Campos -->
                        <div class="p-6 space-y-4">

                            <!-- Tipo de equipo -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide"
                                       for="tipo">
                                    Tipo de equipo <span class="text-red-500">*</span>
                                </label>
                                <select id="tipo" name="tipo" required
                                        class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg
                                               bg-white focus:outline-none focus:ring-2 focus:ring-blue-500
                                               focus:border-transparent text-slate-700 cursor-pointer">
                                    <option value="">— Seleccione un tipo —</option>
                                    <?php foreach (['Desktop', 'Laptop','All in One', 'Servidor', 'Impresora', 'Switch', 'Otro'] as $tipo): ?>
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
                                       value="<?= $e($datos['marca'] ?? '') ?>"
                                       maxlength="100"
                                       placeholder="Ej: Dell, HP, Lenovo, Asus..."
                                       class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg
                                              bg-white focus:outline-none focus:ring-2 focus:ring-blue-500
                                              focus:border-transparent placeholder-slate-400 text-slate-700">
                            </div>

                            <!-- Modelo -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide"
                                       for="modelo">
                                    Modelo <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="modelo" name="modelo"
                                       value="<?= $e($datos['modelo'] ?? '') ?>"
                                       maxlength="100"
                                       placeholder="Ej: OptiPlex 7090, ThinkPad E14..."
                                       class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg
                                              bg-white focus:outline-none focus:ring-2 focus:ring-blue-500
                                              focus:border-transparent placeholder-slate-400 text-slate-700">
                            </div>

                            <!-- Número de serie -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide"
                                       for="numero_serie">
                                    Número de serie
                                    <span class="text-slate-400 font-normal normal-case ml-1">(opcional, único)</span>
                                </label>
                                <input type="text" id="numero_serie" name="numero_serie"
                                       value="<?= $e($datos['numero_serie'] ?? '') ?>"
                                       maxlength="100"
                                       placeholder="Ej: MXL1234ABCD"
                                       class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg
                                              bg-white focus:outline-none focus:ring-2 focus:ring-blue-500
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
                                               bg-white focus:outline-none focus:ring-2 focus:ring-blue-500
                                               focus:border-transparent text-slate-700 cursor-pointer">
                                    <option value="">— Seleccione una zona —</option>
                                    <?php foreach ($zonas as $zona): ?>
                                        <option value="<?= (int) $zona['id'] ?>"
                                            <?= ((string)($datos['zona_id'] ?? '') === (string) $zona['id']) ? 'selected' : '' ?>>
                                            <?= $e($zona['zona_nombre']) ?>
                                            <?php if (!empty($zona['sede_nombre'])): ?>
                                                — <?= $e($zona['sede_nombre']) ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($zonas)): ?>
                                    <p class="text-amber-600 text-xs mt-1.5">
                                        No hay zonas disponibles. Registre al menos una zona antes de continuar.
                                    </p>
                                <?php endif; ?>
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
                                               bg-white focus:outline-none focus:ring-2 focus:ring-blue-500
                                               focus:border-transparent text-slate-700 cursor-pointer">
                                    <option value="">— Sin asignar —</option>
                                    <?php foreach ($funcionarios as $func): ?>
                                        <option value="<?= (int) $func['id'] ?>"
                                            <?= ((string)($datos['funcionario_id'] ?? '') === (string) $func['id']) ? 'selected' : '' ?>>
                                            <?= $e($func['nombre']) ?>
                                            <?php if (!empty($func['cargo'])): ?>
                                                · <?= $e($func['cargo']) ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Estado del equipo -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide"
                                       for="estado">
                                    Estado del equipo <span class="text-red-500">*</span>
                                </label>
                                <select id="estado" name="estado" required
                                        class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg
                                               bg-white focus:outline-none focus:ring-2 focus:ring-blue-500
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

                        <!-- Cabecera de sección -->
                        <div class="px-6 py-4 bg-slate-50 border-b border-slate-200 flex items-center gap-2.5">
                            <div class="w-7 h-7 bg-emerald-100 rounded-lg flex items-center justify-center">
                                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/>
                                </svg>
                            </div>
                            <h2 class="text-sm font-semibold text-slate-700">Especificaciones Técnicas de Hardware</h2>
                        </div>

                        <!-- Campos -->
                        <div class="p-6 space-y-4">

                            <!-- Aviso: campos opcionales -->
                            <div class="flex items-start gap-2 p-3 bg-slate-50 rounded-lg border border-slate-200">
                                <svg class="w-4 h-4 text-slate-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p class="text-xs text-slate-500">
                                    Todos los campos de especificaciones son opcionales.
                                    Pueden completarse o editarse posteriormente desde la ficha del equipo.
                                </p>
                            </div>

                            <!-- Procesador -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide"
                                       for="procesador">
                                    Procesador (CPU)
                                </label>
                                <input type="text" id="procesador" name="procesador"
                                       value="<?= $e($datos['procesador'] ?? '') ?>"
                                       maxlength="100"
                                       placeholder="Ej: Intel Core i5-10400 @ 2.90GHz"
                                       class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg
                                              bg-white focus:outline-none focus:ring-2 focus:ring-blue-500
                                              focus:border-transparent placeholder-slate-400 text-slate-700">
                            </div>

                            <!-- Memoria RAM -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide"
                                       for="ram">
                                    Memoria RAM
                                </label>
                                <input type="text" id="ram" name="ram"
                                       value="<?= $e($datos['ram'] ?? '') ?>"
                                       maxlength="50"
                                       placeholder="Ej: 8 GB DDR4 2666 MHz"
                                       class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg
                                              bg-white focus:outline-none focus:ring-2 focus:ring-blue-500
                                              focus:border-transparent placeholder-slate-400 text-slate-700">
                            </div>

                            <!-- Almacenamiento -->
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide"
                                       for="almacenamiento">
                                    Almacenamiento
                                </label>
                                <input type="text" id="almacenamiento" name="almacenamiento"
                                       value="<?= $e($datos['almacenamiento'] ?? '') ?>"
                                       maxlength="100"
                                       placeholder="Ej: SSD 256 GB NVMe + HDD 1 TB"
                                       class="w-full px-3 py-2.5 text-sm border border-slate-200 rounded-lg
                                              bg-white focus:outline-none focus:ring-2 focus:ring-blue-500
                                              focus:border-transparent placeholder-slate-400 text-slate-700">
                            </div>

                            <!-- Separador visual -->
                            <div class="border-t border-slate-100 my-2"></div>

                            <!-- Resumen de lo que se guardará (informativo) -->
                            <div class="rounded-lg bg-blue-50 border border-blue-100 p-4">
                                <p class="text-xs font-semibold text-blue-700 mb-2 uppercase tracking-wide">
                                    Al guardar se creará:
                                </p>
                                <ul class="text-xs text-blue-600 space-y-1">
                                    <li class="flex items-center gap-1.5">
                                        <svg class="w-3 h-3 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Un registro en la tabla <strong>equipos</strong>
                                    </li>
                                    <li class="flex items-center gap-1.5">
                                        <svg class="w-3 h-3 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Un registro vinculado en <strong>especificaciones</strong>
                                    </li>
                                    <li class="flex items-center gap-1.5">
                                        <svg class="w-3 h-3 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        Ambos dentro de una <strong>transacción atómica</strong>
                                    </li>
                                </ul>
                            </div>

                        </div><!-- /campos derecha -->
                    </div><!-- /columna derecha -->

                </div><!-- /grid dos columnas -->

                <!-- ── Acciones del formulario ────────────────────────────────── -->
                <div class="flex items-center gap-3 mt-6 pt-6 border-t border-slate-200">
                    <button type="submit"
                            class="inline-flex items-center gap-2 px-6 py-2.5 text-sm font-semibold
                                   bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-lg
                                   transition-colors duration-150 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                        </svg>
                        Guardar equipo
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

            </form><!-- /formulario -->

        </main><!-- /contenido desplazable -->

    </div><!-- /columna principal -->

</body>
</html>

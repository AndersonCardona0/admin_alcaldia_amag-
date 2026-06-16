<?php

declare(strict_types=1);

/**
 * funcionarios.php — Panel de gestión de funcionarios.
 * Recibe de FuncionarioController::mostrar():
 *   $funcionarios → array con todos los registros (id, nombre, cargo, zona_nombre, email)
 *   $total        → int con el conteo total para el badge del encabezado
 *   $zonas        → array con todas las zonas (id, nombre) para poblar el <select>
 */

// Alias de escape seguro
$e = fn(mixed $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Funcionarios — Alcaldía Municipal</title>
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
                <h1 class="text-xl font-bold text-slate-800">Gestión de Funcionarios</h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    Administre los servidores públicos responsables de activos tecnológicos
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
                        <h2 class="text-sm font-bold text-slate-700">Nuevo Funcionario</h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Complete los campos y guarde para agregar un servidor público
                        </p>
                    </div>

                    <form method="POST" action="/?page=funcionarios" novalidate>
                        <input type="hidden" name="csrf_token"
                               value="<?= $e($_SESSION['csrf_token'] ?? '') ?>">

                        <!-- Nombre completo -->
                        <div class="mb-4">
                            <label for="nombre"
                                   class="block text-xs font-semibold text-slate-600
                                          uppercase tracking-wide mb-1.5">
                                Nombre completo <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="nombre"
                                   name="nombre"
                                   maxlength="150"
                                   required
                                   placeholder="Ej: Dra. Sandra Gómez"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                                          bg-white text-slate-800 placeholder-slate-400
                                          focus:outline-none focus:ring-2 focus:ring-slate-400
                                          transition-shadow duration-150">
                        </div>

                        <!-- Cargo -->
                        <div class="mb-4">
                            <label for="cargo"
                                   class="block text-xs font-semibold text-slate-600
                                          uppercase tracking-wide mb-1.5">
                                Cargo <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="cargo"
                                   name="cargo"
                                   maxlength="120"
                                   required
                                   placeholder="Ej: Secretaria de Hacienda"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                                          bg-white text-slate-800 placeholder-slate-400
                                          focus:outline-none focus:ring-2 focus:ring-slate-400
                                          transition-shadow duration-150">
                        </div>

                        <!-- Zona (selector dinámico poblado desde el controlador) -->
                        <div class="mb-4">
                            <label for="zona_id"
                                   class="block text-xs font-semibold text-slate-600
                                          uppercase tracking-wide mb-1.5">
                                Zona <span class="text-red-500">*</span>
                            </label>
                            <select id="zona_id"
                                    name="zona_id"
                                    required
                                    class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                                           bg-white text-slate-700 cursor-pointer
                                           focus:outline-none focus:ring-2 focus:ring-slate-400
                                           transition-shadow duration-150">
                                <option value="">— Seleccione una zona —</option>
                                <?php foreach ($zonas as $zona): ?>
                                    <option value="<?= $e((string) $zona['id']) ?>">
                                        <?= htmlspecialchars($zona['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Correo electrónico (opcional) -->
                        <div class="mb-5">
                            <label for="email"
                                   class="block text-xs font-semibold text-slate-600
                                          uppercase tracking-wide mb-1.5">
                                Correo electrónico
                                <span class="font-normal text-slate-400 normal-case
                                             tracking-normal ml-1">(opcional)</span>
                            </label>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   maxlength="180"
                                   placeholder="Ej: s.gomez@amagaantioquia.gov.co"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                                          bg-white text-slate-800 placeholder-slate-400
                                          focus:outline-none focus:ring-2 focus:ring-slate-400
                                          transition-shadow duration-150">
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
                            Registrar Funcionario
                        </button>
                    </form>

                </div><!-- /formulario -->

                <!-- ══════════════════════════════════════════════════════════════
                     COLUMNA DERECHA (×2) — Tabla de funcionarios
                ════════════════════════════════════════════════════════════════ -->
                <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm">

                    <!-- Encabezado de tabla -->
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center
                                justify-between">
                        <div>
                            <h2 class="text-sm font-bold text-slate-700">
                                Servidores Públicos Registrados
                            </h2>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Funcionarios disponibles para asignar a equipos del inventario
                            </p>
                        </div>
                        <span class="text-xs font-semibold text-slate-500 bg-slate-100
                                     px-2.5 py-1 rounded-full">
                            <?= $e((string) $total) ?>
                            <?= $total === 1 ? 'funcionario' : 'funcionarios' ?>
                        </span>
                    </div>

                    <?php if (empty($funcionarios)): ?>
                        <!-- Estado vacío -->
                        <div class="p-10 flex flex-col items-center justify-center text-center gap-3">
                            <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center
                                        justify-center">
                                <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          stroke-width="2"
                                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-600">
                                    Sin funcionarios registrados
                                </p>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    Use el formulario para agregar el primer servidor público
                                </p>
                            </div>
                        </div>

                    <?php else: ?>
                        <!-- Tabla con scroll vertical cuando hay muchos registros -->
                        <div class="overflow-x-auto overflow-y-auto max-h-[calc(100vh-20rem)]">
                            <table class="w-full text-sm">
                                <thead class="sticky top-0 bg-slate-50 z-10">
                                    <tr class="text-left text-xs font-semibold text-slate-500
                                               uppercase tracking-wider">
                                        <th class="px-5 py-3 border-b border-slate-200 w-8">
                                            #
                                        </th>
                                        <th class="px-4 py-3 border-b border-slate-200">
                                            Nombre
                                        </th>
                                        <th class="px-4 py-3 border-b border-slate-200">
                                            Cargo
                                        </th>
                                        <th class="px-4 py-3 border-b border-slate-200">
                                            Zona
                                        </th>
                                        <th class="px-4 py-3 border-b border-slate-200">
                                            Correo electrónico
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($funcionarios as $idx => $f): ?>
                                        <tr class="hover:bg-slate-50 transition-colors duration-100">
                                            <td class="px-5 py-3 text-slate-400 text-xs font-mono">
                                                <?= $e((string) ($idx + 1)) ?>
                                            </td>
                                            <td class="px-4 py-3 font-semibold text-slate-800">
                                                <?= $e($f['nombre']) ?>
                                            </td>
                                            <td class="px-4 py-3 text-slate-600">
                                                <?= $e($f['cargo']) ?>
                                            </td>
                                            <td class="px-4 py-3 text-slate-500">
                                                <?= $e($f['zona_nombre']) ?>
                                            </td>
                                            <td class="px-4 py-3">
                                                <?php if (!empty($f['email'])): ?>
                                                    <a href="mailto:<?= $e($f['email']) ?>"
                                                       class="text-slate-600 hover:text-slate-900
                                                              hover:underline text-xs font-mono
                                                              transition-colors duration-100">
                                                        <?= $e($f['email']) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-slate-300 text-xs">—</span>
                                                <?php endif; ?>
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

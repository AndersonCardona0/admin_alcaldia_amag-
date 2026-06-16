<?php

declare(strict_types=1);

/**
 * ajustes.php — Panel de ajustes del sistema y gestión de cuentas.
 *
 * Variables recibidas de AjustesController::mostrarPanel():
 *   $usuarios   → array  Listado de cuentas (vacío para rol AUDITOR)
 *   $tabActiva  → string 'usuarios' | 'seguridad'
 */

// Alias de escape seguro — usado en TODAS las salidas dinámicas
$e = fn(mixed $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');

$esAdmin = esAdministrador();

// Badge de color según rol de usuario
$badgeRol = static fn(string $rol): string => match ($rol) {
    'ADMINISTRADOR' => 'bg-blue-100 text-blue-700 ring-1 ring-blue-200',
    'AUDITOR'       => 'bg-violet-100 text-violet-700 ring-1 ring-violet-200',
    default         => 'bg-slate-100 text-slate-500 ring-1 ring-slate-200',
};

// Badge de color según estado activo
$badgeEstado = static fn(int $activo): string => $activo === 1
    ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200'
    : 'bg-red-100 text-red-600 ring-1 ring-red-200';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajustes — Alcaldía Municipal</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="flex h-screen bg-slate-100 overflow-hidden font-sans antialiased">

    <!-- ── Sidebar ──────────────────────────────────────────────────────────── -->
    <?php include __DIR__ . '/modules/sidebar.php'; ?>

    <!-- ── Columna principal ─────────────────────────────────────────────────── -->
    <div class="flex-1 min-w-0 overflow-y-auto">

        <main class="p-6">

            <!-- Encabezado de sección -->
            <div class="mb-6">
                <h1 class="text-xl font-bold text-slate-800">Ajustes del Sistema</h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    <?= $esAdmin
                        ? 'Gestión de cuentas de acceso y configuración de seguridad personal'
                        : 'Configuración de seguridad de su cuenta'
                    ?>
                </p>
            </div>

            <!-- ── Pestañas de navegación (solo ADMINISTRADOR ve ambas) ────────── -->
            <?php if ($esAdmin): ?>
            <div class="flex items-center gap-1 bg-slate-200/60 p-1 rounded-xl mb-6 w-fit">
                <button type="button"
                        id="btn-tab-usuarios"
                        onclick="switchTab('usuarios')"
                        class="px-5 py-2 text-sm font-medium rounded-lg transition-all duration-150
                               focus:outline-none focus:ring-2 focus:ring-slate-400">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        Gestión de Usuarios
                    </span>
                </button>
                <button type="button"
                        id="btn-tab-seguridad"
                        onclick="switchTab('seguridad')"
                        class="px-5 py-2 text-sm font-medium rounded-lg transition-all duration-150
                               focus:outline-none focus:ring-2 focus:ring-slate-400">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Seguridad de la Cuenta
                    </span>
                </button>
            </div>
            <?php endif; ?>

            <!-- ════════════════════════════════════════════════════════════════
                 PANEL A — Gestión de Usuarios (solo ADMINISTRADOR)
            ════════════════════════════════════════════════════════════════ -->
            <?php if ($esAdmin): ?>
            <div id="panel-usuarios" class="<?= $tabActiva === 'seguridad' ? 'hidden' : '' ?>">

                <!-- Flash messages de la sección de usuarios -->
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

                <!-- Grid: formulario de creación + tabla de usuarios -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

                    <!-- ── Formulario: Crear nuevo usuario ─────────────────────── -->
                    <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5">

                        <div class="mb-5">
                            <h2 class="text-sm font-bold text-slate-700">Nueva Cuenta</h2>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Cree accesos con rol ADMINISTRADOR o AUDITOR
                            </p>
                        </div>

                        <form method="POST" action="/?page=ajustes" novalidate>
                            <input type="hidden" name="csrf_token"
                                   value="<?= $e($_SESSION['csrf_token'] ?? '') ?>">
                            <input type="hidden" name="action" value="crear_usuario">

                            <!-- Nombre completo -->
                            <div class="mb-4">
                                <label for="nombre"
                                       class="block text-xs font-semibold text-slate-600
                                              uppercase tracking-wide mb-1.5">
                                    Nombre completo <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       id="nombre" name="nombre"
                                       maxlength="120" required
                                       placeholder="Ej: Dr. Carlos Restrepo"
                                       class="w-full px-3 py-2 text-sm border border-slate-200
                                              rounded-lg bg-white text-slate-800
                                              placeholder-slate-400 focus:outline-none
                                              focus:ring-2 focus:ring-slate-400
                                              transition-shadow duration-150">
                            </div>

                            <!-- Nombre de usuario -->
                            <div class="mb-4">
                                <label for="usuario"
                                       class="block text-xs font-semibold text-slate-600
                                              uppercase tracking-wide mb-1.5">
                                    Usuario <span class="text-red-500">*</span>
                                </label>
                                <input type="text"
                                       id="usuario" name="usuario"
                                       maxlength="50" required
                                       pattern="[a-zA-Z0-9._-]{3,50}"
                                       placeholder="Ej: c.restrepo"
                                       autocomplete="off"
                                       class="w-full px-3 py-2 text-sm border border-slate-200
                                              rounded-lg bg-white text-slate-800 font-mono
                                              placeholder-slate-400 focus:outline-none
                                              focus:ring-2 focus:ring-slate-400
                                              transition-shadow duration-150">
                                <p class="text-xs text-slate-400 mt-1">
                                    3–50 caracteres: letras, números, puntos y guiones
                                </p>
                            </div>

                            <!-- Contraseña inicial -->
                            <div class="mb-4">
                                <label for="password"
                                       class="block text-xs font-semibold text-slate-600
                                              uppercase tracking-wide mb-1.5">
                                    Contraseña inicial <span class="text-red-500">*</span>
                                </label>
                                <input type="password"
                                       id="password" name="password"
                                       minlength="8" required
                                       autocomplete="new-password"
                                       placeholder="Mínimo 8 caracteres"
                                       class="w-full px-3 py-2 text-sm border border-slate-200
                                              rounded-lg bg-white text-slate-800
                                              placeholder-slate-400 focus:outline-none
                                              focus:ring-2 focus:ring-slate-400
                                              transition-shadow duration-150">
                            </div>

                            <!-- Rol -->
                            <div class="mb-5">
                                <label for="rol"
                                       class="block text-xs font-semibold text-slate-600
                                              uppercase tracking-wide mb-1.5">
                                    Rol <span class="text-red-500">*</span>
                                </label>
                                <select id="rol" name="rol" required
                                        class="w-full px-3 py-2 text-sm border border-slate-200
                                               rounded-lg bg-white text-slate-700 cursor-pointer
                                               focus:outline-none focus:ring-2 focus:ring-slate-400
                                               transition-shadow duration-150">
                                    <option value="">— Seleccione un rol —</option>
                                    <option value="ADMINISTRADOR">ADMINISTRADOR</option>
                                    <option value="AUDITOR">AUDITOR</option>
                                </select>
                                <p class="text-xs text-slate-400 mt-1">
                                    AUDITOR: solo lectura y reportes
                                </p>
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
                                Crear Cuenta
                            </button>
                        </form>

                    </div><!-- /formulario crear usuario -->

                    <!-- ── Tabla de usuarios ────────────────────────────────────── -->
                    <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm">

                        <div class="px-5 py-4 border-b border-slate-100 flex items-center
                                    justify-between">
                            <div>
                                <h2 class="text-sm font-bold text-slate-700">
                                    Cuentas de Acceso
                                </h2>
                                <p class="text-xs text-slate-400 mt-0.5">
                                    Usuarios con acceso al sistema de inventario
                                </p>
                            </div>
                            <span class="text-xs font-semibold text-slate-500 bg-slate-100
                                         px-2.5 py-1 rounded-full">
                                <?= $e((string) count($usuarios)) ?>
                                <?= count($usuarios) === 1 ? 'cuenta' : 'cuentas' ?>
                            </span>
                        </div>

                        <?php if (empty($usuarios)): ?>
                            <div class="p-10 flex flex-col items-center justify-center text-center gap-3">
                                <div class="w-12 h-12 bg-slate-100 rounded-xl flex items-center
                                            justify-center">
                                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor"
                                         viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/>
                                    </svg>
                                </div>
                                <p class="text-sm font-semibold text-slate-600">Sin cuentas registradas</p>
                            </div>

                        <?php else: ?>
                            <div class="overflow-x-auto overflow-y-auto max-h-[calc(100vh-22rem)]">
                                <table class="w-full text-sm">
                                    <thead class="sticky top-0 bg-slate-50 z-10">
                                        <tr class="text-left text-xs font-semibold text-slate-500
                                                   uppercase tracking-wider">
                                            <th class="px-5 py-3 border-b border-slate-200">Nombre</th>
                                            <th class="px-4 py-3 border-b border-slate-200">Usuario</th>
                                            <th class="px-4 py-3 border-b border-slate-200">Rol</th>
                                            <th class="px-4 py-3 border-b border-slate-200">Estado</th>
                                            <th class="px-4 py-3 border-b border-slate-200 text-center">
                                                Acción
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        <?php foreach ($usuarios as $usr): ?>
                                            <?php
                                                $esPropiaCtaAdmin = ((int) $usr['id'] === (int) $_SESSION['usuario_id']);
                                                $activoActual     = (int) $usr['activo'];
                                                $nuevoEstado      = $activoActual === 1 ? 0 : 1;
                                            ?>
                                            <tr class="hover:bg-slate-50 transition-colors duration-100
                                                       <?= $activoActual === 0 ? 'opacity-60' : '' ?>">

                                                <!-- Nombre -->
                                                <td class="px-5 py-3 font-semibold text-slate-800">
                                                    <div class="flex items-center gap-2">
                                                        <?= $e($usr['nombre_completo']) ?>
                                                        <?php if ($esPropiaCtaAdmin): ?>
                                                            <span class="text-xs bg-blue-50 text-blue-600
                                                                         border border-blue-100 px-1.5 py-0.5
                                                                         rounded font-medium">
                                                                (yo)
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>

                                                <!-- Usuario login -->
                                                <td class="px-4 py-3 font-mono text-xs text-slate-500">
                                                    <?= $e($usr['usuario']) ?>
                                                </td>

                                                <!-- Rol (badge) -->
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2.5 py-0.5
                                                                 rounded-full text-xs font-semibold
                                                                 <?= $e($badgeRol($usr['rol'])) ?>">
                                                        <?= $e($usr['rol']) ?>
                                                    </span>
                                                </td>

                                                <!-- Estado (badge) -->
                                                <td class="px-4 py-3">
                                                    <span class="inline-flex items-center px-2.5 py-0.5
                                                                 rounded-full text-xs font-semibold
                                                                 <?= $e($badgeEstado($activoActual)) ?>">
                                                        <?= $activoActual === 1 ? 'Activo' : 'Inactivo' ?>
                                                    </span>
                                                </td>

                                                <!-- Toggle de estado -->
                                                <td class="px-4 py-3 text-center">
                                                    <?php if ($esPropiaCtaAdmin): ?>
                                                        <!-- El admin no puede desactivarse a sí mismo -->
                                                        <span title="No puede desactivar su propia cuenta"
                                                              class="inline-flex items-center px-3 py-1.5
                                                                     text-xs font-medium text-slate-300
                                                                     bg-slate-50 border border-slate-200
                                                                     rounded-lg cursor-not-allowed">
                                                            Protegido
                                                        </span>
                                                    <?php else: ?>
                                                        <form method="POST" action="/?page=ajustes"
                                                              class="inline">
                                                            <input type="hidden" name="csrf_token"
                                                                   value="<?= $e($_SESSION['csrf_token'] ?? '') ?>">
                                                            <input type="hidden" name="action"
                                                                   value="toggle_estado">
                                                            <input type="hidden" name="usuario_id"
                                                                   value="<?= $e((string) $usr['id']) ?>">
                                                            <input type="hidden" name="activo"
                                                                   value="<?= $e((string) $nuevoEstado) ?>">
                                                            <button type="submit"
                                                                    class="inline-flex items-center px-3 py-1.5
                                                                           text-xs font-semibold rounded-lg
                                                                           transition-colors duration-150
                                                                           <?= $activoActual === 1
                                                                               ? 'text-red-600 bg-red-50 hover:bg-red-100 border border-red-200'
                                                                               : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200' ?>">
                                                                <?= $activoActual === 1 ? 'Desactivar' : 'Activar' ?>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>

                    </div><!-- /tabla usuarios -->

                </div><!-- /grid panel A -->

            </div><!-- /#panel-usuarios -->
            <?php endif; // fin $esAdmin — AUDITOR no ve nada de esta sección ?>

            <!-- ════════════════════════════════════════════════════════════════
                 PANEL B — Seguridad de la Cuenta (ADMINISTRADOR y AUDITOR)
            ════════════════════════════════════════════════════════════════ -->
            <div id="panel-seguridad" class="<?= ($esAdmin && $tabActiva !== 'seguridad') ? 'hidden' : '' ?>">

                <!-- Flash messages exclusivos de la sección de contraseña -->
                <?php if (!empty($_SESSION['flash_pwd_success'])): ?>
                    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 mb-5
                                flex items-start gap-3">
                        <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none"
                             stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-emerald-700 font-medium text-sm">
                            <?= $e($_SESSION['flash_pwd_success']) ?>
                        </p>
                    </div>
                    <?php unset($_SESSION['flash_pwd_success']); ?>
                <?php endif; ?>

                <?php if (!empty($_SESSION['flash_pwd_error'])): ?>
                    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-5
                                flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none"
                             stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-red-700 font-medium text-sm">
                            <?= $e($_SESSION['flash_pwd_error']) ?>
                        </p>
                    </div>
                    <?php unset($_SESSION['flash_pwd_error']); ?>
                <?php endif; ?>

                <!-- Tarjeta de cambio de contraseña -->
                <div class="max-w-md bg-white rounded-xl border border-slate-200 shadow-sm p-6">

                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 bg-slate-100 rounded-xl flex items-center
                                    justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-slate-500" fill="none" stroke="currentColor"
                                 viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-slate-700">
                                Cambiar Contraseña
                            </h2>
                            <p class="text-xs text-slate-400 mt-0.5">
                                <?= $e($_SESSION['usuario_nombre'] ?? '') ?>
                                <span class="mx-1 text-slate-300">·</span>
                                <?= $e($_SESSION['usuario_rol'] ?? '') ?>
                            </p>
                        </div>
                    </div>

                    <form method="POST" action="/?page=ajustes" novalidate autocomplete="off">
                        <input type="hidden" name="csrf_token"
                               value="<?= $e($_SESSION['csrf_token'] ?? '') ?>">
                        <input type="hidden" name="action" value="cambiar_pass">

                        <!-- Contraseña actual -->
                        <div class="mb-4">
                            <label for="password_actual"
                                   class="block text-xs font-semibold text-slate-600
                                          uppercase tracking-wide mb-1.5">
                                Contraseña actual <span class="text-red-500">*</span>
                            </label>
                            <input type="password"
                                   id="password_actual"
                                   name="password_actual"
                                   required
                                   autocomplete="current-password"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                                          bg-white text-slate-800 focus:outline-none
                                          focus:ring-2 focus:ring-slate-400
                                          transition-shadow duration-150">
                        </div>

                        <!-- Separador visual -->
                        <div class="border-t border-slate-100 my-4"></div>

                        <!-- Nueva contraseña -->
                        <div class="mb-4">
                            <label for="nueva_password"
                                   class="block text-xs font-semibold text-slate-600
                                          uppercase tracking-wide mb-1.5">
                                Nueva contraseña <span class="text-red-500">*</span>
                            </label>
                            <input type="password"
                                   id="nueva_password"
                                   name="nueva_password"
                                   minlength="8"
                                   required
                                   autocomplete="new-password"
                                   placeholder="Mínimo 8 caracteres"
                                   class="w-full px-3 py-2 text-sm border border-slate-200 rounded-lg
                                          bg-white text-slate-800 placeholder-slate-400
                                          focus:outline-none focus:ring-2 focus:ring-slate-400
                                          transition-shadow duration-150">
                        </div>

                        <!-- Confirmar nueva contraseña -->
                        <div class="mb-6">
                            <label for="confirmar_password"
                                   class="block text-xs font-semibold text-slate-600
                                          uppercase tracking-wide mb-1.5">
                                Confirmar nueva contraseña <span class="text-red-500">*</span>
                            </label>
                            <input type="password"
                                   id="confirmar_password"
                                   name="confirmar_password"
                                   minlength="8"
                                   required
                                   autocomplete="new-password"
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
                                      d="M5 13l4 4L19 7"/>
                            </svg>
                            Actualizar Contraseña
                        </button>
                    </form>

                </div><!-- /card cambio de contraseña -->

            </div><!-- /#panel-seguridad -->

        </main>
    </div>

    <!-- ── Script de tabs (solo para ADMINISTRADOR) ──────────────────────────── -->
    <?php if ($esAdmin): ?>
    <script>
    (function () {
        const TAB_ACTIVE   = ['bg-white', 'text-slate-800', 'shadow-sm', 'font-semibold'];
        const TAB_INACTIVE = ['text-slate-500'];

        function switchTab(tab) {
            ['usuarios', 'seguridad'].forEach(function (id) {
                const panel = document.getElementById('panel-' + id);
                const btn   = document.getElementById('btn-tab-' + id);
                if (!panel || !btn) return;

                if (id === tab) {
                    panel.classList.remove('hidden');
                    btn.classList.add(...TAB_ACTIVE);
                    btn.classList.remove(...TAB_INACTIVE);
                } else {
                    panel.classList.add('hidden');
                    btn.classList.remove(...TAB_ACTIVE);
                    btn.classList.add(...TAB_INACTIVE);
                }
            });
        }

        // Exposición global para los atributos onclick inline
        window.switchTab = switchTab;

        // Estado inicial determinado por el servidor (parámetro ?tab=)
        switchTab('<?= $e($tabActiva) ?>');
    }());
    </script>
    <?php endif; ?>

</body>
</html>

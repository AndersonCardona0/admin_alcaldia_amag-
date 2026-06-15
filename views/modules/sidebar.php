<?php
/**
 * sidebar.php — Componente parcial: menú de navegación lateral.
 * Se incluye dentro del layout flex de dashboard.php.
 * No contiene etiquetas <html>, <head> ni <body>.
 */
$paginaActiva = $_GET['page'] ?? 'dashboard';

// Función auxiliar: retorna las clases Tailwind según si el ítem está activo
$claseNavItem = fn(string $pagina): string => $paginaActiva === $pagina
    ? 'flex items-center gap-3 px-3 py-2.5 rounded-lg bg-slate-700 text-white'
    : 'flex items-center gap-3 px-3 py-2.5 rounded-lg text-slate-300 hover:bg-slate-700 hover:text-white transition-colors duration-150';
?>

<aside class="w-64 bg-slate-800 flex flex-col h-screen flex-shrink-0 shadow-xl">

    <!-- Encabezado / Marca -->
    <div class="flex items-center gap-3 px-5 py-4 bg-slate-900 flex-shrink-0">
        <div class="w-9 h-9 bg-blue-500 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
        </div>
        <div>
            <p class="text-white font-bold text-sm leading-tight">Alcaldía Municipal</p>
            <p class="text-slate-400 text-xs mt-0.5">Inventario Tecnológico</p>
        </div>
    </div>

    <!-- Separador de sección -->
    <div class="px-4 pt-5 pb-2">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-widest">Menú Principal</p>
    </div>

    <!-- Navegación -->
    <nav class="flex-1 px-3 space-y-0.5 overflow-y-auto">

        <!-- Dashboard -->
        <a href="/?page=dashboard" class="<?= $claseNavItem('dashboard') ?>">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
            </svg>
            <span class="text-sm font-medium">Dashboard</span>
        </a>

        <!-- Zonas: solo ADMINISTRADOR -->
        <?php if (esAdministrador()): ?>
        <a href="/?page=zonas" class="<?= $claseNavItem('zonas') ?>">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="text-sm font-medium">Zonas</span>
        </a>
        <?php endif; ?>

        <!-- Funcionarios: solo ADMINISTRADOR -->
        <?php if (esAdministrador()): ?>
        <a href="/?page=funcionarios" class="<?= $claseNavItem('funcionarios') ?>">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="text-sm font-medium">Funcionarios</span>
        </a>
        <?php endif; ?>

        <!-- Inventario -->
        <a href="/?page=inventario" class="<?= $claseNavItem('inventario') ?>">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span class="text-sm font-medium">Inventario</span>
        </a>

        <!-- Reportes: todos los usuarios autenticados -->
        <a href="/?page=reportes" class="<?= $claseNavItem('reportes') ?>">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="text-sm font-medium">Reportes</span>
        </a>

        <!-- Ajustes: solo ADMINISTRADOR -->
        <?php if (esAdministrador()): ?>
        <a href="/?page=ajustes" class="<?= $claseNavItem('ajustes') ?>">
            <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span class="text-sm font-medium">Ajustes</span>
        </a>
        <?php endif; ?>

    </nav>

    <!-- Pie del sidebar: usuario activo + registrar + cerrar sesión -->
    <div class="p-3 flex-shrink-0 border-t border-slate-700 space-y-2">

        <!-- Panel del usuario autenticado -->
        <div class="flex items-center gap-2.5 px-2.5 py-2 rounded-lg bg-slate-900">
            <div class="w-7 h-7 bg-slate-700 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-3.5 h-3.5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-slate-200 truncate">
                    <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8') ?>
                </p>
                <p class="text-xs text-slate-500 truncate">
                    <?= htmlspecialchars($_SESSION['usuario_rol'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
        </div>

        <!-- Registrar nuevo equipo -->
        <a href="/?page=registrar"
           class="flex items-center justify-center gap-2 w-full px-4 py-2.5
                  bg-emerald-500 hover:bg-emerald-400 active:bg-emerald-600
                  text-white rounded-lg text-sm font-semibold
                  transition-colors duration-150 shadow-sm">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Registrar Equipo
        </a>

        <!-- Cierre de sesión (POST + CSRF — previene forced-logout via enlace GET) -->
        <form method="POST" action="/?page=logout">
            <input type="hidden" name="csrf_token"
                   value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit"
                    class="flex items-center justify-center gap-2 w-full px-4 py-2
                           text-slate-500 hover:text-red-300 hover:bg-slate-700/60
                           rounded-lg text-xs font-medium transition-colors duration-150">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Cerrar sesión
            </button>
        </form>

        <p class="text-center text-slate-600 text-xs pt-1">Sistema v1.0.0</p>
    </div>

</aside>

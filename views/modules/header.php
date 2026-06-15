<?php
/**
 * header.php — Componente parcial: barra superior de la aplicación.
 * Contiene el buscador global, el ícono de notificaciones y el perfil del usuario.
 * Se incluye dentro de la columna principal del layout de dashboard.php.
 * No contiene etiquetas <html>, <head> ni <body>.
 */
?>

<header class="bg-white border-b border-slate-200 px-6 py-3
               flex items-center justify-between flex-shrink-0 shadow-sm z-10">

    <!-- Buscador global -->
    <div class="relative w-80">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="search"
               id="buscadorGlobal"
               placeholder="Buscar por ID o Responsable..."
               class="w-full pl-9 pr-4 py-2 text-sm bg-slate-50 border border-slate-200
                      rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500
                      focus:border-transparent placeholder-slate-400 text-slate-700">
    </div>

    <!-- Controles del lado derecho -->
    <div class="flex items-center gap-3">

        <!-- Botón de notificaciones con indicador activo -->
        <button type="button"
                title="Notificaciones"
                class="relative p-2 text-slate-500 hover:text-slate-700
                       hover:bg-slate-100 rounded-lg transition-colors duration-150">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <!-- Indicador de notificación pendiente -->
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full ring-2 ring-white"></span>
        </button>

        <!-- Divisor visual -->
        <div class="w-px h-7 bg-slate-200"></div>

        <!-- Perfil del usuario -->
        <div class="flex items-center gap-2.5 cursor-pointer group">
            <!-- Avatar con inicial -->
            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center
                        ring-2 ring-transparent group-hover:ring-blue-300 transition-all duration-150">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <div class="hidden sm:block leading-tight">
                <p class="text-sm font-semibold text-slate-700">Administrador</p>
                <p class="text-xs text-slate-400">Sistema TI</p>
            </div>
            <!-- Chevron desplegable (futuro menú de usuario) -->
            <svg class="w-4 h-4 text-slate-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </div>

    </div>
</header>

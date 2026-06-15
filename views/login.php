<?php
/**
 * login.php — Pantalla de inicio de sesión institucional.
 * Vista standalone: no incluye sidebar ni header (acceso pre-autenticación).
 * Recibe del AuthController (vía sesión):
 *   $_SESSION['flash_login_error'] — mensaje de error de credenciales
 */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema — Alcaldía Municipal de Amagá</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="min-h-screen bg-slate-100 flex items-center justify-center p-4 font-sans antialiased">

    <div class="w-full max-w-sm">

        <!-- Marca institucional -->
        <div class="flex flex-col items-center mb-8">
            <div class="w-14 h-14 bg-slate-800 rounded-2xl flex items-center justify-center shadow-lg mb-4">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
            </div>
            <h1 class="text-xl font-bold text-slate-800 text-center">Alcaldía Municipal</h1>
            <p class="text-sm text-slate-500 mt-0.5 text-center">Amagá, Antioquia — Colombia</p>
        </div>

        <!-- Tarjeta del formulario -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm px-8 py-7">

            <div class="mb-6">
                <h2 class="text-base font-bold text-slate-800">Control de Activos TI</h2>
                <p class="text-xs text-slate-500 mt-1">Ingrese sus credenciales institucionales para acceder</p>
            </div>

            <!-- Alerta de error de credenciales -->
            <?php if (!empty($_SESSION['flash_login_error'])): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-5
                            flex items-start gap-2.5">
                    <svg class="w-4 h-4 text-red-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-red-700 text-xs font-medium leading-snug">
                        <?= htmlspecialchars($_SESSION['flash_login_error'], ENT_QUOTES, 'UTF-8') ?>
                    </p>
                </div>
                <?php unset($_SESSION['flash_login_error']); ?>
            <?php endif; ?>

            <!-- Formulario de autenticación -->
            <form method="POST" action="/?page=login" novalidate>

                <!-- Token CSRF: previene ataques de login CSRF -->
                <input type="hidden" name="csrf_token"
                       value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                <!-- Campo: Usuario -->
                <div class="mb-4">
                    <label for="usuario"
                           class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">
                        Usuario
                    </label>
                    <input type="text"
                           id="usuario"
                           name="usuario"
                           autocomplete="username"
                           required
                           autofocus
                           spellcheck="false"
                           placeholder="Nombre de usuario"
                           class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-lg
                                  bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-500
                                  focus:border-transparent placeholder-slate-400 text-slate-700
                                  transition-colors duration-150">
                </div>

                <!-- Campo: Contraseña -->
                <div class="mb-6">
                    <label for="password"
                           class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide">
                        Contraseña
                    </label>
                    <input type="password"
                           id="password"
                           name="password"
                           autocomplete="current-password"
                           required
                           placeholder="••••••••••••"
                           class="w-full px-4 py-2.5 text-sm border border-slate-200 rounded-lg
                                  bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-500
                                  focus:border-transparent placeholder-slate-400 text-slate-700
                                  transition-colors duration-150">
                </div>

                <!-- Botón de envío -->
                <button type="submit"
                        class="w-full py-2.5 px-4 text-sm font-bold text-white
                               bg-slate-800 hover:bg-slate-700 active:bg-slate-900
                               rounded-lg transition-colors duration-150 shadow-sm
                               focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    Ingresar al Sistema
                </button>

            </form>

        </div>

        <!-- Nota de uso restringido -->
        <p class="text-center text-xs text-slate-400 mt-6">
            Sistema de Uso Interno — Acceso Exclusivo de Personal Autorizado
        </p>

    </div>

</body>
</html>

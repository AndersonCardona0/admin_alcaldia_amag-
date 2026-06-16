<?php

declare(strict_types=1);

/**
 * Conexion.php
 * Núcleo de persistencia: conexión PDO única (Singleton estático).
 *
 * Estrategia de credenciales por entorno (constante APP_ENV definida en index.php):
 *   · development → lee el archivo .env de la raíz del proyecto
 *   · production  → lee config/config.prod.php (excluido de Git)
 *
 * En ambos casos los mensajes de error expuestos al cliente son genéricos;
 * el detalle técnico solo se registra en error_log del servidor.
 */
class Conexion
{
    /** @var PDO|null Instancia única compartida en toda la petición */
    private static ?PDO $conn = null;

    /** Evita instanciación directa */
    private function __construct() {}

    /**
     * Devuelve (o crea) la conexión PDO.
     *
     * @throws RuntimeException Si las credenciales son incompletas o la conexión falla.
     * @return PDO
     */
    public static function conectar(): PDO
    {
        if (self::$conn !== null) {
            return self::$conn;
        }

        [$host, $dbname, $user, $password] = self::resolverCredenciales();

        if (!$host || !$dbname || !$user) {
            throw new RuntimeException(
                APP_DEBUG
                    ? 'Credenciales de BD incompletas. Verifique el archivo .env'
                    : 'No fue posible establecer la conexión con la base de datos.'
            );
        }

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

        try {
            self::$conn = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);

        } catch (PDOException $e) {
            // En producción nunca se expone el DSN ni el mensaje de PDO al cliente.
            throw new RuntimeException(
                APP_DEBUG
                    ? 'Error PDO: ' . $e->getMessage()
                    : 'No fue posible conectar con la base de datos.'
            );
        }

        return self::$conn;
    }

    /**
     * Cierra explícitamente la conexión activa.
     * Útil en scripts CLI o procesos de larga duración.
     */
    public static function desconectar(): void
    {
        self::$conn = null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Resolución de credenciales por entorno
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Delega la carga de credenciales al mecanismo correcto según APP_ENV.
     *
     * @return array{string|null, string|null, string|null, string|null}
     *         [$host, $dbname, $user, $password]
     */
    private static function resolverCredenciales(): array
    {
        if (defined('APP_ENV') && APP_ENV === 'production') {
            return self::cargarCredencialesProduccion();
        }

        // Entorno de desarrollo: leer el archivo .env de la raíz
        self::cargarEnv();

        return [
            $_ENV['DB_HOST']     ?? null,
            $_ENV['DB_NAME']     ?? null,
            $_ENV['DB_USER']     ?? null,
            $_ENV['DB_PASSWORD'] ?? null,
        ];
    }

    /**
     * Carga las credenciales desde config/config.prod.php.
     * El archivo debe retornar un array asociativo con las claves DB_*.
     *
     * @throws RuntimeException Si el archivo no existe o su formato es inválido.
     * @return array{string|null, string|null, string|null, string|null}
     */
    private static function cargarCredencialesProduccion(): array
    {
        $ruta = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'config'
              . DIRECTORY_SEPARATOR . 'config.prod.php';

        if (!file_exists($ruta)) {
            throw new RuntimeException(
                'Archivo de configuración de producción no encontrado. '
                . 'Contacte al administrador del sistema.'
            );
        }

        $cfg = require $ruta;

        if (!is_array($cfg)) {
            throw new RuntimeException(
                'El archivo config.prod.php debe retornar un array asociativo.'
            );
        }

        return [
            $cfg['DB_HOST']     ?? null,
            $cfg['DB_NAME']     ?? null,
            $cfg['DB_USER']     ?? null,
            $cfg['DB_PASSWORD'] ?? null,
        ];
    }

    /**
     * Parsea el archivo .env de la raíz e inyecta pares CLAVE=VALOR
     * en $_ENV y putenv(). Ignora comentarios (#) y líneas vacías.
     * Solo activo en entorno de desarrollo.
     */
    private static function cargarEnv(): void
    {
        $envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';

        if (!file_exists($envPath)) {
            return;
        }

        $lineas = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lineas as $linea) {
            $linea = trim($linea);

            if ($linea === '' || str_starts_with($linea, '#') || !str_contains($linea, '=')) {
                continue;
            }

            [$clave, $valor] = explode('=', $linea, 2);
            $clave = trim($clave);
            $valor = trim($valor);

            if (!array_key_exists($clave, $_ENV)) {
                $_ENV[$clave]  = $valor;
                putenv("{$clave}={$valor}");
            }
        }
    }
}

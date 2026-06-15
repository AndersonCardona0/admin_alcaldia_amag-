<?php

/**
 * Conexion.php
 * Núcleo de persistencia de datos: gestiona la conexión PDO única
 * hacia la base de datos MySQL. Implementa patrón Singleton estático
 * para evitar conexiones duplicadas durante el ciclo de vida de la petición.
 */
class Conexion
{
    /** @var PDO|null Instancia única compartida en toda la aplicación */
    private static ?PDO $conn = null;

    /** Evita instanciación directa; toda interacción es vía conectar() */
    private function __construct() {}

    /**
     * Devuelve (o crea) la conexión PDO a la base de datos.
     * Lee las credenciales desde el archivo .env ubicado en la raíz del proyecto.
     *
     * @throws RuntimeException si las variables de entorno no están definidas
     *                          o si la conexión a MySQL falla.
     * @return PDO
     */
    public static function conectar(): PDO
    {
        if (self::$conn !== null) {
            return self::$conn;
        }

        // Carga las variables del .env si no han sido inyectadas aún
        self::cargarEnv();

        $host     = $_ENV['DB_HOST']     ?? null;
        $dbname   = $_ENV['DB_NAME']     ?? null;
        $user     = $_ENV['DB_USER']     ?? null;
        $password = $_ENV['DB_PASSWORD'] ?? null;

        if (!$host || !$dbname || !$user) {
            throw new RuntimeException(
                'Variables de entorno de base de datos incompletas. Verifique el archivo .env'
            );
        }

        $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";

        try {
            self::$conn = new PDO($dsn, $user, $password, [
                // Convierte errores de SQL en excepciones PHP capturables
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                // Usa sentencias preparadas nativas del servidor; blinda contra SQL Injection
                PDO::ATTR_EMULATE_PREPARES   => false,
                // Devuelve filas como arrays asociativos por defecto
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Desactiva la conversión automática de tipos en PHP 8
                PDO::ATTR_STRINGIFY_FETCHES  => false,
            ]);
        } catch (PDOException $e) {
            // No expone detalles de credenciales en producción
            throw new RuntimeException(
                'Error al conectar con la base de datos: ' . $e->getMessage()
            );
        }

        return self::$conn;
    }

    /**
     * Cierra explícitamente la conexión PDO activa.
     * Útil en scripts CLI o procesos de larga duración.
     */
    public static function desconectar(): void
    {
        self::$conn = null;
    }

    /**
     * Parsea el archivo .env de la raíz del proyecto e inyecta
     * cada par CLAVE=VALOR en $_ENV y putenv(), ignorando comentarios (#).
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

            // Ignora comentarios y líneas sin el separador '='
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

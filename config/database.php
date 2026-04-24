<?php
/**
 * Configuración de conexión a la base de datos
 * Barbería Premium — config/database.php
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'barberia_db');
define('DB_CHARSET', 'utf8mb4');

/**
 * Retorna una conexión PDO al servidor MySQL.
 * Se llama una sola vez y se reutiliza (singleton ligero).
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // En producción loguear el error, nunca mostrarlo al usuario
            error_log('DB Error: ' . $e->getMessage());
            die(json_encode(['error' => 'Error de conexión a la base de datos.']));
        }
    }

    return $pdo;
}

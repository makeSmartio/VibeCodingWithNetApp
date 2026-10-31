<?php
/**
 * db.php - database connection.
 *
 * Provides db(), which returns a single shared PDO connection to MySQL.
 * Connection settings come from environment variables (set by docker-compose
 * or the container script), so no passwords are hard-coded in the source.
 */
declare(strict_types=1);

function db(): PDO
{
    // "static" keeps the value between calls, so we connect only once per request.
    static $pdo = null;

    if ($pdo === null) {
        // Read settings from the environment, falling back to sensible defaults.
        $host = getenv('DB_HOST') ?: 'localhost';
        $name = getenv('DB_NAME') ?: 'webinar';
        $user = getenv('DB_USER') ?: 'webinar';
        $pass = getenv('DB_PASS') ?: '';

        $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
            // Throw exceptions on SQL errors instead of failing silently.
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Return rows as associative arrays: $row['name'].
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Use real prepared statements (safer against SQL injection).
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        // Make this connection's clock UTC so it matches how we store session times.
        $pdo->exec("SET time_zone = '+00:00'");
    }

    return $pdo;
}

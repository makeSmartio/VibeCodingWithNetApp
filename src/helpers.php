<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/** Format a UTC DATETIME string as Eastern time. */
function format_et(string $utc, string $fmt = 'l, F j, Y \a\t g:i A'): string
{
    $d = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
    $d = $d->setTimezone(new DateTimeZone('America/New_York'));
    return $d->format($fmt) . ' ' . $d->format('T');
}

function upcoming_sessions(): array
{
    $stmt = db()->query(
        "SELECT s.*, (SELECT COUNT(*) FROM registrations r WHERE r.session_id = s.id) AS reg_count
         FROM sessions s WHERE s.starts_at > UTC_TIMESTAMP() ORDER BY s.starts_at ASC"
    );
    return $stmt->fetchAll();
}

function flash(?string $msg = null): ?string
{
    if ($msg !== null) {
        $_SESSION['flash'] = $msg;
        return null;
    }
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

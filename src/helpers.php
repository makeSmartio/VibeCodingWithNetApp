<?php
/**
 * helpers.php - small shared functions used by every page.
 *
 * Including this file also loads the database helper and starts the PHP
 * session (needed for CSRF tokens and one-time "flash" messages).
 */
declare(strict_types=1);

require_once __DIR__ . '/db.php';

// Start the session only if one isn't already running.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Escape a string for safe output in HTML (prevents cross-site scripting).
 * Use it on ANY user-supplied or database value printed into a page.
 */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Return this visitor's CSRF token, creating a random one on first use.
 * It goes in a hidden form field and is checked on submit, which proves the
 * form came from our own page and not from another website.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/**
 * Check a submitted token against the one stored in the session.
 * hash_equals() compares in constant time, so timing can't leak the token.
 */
function csrf_valid(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/**
 * Convert a UTC DATETIME string (as stored in MySQL) to a readable Eastern
 * time string such as "Friday, November 6, 2026 at 4:00 PM EST".
 * PHP handles daylight saving, so EST/EDT is chosen automatically.
 */
function format_et(string $utc, string $fmt = 'l, F j, Y \a\t g:i A'): string
{
    $d = new DateTimeImmutable($utc, new DateTimeZone('UTC'));
    $d = $d->setTimezone(new DateTimeZone('America/New_York'));
    return $d->format($fmt) . ' ' . $d->format('T');
}

/**
 * Fetch all future sessions, soonest first.
 * Each row also carries reg_count (how many people have registered), which
 * the page uses to mark a session "full" when capacity is reached.
 */
function upcoming_sessions(): array
{
    $stmt = db()->query(
        "SELECT s.*, (SELECT COUNT(*) FROM registrations r WHERE r.session_id = s.id) AS reg_count
         FROM sessions s WHERE s.starts_at > UTC_TIMESTAMP() ORDER BY s.starts_at ASC"
    );
    return $stmt->fetchAll();
}

/**
 * One-time "flash" message passed between page loads.
 *  - flash('text') stores a message.
 *  - flash() returns the stored message and deletes it, so it shows only once.
 */
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

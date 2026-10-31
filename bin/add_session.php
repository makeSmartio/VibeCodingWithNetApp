<?php
/**
 * add_session.php - command-line tool to create a webinar session.
 *
 * Usage: php bin/add_session.php ["YYYY-MM-DD HH:MM" (Eastern)] ["Title"] ["Join URL"] [capacity]
 * Every argument is optional. With no date it picks the next Friday at 4:00 PM ET.
 */
declare(strict_types=1);

// Refuse to run if someone requests this over the web.
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}
require_once __DIR__ . '/../src/db.php';

$et = new DateTimeZone('America/New_York');
$when = $argv[1] ?? '';   // $argv holds the command-line arguments

if ($when === '') {
    // Default: next Friday at 4:00 PM Eastern.
    $d = new DateTimeImmutable('now', $et);
    $d = $d->modify('next friday')->setTime(16, 0);
    // But if today IS Friday and it's before 4 PM ET, use today instead.
    $now = new DateTimeImmutable('now', $et);
    if ($now->format('N') === '5' && $now < $now->setTime(16, 0)) {   // 'N' = 5 means Friday
        $d = $now->setTime(16, 0);
    }
} else {
    // The date given is interpreted as Eastern time.
    try {
        $d = new DateTimeImmutable($when, $et);
    } catch (Exception $ex) {
        fwrite(STDERR, "Invalid date: $when\n");
        exit(1);
    }
}

$title = $argv[2] ?? 'Vibe-Coding with NetApp Happy Hour';
$url = ($argv[3] ?? '') !== '' ? $argv[3] : null;                              // optional join link
$cap = isset($argv[4]) && $argv[4] !== '' ? (int)$argv[4] : null;              // optional seat limit

// The database stores UTC; convert from Eastern before saving.
$utc = $d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');

$stmt = db()->prepare('INSERT INTO sessions (title, starts_at, join_url, capacity) VALUES (?, ?, ?, ?)');
$stmt->execute([$title, $utc, $url, $cap]);
echo "Added session #" . db()->lastInsertId() . ": $title at " . $d->format('D, M j, Y g:i A T') . " (UTC $utc)\n";

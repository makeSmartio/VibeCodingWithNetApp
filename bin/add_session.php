<?php
declare(strict_types=1);
// Usage: php bin/add_session.php ["YYYY-MM-DD HH:MM" (ET)] ["Title"] ["Join URL"] [capacity]
// Defaults to the next Friday at 16:00 ET.
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}
require_once __DIR__ . '/../src/db.php';

$et = new DateTimeZone('America/New_York');
$when = $argv[1] ?? '';
if ($when === '') {
    $d = new DateTimeImmutable('now', $et);
    $d = $d->modify('next friday')->setTime(16, 0);
    // Include today if it's Friday and before 4 PM ET.
    $now = new DateTimeImmutable('now', $et);
    if ($now->format('N') === '5' && $now < $now->setTime(16, 0)) {
        $d = $now->setTime(16, 0);
    }
} else {
    try {
        $d = new DateTimeImmutable($when, $et);
    } catch (Exception $ex) {
        fwrite(STDERR, "Invalid date: $when\n");
        exit(1);
    }
}
$title = $argv[2] ?? 'Vibe-Coding with NetApp Happy Hour';
$url = ($argv[3] ?? '') !== '' ? $argv[3] : null;
$cap = isset($argv[4]) && $argv[4] !== '' ? (int)$argv[4] : null;

$utc = $d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
$stmt = db()->prepare('INSERT INTO sessions (title, starts_at, join_url, capacity) VALUES (?, ?, ?, ?)');
$stmt->execute([$title, $utc, $url, $cap]);
echo "Added session #" . db()->lastInsertId() . ": $title at " . $d->format('D, M j, Y g:i A T') . " (UTC $utc)\n";

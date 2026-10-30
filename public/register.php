<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/helpers.php';

function back(string $msg, array $old): never
{
    flash($msg);
    $_SESSION['old'] = $old;
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$old = [
    'session_id' => (int)($_POST['session_id'] ?? 0),
    'name' => trim((string)($_POST['name'] ?? '')),
    'email' => trim((string)($_POST['email'] ?? '')),
    'company' => trim((string)($_POST['company'] ?? '')),
    'question' => trim((string)($_POST['question'] ?? '')),
];

// Honeypot: pretend success for bots.
if (trim((string)($_POST['website'] ?? '')) !== '') {
    header('Location: thanks.php');
    exit;
}

if (!csrf_valid($_POST['csrf'] ?? null)) {
    back('Your session expired. Please try again.', $old);
}
if ($old['name'] === '' || $old['company'] === '' || $old['email'] === '') {
    back('Please fill in name, email, and company.', $old);
}
if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
    back('Please enter a valid email address.', $old);
}
if (mb_strlen($old['name']) > 150 || mb_strlen($old['company']) > 150 || mb_strlen($old['email']) > 255 || mb_strlen($old['question']) > 2000) {
    back('One of your entries is too long.', $old);
}
$old['email'] = strtolower($old['email']);

try {
    $pdo = db();
    $pdo->beginTransaction();
    $stmt = $pdo->prepare('SELECT * FROM sessions WHERE id = ? AND starts_at > UTC_TIMESTAMP() FOR UPDATE');
    $stmt->execute([$old['session_id']]);
    $session = $stmt->fetch();
    if (!$session) {
        $pdo->rollBack();
        back('That session is not available.', $old);
    }
    if ($session['capacity'] !== null) {
        $c = $pdo->prepare('SELECT COUNT(*) FROM registrations WHERE session_id = ?');
        $c->execute([$session['id']]);
        if ((int)$c->fetchColumn() >= (int)$session['capacity']) {
            $pdo->rollBack();
            back('Sorry, that session is full.', $old);
        }
    }
    $dup = $pdo->prepare('SELECT 1 FROM registrations WHERE session_id = ? AND email = ?');
    $dup->execute([$session['id'], $old['email']]);
    if ($dup->fetchColumn()) {
        $pdo->rollBack();
        back('You are already registered for that session.', $old);
    }
    $ins = $pdo->prepare('INSERT INTO registrations (session_id, name, email, company, question) VALUES (?, ?, ?, ?, ?)');
    $ins->execute([$session['id'], $old['name'], $old['email'], $old['company'], $old['question'] !== '' ? $old['question'] : null]);
    $pdo->commit();
} catch (Throwable $ex) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($ex->getMessage());
    back('Something went wrong. Please try again.', $old);
}

$_SESSION['registered'] = ['name' => $old['name'], 'session_id' => (int)$session['id']];
header('Location: thanks.php');
exit;

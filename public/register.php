<?php
/**
 * register.php - handles the registration form POST.
 *
 * Flow: validate input -> check the session is open -> insert the row ->
 * redirect to thanks.php. On any problem we bounce back to the form with
 * a message and the user's previous entries so they don't retype everything.
 */
declare(strict_types=1);
require_once __DIR__ . '/../src/helpers.php';

/**
 * Send the user back to the form with an error message and their old input,
 * then stop the script. ("never" = this function never returns.)
 */
function back(string $msg, array $old): never
{
    flash($msg);
    $_SESSION['old'] = $old;
    header('Location: index.php');
    exit;
}

// Only accept form submissions; anyone browsing here directly goes to the form.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

// Collect and trim the submitted values (never trust raw input).
$old = [
    'session_id' => (int)($_POST['session_id'] ?? 0),
    'name' => trim((string)($_POST['name'] ?? '')),
    'email' => trim((string)($_POST['email'] ?? '')),
    'company' => trim((string)($_POST['company'] ?? '')),
    'question' => trim((string)($_POST['question'] ?? '')),
];

// Honeypot: the hidden "website" field is invisible to humans, so if it has
// content a bot filled it in. Pretend success but save nothing.
if (trim((string)($_POST['website'] ?? '')) !== '') {
    header('Location: thanks.php');
    exit;
}

// --- Validation -----------------------------------------------------------
if (!csrf_valid($_POST['csrf'] ?? null)) {
    back('Your session expired. Please try again.', $old);
}
if ($old['name'] === '' || $old['company'] === '' || $old['email'] === '') {
    back('Please fill in name, email, and company.', $old);
}
if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
    back('Please enter a valid email address.', $old);
}
// Lengths must fit the database columns.
if (mb_strlen($old['name']) > 150 || mb_strlen($old['company']) > 150 || mb_strlen($old['email']) > 255 || mb_strlen($old['question']) > 2000) {
    back('One of your entries is too long.', $old);
}
// Lowercase so "Bob@x.com" and "bob@x.com" count as the same person.
$old['email'] = strtolower($old['email']);

// --- Save the registration -----------------------------------------------
try {
    $pdo = db();
    // A transaction makes the checks + insert one atomic step.
    $pdo->beginTransaction();

    // Look up the session; it must exist and still be in the future.
    // FOR UPDATE locks the row so two simultaneous sign-ups can't both take the last seat.
    $stmt = $pdo->prepare('SELECT * FROM sessions WHERE id = ? AND starts_at > UTC_TIMESTAMP() FOR UPDATE');
    $stmt->execute([$old['session_id']]);
    $session = $stmt->fetch();
    if (!$session) {
        $pdo->rollBack();
        back('That session is not available.', $old);
    }

    // If the session has a capacity limit, make sure there is still room.
    if ($session['capacity'] !== null) {
        $c = $pdo->prepare('SELECT COUNT(*) FROM registrations WHERE session_id = ?');
        $c->execute([$session['id']]);
        if ((int)$c->fetchColumn() >= (int)$session['capacity']) {
            $pdo->rollBack();
            back('Sorry, that session is full.', $old);
        }
    }

    // Friendly duplicate check (the database UNIQUE key is the backstop).
    $dup = $pdo->prepare('SELECT 1 FROM registrations WHERE session_id = ? AND email = ?');
    $dup->execute([$session['id'], $old['email']]);
    if ($dup->fetchColumn()) {
        $pdo->rollBack();
        back('You are already registered for that session.', $old);
    }

    // Insert using placeholders (?) so user input can never alter the SQL.
    $ins = $pdo->prepare('INSERT INTO registrations (session_id, name, email, company, question) VALUES (?, ?, ?, ?, ?)');
    $ins->execute([$session['id'], $old['name'], $old['email'], $old['company'], $old['question'] !== '' ? $old['question'] : null]);
    $pdo->commit();
} catch (Throwable $ex) {
    // Unexpected failure: undo partial work, log details privately, show a generic message.
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($ex->getMessage());
    back('Something went wrong. Please try again.', $old);
}

// Remember who registered so thanks.php can show a personalised message.
$_SESSION['registered'] = ['name' => $old['name'], 'session_id' => (int)$session['id']];
header('Location: thanks.php');
exit;

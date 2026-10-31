<?php
/**
 * unsubscribe.php - remove someone from the mailing list.
 *
 * Each email you send should include the link:
 *     https://your-site/unsubscribe.php?t=<unsubscribe_token>
 * The token is a secret random value, so nobody can unsubscribe someone else
 * by guessing an email address.
 *
 * GET shows a confirmation button; POST does the removal. Requiring a POST
 * stops email security scanners (which "click" links) from unsubscribing people.
 */
declare(strict_types=1);
require_once __DIR__ . '/../src/helpers.php';

// Accept the token from the link (GET) or the form (POST); tokens are 32 hex characters.
$token = (string)($_POST['t'] ?? $_GET['t'] ?? '');
$validToken = (bool)preg_match('/^[a-f0-9]{32}$/', $token);

$status = 'confirm';   // confirm | done | invalid | error

if (!$validToken) {
    $status = 'invalid';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $status = 'error';
    } else {
        try {
            // Deleting 0 rows (already removed) is fine; we show the same message.
            $stmt = db()->prepare('DELETE FROM subscribers WHERE unsubscribe_token = ?');
            $stmt->execute([$token]);
            $status = 'done';
        } catch (Throwable $ex) {
            error_log($ex->getMessage());
            $status = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Unsubscribe - Vibe-Coding with NetApp</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="card">
  <h1>Unsubscribe</h1>

  <?php if ($status === 'confirm'): ?>
    <p>Click below to stop receiving Vibe-Coding with NetApp emails.</p>
    <form method="post" action="unsubscribe.php">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="t" value="<?= e($token) ?>">
      <button type="submit">Unsubscribe me</button>
    </form>
  <?php elseif ($status === 'done'): ?>
    <div class="alert success">You've been unsubscribed. Sorry to see you go!</div>
  <?php elseif ($status === 'invalid'): ?>
    <div class="alert error">This unsubscribe link isn't valid. Please use the link from your most recent email.</div>
  <?php else: ?>
    <div class="alert error">Something went wrong. Please reload the link and try again.</div>
  <?php endif; ?>

  <p><a href="subscribe.php">Join the mailing list</a> &middot; <a href="index.php">Register for a session</a></p>
</main>
</body>
</html>

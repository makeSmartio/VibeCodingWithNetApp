<?php
/**
 * subscribe.php - mailing list sign-up, available at any time.
 *
 * One file does both jobs: GET shows the form, POST validates and saves.
 * Signing up is independent of any webinar session.
 */
declare(strict_types=1);
require_once __DIR__ . '/../src/helpers.php';

$error = null;
$done = false;
$old = ['name' => '', 'email' => '', 'company' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'name' => trim((string)($_POST['name'] ?? '')),
        'email' => strtolower(trim((string)($_POST['email'] ?? ''))),
        'company' => trim((string)($_POST['company'] ?? '')),
    ];

    if (trim((string)($_POST['website'] ?? '')) !== '') {
        // Honeypot filled in: a bot. Pretend success, save nothing.
        $done = true;
    } elseif (!csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (mb_strlen($old['email']) > 255 || mb_strlen($old['name']) > 150 || mb_strlen($old['company']) > 150) {
        $error = 'One of your entries is too long.';
    } else {
        try {
            // INSERT IGNORE skips the row if the email already exists (UNIQUE key),
            // so repeat sign-ups succeed quietly and don't reveal who is on the list.
            // Each subscriber gets a random secret token for their unsubscribe link.
            $stmt = db()->prepare('INSERT IGNORE INTO subscribers (email, name, company, unsubscribe_token) VALUES (?, ?, ?, ?)');
            $stmt->execute([
                $old['email'],
                $old['name'] !== '' ? $old['name'] : null,
                $old['company'] !== '' ? $old['company'] : null,
                bin2hex(random_bytes(16)),
            ]);
            $done = true;
        } catch (Throwable $ex) {
            error_log($ex->getMessage());
            $error = 'Something went wrong. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Join the mailing list - Vibe-Coding with NetApp</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="card">
  <h1>Vibe-Coding with NetApp</h1>

  <?php if ($done): ?>
    <div class="alert success">You're on the list! We'll let you know about upcoming sessions.</div>
  <?php else: ?>
    <p class="tagline">Get notified about upcoming Friday happy hour sessions.</p>
    <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>

    <form method="post" action="subscribe.php">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <!-- Honeypot: hidden from people, bots tend to fill it in -->
      <div class="hp" aria-hidden="true"><label>Leave blank <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

      <label>Email *<input type="email" name="email" maxlength="255" required value="<?= e($old['email']) ?>"></label>
      <label>Name (optional)<input type="text" name="name" maxlength="150" value="<?= e($old['name']) ?>"></label>
      <label>Company (optional)<input type="text" name="company" maxlength="150" value="<?= e($old['company']) ?>"></label>
      <button type="submit">Join the mailing list</button>
    </form>
  <?php endif; ?>

  <p><a href="index.php">Register for a session</a></p>
</main>
</body>
</html>

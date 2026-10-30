<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/helpers.php';

$reg = $_SESSION['registered'] ?? null;
$session = null;
if ($reg) {
    $stmt = db()->prepare('SELECT * FROM sessions WHERE id = ?');
    $stmt->execute([$reg['session_id']]);
    $session = $stmt->fetch() ?: null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>You're registered - Vibe-Coding with NetApp</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="card">
  <h1>You're registered!</h1>
  <?php if ($session): ?>
    <p>Thanks, <?= e($reg['name']) ?>. See you at:</p>
    <p><strong><?= e($session['title']) ?></strong><br><?= e(format_et($session['starts_at'])) ?></p>
    <?php if ($session['join_url']): ?>
      <p><a class="button" href="<?= e($session['join_url']) ?>">Join link</a></p>
    <?php endif; ?>
  <?php else: ?>
    <p>Thanks for registering.</p>
  <?php endif; ?>
  <p><a href="index.php">Back to sessions</a></p>
</main>
</body>
</html>

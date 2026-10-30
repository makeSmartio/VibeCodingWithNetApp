<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/helpers.php';

$error = flash();
$old = $_SESSION['old'] ?? [];
unset($_SESSION['old']);

try {
    $sessions = upcoming_sessions();
    $dbError = false;
} catch (Throwable $ex) {
    $sessions = [];
    $dbError = true;
}
$selected = (int)($old['session_id'] ?? ($_GET['session'] ?? ($sessions[0]['id'] ?? 0)));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Vibe-Coding with NetApp - Register</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="card">
  <h1>Vibe-Coding with NetApp</h1>
  <p class="tagline">A casual Friday happy hour at 4:00 PM Eastern. Build, learn, and have fun.</p>

  <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
  <?php if ($dbError): ?><div class="alert error">Registration is temporarily unavailable. Please try again later.</div><?php endif; ?>

  <?php if (!$sessions && !$dbError): ?>
    <p>No upcoming sessions are scheduled yet. Please check back soon.</p>
  <?php elseif ($sessions): ?>
  <form method="post" action="register.php">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <div class="hp" aria-hidden="true"><label>Leave blank <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

    <fieldset>
      <legend>Choose a session</legend>
      <?php foreach ($sessions as $s):
          $full = $s['capacity'] !== null && (int)$s['reg_count'] >= (int)$s['capacity']; ?>
        <label class="session <?= $full ? 'full' : '' ?>">
          <input type="radio" name="session_id" value="<?= (int)$s['id'] ?>" required
            <?= (int)$s['id'] === $selected ? 'checked' : '' ?> <?= $full ? 'disabled' : '' ?>>
          <span>
            <strong><?= e($s['title']) ?></strong><?= $full ? ' (full)' : '' ?><br>
            <small><?= e(format_et($s['starts_at'])) ?></small>
            <?php if ($s['description']): ?><br><small><?= e($s['description']) ?></small><?php endif; ?>
          </span>
        </label>
      <?php endforeach; ?>
    </fieldset>

    <label>Name *<input type="text" name="name" maxlength="150" required value="<?= e($old['name'] ?? '') ?>"></label>
    <label>Email *<input type="email" name="email" maxlength="255" required value="<?= e($old['email'] ?? '') ?>"></label>
    <label>Company *<input type="text" name="company" maxlength="150" required value="<?= e($old['company'] ?? '') ?>"></label>
    <label>Topic or question (optional)<textarea name="question" rows="3" maxlength="2000"><?= e($old['question'] ?? '') ?></textarea></label>
    <button type="submit">Register</button>
  </form>
  <?php endif; ?>
</main>
</body>
</html>

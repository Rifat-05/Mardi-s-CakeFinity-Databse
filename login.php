<?php
require __DIR__ . '/includes/init.php';
if (!empty($_SESSION['admin_id'])) redirect('dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $u = trim($_POST['username'] ?? '');
    $p = (string)($_POST['password'] ?? '');
    $st = db()->prepare('SELECT id, password_hash FROM admins WHERE username = ?');
    $st->execute([$u]);
    $a = $st->fetch();
    // Always run a hash check so response time does not reveal whether the username exists.
    $hash = $a['password_hash'] ?? '$2y$10$abcdefghijklmnopqrstuuJ0p7q1X8y2YbV9mR0k8wQ3m1hZxLk5e';
    if (password_verify($p, $hash) && $a) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$a['id'];
        $_SESSION['admin_name'] = $u;
        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([password_hash($p, PASSWORD_DEFAULT), $a['id']]);
        }
        redirect('dashboard.php');
    }
    usleep(600000);
    $error = 'Wrong username or password.';
}
?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Log in - Bakery Cards</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="login"><main class="card narrow">
<h1>Bakery Cards</h1><p class="muted">Owner login</p>
<?php if ($error): ?><div class="alert a-error"><?= e($error) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?>
<label>Username<input name="username" required autofocus autocomplete="username"></label>
<label>Password<input type="password" name="password" required autocomplete="current-password"></label>
<button class="btn">Log in</button>
</form></main></body></html>

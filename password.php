<?php
require __DIR__ . '/includes/init.php';
require_admin();
post_form_guard();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cur = (string)$_POST['current'];
    $new = (string)$_POST['new'];
    $st = db()->prepare('SELECT password_hash FROM admins WHERE id = ?');
    $st->execute([$_SESSION['admin_id']]);
    if (!password_verify($cur, (string)$st->fetchColumn())) flash('error', 'Current password is wrong.');
    elseif (strlen($new) < 8) flash('error', 'New password must be at least 8 characters.');
    elseif ($new !== (string)$_POST['confirm']) flash('error', 'New passwords do not match.');
    else {
        db()->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $_SESSION['admin_id']]);
        flash('ok', 'Password changed.');
    }
    redirect('password.php');
}
render_header('Change password'); ?>
<form method="post" class="card narrow"><?= csrf_field() ?>
<label>Current password<input type="password" name="current" required></label>
<label>New password (8+ characters)<input type="password" name="new" required minlength="8"></label>
<label>Repeat new password<input type="password" name="confirm" required minlength="8"></label>
<button class="btn">Save password</button></form>
<?php render_footer();

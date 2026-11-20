<?php
require __DIR__ . '/includes/init.php';
require_admin();
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $code = normalize_code((string)($_POST['code'] ?? ''));
    if ($code) {
        $pdo->beginTransaction();
        $st = $pdo->prepare('SELECT id, status FROM codes WHERE code = ? FOR UPDATE');
        $st->execute([$code]);
        $r = $st->fetch();
        if (!$r) flash('error', 'Code not found.');
        elseif (!in_array($r['status'], ['Available', 'Assigned'], true)) flash('error', "A {$r['status']} code cannot be revoked.");
        else {
            $pdo->prepare("UPDATE codes SET status = 'Revoked', revoked_at = ? WHERE id = ? AND status IN ('Available','Assigned')")->execute([now(), $r['id']]);
            flash('ok', "Code $code revoked.");
        }
        $pdo->commit();
    }
    redirect('search_code.php?code=' . urlencode((string)$code));
}
$raw = trim($_GET['code'] ?? '');
$row = null;
if ($raw !== '') {
    $code = normalize_code($raw);
    if (!$code) flash('error', 'That is not a valid code format.');
    else {
        $st = $pdo->prepare('SELECT c.*, cu.mobile FROM codes c LEFT JOIN customers cu ON cu.id = c.customer_id WHERE c.code = ?');
        $st->execute([$code]);
        $row = $st->fetch();
        if (!$row) flash('error', 'No such code.');
    }
}
render_header('Search unique code'); ?>
<form method="get" class="card narrow"><label>Code<input name="code" value="<?= e($raw) ?>" required autofocus autocomplete="off" maxlength="12" class="mono"></label><button class="btn">Search</button></form>
<?php if ($row): ?><div class="card"><h2 class="mono"><?= e($row['code']) ?></h2>
<dl class="info"><dt>Status</dt><dd><?= badge($row['status']) ?></dd>
<dt>Customer mobile</dt><dd><?= $row['mobile'] ? '<a href="search_customer.php?mobile=' . e($row['mobile']) . '">' . e($row['mobile']) . '</a>' : 'Not assigned' ?></dd>
<dt>Generated</dt><dd><?= e(fmt_date($row['generated_at'])) ?></dd><dt>Assigned</dt><dd><?= e(fmt_date($row['assigned_at'])) ?></dd>
<dt>Claimed</dt><dd><?= e(fmt_date($row['claimed_at'])) ?></dd>
<?php if ($row['revoked_at']): ?><dt>Revoked</dt><dd><?= e(fmt_date($row['revoked_at'])) ?></dd><?php endif; ?></dl>
<?php if (in_array($row['status'], ['Available', 'Assigned'], true)): ?>
<form method="post" data-confirm="Revoke this code? It can never be claimed afterwards."><?= csrf_field() ?><input type="hidden" name="code" value="<?= e($row['code']) ?>"><button class="btn danger">Revoke code</button></form>
<?php endif; ?></div><?php endif; render_footer();

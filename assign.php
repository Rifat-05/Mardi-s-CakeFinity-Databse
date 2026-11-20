<?php
require __DIR__ . '/includes/init.php';
require_admin();
post_form_guard();
$report = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = normalize_mobile((string)($_POST['mobile'] ?? ''));
    $tokens = preg_split('/[\s,;]+/', trim((string)($_POST['codes'] ?? '')), -1, PREG_SPLIT_NO_EMPTY);
    if (!$mobile) flash('error', 'That mobile number is not valid (7 to 15 digits).');
    elseif (!$tokens) flash('error', 'Enter at least one code.');
    elseif (count($tokens) > 500) flash('error', 'Assign at most 500 codes at a time.');
    else {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $cid = find_or_create_customer($pdo, $mobile);
            $up = $pdo->prepare("UPDATE codes SET status = 'Assigned', customer_id = ?, assigned_at = ? WHERE code = ? AND status = 'Available'");
            $chk = $pdo->prepare('SELECT status FROM codes WHERE code = ?');
            $ok = 0; $seen = [];
            foreach ($tokens as $t) {
                $code = normalize_code($t);
                if (!$code) { $report[] = "$t: not a valid code format."; continue; }
                if (isset($seen[$code])) continue;
                $seen[$code] = true;
                $up->execute([$cid, now(), $code]);
                if ($up->rowCount() === 1) { $ok++; continue; }
                $chk->execute([$code]);
                $s = $chk->fetchColumn();
                $report[] = $s === false ? "$code: does not exist." : "$code: cannot be assigned, status is $s.";
            }
            if ($ok) $pdo->prepare('UPDATE customers SET total_generated = total_generated + ? WHERE id = ?')->execute([$ok, $cid]);
            $pdo->commit();
            flash($ok ? 'ok' : 'error', "$ok code(s) assigned to $mobile.");
        } catch (Throwable $t) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $t; }
    }
}
$avail = db()->query("SELECT code FROM codes WHERE status = 'Available' ORDER BY id LIMIT 40")->fetchAll(PDO::FETCH_COLUMN);
render_header('Assign codes'); ?>
<form method="post" class="card narrow"><?= csrf_field() ?>
<label>Customer mobile number<input name="mobile" inputmode="tel" required value="<?= e($_POST['mobile'] ?? '') ?>"></label>
<label>Codes (one per line or separated by spaces)<textarea name="codes" rows="5" required><?= e($_POST['codes'] ?? '') ?></textarea></label>
<button class="btn">Assign codes</button></form>
<?php if ($report): ?><div class="alert a-error"><strong>Some codes were skipped:</strong><ul><?php foreach ($report as $r): ?><li><?= e($r) ?></li><?php endforeach; ?></ul></div><?php endif; ?>
<div class="card"><h2>Available codes</h2>
<?php if ($avail): ?><p class="muted">First <?= count($avail) ?> unassigned codes. Click a code to add it to the box above.</p>
<div class="codes"><?php foreach ($avail as $c): ?><button type="button" class="code pick" data-pick="<?= e($c) ?>"><?= e($c) ?></button><?php endforeach; ?></div>
<?php else: ?><p class="muted">No unassigned codes. Generate some first.</p><?php endif; ?></div>
<?php render_footer();

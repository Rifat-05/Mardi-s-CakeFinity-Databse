<?php
require __DIR__ . '/includes/init.php';
require_admin();
$pdo = db();
$raw = trim($_GET['mobile'] ?? '');
$mobile = $raw !== '' ? normalize_mobile($raw) : null;
$cust = null;
if ($raw !== '' && !$mobile) flash('error', 'That mobile number is not valid (7 to 15 digits).');
elseif ($mobile) {
    $st = $pdo->prepare('SELECT * FROM customers WHERE mobile = ?');
    $st->execute([$mobile]);
    $cust = $st->fetch();
    if (!$cust) flash('error', 'No customer found with that mobile number.');
}
render_header('Search customer'); ?>
<form method="get" class="card narrow"><label>Mobile number<input name="mobile" inputmode="tel" value="<?= e($raw) ?>" required autofocus></label><button class="btn">Search</button></form>
<?php if ($cust):
    $per = cards_per_reward($pdo);
    $claimed = (int)$cust['total_claimed'];
    $into = $claimed % $per;
    $st = $pdo->prepare('SELECT * FROM customer_rewards WHERE customer_id = ? ORDER BY milestone');
    $st->execute([$cust['id']]); $rewards = $st->fetchAll();
    $redeemed = count(array_filter($rewards, fn($r) => $r['redeemed_at']));
    $pending = array_values(array_filter($rewards, fn($r) => !$r['redeemed_at']));
    $st = $pdo->prepare('SELECT * FROM codes WHERE customer_id = ? ORDER BY id DESC');
    $st->execute([$cust['id']]); $codes = $st->fetchAll();
    $st = $pdo->prepare('SELECT cl.claimed_at, c.code FROM claims cl JOIN codes c ON c.id = cl.code_id WHERE cl.customer_id = ? ORDER BY cl.claimed_at DESC, cl.id DESC');
    $st->execute([$cust['id']]); $hist = $st->fetchAll(); ?>
<div class="card"><h2><?= e($cust['mobile']) ?></h2>
<dl class="info"><dt>Customer ID</dt><dd><?= (int)$cust['id'] ?></dd><dt>Registered</dt><dd><?= e(fmt_date($cust['registered_at'])) ?></dd>
<dt>Cards generated</dt><dd><?= (int)$cust['total_generated'] ?></dd><dt>Cards claimed</dt><dd><?= $claimed ?></dd></dl></div>
<div class="card"><h2>Loyalty progress</h2>
<div class="bar"><span style="width:<?= round($into / $per * 100) ?>%"></span></div>
<p><?= $into ?> of <?= $per ?> claimed cards toward the next reward (<?= $per - $into ?> to go).</p>
<p>Rewards earned: <strong><?= count($rewards) ?></strong> &nbsp; Redeemed: <strong><?= $redeemed ?></strong> &nbsp; Waiting to be redeemed: <strong><?= count($pending) ?></strong></p>
<?php if ($pending): ?><form method="post" action="redeem.php" data-confirm="Redeem one reward for this customer?"><?= csrf_field() ?>
<input type="hidden" name="reward_id" value="<?= (int)$pending[0]['id'] ?>"><input type="hidden" name="back" value="customer:<?= e($cust['mobile']) ?>">
<button class="btn">Redeem a reward</button></form><?php endif; ?></div>
<div class="card"><h2>Codes (<?= count($codes) ?>)</h2>
<?php if ($codes): ?><div class="tablewrap"><table><tr><th>Code</th><th>Status</th><th>Generated</th><th>Assigned</th><th>Claimed</th></tr>
<?php foreach ($codes as $c): ?><tr><td class="mono"><a href="search_code.php?code=<?= e($c['code']) ?>"><?= e($c['code']) ?></a></td><td><?= badge($c['status']) ?></td>
<td><?= e(fmt_date($c['generated_at'])) ?></td><td><?= e(fmt_date($c['assigned_at'])) ?></td><td><?= e(fmt_date($c['claimed_at'])) ?></td></tr><?php endforeach; ?></table></div>
<?php else: ?><p class="muted">No codes yet.</p><?php endif; ?></div>
<div class="card"><h2>Claim history (<?= count($hist) ?>)</h2>
<?php if ($hist): ?><div class="tablewrap"><table><tr><th>Claimed on</th><th>Code</th></tr>
<?php foreach ($hist as $h): ?><tr><td><?= e(fmt_date($h['claimed_at'])) ?></td><td class="mono"><?= e($h['code']) ?></td></tr><?php endforeach; ?></table></div>
<?php else: ?><p class="muted">Nothing claimed yet.</p><?php endif; ?></div>
<?php endif; render_footer();

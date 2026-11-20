<?php
require __DIR__ . '/includes/init.php';
require_admin();
$pdo = db();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $n = (int)($_POST['cards_per_reward'] ?? 0);
    if ($n < 1 || $n > 1000) flash('error', 'Enter a number between 1 and 1000.');
    else {
        $pdo->prepare('UPDATE reward_settings SET cards_per_reward = ?, updated_at = ? WHERE id = 1')->execute([$n, now()]);
        flash('ok', "Reward now unlocks after $n claimed cards. New rewards are issued on each customer's next claim.");
    }
    redirect('rewards.php');
}
$per = cards_per_reward($pdo);
$f = ($_GET['show'] ?? 'pending');
$f = in_array($f, ['pending', 'redeemed', 'all'], true) ? $f : 'pending';
$where = ['pending' => 'WHERE r.redeemed_at IS NULL', 'redeemed' => 'WHERE r.redeemed_at IS NOT NULL', 'all' => ''][$f];
$total = (int)$pdo->query("SELECT COUNT(*) FROM customer_rewards r $where")->fetchColumn();
[$page, $pages, $off] = paginate($total, (int)($_GET['page'] ?? 1));
$rows = $pdo->query("SELECT r.*, cu.mobile FROM customer_rewards r JOIN customers cu ON cu.id = r.customer_id $where
    ORDER BY r.earned_at DESC, r.id DESC LIMIT " . PER_PAGE . " OFFSET $off")->fetchAll();
render_header('Loyalty rewards'); ?>
<form method="post" class="card narrow"><?= csrf_field() ?>
<label>Claimed cards needed for one reward<input type="number" name="cards_per_reward" min="1" max="1000" value="<?= $per ?>" required></label>
<p class="muted">Only claimed cards count. Generated or assigned cards do not.</p>
<button class="btn">Save setting</button></form>
<div class="card"><p class="tabs"><?php foreach (['pending' => 'Waiting to be redeemed', 'redeemed' => 'Redeemed', 'all' => 'All'] as $k => $l): ?>
<a href="?show=<?= $k ?>"<?= $k === $f ? ' class="on"' : '' ?>><?= e($l) ?></a><?php endforeach; ?></p>
<div class="tablewrap"><table><tr><th>Customer</th><th>Reward no.</th><th>Earned</th><th>Redeemed</th><th></th></tr>
<?php foreach ($rows as $r): ?><tr><td><a href="search_customer.php?mobile=<?= e($r['mobile']) ?>"><?= e($r['mobile']) ?></a></td><td><?= (int)$r['milestone'] ?></td>
<td><?= e(fmt_date($r['earned_at'])) ?></td><td><?= e(fmt_date($r['redeemed_at'])) ?></td>
<td><?php if (!$r['redeemed_at']): ?><form method="post" action="redeem.php" class="inline" data-confirm="Redeem this reward?"><?= csrf_field() ?>
<input type="hidden" name="reward_id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="back" value="rewards"><button class="btn small">Redeem</button></form><?php endif; ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="5" class="muted">Nothing here yet.</td></tr><?php endif; ?></table></div><?= pager($page, $pages, ['show' => $f]) ?></div>
<?php render_footer();

<?php
require __DIR__ . '/includes/init.php';
require_admin();
$pdo = db();
$c = $pdo->query("SELECT COUNT(*) total,
    COALESCE(SUM(status='Available'),0) available, COALESCE(SUM(status='Assigned'),0) assigned,
    COALESCE(SUM(status='Claimed'),0) claimed, COALESCE(SUM(status='Revoked'),0) revoked FROM codes")->fetch();
$customers = (int)$pdo->query('SELECT COUNT(*) FROM customers')->fetchColumn();
$r = $pdo->query('SELECT COUNT(*) earned, COALESCE(SUM(redeemed_at IS NOT NULL),0) redeemed FROM customer_rewards')->fetch();
$stats = [
    'Customers' => $customers, 'Codes generated' => $c['total'], 'Assigned (waiting to be claimed)' => $c['assigned'],
    'Claimed' => $c['claimed'], 'Available (unassigned)' => $c['available'], 'Revoked' => $c['revoked'],
    'Rewards earned' => $r['earned'], 'Rewards redeemed' => $r['redeemed'],
];
$links = [
    'generate.php' => 'Generate codes', 'assign.php' => 'Assign codes', 'claim.php' => 'Claim a card',
    'search_customer.php' => 'Search customer', 'search_code.php' => 'Search unique code', 'customers.php' => 'Customer list',
    'claims.php' => 'Claim history', 'rewards.php' => 'Loyalty rewards',
];
render_header('Dashboard'); ?>
<div class="stats">
<?php foreach ($stats as $label => $v): ?><div class="card stat"><span class="num"><?= (int)$v ?></span><span><?= e($label) ?></span></div><?php endforeach; ?>
</div>
<h2>Quick actions</h2>
<div class="quick"><?php foreach ($links as $f => $l): ?><a class="card" href="<?= $f ?>"><?= e($l) ?></a><?php endforeach; ?></div>
<?php render_footer();

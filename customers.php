<?php
require __DIR__ . '/includes/init.php';
require_admin();
$pdo = db();
$q = trim($_GET['q'] ?? '');
$like = '%' . preg_replace('/\D+/', '', $q) . '%';
$where = $q !== '' && preg_match('/\d/', $q) ? 'WHERE mobile LIKE ?' : '';
$args = $where ? [$like] : [];
$st = $pdo->prepare("SELECT COUNT(*) FROM customers $where"); $st->execute($args);
[$page, $pages, $off] = paginate((int)$st->fetchColumn(), (int)($_GET['page'] ?? 1));
$st = $pdo->prepare("SELECT * FROM customers $where ORDER BY id DESC LIMIT " . PER_PAGE . " OFFSET $off"); $st->execute($args);
$rows = $st->fetchAll();
render_header('Customers'); ?>
<form method="get" class="card narrow"><label>Filter by mobile digits<input name="q" value="<?= e($q) ?>"></label><button class="btn">Filter</button></form>
<div class="card"><div class="tablewrap"><table><tr><th>ID</th><th>Mobile</th><th>Registered</th><th>Generated</th><th>Claimed</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= (int)$r['id'] ?></td><td><a href="search_customer.php?mobile=<?= e($r['mobile']) ?>"><?= e($r['mobile']) ?></a></td>
<td><?= e(fmt_date($r['registered_at'])) ?></td><td><?= (int)$r['total_generated'] ?></td><td><?= (int)$r['total_claimed'] ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="5" class="muted">No customers yet.</td></tr><?php endif; ?></table></div><?= pager($page, $pages, ['q' => $q]) ?></div>
<?php render_footer();

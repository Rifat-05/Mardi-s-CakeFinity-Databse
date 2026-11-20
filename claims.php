<?php
require __DIR__ . '/includes/init.php';
require_admin();
$pdo = db();
[$page, $pages, $off] = paginate((int)$pdo->query('SELECT COUNT(*) FROM claims')->fetchColumn(), (int)($_GET['page'] ?? 1));
$rows = $pdo->query('SELECT cl.claimed_at, c.code, cu.mobile, a.username FROM claims cl
    JOIN codes c ON c.id = cl.code_id JOIN customers cu ON cu.id = cl.customer_id LEFT JOIN admins a ON a.id = cl.admin_id
    ORDER BY cl.claimed_at DESC, cl.id DESC LIMIT ' . PER_PAGE . " OFFSET $off")->fetchAll();
render_header('Claim history'); ?>
<div class="card"><div class="tablewrap"><table><tr><th>Claimed on</th><th>Code</th><th>Customer</th><th>Recorded by</th></tr>
<?php foreach ($rows as $r): ?><tr><td><?= e(fmt_date($r['claimed_at'])) ?></td><td class="mono"><a href="search_code.php?code=<?= e($r['code']) ?>"><?= e($r['code']) ?></a></td>
<td><a href="search_customer.php?mobile=<?= e($r['mobile']) ?>"><?= e($r['mobile']) ?></a></td><td><?= e($r['username'] ?? '-') ?></td></tr>
<?php endforeach; if (!$rows): ?><tr><td colspan="4" class="muted">No claims yet.</td></tr><?php endif; ?></table></div><?= pager($page, $pages) ?></div>
<?php render_footer();

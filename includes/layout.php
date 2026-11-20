<?php
function render_header(string $title): void {
    $cur = basename($_SERVER['SCRIPT_NAME']);
    $links = [
        'dashboard.php' => 'Dashboard', 'generate.php' => 'Generate codes', 'assign.php' => 'Assign codes',
        'claim.php' => 'Claim a card', 'search_customer.php' => 'Search customer', 'search_code.php' => 'Search code',
        'customers.php' => 'Customers', 'claims.php' => 'Claim history', 'rewards.php' => 'Rewards',
    ];
    ?><!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> - Bakery Cards</title>
<link rel="stylesheet" href="assets/css/style.css"></head>
<body>
<header class="top"><div class="wrap">
  <a class="brand" href="dashboard.php">Bakery Cards</a>
  <nav class="menu">
    <?php foreach ($links as $f => $l): ?><a href="<?= $f ?>"<?= $f === $cur ? ' class="on"' : '' ?>><?= e($l) ?></a><?php endforeach; ?>
    <a href="password.php"<?= $cur === 'password.php' ? ' class="on"' : '' ?>>Password</a>
    <form method="post" action="logout.php" class="inline"><?= csrf_field() ?><button class="linkbtn">Log out</button></form>
  </nav>
</div></header>
<main class="wrap">
<h1><?= e($title) ?></h1>
<?php foreach (flash_take() as [$t, $m]): ?><div class="alert a-<?= e($t) ?>"><?= e($m) ?></div><?php endforeach;
}

function render_footer(): void { ?>
</main><script src="assets/js/app.js"></script></body></html>
<?php }

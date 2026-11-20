<?php
require __DIR__ . '/includes/init.php';
require_admin();
post_form_guard();
$codes = [];
$assignedTo = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $count = (int)($_POST['count'] ?? 0);
    $rawMobile = trim($_POST['mobile'] ?? '');
    $mobile = null;
    if ($count < 1 || $count > MAX_CODES_PER_BATCH) {
        flash('error', 'Enter a number of codes between 1 and ' . MAX_CODES_PER_BATCH . '.');
    } elseif ($rawMobile !== '' && !($mobile = normalize_mobile($rawMobile))) {
        flash('error', 'That mobile number is not valid (7 to 15 digits).');
    } else {
        $codes = generate_codes(db(), $count, $mobile);
        $assignedTo = $mobile;
    }
}
render_header('Generate codes'); ?>
<form method="post" class="card narrow"><?= csrf_field() ?>
<label>How many codes?<input type="number" name="count" min="1" max="<?= MAX_CODES_PER_BATCH ?>" value="<?= e($_POST['count'] ?? 1) ?>" required></label>
<label>Customer mobile number (optional)<input name="mobile" inputmode="tel" value="<?= e($_POST['mobile'] ?? '') ?>" placeholder="Leave empty to assign later"></label>
<p class="muted">With a mobile number the codes are assigned straight away. Without one they stay Available until you assign them.</p>
<button class="btn">Generate</button></form>
<?php if ($codes): ?>
<div class="card"><h2><?= count($codes) ?> new code<?= count($codes) > 1 ? 's' : '' ?></h2>
<p class="muted"><?= $assignedTo ? 'Assigned to ' . e($assignedTo) . '. ' : 'Status: Available. ' ?>Write each code on a physical card.</p>
<div class="codes" id="codes"><?php foreach ($codes as $c): ?><span class="code"><?= e($c) ?></span><?php endforeach; ?></div>
<p><button class="btn ghost" data-copy="#codes">Copy all</button> <button class="btn ghost" onclick="window.print()">Print</button></p></div>
<?php endif;
render_footer();

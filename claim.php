<?php
require __DIR__ . '/includes/init.php';
require_admin();
post_form_guard();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = normalize_code((string)($_POST['code'] ?? ''));
    $mobile = normalize_mobile((string)($_POST['mobile'] ?? ''));
    if (!$code) flash('error', 'That is not a valid code. Codes have 8 letters/digits and never contain O, 0, I or 1.');
    elseif (!$mobile) flash('error', 'That mobile number is not valid (7 to 15 digits).');
    else {
        $res = claim_card(db(), $code, $mobile, (int)$_SESSION['admin_id']);
        flash($res['ok'] ? 'ok' : 'error', $res['msg']);
        if ($res['new_rewards'] > 0) flash('ok', 'This customer just earned ' . $res['new_rewards'] . ' new reward' . ($res['new_rewards'] > 1 ? 's' : '') . '!');
    }
    redirect('claim.php');
}
render_header('Claim a card'); ?>
<form method="post" class="card narrow"><?= csrf_field() ?>
<label>Code from the card<input name="code" required autofocus autocomplete="off" maxlength="12" class="mono"></label>
<label>Customer mobile number<input name="mobile" inputmode="tel" required></label>
<button class="btn">Claim card</button></form>
<?php render_footer();

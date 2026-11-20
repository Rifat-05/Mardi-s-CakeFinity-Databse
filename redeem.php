<?php
require __DIR__ . '/includes/init.php';
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('rewards.php');
csrf_check();
// Atomic: only succeeds if the reward is still unredeemed.
$st = db()->prepare('UPDATE customer_rewards SET redeemed_at = ? WHERE id = ? AND redeemed_at IS NULL');
$st->execute([now(), (int)($_POST['reward_id'] ?? 0)]);
flash($st->rowCount() === 1 ? 'ok' : 'error', $st->rowCount() === 1 ? 'Reward redeemed.' : 'That reward was already redeemed or does not exist.');
$back = (string)($_POST['back'] ?? '');
if (strpos($back, 'customer:') === 0) redirect('search_customer.php?mobile=' . urlencode(substr($back, 9)));
redirect('rewards.php');

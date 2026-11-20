<?php
const CODE_ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no O, 0, I, 1

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function now(): string { return date('Y-m-d H:i:s'); }

function fmt_date($d): string { return $d ? date('d M Y, H:i', strtotime($d)) : '-'; }

function redirect(string $url): void { header('Location: ' . $url); exit; }

/* ---------- flash messages ---------- */
function flash(string $type, string $msg): void { $_SESSION['flash'][] = [$type, $msg]; }
function flash_take(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

/* ---------- CSRF ---------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void {
    $ok = isset($_POST['csrf']) && hash_equals($_SESSION['csrf'] ?? '', (string)$_POST['csrf']);
    if (!$ok) { http_response_code(400); exit('Invalid or expired form. Go back, reload the page and try again.'); }
}

/* ---------- input normalisation ---------- */
// Returns digits-only mobile number, or null if it is not valid.
function normalize_mobile(string $raw): ?string {
    $d = preg_replace('/\D+/', '', $raw);
    if ($d === '') return null;
    if (strpos($d, '00') === 0) $d = substr($d, 2);
    elseif (DEFAULT_COUNTRY_CODE !== '' && $d[0] === '0') $d = DEFAULT_COUNTRY_CODE . substr($d, 1);
    return (strlen($d) >= 7 && strlen($d) <= 15) ? $d : null;
}

// Returns upper-case 8-char code using the allowed alphabet, or null.
function normalize_code(string $raw): ?string {
    $c = strtoupper(preg_replace('/[\s\-]+/', '', $raw));
    return preg_match('/^[' . CODE_ALPHABET . ']{' . CODE_LENGTH . '}$/', $c) ? $c : null;
}

/* ---------- customers ---------- */
function find_or_create_customer(PDO $pdo, string $mobile): int {
    $st = $pdo->prepare('INSERT INTO customers (mobile, registered_at) VALUES (?, ?)
                         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
    $st->execute([$mobile, now()]);
    return (int)$pdo->lastInsertId();
}

/* ---------- loyalty ---------- */
function cards_per_reward(PDO $pdo): int {
    $n = (int)$pdo->query('SELECT cards_per_reward FROM reward_settings WHERE id = 1')->fetchColumn();
    return max(1, $n);
}

// Call inside the claim transaction after total_claimed was incremented.
// Issues any missing milestones; the UNIQUE(customer_id, milestone) key makes duplicates impossible.
function sync_rewards(PDO $pdo, int $customerId): int {
    $per = cards_per_reward($pdo);
    $st = $pdo->prepare('SELECT total_claimed FROM customers WHERE id = ?');
    $st->execute([$customerId]);
    $earnedTarget = intdiv((int)$st->fetchColumn(), $per);
    $st = $pdo->prepare('SELECT COALESCE(MAX(milestone), 0) FROM customer_rewards WHERE customer_id = ?');
    $st->execute([$customerId]);
    $have = (int)$st->fetchColumn();
    $ins = $pdo->prepare('INSERT IGNORE INTO customer_rewards (customer_id, milestone, cards_required, earned_at) VALUES (?, ?, ?, ?)');
    $new = 0;
    for ($m = $have + 1; $m <= $earnedTarget; $m++) {
        $ins->execute([$customerId, $m, $per, now()]);
        $new += $ins->rowCount();
    }
    return $new;
}

/* ---------- code generation ---------- */
function random_code(): string {
    $s = '';
    $max = strlen(CODE_ALPHABET) - 1;
    for ($i = 0; $i < CODE_LENGTH; $i++) $s .= CODE_ALPHABET[random_int(0, $max)];
    return $s;
}

/**
 * Generates $count unique codes. Duplicates are rejected by the UNIQUE index (error 1062) and retried.
 * If $mobile is given the codes are stored as Assigned to that customer.
 */
function generate_codes(PDO $pdo, int $count, ?string $mobile): array {
    $codes = [];
    $pdo->beginTransaction();
    try {
        $cid = $mobile !== null ? find_or_create_customer($pdo, $mobile) : null;
        $status = $cid ? 'Assigned' : 'Available';
        $ts = now();
        $ins = $pdo->prepare('INSERT INTO codes (code, status, customer_id, generated_at, assigned_at) VALUES (?, ?, ?, ?, ?)');
        while (count($codes) < $count) {
            $tries = 0;
            while (true) {
                $c = random_code();
                try {
                    $pdo->exec('SAVEPOINT g');
                    $ins->execute([$c, $status, $cid, $ts, $cid ? $ts : null]);
                    $pdo->exec('RELEASE SAVEPOINT g');
                    $codes[] = $c;
                    break;
                } catch (PDOException $ex) {
                    if (($ex->errorInfo[1] ?? 0) != 1062 || ++$tries > 50) throw $ex;
                    $pdo->exec('ROLLBACK TO SAVEPOINT g');
                }
            }
        }
        if ($cid) $pdo->prepare('UPDATE customers SET total_generated = total_generated + ? WHERE id = ?')->execute([$count, $cid]);
        $pdo->commit();
    } catch (Throwable $t) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $t;
    }
    return $codes;
}

/* ---------- claiming ---------- */
// Returns ['ok' => bool, 'msg' => string, 'new_rewards' => int]
function claim_card(PDO $pdo, string $code, string $mobile, ?int $adminId): array {
    for ($try = 1; ; $try++) {
        try {
            return claim_card_once($pdo, $code, $mobile, $adminId);
        } catch (PDOException $ex) {
            // 1213 = deadlock, 1205 = lock wait timeout: safe to retry, nothing was committed.
            if ($try >= 4 || !in_array($ex->errorInfo[1] ?? 0, [1213, 1205], true)) throw $ex;
            usleep(random_int(20000, 120000));
        }
    }
}

function claim_card_once(PDO $pdo, string $code, string $mobile, ?int $adminId): array {
    $fail = fn(string $m) => ['ok' => false, 'msg' => $m, 'new_rewards' => 0];
    $pdo->beginTransaction();
    try {
        $st = $pdo->prepare('SELECT id, status, customer_id, claimed_at FROM codes WHERE code = ? FOR UPDATE');
        $st->execute([$code]);
        $row = $st->fetch();
        if (!$row) { $pdo->rollBack(); return $fail("Code $code does not exist."); }
        if ($row['status'] === 'Claimed') { $pdo->rollBack(); return $fail("Code $code was already claimed on " . fmt_date($row['claimed_at']) . '.'); }
        if ($row['status'] === 'Revoked') { $pdo->rollBack(); return $fail("Code $code has been revoked and cannot be claimed."); }
        if ($row['status'] !== 'Assigned' || !$row['customer_id']) { $pdo->rollBack(); return $fail("Code $code is not assigned to any customer yet."); }

        // Lock order is always code row, then customer row, so parallel claims queue up instead of deadlocking.
        $st = $pdo->prepare('SELECT id FROM customers WHERE mobile = ? FOR UPDATE');
        $st->execute([$mobile]);
        $cid = $st->fetchColumn();
        if (!$cid) { $pdo->rollBack(); return $fail('No customer is registered with that mobile number.'); }
        if ((int)$cid !== (int)$row['customer_id']) { $pdo->rollBack(); return $fail("Code $code is assigned to a different customer. Check the mobile number."); }

        $ts = now();
        $u = $pdo->prepare("UPDATE codes SET status = 'Claimed', claimed_at = ? WHERE id = ? AND status = 'Assigned'");
        $u->execute([$ts, $row['id']]);
        if ($u->rowCount() !== 1) { $pdo->rollBack(); return $fail("Code $code could not be claimed (it just changed status)."); }

        $pdo->prepare('INSERT INTO claims (code_id, customer_id, admin_id, claimed_at) VALUES (?, ?, ?, ?)')
            ->execute([$row['id'], $cid, $adminId, $ts]);
        $pdo->prepare('UPDATE customers SET total_claimed = total_claimed + 1 WHERE id = ?')->execute([$cid]);
        $new = sync_rewards($pdo, (int)$cid);
        $pdo->commit();
        return ['ok' => true, 'msg' => "Code $code claimed successfully.", 'new_rewards' => $new];
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if (($ex->errorInfo[1] ?? 0) == 1062) return $fail("Code $code was already claimed.");
        throw $ex;
    }
}

/* ---------- pagination ---------- */
function paginate(int $total, int $page): array {
    $pages = max(1, (int)ceil($total / PER_PAGE));
    $page = min(max(1, $page), $pages);
    return [$page, $pages, ($page - 1) * PER_PAGE];
}
function pager(int $page, int $pages, array $params = []): string {
    if ($pages <= 1) return '';
    $h = '<nav class="pager">';
    for ($i = 1; $i <= $pages; $i++) {
        if ($pages > 12 && abs($i - $page) > 3 && $i != 1 && $i != $pages) continue;
        $q = http_build_query($params + ['page' => $i]);
        $h .= $i == $page ? "<span class=\"cur\">$i</span>" : "<a href=\"?" . e($q) . "\">$i</a>";
    }
    return $h . '</nav>';
}

function badge(string $status): string {
    return '<span class="badge b-' . strtolower(e($status)) . '">' . e($status) . '</span>';
}

function post_form_guard(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') csrf_check();
}

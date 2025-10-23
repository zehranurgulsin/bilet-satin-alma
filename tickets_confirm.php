
<?php
require __DIR__.'/bootstrap.php'; require_login();
csrf_check();
if($_SERVER['REQUEST_METHOD']!=='POST') { header('Location: index.php'); exit; }

$trip_id=(int)($_POST['trip_id']??0);
$seat_no=(int)($_POST['seat_no']??0);
$coupon_code=trim($_POST['coupon_code']??'') ?: null;

$trip=$pdo->prepare("SELECT * FROM trips WHERE id=?"); $trip->execute([$trip_id]); $trip=$trip->fetch();
if(!$trip) die("Sefer yok.");
try {
    $pdo->beginTransaction();

   
    $st = $pdo->prepare("SELECT COUNT(*) FROM tickets WHERE trip_id=? AND seat_no=? AND status='purchased'");
    $st->execute([$trip_id, $seat_no]);
    if ((int)$st->fetchColumn() > 0) {
        throw new Exception("Bu koltuk az önce doldu.");
    }

    $price = (int)$trip['price'];
    $appliedCode = null;
    if ($coupon_code) {
        $c = $pdo->prepare("
            SELECT * FROM coupons
            WHERE code=? AND active=1
              AND (company_id IS NULL OR company_id = ?)
        ");
        $c->execute([$coupon_code, (int)$trip['company_id']]);
        $c = $c->fetch();

        $now = date('Y-m-d H:i:s');
        $valid = $c && ($c['expires_at'] === null || $c['expires_at'] > $now)
               && ($c['usage_limit'] === null || $c['used_count'] < $c['usage_limit'])
               && $price >= (int)$c['min_price'];

        if ($valid) {
            $appliedCode = $c['code'];
            if ($c['discount_type'] === 'percent') {
                $price = (int) round($price * (100 - (int)$c['value']) / 100);
            } else {
                $price = max(0, $price - (int)$c['value']);
            }
            $update = $pdo->prepare("
              UPDATE coupons
              SET used_count = used_count + 1
                WHERE id = ?
                  AND active = 1
                  AND (usage_limit IS NULL OR used_count < usage_limit)
            ");
            $update->execute([$c['id']]);

              
              if ($update->rowCount() === 0 && $c['usage_limit'] !== null) {
                $pdo->rollBack();
                exit('Kupon kullanım limiti doldu veya geçersiz.');
              }

        }
    }

    $st = $pdo->prepare("SELECT wallet_balance FROM users WHERE id=?");
    $st->execute([me()['id']]);
    $balance = (int)$st->fetchColumn();

    if ($balance < $price) {
        throw new Exception("Bakiye yetersiz.");
    }

   
    $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?")
        ->execute([$price, me()['id']]);
    $pdo->prepare("
        INSERT INTO tickets (trip_id, user_id, seat_no, price_paid, coupon_code, status)
        VALUES (?, ?, ?, ?, ?, 'purchased')
    ")->execute([$trip_id, me()['id'], $seat_no, $price, $appliedCode]);
    $ticket_id = (int)$pdo->lastInsertId();
    $pdo->prepare("
        INSERT INTO wallet_transactions (user_id, amount, type, ref_ticket_id)
        VALUES (?, ?, 'purchase', ?)
    ")->execute([me()['id'], -$price, $ticket_id]);

    $pdo->commit();
    reload_me($pdo);
    header("Location: my_tickets.php?ok=1");
    exit;

} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(409);
    exit('İşlem başarısız: ' . htmlspecialchars($e->getMessage()));
}

reload_me($pdo);
header("Location: my_tickets.php?ok=1");

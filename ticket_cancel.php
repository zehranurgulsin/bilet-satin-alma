<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php'; require_login();

$tid = (int)($_GET['id'] ?? 0);

$st = $pdo->prepare("
  SELECT tk.*, tr.departure_at
  FROM tickets tk
  JOIN trips tr ON tr.id = tk.trip_id
  WHERE tk.id=? AND tk.user_id=?
");
$st->execute([$tid, me()['id']]);
$t = $st->fetch();

if(!$t){ http_response_code(404); exit("Bilet bulunamadı."); }
if($t['status'] !== 'purchased'){ exit("Bu bilet zaten iptal edilmiş."); }

$depTs = strtotime($t['departure_at']);
if ($depTs - time() < 3600) {
  exit("Kalkış saatine 1 saatten az kaldığı için iptal edilemez.");
}

$pdo->beginTransaction();
try {
  $pdo->prepare("UPDATE tickets SET status='cancelled', cancelled_at=datetime('now') WHERE id=?")
      ->execute([$tid]);

  $refund = (int)$t['price_paid'];
  $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id=?")
      ->execute([$refund, me()['id']]);

  $pdo->prepare("INSERT INTO wallet_transactions (user_id, amount, type, ref_ticket_id)
                 VALUES (?, ?, 'refund', ?)")
      ->execute([me()['id'], $refund, $tid]);

  $pdo->commit();
  reload_me($pdo);
  header("Location: my_tickets.php"); 
  exit;
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  exit("İptal sırasında bir hata oluştu.");
}

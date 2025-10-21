<?php
// ticket_cancel.php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php'; require_login();

$tid = (int)($_GET['id'] ?? 0);

/* Bilet + seferi oku (sadece sahibi) */
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

/* Kalkışa ≥ 1 saat kuralı */
$depTs = strtotime($t['departure_at']);
if ($depTs - time() < 3600) {
  exit("Kalkış saatine 1 saatten az kaldığı için iptal edilemez.");
}

$pdo->beginTransaction();
try {
  // 1) Bileti iptal et
  $pdo->prepare("UPDATE tickets SET status='cancelled', cancelled_at=datetime('now') WHERE id=?")
      ->execute([$tid]);

  // 2) Ücreti kullanıcı cüzdanına iade et
  $refund = (int)$t['price_paid'];
  $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id=?")
      ->execute([$refund, me()['id']]);

  // 3) Cüzdan hareketi (denetim izi)
  $pdo->prepare("INSERT INTO wallet_transactions (user_id, amount, type, ref_ticket_id)
                 VALUES (?, ?, 'refund', ?)")
      ->execute([me()['id'], $refund, $tid]);

  $pdo->commit();
  reload_me($pdo);
  header("Location: my_tickets.php"); // istersen ?msg=Iptal+basarili
  exit;
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  exit("İptal sırasında bir hata oluştu.");
}

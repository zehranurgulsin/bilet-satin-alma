<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';

$trip_id = (int)($_GET['id'] ?? 0);

/* Sefer + firma bilgisi */
$st = $pdo->prepare("
  SELECT t.*, c.name AS company_name
  FROM trips t
  JOIN companies c ON c.id = t.company_id
  WHERE t.id = ?
");
$st->execute([$trip_id]);
$trip = $st->fetch();
if (!$trip) { http_response_code(404); exit('Sefer bulunamadı'); }

/* Dolu koltuklar */
$st = $pdo->prepare("SELECT seat_no FROM tickets WHERE trip_id=? AND status='purchased' ORDER BY seat_no");
$st->execute([$trip_id]);
$taken = array_map('intval', array_column($st->fetchAll(), 'seat_no'));

$seatCount = (int)$trip['seat_count'];
$me = $_SESSION['user'] ?? null;
?>
<!doctype html>
<html lang="tr">
<meta charset="utf-8">
<title>Sefer Detay • <?=htmlspecialchars($trip['company_name'])?></title>
<style>
  body{font-family:system-ui,Arial;padding:24px;max-width:900px;margin:24px auto;background:#fafafa;}
  .wrap{display:grid;grid-template-columns:1fr;gap:16px}
  .card{background:#fff;border:1px solid #eee;border-radius:12px;padding:16px;box-shadow:0 2px 4px rgba(0,0,0,.04)}
  .muted{color:#666}
  .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(44px,1fr));gap:6px}
  .seat{border:1px solid #ddd;border-radius:8px;padding:8px;text-align:center}
  .seat.taken{background:#f5f5f5;color:#aaa;border-color:#eee}
  .row{display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap}
  .btn{padding:8px 12px;border:1px solid #ccc;border-radius:8px;text-decoration:none;display:inline-block;background:#fff}
  .btn.primary{border-color:#0a7;box-shadow:0 0 0 2px #0a75 inset}
</style>

<header class="row" style="margin-bottom:8px">
  <div>
    <a class="btn" href="index.php">← Geri</a>
  </div>
  <nav style="display:flex;gap:8px;">
    <?php if(!$me): ?>
      <a class="btn" href="register.php">Kayıt Ol</a>
      <a class="btn" href="login.php?next=<?=urlencode($_SERVER['REQUEST_URI'])?>">Giriş Yap</a>
    <?php else: ?>
      <span class="muted">Merhaba, <strong><?=htmlspecialchars($me['name'])?></strong></span>
      <a class="btn" href="my_tickets.php">Biletlerim</a>
      <a class="btn" href="topup.php">Bakiye Yükle</a>
      <a class="btn" href="logout.php">Çıkış Yap</a>
    <?php endif; ?>
  </nav>
</header>

<div class="wrap">
  <section class="card">
    <h2 style="margin:0 0 8px 0"><?=htmlspecialchars($trip['company_name'])?></h2>
    <div style="font-size:18px;margin-bottom:6px">
      <?=htmlspecialchars($trip['origin'])?> → <?=htmlspecialchars($trip['destination'])?>
    </div>
    <div class="muted" style="margin-bottom:10px">
      Kalkış: <?=date('d.m.Y H:i', strtotime($trip['departure_at']))?>
    </div>
    <div style="font-weight:700;font-size:18px;margin-bottom:12px">
      <?=format_price((int)$trip['price'])?>
    </div>

    <?php if($me): ?>
      <a class="btn primary" href="tickets_buy.php?id=<?=$trip['id']?>">Bilet Al</a>
    <?php else: ?>
      <a class="btn primary" href="login.php?next=<?=urlencode("tickets_buy.php?id=".$trip['id'])?>">Bilet Almak için Giriş Yap</a>
    <?php endif; ?>
  </section>

  <section class="card">
    <h3 style="margin-top:0">Koltuk Durumu (<?=$seatCount?>)</h3>
    <div class="grid">
      <?php for($i=1;$i<=$seatCount;$i++):
        $isTaken = in_array($i,$taken,true);
      ?>
        <div class="seat <?=$isTaken?'taken':''?>"><?=$i?></div>
      <?php endfor; ?>
    </div>
    <?php if(!empty($taken)): ?>
      <div class="muted" style="margin-top:8px">Dolu koltuklar: <?=implode(', ', $taken)?></div>
    <?php endif; ?>
  </section>
</div>
</html>

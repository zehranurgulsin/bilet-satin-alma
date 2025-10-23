<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/bootstrap.php';   

$origin = trim($_GET['origin'] ?? '');
$destination = trim($_GET['destination'] ?? '');
$date = trim($_GET['date'] ?? '');

$sql = "SELECT t.*, c.name AS company_name
        FROM trips t 
        JOIN companies c ON c.id = t.company_id
        WHERE 1=1";
$args = [];
if ($origin !== '')      { $sql .= " AND t.origin = ?"; $args[] = $origin; }
if ($destination !== '') { $sql .= " AND t.destination = ?"; $args[] = $destination; }
if ($date !== '')        { $sql .= " AND date(t.departure_at) = ?"; $args[] = $date; }
$sql .= " ORDER BY t.departure_at ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$trips = $stmt->fetchAll();


$me = me();
?>
<!doctype html>
<html lang="tr">
<meta charset="utf-8">
<title>🚌 Otobüs Seferleri</title>
<style>
body{font-family:system-ui,Arial;padding:24px;max-width:900px;margin:24px auto;background:#fafafa;}
h1{margin-bottom:8px}
form.search{display:flex;gap:8px;flex-wrap:wrap;margin:12px 0 20px}
input,button,select{padding:8px 10px}
.card{background:#fff;border:1px solid #eee;border-radius:12px;padding:14px;margin:10px 0;box-shadow:0 2px 4px rgba(0,0,0,.04)}
.row{display:flex;gap:16px;align-items:center;justify-content:space-between;flex-wrap:wrap}
.muted{color:#666}
.price{font-weight:700}
.btn{padding:8px 12px;border:1px solid #ccc;border-radius:8px;text-decoration:none;display:inline-block}
</style>

<header style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin:8px 0 16px;">
  <div>
    <h1 style="margin:0;">🚌 Otobüs Seferleri</h1>
    <div class="muted" style="margin-top:4px;">Kalkış, varış ve tarihi seçip arayın.</div>
  </div>

  <nav style="display:flex;gap:8px;">
    <?php if(!$me): ?>
      <a class="btn" href="register.php">Kayıt Ol</a>
      <a class="btn" href="login.php">Giriş Yap</a>
    <?php else: ?>
      <span class="muted">Merhaba, <strong><?=htmlspecialchars($me['name'])?></strong></span>

      <?php if(is_company_admin()): ?>
        <a class="btn" href="company_trips.php">Firma Seferleri</a>
      <?php endif; ?>

      <?php if(is_admin()): ?>
        <a class="btn" href="admin_panel.php">Admin Paneli</a>
      <?php endif; ?>

      <a class="btn" href="my_tickets.php">Biletlerim</a>
      <a class="btn" href="topup.php">Bakiye Yükle</a>
      <a class="btn" href="logout.php">Çıkış Yap</a>
    <?php endif; ?>
  </nav>
</header>

<form class="search" method="get">
  <input name="origin" placeholder="Kalkış (örn: İstanbul)" value="<?=htmlspecialchars($origin)?>">
  <input name="destination" placeholder="Varış (örn: Ankara)" value="<?=htmlspecialchars($destination)?>">
  <input type="date" name="date" value="<?=htmlspecialchars($date)?>">
  <button type="submit">Ara</button>
  <a class="btn" href="?">Sıfırla</a>
</form>

<?php if (!$trips): ?>
  <div class="card">Sefer bulunamadı. Farklı kriterler deneyin.</div>
<?php else: foreach ($trips as $t): ?>
  <div class="card">
    <div class="row">
      <div>
        <div><strong><?=htmlspecialchars($t['company_name'])?></strong></div>
        <div><?=htmlspecialchars($t['origin'])?> → <?=htmlspecialchars($t['destination'])?></div>
        <div class="muted">Kalkış: <?=date('d.m.Y H:i', strtotime($t['departure_at']))?></div>
      </div>
      <div>
        <div class="price"><?=format_price((int)$t['price'])?></div>
        <a class="btn" href="tickets_buy.php?id=<?=$t['id']?>">Bilet Al</a>
        <a class="btn" href="trip_show.php?id=<?=$t['id']?>">Detay</a>
      </div>
    </div>
  </div>
<?php endforeach; endif; ?>

<hr>
<footer class="muted">© <?=date('Y')?> Otobüs Bilet Sistemi</footer>
</html>
  

<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
require_login();


$trip_id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
  SELECT t.id, t.company_id, t.seat_count, t.origin, t.destination, 
         t.departure_at, t.price,
         c.name AS company_name
  FROM trips t
  JOIN companies c ON c.id = t.company_id
  WHERE t.id = ?
");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch();

if (!$trip) {
    http_response_code(404);
    exit('Sefer bulunamadı');
}


$st = $pdo->prepare("
  SELECT seat_no
  FROM tickets
  WHERE trip_id = ? 
    AND status IN ('purchased','reserved')
");
$st->execute([$trip_id]);
$taken = array_map('intval', array_column($st->fetchAll(), 'seat_no'));

$seatCount = max(0, (int)$trip['seat_count']);
$seats = range(1, $seatCount);


$token = csrf_token(); 
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <title>Bilet Satın Al</title>
  <style>
    body { font-family: system-ui; max-width: 600px; margin: 40px auto; }
    label { display:block; margin-top:10px; }
    select, input, button { padding:8px; font-size:1rem; }
  </style>
</head>
<body>

<h2><?= htmlspecialchars($trip['company_name'], ENT_QUOTES, 'UTF-8') ?>
    — <?= htmlspecialchars($trip['origin'], ENT_QUOTES, 'UTF-8') ?> →
    <?= htmlspecialchars($trip['destination'], ENT_QUOTES, 'UTF-8') ?></h2>

<p>
  Kalkış: <?= date('d.m.Y H:i', strtotime($trip['departure_at'])) ?><br>
  Fiyat: <strong><?= format_price((int)$trip['price']) ?></strong><br>
  Bakiye: <strong><?= format_price((int)me()['wallet_balance']) ?></strong>
</p>

<form method="post" action="tickets_confirm.php" autocomplete="off">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
  <input type="hidden" name="trip_id" value="<?= (int)$trip_id ?>">

  <label for="seat">Koltuk:</label>
  <select id="seat" name="seat_no" required>
    <?php foreach ($seats as $s): 
        $disabled = in_array($s, $taken, true) ? 'disabled' : '';
        $val = htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        $label = 'Koltuk ' . $s . ($disabled ? ' (Dolu)' : '');
    ?>
      <option value="<?= $val ?>" <?= $disabled ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
    <?php endforeach; ?>
  </select>

  <label for="coupon">Kupon Kodu (opsiyonel):</label>
  <input id="coupon" name="coupon_code" placeholder="Örn: INDIRIM10" inputmode="latin" maxlength="32">

  <button type="submit">Satın Al</button>
</form>

</body>
</html>

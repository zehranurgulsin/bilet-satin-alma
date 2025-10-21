<?php
require_once __DIR__.'/bootstrap.php'; require_login();

$st=$pdo->prepare("
  SELECT tk.*, tr.origin, tr.destination, tr.departure_at
  FROM tickets tk
  JOIN trips tr ON tr.id = tk.trip_id
  WHERE tk.user_id=?
  ORDER BY tk.purchased_at DESC
");
$st->execute([me()['id']]);
$rows = $st->fetchAll();
?>
<h2>Biletlerim</h2>

<?php foreach($rows as $r):
  $depTs = strtotime($r['departure_at']);
  $canCancel = $r['status']==='purchased' && ($depTs - time() >= 3600); // ≥ 1 saat kuralı
?>
  <div style="border:1px solid #ddd;padding:10px;margin:8px 0;border-radius:8px;">
    <div><strong><?=htmlspecialchars($r['origin'])?> → <?=htmlspecialchars($r['destination'])?></strong></div>
    <div>Kalkış: <?=date('d.m.Y H:i',$depTs)?> | Koltuk: <?=$r['seat_no']?> | Ödenen: <?=format_price((int)$r['price_paid'])?> | Durum: <b><?=$r['status']?></b></div>
    <div style="margin-top:6px;">
      <a href="ticket_pdf.php?id=<?=$r['id']?>">PDF İndir</a>
      <?php if($canCancel): ?>
        | <a href="ticket_cancel.php?id=<?=$r['id']?>" onclick="return confirm('Bileti iptal etmek istediğinize emin misiniz?')">Bileti İptal Et</a>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>

<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php'; require_login();
if(!is_company_admin()) exit('Yetki yok.');

$firmId = (int)(me()['company_id'] ?? 0);
if(!$firmId) exit('Firma atanmadı. (Admin sizi bir firmaya atamalı)');


if(isset($_GET['del'])){
  $id=(int)$_GET['del'];
  $pdo->prepare("DELETE FROM trips WHERE id=? AND company_id=?")->execute([$id,$firmId]);
  header("Location: company_trips.php"); exit;
}


if($_SERVER['REQUEST_METHOD']==='POST'){
  $id = (int)($_POST['id']??0);
  $origin = trim($_POST['origin']??'');
  $destination = trim($_POST['destination']??'');
  $departure_at = trim($_POST['departure_at']??'');
  $price = max(0,(int)round(((float)str_replace(',','.',$_POST['price']??'0'))*100));
  $seat_count = max(1,(int)($_POST['seat_count']??40));

  if($id){
    $pdo->prepare("UPDATE trips SET origin=?, destination=?, departure_at=?, price=?, seat_count=? WHERE id=? AND company_id=?")
        ->execute([$origin,$destination,$departure_at,$price,$seat_count,$id,$firmId]);
  }else{
    $pdo->prepare("INSERT INTO trips (company_id,origin,destination,departure_at,price,seat_count) VALUES (?,?,?,?,?,?)")
        ->execute([$firmId,$origin,$destination,$departure_at,$price,$seat_count]);
  }
  header("Location: company_trips.php"); exit;
}


$rows=$pdo->prepare("SELECT * FROM trips WHERE company_id=? ORDER BY departure_at DESC");
$rows->execute([$firmId]); $rows=$rows->fetchAll();
$edit=null;
if(isset($_GET['edit'])){
  $st=$pdo->prepare("SELECT * FROM trips WHERE id=? AND company_id=?");
  $st->execute([(int)$_GET['edit'],$firmId]); $edit=$st->fetch();
}
?>
<!doctype html><meta charset="utf-8"><title>Firma Seferleri</title>
<body style="font-family:system-ui;max-width:900px;margin:24px auto;">
<h2>Firma Seferleri</h2>
<p><a href="company_coupons.php">Firma Kuponları</a> | <a href="index.php">Ana sayfa</a></p>

<form method="post" style="border:1px solid #ddd;padding:10px;border-radius:8px;margin:8px 0;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px;">
  <input type="hidden" name="id" value="<?=$edit['id']??''?>">
  <input name="origin" placeholder="Kalkış" value="<?=htmlspecialchars($edit['origin']??'')?>" required>
  <input name="destination" placeholder="Varış" value="<?=htmlspecialchars($edit['destination']??'')?>" required>
  <input type="datetime-local" name="departure_at" value="<?=isset($edit)?date('Y-m-d\TH:i',strtotime($edit['departure_at'])):''?>" required>
  <input type="number" step="0.01" name="price" placeholder="Fiyat (₺)" value="<?=isset($edit)?($edit['price']/100):''?>" required>
  <input type="number" name="seat_count" min="1" value="<?=htmlspecialchars($edit['seat_count']??40)?>" required>
  <button><?= $edit?'Güncelle':'Ekle' ?></button>
</form>

<table border="1" cellpadding="6" cellspacing="0" width="100%">
  <tr><th>ID</th><th>Güzergâh</th><th>Kalkış</th><th>Fiyat</th><th>Koltuk</th><th>İşlem</th></tr>
  <?php foreach($rows as $r): ?>
    <tr>
      <td><?=$r['id']?></td>
      <td><?=htmlspecialchars($r['origin'])?> → <?=htmlspecialchars($r['destination'])?></td>
      <td><?=date('d.m.Y H:i',strtotime($r['departure_at']))?></td>
      <td><?=format_price((int)$r['price'])?></td>
      <td><?=$r['seat_count']?></td>
      <td>
        <a href="?edit=<?=$r['id']?>">Düzenle</a> |
        <a href="?del=<?=$r['id']?>" onclick="return confirm('Silinsin mi?')">Sil</a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
</body>

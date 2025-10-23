<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php'; require_login();
if(!is_company_admin()) exit('Yetki yok.');
$firmId = (int)(me()['company_id'] ?? 0);
if(!$firmId) exit('Firma atanmadı.');


if(isset($_GET['del'])){
  $pdo->prepare("DELETE FROM coupons WHERE id=? AND company_id=?")->execute([(int)$_GET['del'],$firmId]);
  header("Location: company_coupons.php"); exit;
}


if($_SERVER['REQUEST_METHOD']==='POST'){
  $id=(int)($_POST['id']??0);
  $code = strtoupper(trim($_POST['code']??''));
  $discount_type = ($_POST['discount_type']??'percent')==='amount' ? 'amount' : 'percent';
  $value = (int)($_POST['value']??0);
  $usage_limit = $_POST['usage_limit']!=='' ? (int)$_POST['usage_limit'] : null;
  $min_price = max(0,(int)round(((float)str_replace(',','.',$_POST['min_price']??'0'))*100));
  $expires_at = trim($_POST['expires_at']??'') ?: null;
  $active = isset($_POST['active']) ? 1 : 0;

  if($id){
    $pdo->prepare("UPDATE coupons SET code=?, discount_type=?, value=?, expires_at=?, usage_limit=?, min_price=?, active=? 
                   WHERE id=? AND company_id=?")
        ->execute([$code,$discount_type,$value,$expires_at,$usage_limit,$min_price,$active,$id,$firmId]);
  }else{
    $pdo->prepare("INSERT INTO coupons (company_id, code, discount_type, value, expires_at, usage_limit, min_price, active)
                   VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$firmId,$code,$discount_type,$value,$expires_at,$usage_limit,$min_price,$active]);
  }
  header("Location: company_coupons.php"); exit;
}


$rows=$pdo->prepare("SELECT * FROM coupons WHERE company_id=? ORDER BY id DESC");
$rows->execute([$firmId]); $rows=$rows->fetchAll();
$edit=null;
if(isset($_GET['edit'])){
  $st=$pdo->prepare("SELECT * FROM coupons WHERE id=? AND company_id=?");
  $st->execute([(int)$_GET['edit'],$firmId]); $edit=$st->fetch();
}
?>
<!doctype html><meta charset="utf-8"><title>Firma Kuponları</title>
<body style="font-family:system-ui;max-width:900px;margin:24px auto;">
<h2>Firma Kuponları</h2>
<p><a href="company_trips.php">← Firma Seferleri</a> | <a href="index.php">Ana Sayfa</a></p>

<form method="post" style="border:1px solid #ddd;padding:10px;border-radius:8px;margin:8px 0;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:8px;">
  <input type="hidden" name="id" value="<?=$edit['id']??''?>">
  <input name="code" placeholder="Kod (Örn: INDIRIM10)" value="<?=htmlspecialchars($edit['code']??'')?>" required>
  <select name="discount_type">
    <option value="percent" <?=(($edit['discount_type']??'percent')==='percent'?'selected':'')?>>Yüzde</option>
    <option value="amount"  <?=(($edit['discount_type']??'')==='amount'?'selected':'')?>>Tutar (₺)</option>
  </select>
  <input type="number" name="value" min="0" placeholder="Değer" value="<?=htmlspecialchars($edit['value']??'')?>" required>
  <input type="number" name="usage_limit" min="0" placeholder="Kullanım Limiti (boş = limitsiz)" value="<?=htmlspecialchars($edit['usage_limit']??'')?>">
  <input type="number" step="0.01" name="min_price" placeholder="Min Fiyat (₺)" value="<?=isset($edit)?($edit['min_price']/100):''?>">
  <input type="datetime-local" name="expires_at" value="<?=isset($edit)&&$edit['expires_at']?date('Y-m-d\TH:i',strtotime($edit['expires_at'])):''?>">
  <label style="display:flex;align-items:center;gap:6px;"><input type="checkbox" name="active" <?=!isset($edit) || ($edit['active']??0)?'checked':''?>> Aktif</label>
  <button><?= $edit?'Güncelle':'Ekle' ?></button>
</form>

<table border="1" cellpadding="6" cellspacing="0" width="100%">
  <tr><th>ID</th><th>Kod</th><th>Tür</th><th>Değer</th><th>Min Fiyat</th><th>Limit</th><th>Son Tarih</th><th>Aktif</th><th>İşlem</th></tr>
  <?php foreach($rows as $r): ?>
  <tr>
    <td><?=$r['id']?></td>
    <td><?=htmlspecialchars($r['code'])?></td>
    <td><?=$r['discount_type']?></td>
    <td><?=$r['discount_type']==='percent' ? ("% ".$r['value']) : format_price((int)$r['value'])?></td>
    <td><?=format_price((int)$r['min_price'])?></td>
    <td><?=is_null($r['usage_limit'])?'—':$r['usage_limit']?></td>
    <td><?=$r['expires_at']?date('d.m.Y H:i',strtotime($r['expires_at'])):'—'?></td>
    <td><?=$r['active']?'Evet':'Hayır'?></td>
    <td>
      <a href="?edit=<?=$r['id']?>">Düzenle</a> |
      <a href="?del=<?=$r['id']?>" onclick="return confirm('Silinsin mi?')">Sil</a>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
</body>

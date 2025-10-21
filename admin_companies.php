<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php'; require_login();
if(!is_admin()) exit('Yetki yok.');

if(isset($_GET['del'])){
  $pdo->prepare("DELETE FROM companies WHERE id=?")->execute([(int)$_GET['del']]);
  header("Location: admin_companies.php"); exit;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
  $id=(int)($_POST['id']??0);
  $name=trim($_POST['name']??'');
  if($id){
    $pdo->prepare("UPDATE companies SET name=? WHERE id=?")->execute([$name,$id]);
  }else{
    $pdo->prepare("INSERT INTO companies (name) VALUES (?)")->execute([$name]);
  }
  header("Location: admin_companies.php"); exit;
}

$rows=$pdo->query("SELECT * FROM companies ORDER BY id DESC")->fetchAll();
$edit=null;
if(isset($_GET['edit'])){
  $st=$pdo->prepare("SELECT * FROM companies WHERE id=?"); $st->execute([(int)$_GET['edit']]); $edit=$st->fetch();
}
?>
<!doctype html><meta charset="utf-8"><title>Firmalar</title>
<body style="font-family:system-ui;max-width:900px;margin:24px auto;">
<h2>Firmalar</h2>
<form method="post" style="border:1px solid #ddd;padding:10px;border-radius:8px;margin:8px 0;">
  <input type="hidden" name="id" value="<?=$edit['id']??''?>">
  <input name="name" placeholder="Firma adı" value="<?=htmlspecialchars($edit['name']??'')?>" required>
  <button><?= $edit?'Güncelle':'Ekle' ?></button>
</form>

<table border="1" cellpadding="6" cellspacing="0" width="100%">
  <tr><th>ID</th><th>Firma</th><th>İşlem</th></tr>
  <?php foreach($rows as $r): ?>
    <tr>
      <td><?=$r['id']?></td>
      <td><?=htmlspecialchars($r['name'])?></td>
      <td>
        <a href="?edit=<?=$r['id']?>">Düzenle</a> |
        <a href="?del=<?=$r['id']?>" onclick="return confirm('Silinsin mi?')">Sil</a>
      </td>
    </tr>
  <?php endforeach; ?>
</table>
<p><a href="admin_panel.php">← Admin Paneli</a></p>
</body>

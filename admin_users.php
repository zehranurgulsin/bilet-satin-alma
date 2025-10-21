<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php'; require_login();
if(!is_admin()) exit('Yetki yok.');

if($_SERVER['REQUEST_METHOD']==='POST'){
  $id=(int)($_POST['id']??0);
  $role=$_POST['role']??'user';
  $company_id = $_POST['company_id']!=='' ? (int)$_POST['company_id'] : null;
  $pdo->prepare("UPDATE users SET role=?, company_id=? WHERE id=?")->execute([$role,$company_id,$id]);
  header("Location: admin_users.php"); exit;
}

$users=$pdo->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
$companies=$pdo->query("SELECT * FROM companies ORDER BY name")->fetchAll();
?>
<!doctype html><meta charset="utf-8"><title>Kullanıcılar</title>
<body style="font-family:system-ui;max-width:1000px;margin:24px auto;">
<h2>Kullanıcılar & Firma Atama</h2>
<table border="1" cellpadding="6" cellspacing="0" width="100%">
  <tr><th>ID</th><th>Ad</th><th>E-posta</th><th>Rol</th><th>Firma</th><th>Kaydet</th></tr>
  <?php foreach($users as $u): ?>
  <tr>
    <form method="post">
      <td><?=$u['id']?><input type="hidden" name="id" value="<?=$u['id']?>"></td>
      <td><?=htmlspecialchars($u['name'])?></td>
      <td><?=htmlspecialchars($u['email'])?></td>
      <td>
        <select name="role">
          <option value="user" <?=($u['role']==='user'?'selected':'')?>>user</option>
          <option value="company_admin" <?=($u['role']==='company_admin'?'selected':'')?>>company_admin</option>
          <option value="admin" <?=($u['role']==='admin'?'selected':'')?>>admin</option>
        </select>
      </td>
      <td>
        <select name="company_id">
          <option value="" <?=is_null($u['company_id'])?'selected':''?>>—</option>
          <?php foreach($companies as $c): ?>
            <option value="<?=$c['id']?>" <?=((int)$u['company_id']===(int)$c['id'])?'selected':''?>><?=htmlspecialchars($c['name'])?></option>
          <?php endforeach; ?>
        </select>
      </td>
      <td><button>Kaydet</button></td>
    </form>
  </tr>
  <?php endforeach; ?>
</table>
<p><a href="admin_panel.php">← Admin Paneli</a></p>
</body>

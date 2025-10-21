<?php
declare(strict_types=1);
require_once __DIR__.'/bootstrap.php'; require_login();
if(!is_admin()) exit('Yetki yok.');
?>
<!doctype html><meta charset="utf-8"><title>Admin Paneli</title>
<body style="font-family:system-ui;max-width:900px;margin:24px auto;">
<h2>Admin Paneli</h2>
<ul>
  <li><a href="admin_companies.php">Firmalar (CRUD)</a></li>
  <li><a href="admin_users.php">Kullanıcılar & Firma Admin Atama</a></li>
  <li><a href="admin_coupons.php">Global Kuponlar (Tüm firmalar)</a></li>
</ul>
<p><a href="index.php">← Ana sayfa</a></p>
</body>

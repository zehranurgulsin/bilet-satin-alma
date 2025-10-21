<?php
require __DIR__.'/bootstrap.php';
if(!me()){ die("Önce giriş yap."); }
$pdo->prepare("UPDATE users SET role='admin' WHERE id=?")->execute([me()['id']]);
echo "Artık adminsiniz. Bu dosyayı silin.";

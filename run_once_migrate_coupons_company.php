<?php
// run_once_migrate_coupons_company.php  (bir kere çalıştırıp sonra silebilirsin)
require_once __DIR__.'/bootstrap.php';
$hasCol = false;
foreach($pdo->query("PRAGMA table_info(coupons)") as $col){
  if ($col['name']==='company_id') { $hasCol=true; break; }
}
if(!$hasCol){
  $pdo->exec("ALTER TABLE coupons ADD COLUMN company_id INTEGER NULL");
  echo "coupons.company_id eklendi\n";
} else {
  echo "Zaten var\n";
}

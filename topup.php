<?php
require __DIR__.'/bootstrap.php'; require_login();
$info="";
if($_SERVER['REQUEST_METHOD']==='POST'){
  $amount=max(0,(int)round(((float)($_POST['amount']??0))*100));
  if($amount>0){
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id=?")
        ->execute([$amount, me()['id']]);
    $pdo->prepare("INSERT INTO wallet_transactions (user_id,amount,type) VALUES (?,?, 'load')")
        ->execute([me()['id'],$amount]);
    $pdo->commit();
    reload_me($pdo);
    $info = number_format($amount / 100, 2, ',', '.') . " ₺ yüklendi.";
  }
}
?>
<h2>Bakiye Yükle</h2>
<p>Mevcut bakiye: <strong><?=format_price((int)me()['wallet_balance'])?></strong></p>
<?php if($info) echo "<div>$info</div>"; ?>
<form method="post">
  <input name="amount" type="number" min="1" step="0.01" placeholder="Tutar (₺)">
  <button>Yükle</button>
</form>

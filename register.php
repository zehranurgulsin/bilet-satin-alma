<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/bootstrap.php'; // DOĞRU: bootstrap

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Geçerli bir ad ve e-posta girin.";
    } elseif (strlen($pass) < 6) {
        $error = "Şifre en az 6 karakter olmalı.";
    } elseif ($pass !== $confirm) {
        $error = "Şifreler eşleşmiyor.";
    } else {
        try {
            $st = $pdo->prepare("INSERT INTO users (name,email,password_hash) VALUES (?,?,?)");
            $st->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
            header("Location: login.php?registered=1");
            exit;
        } catch (PDOException $e) {
            $error = str_contains($e->getMessage(), 'UNIQUE') ? "Bu e-posta zaten kayıtlı." : "Kayıt sırasında hata oluştu.";
        }
    }
}
?>
<!doctype html>
<html lang="tr"><meta charset="utf-8"><title>Kayıt</title>
<body>
<h2>Kayıt Ol</h2>
<?php if($error): ?><div style="color:red"><?=htmlspecialchars($error)?></div><?php endif; ?>
<form method="post">
  <input name="name" placeholder="Ad Soyad" required>
  <input name="email" type="email" placeholder="E-posta" required>
  <input name="password" type="password" placeholder="Şifre" required>
  <input name="confirm" type="password" placeholder="Şifre Tekrar" required>
  <button>Kayıt Ol</button>
</form>
<p><a href="login.php">Giriş yap</a></p>
</body></html>

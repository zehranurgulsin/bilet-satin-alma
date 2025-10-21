<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/bootstrap.php';  // index.php'yi asla require etme!
csrf_check(); 
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = $user;          // giriş başarılı
        $next = $_GET['next'] ?? 'index.php';
        header("Location: $next");
        exit;
    } else {
        $error = "E-posta veya şifre hatalı.";
    }
}
?>
<!doctype html>
<html lang="tr">
<meta charset="utf-8">
<title>Giriş Yap</title>
<body style="font-family:system-ui;max-width:600px;margin:40px auto;">
<h2>Giriş Yap</h2>

<?php if (isset($_GET['registered'])): ?>
  <div style="color:green;">Kayıt tamamlandı, şimdi giriş yapabilirsiniz.</div>
<?php endif; ?>

<?php if ($error): ?>
  <div style="color:red;"><?=htmlspecialchars($error)?></div>
<?php endif; ?>

<form method="post" style="display:flex;flex-direction:column;gap:8px;">
  <input name="email" type="email" placeholder="E-posta" required>
  <input name="password" type="password" placeholder="Şifre" required>
  <button type="submit">Giriş Yap</button>
</form>
<p><a href="register.php">Hesabınız yok mu? Kayıt olun</a></p>
</body>
</html>

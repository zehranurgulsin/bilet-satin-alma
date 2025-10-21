<?php
declare(strict_types=1);

/* ------------------------------------------------------------------
   1) SESSION / GÜVENLİ ÇEREZ AYARLARI + GÜVENLİK BAŞLIKLARI
-------------------------------------------------------------------*/
$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
session_set_cookie_params([
  'lifetime' => 0,
  'path'     => '/',
  'domain'   => '',          // gerekiyorsa domain gir
  'secure'   => $secure,     // HTTPS varsa true
  'httponly' => true,
  'samesite' => 'Lax',       // daha katı istersen 'Strict'
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

/* (Opsiyonel ama faydalı) Güvenlik başlıkları */
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
if ($secure) {
  header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

/* ------------------------------------------------------------------
   2) VERİTABANI BAĞLANTISI + PRAGMA
-------------------------------------------------------------------*/
$dir = __DIR__ . '/data';
@mkdir($dir, 0777, true);
$dbFile = $dir . '/app.db';

$pdo = new PDO('sqlite:' . $dbFile, null, null, [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$pdo->exec("
PRAGMA foreign_keys = ON;
PRAGMA journal_mode = WAL;
PRAGMA busy_timeout = 5000;
");

/* ------------------------------------------------------------------
   3) ŞEMA (idempotent)
-------------------------------------------------------------------*/
$pdo->exec("
CREATE TABLE IF NOT EXISTS companies (
  id   INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS trips (
  id           INTEGER PRIMARY KEY AUTOINCREMENT,
  company_id   INTEGER NOT NULL,
  origin       TEXT NOT NULL,
  destination  TEXT NOT NULL,
  departure_at TEXT NOT NULL,
  price        INTEGER NOT NULL,         -- kuruş
  seat_count   INTEGER NOT NULL DEFAULT 40,
  FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS users (
  id             INTEGER PRIMARY KEY AUTOINCREMENT,
  name           TEXT NOT NULL,
  email          TEXT NOT NULL UNIQUE,
  password_hash  TEXT NOT NULL,
  role           TEXT NOT NULL DEFAULT 'user',   -- user | company_admin | admin
  company_id     INTEGER,
  wallet_balance INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS coupons (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  code          TEXT NOT NULL UNIQUE,
  discount_type TEXT NOT NULL CHECK(discount_type IN ('percent','amount')),
  value         INTEGER NOT NULL,                -- percent: 0-100, amount: kuruş
  expires_at    TEXT,
  usage_limit   INTEGER,                         -- NULL = limitsiz
  used_count    INTEGER NOT NULL DEFAULT 0,
  min_price     INTEGER NOT NULL DEFAULT 0,
  active        INTEGER NOT NULL DEFAULT 1,
  /* Firma bazlı kuponlar için istersen migration ile company_id eklersin:
     ALTER TABLE coupons ADD COLUMN company_id INTEGER NULL; */
  company_id    INTEGER
);

CREATE TABLE IF NOT EXISTS tickets (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  trip_id       INTEGER NOT NULL,
  user_id       INTEGER NOT NULL,
  seat_no       INTEGER NOT NULL,
  price_paid    INTEGER NOT NULL,                -- kuruş
  coupon_code   TEXT,
  status        TEXT NOT NULL DEFAULT 'purchased' CHECK(status IN ('purchased','cancelled')),
  purchased_at  TEXT NOT NULL DEFAULT (datetime('now')),
  cancelled_at  TEXT,
  FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (coupon_code) REFERENCES coupons(code),
  UNIQUE (trip_id, seat_no)                      -- aynı koltuk bir kez
);

CREATE TABLE IF NOT EXISTS wallet_transactions (
  id            INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id       INTEGER NOT NULL,
  amount        INTEGER NOT NULL,                -- +yüklendi / -harcandı (kuruş)
  type          TEXT NOT NULL CHECK(type IN ('load','purchase','refund','adjust')),
  ref_ticket_id INTEGER,
  created_at    TEXT NOT NULL DEFAULT (datetime('now')),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
");

/* ------------------------------------------------------------------
   4) SEED (örnek veri) — sadece boşsa ekle
-------------------------------------------------------------------*/
if ((int)$pdo->query("SELECT COUNT(*) FROM companies")->fetchColumn() === 0) {
  $pdo->exec("INSERT INTO companies (name) VALUES ('HızlıTur'), ('AnadoluEkspres')");
}
if ((int)$pdo->query("SELECT COUNT(*) FROM trips")->fetchColumn() === 0) {
  $now  = time();
  $stmt = $pdo->prepare("
    INSERT INTO trips (company_id,origin,destination,departure_at,price,seat_count)
    VALUES (?,?,?,?,?,?)
  ");
  foreach ([
    [1,'İstanbul','Ankara',  date('Y-m-d 10:00:00', $now + 3600*6),  35000, 44],
    [1,'İstanbul','İzmir',   date('Y-m-d 14:30:00', $now + 3600*10), 42000, 40],
    [2,'Ankara',  'İstanbul',date('Y-m-d 09:00:00', $now + 3600*30), 36000, 46],
    [2,'İzmir',   'İstanbul',date('Y-m-d 20:00:00', $now + 3600*34), 39000, 42],
  ] as $e) {
    $stmt->execute($e);
  }
}

/* ------------------------------------------------------------------
   5) CSRF YARDIMCILARI
-------------------------------------------------------------------*/
function csrf_token(): string {
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}
function csrf_check(): void {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $t = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)$t)) {
      http_response_code(403);
      exit('CSRF doğrulaması başarısız.');
    }
  }
}

/* ------------------------------------------------------------------
   6) GENEL YARDIMCI FONKSİYONLAR
-------------------------------------------------------------------*/
function me(): ?array {
  return $_SESSION['user'] ?? null;
}

function require_login(): void {
  if (!me()) {
    $next = urlencode($_SERVER['REQUEST_URI'] ?? 'index.php');
    header("Location: login.php?next={$next}");
    exit;
  }
}

function reload_me(PDO $pdo): void {
  if (!me()) return;
  $st = $pdo->prepare("SELECT * FROM users WHERE id=?");
  $st->execute([me()['id']]);
  $_SESSION['user'] = $st->fetch() ?: null;
}

function is_admin(): bool {
  return (me()['role'] ?? '') === 'admin';
}

function is_company_admin(): bool {
  return (me()['role'] ?? '') === 'company_admin';
}

function format_price(int $krs): string {
  return number_format($krs/100, 2, ',', '.') . ' ₺';
}

function base_path(): string {
  $base = rtrim(dirname($_SERVER['PHP_SELF'] ?? ''), '/');
  return $base === '.' ? '' : $base;
}

/* ------------------------------------------------------------------
   7) HATA GÖSTERİMİ (ENV'e göre)
-------------------------------------------------------------------*/
if (getenv('APP_ENV') === 'prod') {
  ini_set('display_errors', '0');
  ini_set('log_errors', '1');
  @mkdir(__DIR__.'/logs', 0777, true);
  ini_set('error_log', __DIR__.'/logs/php-error.log');
} else {
  ini_set('display_errors', '1');
}

# 🚌 Bilet Satın Alma Platformu

Bu proje, PHP ve SQLite kullanılarak geliştirilmiş basit bir **otobüs bileti satın alma sistemi**dir.  
Kullanıcılar sefer arayabilir, bilet satın alabilir, bakiye yükleyebilir ve biletlerini PDF olarak indirebilir.

---

## 🚀 Özellikler

- ✨ Kullanıcı kayıt ve giriş sistemi  
- 💳 Cüzdan (bakiye) yükleme  
- 🎫 Bilet satın alma ve iptal etme  
- 🧾 PDF formatında bilet çıktısı alma  
- 🧍‍♂️ Firma yetkilisi (company admin) için sefer yönetimi (CRUD)  
- 🛠️ Sistem yöneticisi (admin) için firma ve kupon yönetimi  
- 🔐 Güvenlik:
  - CSRF koruması  
  - Güvenli oturum ayarları (HTTPOnly, SameSite)  
  - Transaction ve veri bütünlüğü kontrolleri  

---

## 🐳 Docker ile Çalıştırma

### 1️⃣ Gerekli dosyalar:
- `Dockerfile`
- `docker-compose.yml`

### 2️⃣ Çalıştırma adımları:

```bash
# Proje klasörüne gir
cd yavuzlarodev

# Docker imajını oluştur ve konteyneri başlat
docker compose up --build
Sonrasında tarayıcıda:
http://localhost:8080
adresine giderek projeyi kullanabilirsiniz.

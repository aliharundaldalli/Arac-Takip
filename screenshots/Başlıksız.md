# 🚗 Kampüs Araç Kayıt ve Takip Sistemi

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)  
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)  
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)  
![Status](https://img.shields.io/badge/Proje-Tamamland%C4%B1-success?style=for-the-badge)

---

**Kampüs Araç Kayıt ve Takip Sistemi**, üniversiteler, kurumlar ve site yönetimleri için geliştirilmiş modern ve güvenli bir araç yönetim uygulamasıdır.  
Sistem; araç giriş–çıkış izinleri, başvuru onay süreçleri ve kullanıcı/rol yönetimini tek bir merkezden yönetilebilir hale getirir.

Yönetici, Birim Sorumlusu ve Kullanıcı rollerine özel tasarlanmış arayüzleriyle hızlı, ölçeklenebilir ve kullanımı son derece kolay bir yapı sunar.

---

## 🚀 Temel Özellikler

### 👥 Rol Tabanlı Yetkilendirme

- **Admin:** Kullanıcı, araç, birim ve sistem ayarlarını yönetir.
    
- **Birim Sorumlusu (Approver):** Kendi birimine ait araç başvurularını onaylar veya reddeder.
    
- **Kullanıcı (Personel / Öğrenci):** Araç kaydı yapar ve başvurularının durumunu takip eder.
    

---

### 🚗 Araç Yönetimi

- Plaka, marka, model, renk ve ruhsat görseli ile detaylı araç kaydı.
    
- Başvuruların **Bekliyor → Onaylandı / Reddedildi** akışında yönetilmesi.
    
- Reddedilen başvurular için gerekçe zorunluluğu.
    
- Yöneticiler için **birleşik araç yönetimi** (kendi araçlarını kullanıcı panelinden yönetebilir).
    

---

### 🔔 Bildirim & İletişim

- Araç başvuru onay/red işlemlerinde otomatik e-posta bildirimi.
    
- “Şifremi Unuttum” için token tabanlı güvenli şifre sıfırlama.
    
- Admin panelinden SMTP ayarlarının manuel olarak yapılandırılması (Gmail, Outlook vb.)
    

---

### 🛡️ Güvenlik ve Altyapı

- Rol tabanlı erişim kontrolü (RBAC).
    
- PDO ile güvenli veritabanı işlemleri.
    
- `password_hash()` ile güçlü şifreleme.
    
- Admin tarafından sıfırlanan hesaplarda **zorunlu şifre yenileme**.
    
- Tüm sayfalarda oturum güvenliği ve CSRF’ye dayanıklı yapı.
    

---

### 🎨 Arayüz ve Kullanılabilirlik

- Bootstrap 5 ile oluşturulmuş modern, responsive tasarım.
    
- Temiz dashboard yapısı, hızlı erişim menüleri.
    
- Mobil uyumlu arayüz.
    

---

## 🛠️ Kurulum

### 🔹 1. Dosyaları Yükleyin

Proje klasörünü yerel sunucunuzdaki `htdocs` veya `www` dizinine kopyalayın.

---

### 🔹 2. Otomatik Kurulum (Tavsiye Edilen)

1. Tarayıcıdan şu adresi açın:  
    **`http://localhost/arac_takip/install.php`**
    
2. Veritabanı bilgilerinizi girin.
    
3. Yönetici kullanıcı oluşturun.
    
4. “Kurulumu Tamamla” butonuna tıklayın.
    

> **Not:** Kurulumdan sonra güvenlik için `install.php` dosyasını silin.

---

### 🔹 3. Manuel Kurulum

1. `database.sql` dosyasını veritabanınıza içe aktarın.
    
2. `includes/config.sample.php` dosyasını `config.php` olarak kaydedip düzenleyin:
    

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'arac_yonetim');
```

---

### 🔹 4. Bağımlılıkların Kurulumu

Projede e-posta gönderimi için PHPMailer kullanılır.

Terminalde çalıştırın:

```bash
composer install
```

---

## 📂 Proje Dizin Yapısı

```text
admin/       → Yönetici paneli
approver/    → Birim sorumlusu onay ekranları
auth/        → Giriş, çıkış, şifre sıfırlama
user/        → Kullanıcı paneli ve araç işlemleri
includes/    → Veritabanı ve yardımcı fonksiyonlar
assets/      → CSS, JS ve görseller
uploads/     → Ruhsat/resim dosyaları
```

---

## ✉️ SMTP Yapılandırması

Mail gönderimi için Admin panelindeki:  
**Ayarlar → SMTP Ayarları**  
ekranına erişip e-posta sunucusu bilgilerinizi girin.

---

## 👨‍💻 Geliştirici

**Ali Harun DALDALLI**

---

# 🚗 AHD Kampüs Araç Kayıt ve Takip Sistemi

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![Status](https://img.shields.io/badge/Durum-Tamamland%C4%B1-success?style=for-the-badge)

Bu proje, üniversite kampüsleri, siteler veya kurumlar için geliştirilmiş; araç giriş-çıkış izinlerini yönetmek, araç kayıtlarını tutmak ve onay süreçlerini dijitalleştirmek amacıyla hazırlanmış **web tabanlı bir yönetim sistemidir.**

---

## 🚀 Özellikler

### 👤 Kullanıcı Rolleri ve Yetkiler
* **Admin:** Tüm sistemi yönetir, kullanıcı ekler, birim tanımlar, genel ayarları değiştirir.
* **Birim Sorumlusu (Approver):** Kendi birimine (Fakülte/Bölüm) ait başvuruları inceler, onaylar veya reddeder.
* **Kullanıcı (Personel/Öğrenci):** Kendi araçlarını sisteme kaydeder, başvuru durumunu takip eder.

### 🚗 Araç Yönetimi
* **Detaylı Kayıt:** Plaka, marka, model, renk ve ruhsat görseli yükleme ile araç kaydı.
* **Onay Mekanizması:** Başvurular "Bekliyor", "Onaylandı" veya "Reddedildi" statülerinde yönetilir.
* **Gerekçeli Red:** Reddedilen araçlar için açıklama girilir ve kullanıcıya bildirilir.
* **Birleşik Yönetim:** Yöneticiler, yönetim panelinden çıkmadan kendi şahsi araçlarını da yönetebilir.

### 🔔 Bildirim ve İletişim
* **Otomatik E-Posta:** Araç onay/red durumlarında ve yeni kayıtlarda sistem otomatik mail gönderir.
* **Güvenli Şifre Sıfırlama:** Token tabanlı "Şifremi Unuttum" yapısı.
* **SMTP Desteği:** Admin panelinden Gmail, Outlook vb. SMTP ayarları yapılandırılabilir.

### 🛡️ Güvenlik ve Altyapı
* **Rol Tabanlı Erişim (RBAC):** Her kullanıcı sadece yetkisi olan sayfalara erişebilir.
* **Veri Güvenliği:** PDO ile SQL Injection koruması ve `password_hash` ile şifreleme.
* **Zorunlu Şifre Değişimi:** Admin tarafından sıfırlanan şifrelerde, kullanıcının ilk girişte şifre değiştirmesi zorunlu kılınır.

---

## 📸 Ekran Görüntüleri

### 1. Yönetici Paneli (Admin)
Sistemdeki tüm araçların, kullanıcıların ve ayarların yönetildiği merkez.

| Dashboard & Özet | Araç Yönetimi |
|:---:|:---:|
| ![Admin Dashboard](screenshots/admin_dashboard.png) | ![Admin Araçlar](screenshots/admin_araclar.png) |

| Kullanıcı Yönetimi | Toplu Kullanıcı Ekleme (Excel) |
|:---:|:---:|
| ![Admin Kullanıcılar](screenshots/admin_kullanicilar.png) | ![Excel Yükleme](screenshots/admin_toplu_kullanici_ekleme.png) |

| Birim Yönetimi | Site Ayarları |
|:---:|:---:|
| ![Birimler](screenshots/admin_birimler.png) | ![Ayarlar](screenshots/site_ayarlari.png) |

---

### 2. Birim Sorumlusu (Approver)
Kendi fakültesine/birimine ait başvuruları inceleyen yetkili ekranı.

| Birim Özeti | Bekleyen Başvurular |
|:---:|:---:|
| ![Approver Dashboard](screenshots/approver_dashboard.png) | ![Bekleyenler](screenshots/approver_bekleyen.png) |

---

### 3. Kullanıcı Arayüzü
Personel ve öğrencilerin işlem yaptığı ekranlar.

| Kullanıcı Paneli | Araçlarım |
|:---:|:---:|
| ![User Dashboard](screenshots/user_dahsboard.png) | ![User Araçlar](screenshots/user_araclarim.png) |

---

## 🛠️ Kurulum

Projeyi yerel sunucunuzda (Localhost) çalıştırmak için aşağıdaki adımları izleyin.

### Seçenek A: Otomatik Kurulum (Önerilen)
1.  Dosyaları `htdocs` veya `www` klasörüne kopyalayın.
2.  Tarayıcıdan `http://localhost/arac_takip/install.php` adresine gidin.
3.  Veritabanı ve Yönetici bilgilerinizi girerek kurulumu tamamlayın.
4.  **Güvenlik:** Kurulum sonrası `install.php` dosyasını silin.

### Seçenek B: Manuel Kurulum
1.  Veritabanınızda `arac_yonetim` adında bir DB oluşturun.
2.  `database/arac_yonetim.sql` dosyasını içe aktarın.
3.  `includes/config.sample.php` dosyasının adını `config.php` yapın.
4.  Dosya içerisindeki DB bilgilerini düzenleyin:
    ```php
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'arac_yonetim');
    ```
---

### 4. Güvenlik ve Giriş İşlemleri
Giriş güvenliği, şifre sıfırlama ve yetkilendirme ekranları.

| Giriş Ekranı | Şifremi Unuttum |
|:---:|:---:|
| ![Login Ekranı](screenshots/login.png) | ![Şifre Sıfırlama İsteği](screenshots/sifremi_unuttum.png) |

| Şifre Sıfırlama (Token) | Zorunlu Şifre Değişimi |
|:---:|:---:|
| ![Yeni Şifre Belirleme](screenshots/sifre-sifirla.png) | ![Zorla Değiştir](screenshots/zorla_sifre_degistirme.png) |

---

### 5. E-Posta Bildirim Sistemi (SMTP)
Sistem, durumu değişen araçlar ve hesap güvenliği için otomatik HTML formatında e-postalar gönderir.

| Kayıt Başarılı Maili | Araç Onay Maili |
|:---:|:---:|
| ![Kayıt Maili](screenshots/arac_kayit_maili.png) | ![Onay Maili](screenshots/arac_onay_maili.png) |

| Şifre Sıfırlama Maili | SMTP Ayar Paneli |
|:---:|:---:|
| ![Şifre Maili](screenshots/sifremi_unuttum_maili.png) | ![SMTP Ayarları](screenshots/smtp_ayarlari.png) |

###  6. Bağımlılıkların Kurulumu

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

# Kampüs Araç Kayıt ve Takip Sistemi

Bu proje, üniversite kampüsleri veya kurumlar için geliştirilmiş, araç giriş-çıkış izinlerini yönetmek, araç kayıtlarını tutmak ve onay süreçlerini dijitalleştirmek amacıyla hazırlanmış bir web tabanlı yönetim sistemidir.

## 🚀 Özellikler

*   **Kullanıcı Rolleri:**
    *   **Admin:** Tüm sistemi yönetir, kullanıcı ekler, birim tanımlar, ayarları değiştirir.
    *   **Birim Sorumlusu (Approver):** Kendi birimine veya genel havuza düşen araç başvurularını inceler, onaylar veya reddeder.
    *   **Kullanıcı (Personel/Öğrenci):** Kendi araçlarını sisteme kaydeder, durumunu takip eder.
*   **Araç Yönetimi:**
    *   Plaka, marka, model, renk ve ruhsat görseli ile araç kaydı.
    *   Onay mekanizması (Bekliyor -> Onaylandı / Reddedildi).
    *   Reddedilen araçlar için gerekçe belirtme ve e-posta bildirimi.
*   **E-Posta Bildirimleri:**
    *   Araç onaylandığında veya reddedildiğinde otomatik bilgilendirme maili.
    *   Şifre sıfırlama işlemleri için token tabanlı güvenli mail gönderimi.
*   **Güvenlik:**
    *   Rol tabanlı erişim kontrolü (RBAC).
    *   PDO ile güvenli veritabanı işlemleri.
    *   Şifreli parola saklama (Password Hash).
*   **Modern Arayüz:**
    *   Bootstrap 5 ve özel CSS ile responsive tasarım.
    *   Kullanıcı dostu dashboard ve tablolar.
*   **Gelişmiş Özellikler (Yeni):**
    *   **Birleşik Araç Yönetimi:** Yöneticiler ve Birim Sorumluları, kişisel araçlarını standart kullanıcı arayüzü üzerinden yönetir.
    *   **Akıllı Navigasyon:** Yetkili kullanıcılar için kullanıcı panelinden yönetim paneline tek tıkla dönüş butonu.
    *   **Gelişmiş Kullanıcı Düzenleme:** Admin panelinden kullanıcıların TC Kimlik numarası düzenlenebilir ve şifreleri sıfırlanabilir (Bir sonraki girişte değişim zorunluluğu ile).

## 🛠️ Kurulum

1.  **Dosyaları İndirin:**
    Proje dosyalarını sunucunuza (örn: `htdocs` veya `www` klasörüne) kopyalayın.

2.  **Otomatik Kurulum:**
    *   Tarayıcınızdan `http://localhost/arac_takip/install.php` adresine gidin.
    *   Veritabanı bilgilerinizi (Host, Kullanıcı Adı, Şifre) girin.
    *   Yönetici hesabı bilgilerinizi belirleyin.
    *   "Kurulumu Tamamla" butonuna tıklayın. Sistem otomatik olarak veritabanını oluşturacak ve ayar dosyasını yazacaktır.

3.  **Manuel Kurulum (Alternatif):**
    *   `database.sql` dosyasını veritabanınıza içe aktarın.
    *   `includes/config.sample.php` dosyasını `config.php` yapıp bilgileri düzenleyin.

4.  **Bağımlılıklar:**
    *   `composer install` komutu ile PHPMailer kütüphanesini yükleyin.

5.  **Güvenlik:**
    *   Kurulum bittikten sonra `install.php` dosyasını **mutlaka silin**.

## 📂 Proje Yapısı

*   `admin/`: Yönetici paneli sayfaları.
*   `approver/`: Birim sorumlusu paneli sayfaları.
*   `auth/`: Giriş, çıkış, şifre sıfırlama işlemleri.
*   `user/`: Son kullanıcı (personel/öğrenci) ekranları.
*   `includes/`: Veritabanı bağlantısı, fonksiyonlar ve header/footer dosyaları.
*   `assets/`: CSS, JS ve görsel dosyalar.
*   `uploads/`: Yüklenen ruhsat görselleri.

## ✉️ SMTP Ayarları

Sistemin mail gönderebilmesi için Admin panelinden **Ayarlar > SMTP Ayarları** sekmesine giderek geçerli bir SMTP sunucu bilgisi (Gmail, Outlook vb.) girmeniz gerekmektedir.

---
Geliştirici: [Adınız/Ekibiniz]

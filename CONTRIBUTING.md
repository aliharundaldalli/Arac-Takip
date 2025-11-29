# Katkıda Bulunma Rehberi

**AHD Kampüs Araç Kayıt ve Takip Sistemi** projesine katkıda bulunmak istediğiniz için teşekkürler! Bu proje, üniversite ve kurumlar için açık kaynaklı bir çözüm sunmayı amaçlar.

Aşağıdaki adımları ve kuralları takiperek projeye değer katabilirsiniz.

---

## 🚀 Nasıl Katkıda Bulunabilirim?

### 1. Hazırlık ve Kurulum
Öncelikle projeyi yerel ortamınızda çalışır hale getirin (Bkz: `README.md` -> Kurulum). `config.sample.php` dosyasını `config.php` olarak ayarlamayı unutmayın.

### 2. Geliştirme Süreci

1.  **Forklayın**: Bu repoyu sağ üstteki butonu kullanarak kendi GitHub hesabınıza forklayın.
2.  **Klonlayın**: Forkladığınız repoyu yerel makinenize indirin.
3.  **Branch Oluşturun**: Yapacağınız değişiklik türüne göre isimlendirilmiş bir dal (branch) açın.
    ```bash
    # Yeni bir özellik için:
    git checkout -b ozellik/plaka-tanima

    # Hata düzeltmesi için:
    git checkout -b fix/giris-sayfasi-hatasi
    ```
4.  **Değişiklik Yapın**: Kodunuzu yazın. Veritabanı şemasında değişiklik yaptıysanız `database/arac_yonetim.sql` dosyasını da güncellemeyi unutmayın.
5.  **Test Edin**:
    * Farklı kullanıcı rolleriyle (Admin, Approver, User) giriş yapıp değişikliğinizi test edin.
    * PHP 8.3 sürümünde hata vermediğinden emin olun.
6.  **Commitleyin**: Yaptığınız değişikliği net anlatan bir mesaj yazın.
    ```bash
    git commit -m "Özellik: Araç detay sayfasına ruhsat önizleme eklendi"
    ```
7.  **Pushlayın**: Branchinizi GitHub'a gönderin.
    ```bash
    git push origin ozellik/plaka-tanima
    ```
8.  **Pull Request (PR) Açın**: GitHub üzerinden ana repoya (main branch) Pull Request gönderin. Açıklama kısmında neyi değiştirdiğinizi detaylıca yazın.

---

## 💻 Kodlama ve Güvenlik Standartları

Bu proje hassas veriler (plaka, telefon, kimlik vb.) barındırabileceği için güvenlik en önemli önceliktir.

### Genel Kurallar
* **PHP Sürümü:** Kodlarınız PHP 8.3 ve üzeri ile uyumlu olmalıdır.
* **İsimlendirme:** Değişken ve fonksiyon isimlerinde `camelCase` veya `snake_case` kullanabilirsiniz, ancak dosya genelindeki tutarlılığı bozmayın.
* **Arayüz:** Yeni eklenen sayfaların **Bootstrap 5** yapısına ve projenin mevcut renk paletine (Header/Footer) uygun olduğundan emin olun.

### Güvenlik Kuralları (Kritik!)
1.  **SQL Injection:** Tüm veritabanı sorgularında **PDO ve Prepared Statements** kullanmak **zorunludur**. Doğrudan SQL içine değişken yazdığınız kodlar kabul edilmeyecektir.
    * ❌ Yanlış: `query("SELECT * FROM users WHERE id = $id")`
    * ✅ Doğru: `prepare("SELECT * FROM users WHERE id = :id")`
2.  **XSS Koruması:** Ekrana basılan kullanıcı girdilerini `htmlspecialchars()` fonksiyonundan geçirin.
3.  **Yetki Kontrolü:** Yeni oluşturduğunuz sayfalarda (özellikle `admin/` ve `approver/` klasörlerinde) en üstte oturum ve rol kontrolü (`RoleCheck`) olduğundan emin olun.

---

## 🐛 Hata Bildirimi

Bir hata (bug) bulursanız veya bir güvenlik açığı fark ederseniz:

1.  GitHub **Issues** sekmesine gidin.
2.  Hatanın nasıl tekrar edilebileceğini adım adım yazın.
3.  Varsa ekran görüntüsü ve hata loglarını ekleyin.

---

## 📄 Lisans

Katkıda bulunduğunuz kodlar, projenin mevcut **MIT Lisansı** altında yayımlanacaktır.

---

Geliştirmeye verdiğiniz destek için tekrar teşekkürler!

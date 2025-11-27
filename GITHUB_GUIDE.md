# GitHub'a Proje Yükleme Adımları

Bu rehber, projenizi GitHub'a yüklemeniz için gereken adımları içerir.

## 1. Yerel Git Kurulumu (Terminal)

Aşağıdaki komutları sırasıyla terminalde çalıştırarak projenizi git'e hazırlayın:

```bash
# 1. Git'i başlat
git init

# 2. Tüm dosyaları ekle (.gitignore kurallarına uyar)
git add .

# 3. İlk versiyonu oluştur (Commit)
git commit -m "Proje ilk kurulumu: Araç Takip Sistemi v1.0"

# 4. Ana dal ismini 'main' olarak ayarla
git branch -M main
```

## 2. GitHub'da Depo (Repository) Oluşturma

1.  [GitHub.com](https://github.com) adresine gidin ve giriş yapın.
2.  Sağ üst köşedeki **+** ikonuna tıklayın ve **New repository** seçeneğini seçin.
3.  **Repository name** kısmına bir isim verin (örn: `arac-takip-sistemi`).
4.  **Public** (Herkese açık) veya **Private** (Gizli) seçeneğini belirleyin.
5.  **Initialize this repository with:** kısmındaki kutucukları **BOŞ BIRAKIN** (README, .gitignore vs. eklemeyin, çünkü bizde zaten var).
6.  **Create repository** butonuna tıklayın.

## 3. Projeyi GitHub'a Gönderme

GitHub'da depoyu oluşturduktan sonra size verilen **HTTPS** veya **SSH** bağlantısını kopyalayın. Ardından terminale dönüp şu komutları girin:

```bash
# 1. Uzak sunucuyu ekle (LINK_ADRESI yerine kopyaladığınız adresi yapıştırın)
# Örnek: https://github.com/kullaniciadi/arac-takip-sistemi.git
git remote add origin LINK_ADRESI

# 2. Kodları gönder
git push -u origin main
```

## Notlar
*   `includes/config.php` dosyası güvenlik nedeniyle yüklenmez. Kullanıcılar `includes/config.sample.php` dosyasını kullanarak kendi ayarlarını yapmalıdır.
*   `uploads/` klasörü yüklenmez, her kurulumda boş olarak oluşturulur.

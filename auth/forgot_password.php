<?php
require_once '../includes/config.php';
require_once '../includes/functions.php'; // <-- Artık bu dosyayı çağırıyoruz

// Ayarları çek (Sadece başlık ve logo için, SMTP ayarları functions.php'de çekilecek)
$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_title', 'site_logo', 'site_favicon', 'theme_color')");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle   = $settings['site_title'] ?? "Ahd Akademi";
$themeColor  = $settings['theme_color'] ?? "#0d6efd";
$siteLogo    = $settings['site_logo'] ?? null;
$siteFavicon = $settings['site_favicon'] ?? null;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = "Lütfen kayıtlı e-posta adresinizi girin.";
    } else {
        // Kullanıcı bul
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "Bu e-posta ile kayıtlı kullanıcı bulunamadı.";
        } else {

            // Token oluştur
            $token = bin2hex(random_bytes(32));
            $expires = date("Y-m-d H:i:s", strtotime("+15 minutes"));

            // Eski tokenleri temizle (Veritabanında password_resets tablosu olmalı)
            // Eğer yoksa users tablosuna reset_token sütunu eklediysen orayı güncellemelisin.
            // Varsayılan olarak password_resets tablosunu kullanıyoruz:
            try {
                // Tablo kontrolü yapılabilir veya hata bastırılabilir
                $pdo->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user['id']]);
                
                $pdo->prepare("
                    INSERT INTO password_resets (user_id, token, expires_at)
                    VALUES (?, ?, ?)
                ")->execute([$user['id'], $token, $expires]);

                // Link Oluşturma
                // HTTP/HTTPS algılama
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $host = $_SERVER['HTTP_HOST'];
                // Alt klasörde çalışıyorsa yolu bulmak için (Opsiyonel, daha sağlam link yapısı)
                $path = dirname($_SERVER['PHP_SELF']); 
                // dirname '/auth' döndürür, o yüzden reset_password.php'ye tam yol verelim:
                $resetLink = "$protocol://$host" . str_replace('/auth', '', $path) . "/auth/reset_password.php?token=" . $token;
                
                // Eğer yukarıdaki dinamik link hata verirse manuel link:
                // $resetLink = "http://localhost/arac_takip/auth/reset_password.php?token=" . $token;

                // --- MAİL GÖNDERİMİ (Functions.php Kullanarak) ---
                
                $mailSubject = "Şifre Sıfırlama - $pageTitle";
                $mailBody = "
                    <p>Merhaba {$user['name']},</p>
                    <p>Şifrenizi sıfırlamak için bir talepte bulundunuz.</p>
                    <p>Aşağıdaki butona tıklayarak yeni şifrenizi belirleyebilirsiniz:</p>
                    <p style='text-align: center;'>
                        <a href='$resetLink' class='btn'>Şifremi Sıfırla</a>
                    </p>
                    <p><small>Bu bağlantı 15 dakika süreyle geçerlidir.</small></p>
                    <p>Eğer bu işlemi siz yapmadıysanız, lütfen bu e-postayı dikkate almayınız.</p>
                ";

                if (sendMail($email, $mailSubject, $mailBody)) {
                    $success = "Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.";
                } else {
                    $error = "Mail gönderilemedi. Lütfen sistem yöneticisiyle iletişime geçin.";
                }

            } catch (PDOException $e) {
                $error = "Veritabanı hatası: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Şifremi Unuttum - <?= htmlspecialchars($pageTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php if ($siteFavicon): ?>
        <link rel="icon" href="../assets/img/<?= htmlspecialchars($siteFavicon) ?>">
    <?php endif; ?>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            background: linear-gradient(135deg, #1c92d2, #f2fcfe);
            /* Arkaplan rengini temaya uydurmak istersen: */
            /* background: linear-gradient(135deg, <?= $themeColor ?>, #f0f2f5); */
            font-family: 'Segoe UI', sans-serif;
        }

        .glass-card {
            width: 100%;
            max-width: 420px;
            padding: 2.5rem 2rem 2rem;
            border-radius: 20px;
            background: rgba(255,255,255,0.85); /* Biraz daha opak yaptım okunabilirlik için */
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.5);
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            text-align: center;
            animation: fadeIn .7s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-25px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .brand-circle {
            width: 85px;
            height: 85px;
            margin: 0 auto 1rem;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }

        .brand-circle img {
            max-height: 50px;
            max-width: 50px;
        }

        .btn-reset {
            background-color: <?= $themeColor ?>;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 12px;
            width: 100%;
            font-size: 1.1rem;
            font-weight: 600;
            transition: .25s;
            cursor: pointer;
        }

        .btn-reset:hover {
            filter: brightness(90%);
            transform: translateY(-2px);
        }

        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #ddd;
            background: #fff;
        }
        
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15); /* Tema rengine göre ayarlanabilir */
            border-color: <?= $themeColor ?>;
        }

        a.back-link {
            font-size: .9rem;
            color: #666;
            text-decoration: none;
            font-weight: 500;
            transition: 0.2s;
        }

        a.back-link:hover {
            color: <?= $themeColor ?>;
        }
    </style>
</head>

<body>

<div class="glass-card">

    <div class="brand-circle">
        <?php if ($siteLogo): ?>
            <img src="../assets/img/<?= htmlspecialchars($siteLogo) ?>" alt="Logo">
        <?php else: ?>
            <i class="bi bi-envelope-exclamation-fill fs-1" style="color:<?= $themeColor ?>;"></i>
        <?php endif; ?>
    </div>

    <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($pageTitle) ?></h4>
    <p class="text-muted small mb-4">Şifrenizi sıfırlamak için e-posta adresinizi girin.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger p-2 small shadow-sm border-0 mb-3">
            <i class="bi bi-exclamation-circle me-1"></i> <?= $error ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success p-2 small shadow-sm border-0 mb-3">
            <i class="bi bi-check-circle me-1"></i> <?= $success ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="mt-2 text-start">
        <div class="mb-3">
            <label class="form-label fw-semibold small text-secondary ps-1">E-Posta Adresi</label>
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 rounded-start-3"><i class="bi bi-envelope text-muted"></i></span>
                <input type="email" name="email" class="form-control border-start-0 ps-0" required placeholder="ornek@mail.com">
            </div>
        </div>

        <button type="submit" class="btn-reset shadow-sm">
            <i class="bi bi-send me-2"></i>Bağlantıyı Gönder
        </button>

        <div class="text-center mt-4">
            <a href="login.php" class="back-link">
                <i class="bi bi-arrow-left me-1"></i>Giriş Sayfasına Dön
            </a>
        </div>
    </form>

</div>

</body>
</html>
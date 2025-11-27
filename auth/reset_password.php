<?php
require_once '../includes/config.php';

$error = '';
$success = '';

// Token kontrolü
if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Geçersiz bağlantı.");
}

$token = $_GET['token'];

// Token geçerli mi?
$stmt = $pdo->prepare("
    SELECT pr.*, u.email, u.name 
    FROM password_resets pr 
    JOIN users u ON pr.user_id = u.id
    WHERE pr.token = ? LIMIT 1
");
$stmt->execute([$token]);
$resetData = $stmt->fetch();

if (!$resetData) {
    die("Token bulunamadı veya geçersiz.");
}

// Süre kontrolü
if (strtotime($resetData['expires_at']) < time()) {
    die("Bu sıfırlama bağlantısının süresi dolmuş. Lütfen tekrar isteyin.");
}

$user_id = $resetData['user_id'];

// Site ayarları
$stmt = $pdo->query("SELECT * FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$pageTitle   = $settings['site_title'] ?? "Ahd Akademi";
$themeColor  = $settings['theme_color'] ?? "#0d6efd";
$siteLogo    = $settings['site_logo'] ?? null;
$siteFavicon = $settings['site_favicon'] ?? null;

// FORM POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pass    = trim($_POST['password']);
    $confirm = trim($_POST['confirm_password']);

    if (empty($pass) || empty($confirm)) {
        $error = "Lütfen tüm alanları doldurun.";
    } elseif ($pass !== $confirm) {
        $error = "Şifreler eşleşmiyor.";
    } elseif (strlen($pass) < 6) {
        $error = "Şifre en az 6 karakter olmalıdır.";
    } else {

        $hashed = password_hash($pass, PASSWORD_DEFAULT);

        // Şifreyi güncelle
        $pdo->prepare("UPDATE users SET password=?, must_change_password=0 WHERE id=?")
            ->execute([$hashed, $user_id]);

        // Tokeni sil
        $pdo->prepare("DELETE FROM password_resets WHERE user_id=?")
            ->execute([$user_id]);

        $success = "Şifreniz başarıyla güncellendi! Yönlendiriliyorsunuz...";

        header("refresh:2; url=login.php");
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Şifre Sıfırla - <?= htmlspecialchars($pageTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php if ($siteFavicon): ?>
        <link rel="icon" href="../assets/img/<?= $siteFavicon ?>">
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            height: 100vh;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #1c92d2, #f2fcfe);
            font-family: 'Segoe UI', sans-serif;
        }

        .glass-card {
            width: 100%;
            max-width: 420px;
            padding: 2.5rem 2rem 2rem;
            border-radius: 20px;
            background: rgba(255,255,255,0.25);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.35);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
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
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .brand-circle img {
            max-height: 60px;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #ddd;
            background: rgba(255,255,255,0.65);
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
        }

        .btn-reset:hover {
            transform: translateY(-2px);
            opacity: 0.95;
        }

        a.back-link {
            color: #444;
            font-size: .9rem;
            text-decoration: none;
        }

        a.back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

<div class="glass-card">

    <div class="brand-circle">
        <?php if ($siteLogo): ?>
            <img src="../assets/img/<?= htmlspecialchars($siteLogo) ?>" alt="Logo">
        <?php else: ?>
            <i class="bi bi-shield-lock-fill fs-1" style="color:<?= $themeColor ?>"></i>
        <?php endif; ?>
    </div>

    <h3 class="fw-bold text-dark">Şifre Sıfırla</h3>
    <p class="text-muted small">Hesabınız için yeni bir şifre belirleyin.</p>

    <?php if ($error): ?>
        <div class="alert alert-danger p-2 small"><?= $error ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success p-2 small"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" class="mt-3 text-start">
        <label class="form-label fw-semibold">Yeni Şifre</label>
        <input type="password" name="password" class="form-control mb-3" required>

        <label class="form-label fw-semibold">Yeni Şifre (Tekrar)</label>
        <input type="password" name="confirm_password" class="form-control mb-3" required>

        <button class="btn-reset">Şifreyi Güncelle</button>

        <div class="text-center mt-3">
            <a href="login.php" class="back-link">Giriş Sayfasına Dön</a>
        </div>
    </form>

</div>

</body>
</html>

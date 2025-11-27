<?php
// auth/login.php
require_once '../includes/config.php';

// Zaten giriş yapmışsa yönlendir
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$pageTitle = $settings['site_title'] ?? 'Ahd Akademi';
$themeColor = $settings['theme_color'] ?? '#0d6efd';
$siteFavicon = $settings['site_favicon'] ?? '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tc = trim($_POST['tc']);
    $password = $_POST['password'];

    if (empty($tc) || empty($password)) {
        $error = "Lütfen alanları doldurun.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE tc_number = ? LIMIT 1");
        $stmt->execute([$tc]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['is_active'] == 0) {
                $error = "Hesap pasif.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['unit_id'] = $user['unit_id'];
                $_SESSION['must_change_password'] = $user['must_change_password'];

                if ($user['must_change_password'] == 1) {
                    header("Location: change_password.php"); 
                    exit;
                }
                header("Location: ../index.php");
                exit;
            }
        } else {
            $error = "Bilgiler hatalı!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş - <?= htmlspecialchars($pageTitle) ?></title>
        <?php if (!empty($siteFavicon)): ?>
        <link rel="icon" href="../assets/img/<?= $siteFavicon ?>">
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1c92d2, #f2fcfe);
            align-items: center;
            justify-content: center;
        }
        .brand-circle {
            color: <?= $themeColor ?>;
        }
        .btn-login {
            background-color: <?= $themeColor ?>;
        }
        .btn-login:hover {
            background-color: #084298;
        }
        a.forgot {
            font-size: .9rem;
            color: #944b4bff;
            text-decoration: none;
        }
        a.forgot:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>

<div class="glass-card">

    <div class="brand-circle">
        <?php if (!empty($settings['site_logo'])): ?>
            <img src="../assets/img/<?= htmlspecialchars($settings['site_logo']) ?>" style="max-width:60px;">
        <?php else: ?>
            <i class="bi bi-shield-lock-fill"></i>
        <?php endif; ?>
    </div>

    <h3 class="text-center mb-3 fw-bold text-dark"><?= htmlspecialchars($pageTitle) ?></h3>
    <p class="text-center text-muted mb-4">Hesabınıza giriş yapın</p>

    <?php if ($error): ?>
        <div class="alert alert-danger p-2 text-center"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label fw-semibold">TC Kimlik No</label>
            <input type="text" class="form-control" name="tc" maxlength="11" required placeholder="11 haneli TC giriniz">
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">Şifre</label>
            <input type="password" class="form-control" name="password" required placeholder="******">
        </div>

        <button type="submit" class="btn btn-login w-100" style="color:white;">Giriş Yap</button>

        <div class="text-center mt-3">
            <a href="forgot_password.php" class="forgot">Şifremi Unuttum?</a>
        </div>
    </form>

</div>

</body>
</html>

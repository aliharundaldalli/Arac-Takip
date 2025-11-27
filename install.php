<?php
// install.php
// Bu dosya veritabanını kurmak ve config dosyasını oluşturmak içindir.

$message = "";
$msgType = "";

if (file_exists('includes/config.php')) {
    $message = "Sistem zaten kurulu görünüyor (includes/config.php mevcut). Yeniden kurmak istiyorsanız önce bu dosyayı silin.";
    $msgType = "warning";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['install'])) {
    
    // 1. Form Verilerini Al
    $db_host = trim($_POST['db_host']);
    $db_name = trim($_POST['db_name']);
    $db_user = trim($_POST['db_user']);
    $db_pass = $_POST['db_pass'];
    $db_port = trim($_POST['db_port']);

    $admin_name  = trim($_POST['admin_name']);
    $admin_tc    = trim($_POST['admin_tc']);
    $admin_email = trim($_POST['admin_email']);
    $admin_pass  = $_POST['admin_pass'];

    // 2. Veritabanı Bağlantısını Test Et
    try {
        // Önce veritabanı olmadan bağlan
        $dsn = "mysql:host=$db_host;port=$db_port;charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

        // Veritabanını oluştur (Varsa bir şey yapma)
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_turkish_ci");
        $pdo->exec("USE `$db_name`");

        // 3. SQL Dosyasını İçe Aktar
        if (file_exists('database.sql')) {
            $sql = file_get_contents('database.sql');
            // SQL dosyasındaki sorguları çalıştır
            $pdo->exec($sql);
        } else {
            throw new Exception("database.sql dosyası bulunamadı!");
        }

        // 4. Admin Kullanıcısını Ekle
        $hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (tc_number, name, email, password, role, is_active, must_change_password) VALUES (?, ?, ?, ?, 'admin', 1, 0)");
        $stmt->execute([$admin_tc, $admin_name, $admin_email, $hashed_pass]);

        // 5. Config Dosyasını Oluştur
        $configContent = "<?php\n";
        $configContent .= "// includes/config.php\n";
        $configContent .= "// Oluşturulma Tarihi: " . date('d.m.Y H:i') . "\n\n";
        $configContent .= "if (session_status() === PHP_SESSION_NONE) {\n    session_start();\n}\n";
        $configContent .= "error_reporting(E_ALL);\nini_set('display_errors', 1);\n\n";
        $configContent .= "// --- VERİTABANI AYARLARI ---\n";
        $configContent .= "\$host = '$db_host';\n";
        $configContent .= "\$dbname = '$db_name';\n";
        $configContent .= "\$username = '$db_user';\n";
        $configContent .= "\$password = '$db_pass';\n";
        $configContent .= "\$port = $db_port;\n";
        $configContent .= "\$charset = 'utf8mb4';\n\n";
        $configContent .= "try {\n";
        $configContent .= "    \$dsn = \"mysql:host=\$host;port=\$port;dbname=\$dbname;charset=\$charset\";\n";
        $configContent .= "    \$options = [\n";
        $configContent .= "        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,\n";
        $configContent .= "        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n";
        $configContent .= "        PDO::ATTR_EMULATE_PREPARES   => false,\n";
        $configContent .= "    ];\n";
        $configContent .= "    \$pdo = new PDO(\$dsn, \$username, \$password, \$options);\n";
        $configContent .= "} catch (\\PDOException \$e) {\n";
        $configContent .= "    die(\"Veritabanı bağlantı hatası: \" . \$e->getMessage());\n";
        $configContent .= "}\n\n";
        $configContent .= "// Ayarları veritabanından çek\n";
        $configContent .= "try {\n";
        $configContent .= "    \$stmt = \$pdo->query(\"SELECT * FROM settings\");\n";
        $configContent .= "    \$settings = [];\n";
        $configContent .= "    while (\$row = \$stmt->fetch()) {\n";
        $configContent .= "        \$settings[\$row['setting_key']] = \$row['setting_value'];\n";
        $configContent .= "    }\n";
        $configContent .= "} catch (\\PDOException \$e) {\n";
        $configContent .= "    \$settings = ['site_title' => 'Araç Takip', 'theme_color' => '#0d6efd'];\n";
        $configContent .= "}\n";
        $configContent .= "?>";

        file_put_contents('includes/config.php', $configContent);

        $message = "Kurulum başarıyla tamamlandı! <br> <a href='auth/login.php' class='btn btn-success mt-2'>Giriş Yap</a> <br><br> <small class='text-danger'>Güvenlik için lütfen install.php dosyasını siliniz.</small>";
        $msgType = "success";

    } catch (Exception $e) {
        $message = "Kurulum Hatası: " . $e->getMessage();
        $msgType = "danger";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Kurulumu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding-top: 50px; }
        .install-card { max-width: 600px; margin: 0 auto; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .step-header { background: #0d6efd; color: white; padding: 20px; border-radius: 15px 15px 0 0; }
    </style>
</head>
<body>

<div class="container mb-5">
    <div class="card install-card border-0">
        <div class="step-header text-center">
            <h3 class="fw-bold mb-0">🚀 Sistem Kurulum Sihirbazı</h3>
            <p class="mb-0 opacity-75">Veritabanı ve Yönetici Ayarları</p>
        </div>
        <div class="card-body p-4">

            <?php if ($message): ?>
                <div class="alert alert-<?= $msgType ?> text-center">
                    <?= $message ?>
                </div>
            <?php endif; ?>

            <?php if ($msgType != 'success'): ?>
            <form method="POST">
                <h5 class="text-primary fw-bold mb-3"><i class="bi bi-database-fill me-2"></i>Veritabanı Bilgileri</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-8">
                        <label class="form-label">Sunucu (Host)</label>
                        <input type="text" name="db_host" class="form-control" value="localhost" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Port</label>
                        <input type="number" name="db_port" class="form-control" value="3306" required>
                        <div class="form-text text-muted" style="font-size: 11px;">MAMP: 8889, XAMPP: 3306</div>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Veritabanı Adı</label>
                        <input type="text" name="db_name" class="form-control" value="arac_yonetim" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kullanıcı Adı</label>
                        <input type="text" name="db_user" class="form-control" value="root" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Şifre</label>
                        <input type="text" name="db_pass" class="form-control" placeholder="Varsa şifreniz">
                    </div>
                </div>

                <hr>

                <h5 class="text-primary fw-bold mb-3"><i class="bi bi-person-badge-fill me-2"></i>Yönetici Hesabı</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Ad Soyad</label>
                        <input type="text" name="admin_name" class="form-control" required placeholder="Admin">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">TC Kimlik No</label>
                        <input type="text" name="admin_tc" class="form-control" required maxlength="11" placeholder="11111111111">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">E-Posta</label>
                        <input type="email" name="admin_email" class="form-control" required placeholder="admin@site.com">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Şifre</label>
                        <input type="password" name="admin_pass" class="form-control" required placeholder="******">
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" name="install" class="btn btn-primary btn-lg fw-bold">
                        Kurulumu Tamamla
                    </button>
                </div>
            </form>
            <?php endif; ?>

        </div>
    </div>
</div>

</body>
</html>

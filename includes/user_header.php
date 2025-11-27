<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Config Dosyası Kontrolü
if (file_exists('../includes/config.php')) {
    require_once '../includes/config.php';
} elseif (file_exists('config.php')) {
    require_once 'config.php';
}

// Güvenlik: Giriş yapılmamışsa login'e at (Opsiyonel, her sayfada zaten kontrol ediyorsun ama burada da olması iyidir)
if (!isset($_SESSION['user_id'])) {
    // header("Location: ../auth/login.php"); 
    // exit;
}

// Ayarları Çek
$settings = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM settings");
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) { }
}

$themeColor  = $settings['theme_color'] ?? '#0d6efd'; // Varsayılan Mavi
$siteLogo    = $settings['site_logo'] ?? '';
$siteTitle   = $settings['site_title'] ?? 'Kampüs Paneli';
$siteFavicon = $settings['site_favicon'] ?? '';
$userName    = $_SESSION['user_name'] ?? 'Kullanıcı';

// Aktif Sayfayı Bul (Menüyü parlatmak için)
$activePage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteTitle); ?></title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <?php if (!empty($siteFavicon)): ?>
    <link rel="icon" href="../assets/img/<?= $siteFavicon; ?>">
    <?php endif; ?>

    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
        }

        /* User Header Stili */
        .user-header {
            background: <?= $themeColor ?>; 
            background: linear-gradient(135deg, <?= $themeColor ?>, #0a2347);
            padding: 0.8rem 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.1rem;
            color: white !important;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-link {
            padding: 8px 16px !important;
            font-weight: 500;
        }
        
        .brand-logo-img {
            height: 36px;
            background: rgba(255,255,255,0.1);
            padding: 3px;
            border-radius: 6px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark user-header">
    <div class="container">
        
        <a class="navbar-brand" href="index.php">
            <?php if (!empty($siteLogo)): ?>
                <img src="../assets/img/<?= $siteLogo ?>" class="brand-logo-img" alt="Logo">
            <?php else: ?>
                <div class="brand-logo-img d-flex align-items-center justify-content-center" style="width: 36px;">
                    <i class="bi bi-car-front-fill fs-5"></i>
                </div>
            <?php endif; ?>
            <div class="d-flex flex-column">
                <span class="lh-1"><?= htmlspecialchars($siteTitle) ?></span>
                <span style="font-size: 10px; opacity: 0.7; font-weight: 400;">Öğrenci & Personel</span>
            </div>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#userNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="userNavbar">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                
                <!-- ROL BAZLI GERİ DÖNÜŞ BUTONU -->
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                    <li class="nav-item me-2">
                        <a class="btn btn-danger btn-sm fw-bold d-flex align-items-center mt-1" href="../admin/index.php">
                            <i class="bi bi-arrow-left-circle-fill me-2"></i> Yönetim Paneline Dön
                        </a>
                    </li>
                <?php elseif (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'approver'): ?>
                    <li class="nav-item me-2">
                        <a class="btn btn-warning btn-sm fw-bold d-flex align-items-center mt-1 text-dark" href="../approver/index.php">
                            <i class="bi bi-arrow-left-circle-fill me-2"></i> Birim Paneline Dön
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $activePage == 'index.php' ? 'active' : '' ?>" href="index.php">
                            <i class="bi bi-grid-fill me-1"></i> Ana Sayfa
                        </a>
                    </li>
                <?php endif; ?>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($activePage, ['my_vehicles.php', 'add_vehicle.php', 'edit_vehicle.php']) ? 'active' : '' ?>" 
                       href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-car-front-fill me-1"></i> Araçlarım
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item" href="my_vehicles.php">
                                <i class="bi bi-list-ul me-2 text-primary"></i>Araçlarımı Listele
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="vehicle_add.php">
                                <i class="bi bi-plus-circle me-2 text-success"></i>Yeni Araç Ekle
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item d-lg-none">
                    <a class="nav-link <?= $activePage == 'profile.php' ? 'active' : '' ?>" href="profile.php">
                        <i class="bi bi-person-gear me-1"></i> Profil Ayarları
                    </a>
                </li>

            </ul>

            <ul class="navbar-nav ms-auto d-none d-lg-flex">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown">
                        <div class="bg-white text-dark rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-weight: bold;">
                            <?= strtoupper(substr($userName, 0, 1)) ?>
                        </div>
                        <span><?= htmlspecialchars($userName) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-header">Hesap İşlemleri</span></li>
                        <li>
                            <a class="dropdown-item" href="profile.php">
                                <i class="bi bi-person-gear me-2"></i>Profil Bilgilerim
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="../auth/logout.php">
                                <i class="bi bi-box-arrow-right me-2"></i>Güvenli Çıkış
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
            
            <div class="d-lg-none mt-2">
                <a href="../auth/logout.php" class="btn btn-danger w-100">
                    <i class="bi bi-box-arrow-right me-2"></i>Çıkış Yap
                </a>
            </div>

        </div>
    </div>
</nav>

<main class="flex-grow-1 py-4"></main>
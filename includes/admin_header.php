<?php
// Oturum ve Config Kontrolleri
if (session_status() === PHP_SESSION_NONE) session_start();

// Dosya yolu kontrolü
if (file_exists('../includes/config.php')) {
    require_once '../includes/config.php';
} elseif (file_exists('config.php')) {
    require_once 'config.php';
}

// Admin yetki kontrolü (Güvenlik)
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// Ayarları Veritabanından Çek
$settings = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT * FROM settings");
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {}
}

$siteTitle   = $settings['site_title'] ?? 'Yönetim Paneli';
$siteLogo    = $settings['site_logo'] ?? '';
$siteFavicon = $settings['site_favicon'] ?? '';
$themeColor  = $settings['theme_color'] ?? '#3b136c'; // Varsayılan Admin Rengi

$activePage  = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteTitle) ?></title>

    <?php if (!empty($siteFavicon)): ?>
        <link rel="icon" href="../assets/img/<?= $siteFavicon ?>">
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .navbar-admin {
            background: linear-gradient(to right, <?= $themeColor ?>, #1a1a1a);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .nav-link.active {
            background-color: rgba(255,255,255,0.1);
            border-radius: 6px;
            font-weight: 500;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-admin py-3 sticky-top">
    <div class="container">

        <a class="navbar-brand fw-bold d-flex align-items-center" href="index.php">
            <?php if (!empty($siteLogo)): ?>
                <img src="../assets/img/<?= $siteLogo ?>" style="height:40px; margin-right:10px; border-radius:4px;">
            <?php else: ?>
                <i class="bi bi-shield-check me-2 fs-4"></i>
            <?php endif; ?>
            <?= htmlspecialchars($siteTitle) ?>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminMenu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="adminMenu">
            
            <ul class="navbar-nav me-auto ms-3">
                
                <li class="nav-item">
                    <a class="nav-link <?= $activePage == 'index.php' ? 'active' : '' ?>" href="index.php">
                        <i class="bi bi-speedometer2 me-1"></i> Özet
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $activePage == 'pending_vehicles.php' ? 'active' : '' ?>" href="pending_vehicles.php">
                        <i class="bi bi-hourglass-split me-1 text-warning"></i> Bekleyenler
                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($activePage, ['vehicles.php', 'approved_vehicles.php', 'rejected_vehicles.php']) ? 'active' : '' ?>" 
                       href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-collection me-1"></i> Araç Yönetimi
                    </a>
                    <ul class="dropdown-menu shadow border-0 rounded-3 mt-2">
                        <li><a class="dropdown-item" href="vehicles.php"><i class="bi bi-list-ul me-2"></i>Tüm Araçlar</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="approved_vehicles.php"><i class="bi bi-check-circle me-2 text-success"></i>Onaylananlar</a></li>
                        <li><a class="dropdown-item" href="rejected_vehicles.php"><i class="bi bi-x-circle me-2 text-danger"></i>Reddedilenler</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($activePage, ['users.php', 'units.php', 'users_import.php']) ? 'active' : '' ?>" 
                       href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-buildings me-1"></i> Kurumsal
                    </a>
                    <ul class="dropdown-menu shadow border-0 rounded-3 mt-2">
                        <li><a class="dropdown-item" href="units.php"><i class="bi bi-building me-2"></i>Birim Yönetimi</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="users.php"><i class="bi bi-people me-2"></i>Kullanıcılar</a></li>
                        <li><a class="dropdown-item" href="users_import.php"><i class="bi bi-file-earmark-excel me-2"></i>Excel İçe Aktar</a></li>
                    </ul>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-car-front-fill me-1"></i> Şahsi İşlemler
                    </a>
                    <ul class="dropdown-menu shadow border-0 rounded-3 mt-2">
                        <li><a class="dropdown-item" href="../user/my_vehicles.php">
                            <i class="bi bi-eye me-2"></i>Araçlarım</a>
                        </li>
                        <li><a class="dropdown-item fw-bold" href="../user/vehicle_add.php">
                            <i class="bi bi-plus-circle-fill me-2 text-primary"></i>Hızlı Araç Ekle</a>
                        </li>
                    </ul>
                </li>

            </ul>

            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                        <div class="bg-white text-dark rounded-circle d-flex align-items-center justify-content-center me-2" 
                             style="width: 32px; height: 32px; font-weight: bold;">
                            <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
                        </div>
                        <span class="d-none d-md-inline"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Yönetici') ?></span>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person-circle me-2"></i>Profilim</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="bi bi-gear me-2"></i>Sistem Ayarları</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Çıkış Yap</a></li>
                    </ul>
                </li>
            </ul>

        </div>
    </div>
</nav>

<main class="flex-grow-1 py-4">
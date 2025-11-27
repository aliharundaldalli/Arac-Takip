<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Config
if (file_exists('../includes/config.php')) {
    require_once '../includes/config.php';
} else {
    require_once 'config.php';
}

// Approver yetki kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'approver') {
    header("Location: ../auth/login.php");
    exit;
}

// Birim ID
if (!isset($_SESSION['unit_id'])) {
    $stmt = $pdo->prepare("SELECT unit_id FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $_SESSION['unit_id'] = $stmt->fetchColumn();
}

// Ayarlar
$settings = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$siteTitle   = $settings['site_title'] ?? 'Birim Paneli';
$siteLogo    = $settings['site_logo'] ?? '';
$siteFavicon = $settings['site_favicon'] ?? '';
$themeColor  = $settings['theme_color'] ?? '#343a40';

$activePage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteTitle) ?></title>

    <?php if ($siteFavicon): ?>
        <link rel="icon" href="../assets/img/<?= $siteFavicon ?>">
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar-approver {
            background: linear-gradient(to right, <?= $themeColor ?>, #1c1f23);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark navbar-approver py-3 sticky-top">
    <div class="container">

        <a class="navbar-brand d-flex align-items-center fw-bold" href="index.php">
            <?php if ($siteLogo): ?>
                <img src="../assets/img/<?= $siteLogo ?>" style="height:40px; margin-right:10px; border-radius:4px;">
            <?php else: ?>
                <i class="bi bi-building-check fs-4 me-2"></i>
            <?php endif; ?>
            <?= htmlspecialchars($siteTitle) ?>
        </a>

        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div id="menu" class="collapse navbar-collapse">
            
            <ul class="navbar-nav me-auto ms-3">

                <li class="nav-item">
                    <a class="nav-link <?= $activePage == 'index.php' ? 'active' : '' ?>"
                       href="index.php"><i class="bi bi-speedometer2 me-1"></i> Özet</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $activePage == 'pending_vehicles.php' ? 'active' : '' ?>"
                       href="pending_vehicles.php"><i class="bi bi-hourglass-split me-1 text-warning"></i> Bekleyenler</a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($activePage, ['approved_vehicles.php','rejected_vehicles.php']) ? 'active' : '' ?>"
                       href="#" data-bs-toggle="dropdown"><i class="bi bi-archive me-1"></i> Arşiv</a>

                    <ul class="dropdown-menu rounded-3 shadow border-0 mt-2">
                        <li><a class="dropdown-item" href="approved_vehicles.php">
                            <i class="bi bi-check-circle text-success me-2"></i> Onaylananlar</a></li>
                        <li><a class="dropdown-item" href="rejected_vehicles.php">
                            <i class="bi bi-x-circle text-danger me-2"></i> Reddedilenler</a></li>
                    </ul>
                </li>

                <!-- Şahsi Araçlar -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($activePage, ['my_vehicles.php','add_vehicle.php','edit_vehicle.php','vehicle_detail.php']) ? 'active' : '' ?>"
                       href="#" data-bs-toggle="dropdown">
                        <i class="bi bi-car-front-fill me-1"></i> Şahsi Araçlarım
                    </a>

                    <ul class="dropdown-menu rounded-3 shadow border-0 mt-2">
                        <li><a class="dropdown-item" href="../user/my_vehicles.php">
                            <i class="bi bi-list-ul me-2"></i> Araç Listem</a></li>
                        <li><a class="dropdown-item" href="../user/vehicle_add.php">
                            <i class="bi bi-plus-circle me-2"></i> Yeni Araç Ekle</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $activePage == 'users.php' ? 'active' : '' ?>"
                       href="users.php"><i class="bi bi-people me-1"></i> Personel / Öğrenci</a>
                </li>
            </ul>

            <!-- SAĞ TARAF PROFİL -->
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown" href="#">
                        <div class="bg-white text-dark rounded-circle d-flex align-items-center justify-content-center me-2"
                             style="width:32px; height:32px; font-weight:bold;">
                             <?= strtoupper(substr($_SESSION['user_name'] ?? 'A', 0, 1)) ?>
                        </div>
                        <?= htmlspecialchars($_SESSION['user_name']) ?>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li><a class="dropdown-item" href="profile.php">
                            <i class="bi bi-person-gear me-2"></i> Profilim</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../auth/logout.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Çıkış Yap</a></li>
                    </ul>
                </li>
            </ul>

        </div>
    </div>
</nav>

<main class="flex-grow-1 py-4">

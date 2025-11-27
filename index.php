<?php
// index.php
require_once 'includes/config.php';

// 1. KONTROL: Giriş yapmış mı?
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

// 2. KONTROL: Şifre değiştirmesi zorunlu mu? (YENİ EKLENEN KISIM)
// Login olurken session'a atmıştık, ama veritabanından taze kontrol etmek daha güvenli.
$stmt = $pdo->prepare("SELECT must_change_password FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userStatus = $stmt->fetch();

if ($userStatus && $userStatus['must_change_password'] == 1) {
    // Sadece şifre değiştirme sayfasına gitmesine izin ver
    header("Location: auth/change_password.php");
    exit;
}

// 3. KONTROL: Rolü ne?
$role = $_SESSION['user_role'] ?? 'user';

switch ($role) {
    case 'admin':
        header("Location: admin/index.php");
        break;
    case 'approver':
        header("Location: approver/index.php");
        break;
    case 'user':
        header("Location: user/index.php");
        break;
    default:
        header("Location: auth/logout.php");
        exit;
}
?>
<?php
require_once '../includes/config.php';
require_once '../includes/user_header.php';

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$msgType = "";

// ---------------------------------------------------
// 1. TELEFON GÜNCELLEME İŞLEMİ
// ---------------------------------------------------
if (isset($_POST['update_phone'])) {
    $phone = trim($_POST['phone']);
    
    // Basit bir validasyon (İsteğe bağlı regex eklenebilir)
    if (strlen($phone) > 20) {
        $message = "Telefon numarası çok uzun.";
        $msgType = "danger";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
        if ($stmt->execute([$phone, $user_id])) {
            $message = "İletişim bilgileriniz güncellendi.";
            $msgType = "success";
        } else {
            $message = "Güncelleme sırasında bir hata oluştu.";
            $msgType = "danger";
        }
    }
}

// ---------------------------------------------------
// 2. ŞİFRE DEĞİŞTİRME İŞLEMİ
// ---------------------------------------------------
if (isset($_POST['change_password'])) {
    $current_pass = $_POST['current_pass']; // Güvenlik için mevcut şifre sorulmalı (Opsiyonel ama önerilir)
    $pass1 = $_POST['new_pass'];
    $pass2 = $_POST['confirm_pass'];

    // Mevcut şifre kontrolü (Veritabanından çekip doğrulama)
    $checkStmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $checkStmt->execute([$user_id]);
    $currentUser = $checkStmt->fetch();

    if (!password_verify($current_pass, $currentUser['password'])) {
        $message = "Mevcut şifrenizi yanlış girdiniz.";
        $msgType = "danger";
    } elseif ($pass1 !== $pass2) {
        $message = "Yeni şifreler birbiriyle eşleşmiyor!";
        $msgType = "danger";
    } elseif (strlen($pass1) < 6) {
        $message = "Yeni şifre en az 6 karakter olmalıdır.";
        $msgType = "danger";
    } else {
        $hashed = password_hash($pass1, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($updateStmt->execute([$hashed, $user_id])) {
            $message = "Şifreniz başarıyla değiştirildi.";
            $msgType = "success";
        }
    }
}

// ---------------------------------------------------
// KULLANICI BİLGİLERİNİ ÇEKME (En Son Hali)
// ---------------------------------------------------
$stmt = $pdo->prepare("SELECT u.*, un.name as unit_name 
                       FROM users u 
                       LEFT JOIN units un ON un.id = u.unit_id 
                       WHERE u.id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Rol İsimlendirmesi
$roleLabels = [
    'admin' => '<span class="badge bg-danger">Yönetici</span>',
    'approver' => '<span class="badge bg-warning text-dark">Birim Sorumlusu</span>',
    'user' => '<span class="badge bg-secondary">Personel/Öğrenci</span>'
];

$userRoleLabel = $roleLabels[$user['role']] ?? '<span class="badge bg-light text-dark">Belirsiz</span>';
?>

<div class="container mt-5 mb-5">
    
    <?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-<?= $msgType == 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center pt-5 pb-4">
                    <div class="mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle" style="width: 100px; height: 100px;">
                            <i class="bi bi-person-fill text-secondary" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                    
                    <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($user['name']) ?></h5>
                    <div class="mb-3"><?= $userRoleLabel ?></div>
                    
                    <hr class="my-4 opacity-25">
                    
                    <div class="text-start">
                        <div class="mb-3">
                            <label class="small text-muted text-uppercase fw-bold">TC Kimlik No</label>
                            <div class="fw-medium text-dark">
                                <i class="bi bi-person-vcard me-2 text-primary"></i>
                                <?= htmlspecialchars($user['tc_number']) ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted text-uppercase fw-bold">E-Posta Adresi</label>
                            <div class="fw-medium text-dark text-truncate" title="<?= htmlspecialchars($user['email']) ?>">
                                <i class="bi bi-envelope me-2 text-primary"></i>
                                <?= htmlspecialchars($user['email']) ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="small text-muted text-uppercase fw-bold">Bağlı Birim</label>
                            <div class="fw-medium text-dark">
                                <i class="bi bi-building me-2 text-primary"></i>
                                <?= htmlspecialchars($user['unit_name'] ?? 'Birim Atanmamış') ?>
                            </div>
                        </div>

                        <div>
                            <label class="small text-muted text-uppercase fw-bold">Kayıt Tarihi</label>
                            <div class="fw-medium text-dark">
                                <i class="bi bi-calendar-check me-2 text-primary"></i>
                                <?= date('d.m.Y', strtotime($user['created_at'])) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-telephone-fill me-2 text-primary"></i>İletişim Bilgileri</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="row align-items-end">
                            <div class="col-md-8 mb-3 mb-md-0">
                                <label for="phone" class="form-label text-muted">Telefon Numarası</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-phone"></i></span>
                                    <input type="text" name="phone" id="phone" class="form-control border-start-0 ps-0" 
                                           placeholder="05XX XXX XX XX"
                                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                                <div class="form-text small">İletişim için kullanılacak aktif numaranızı giriniz.</div>
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="submit" name="update_phone" class="btn btn-primary w-100">
                                    <i class="bi bi-save me-1"></i> Kaydet
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-shield-lock-fill me-2 text-primary"></i>Güvenlik Ayarları</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        
                        <div class="mb-3">
                            <label for="current_pass" class="form-label text-muted">Mevcut Şifre</label>
                            <input type="password" name="current_pass" id="current_pass" class="form-control" required>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="new_pass" class="form-label text-muted">Yeni Şifre</label>
                                <input type="password" name="new_pass" id="new_pass" class="form-control" required minlength="6">
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_pass" class="form-label text-muted">Yeni Şifre (Tekrar)</label>
                                <input type="password" name="confirm_pass" id="confirm_pass" class="form-control" required minlength="6">
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" name="change_password" class="btn btn-dark px-4">
                                <i class="bi bi-key me-1"></i> Şifreyi Güncelle
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../includes/user_footer.php'; ?>
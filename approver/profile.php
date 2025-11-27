<?php
require_once '../includes/approver_header.php'; // Config ve Session buradan gelir

// GÜVENLİK: Sadece Approver
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'approver') {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$msgType = "";

// ---------------------------------------------------
// 1. TELEFON GÜNCELLEME
// ---------------------------------------------------
if (isset($_POST['update_phone'])) {
    $phone = trim($_POST['phone']);
    
    // Basit validasyon
    if (strlen($phone) > 20) {
        $message = "Telefon numarası çok uzun.";
        $msgType = "danger";
    } else {
        $stmt = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
        if ($stmt->execute([$phone, $user_id])) {
            $message = "İletişim bilgileriniz güncellendi.";
            $msgType = "success";
        } else {
            $message = "Güncelleme sırasında hata oluştu.";
            $msgType = "danger";
        }
    }
}

// ---------------------------------------------------
// 2. ŞİFRE DEĞİŞTİRME
// ---------------------------------------------------
if (isset($_POST['change_password'])) {
    $current_pass = $_POST['current_pass'];
    $pass1 = $_POST['new_pass'];
    $pass2 = $_POST['confirm_pass'];

    // Mevcut şifreyi veritabanından çek
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $real_pass = $stmt->fetchColumn();

    if (!password_verify($current_pass, $real_pass)) {
        $message = "Mevcut şifrenizi yanlış girdiniz.";
        $msgType = "danger";
    } elseif ($pass1 !== $pass2) {
        $message = "Yeni şifreler birbiriyle uyuşmuyor.";
        $msgType = "danger";
    } elseif (strlen($pass1) < 6) {
        $message = "Yeni şifre en az 6 karakter olmalıdır.";
        $msgType = "danger";
    } else {
        $hashed = password_hash($pass1, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        
        if ($update->execute([$hashed, $user_id])) {
            $message = "Şifreniz başarıyla değiştirildi.";
            $msgType = "success";
        }
    }
}

// ---------------------------------------------------
// KULLANICI BİLGİLERİNİ ÇEK
// ---------------------------------------------------
$stmt = $pdo->prepare("SELECT u.*, un.name as unit_name 
                       FROM users u 
                       LEFT JOIN units un ON un.id = u.unit_id 
                       WHERE u.id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>

<div class="container mt-4 mb-5">
    
    <?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?> alert-dismissible fade show shadow-sm" role="alert">
        <i class="bi bi-<?= $msgType == 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
        <?= $message ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100 rounded-4 overflow-hidden">
                <div class="card-header bg-dark text-white text-center py-4">
                    <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <span class="fs-1 fw-bold text-dark"><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                    </div>
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($user['name']) ?></h5>
                    <span class="badge bg-warning text-dark">Birim Sorumlusu</span>
                </div>
                
                <div class="card-body p-4">
                    <div class="mb-3">
                        <label class="small text-muted text-uppercase fw-bold">Bağlı Birim</label>
                        <div class="fw-medium text-dark d-flex align-items-center">
                            <i class="bi bi-building-fill me-2 text-primary"></i>
                            <?= htmlspecialchars($user['unit_name'] ?? 'Genel Yönetim') ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted text-uppercase fw-bold">E-Posta</label>
                        <div class="fw-medium text-dark text-truncate" title="<?= htmlspecialchars($user['email']) ?>">
                            <i class="bi bi-envelope-at me-2 text-primary"></i>
                            <?= htmlspecialchars($user['email']) ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="small text-muted text-uppercase fw-bold">TC Kimlik No</label>
                        <div class="fw-medium text-dark">
                            <i class="bi bi-person-vcard me-2 text-primary"></i>
                            <?= htmlspecialchars($user['tc_number']) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-telephone-fill me-2 text-success"></i>İletişim Bilgileri</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <div class="row align-items-end">
                            <div class="col-md-8 mb-3 mb-md-0">
                                <label for="phone" class="form-label text-muted">Telefon Numarası</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-phone"></i></span>
                                    <input type="text" name="phone" id="phone" class="form-control" 
                                           placeholder="05XX XXX XX XX"
                                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" name="update_phone" class="btn btn-success w-100 text-white fw-semibold">
                                    <i class="bi bi-save me-1"></i> Kaydet
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-dark"><i class="bi bi-shield-lock-fill me-2 text-danger"></i>Güvenlik Ayarları</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        
                        <div class="alert alert-light border small text-muted mb-4">
                            <i class="bi bi-info-circle me-1"></i> Hesabınızın güvenliği için şifrenizi düzenli aralıklarla değiştirmeniz önerilir.
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Mevcut Şifre</label>
                            <input type="password" name="current_pass" class="form-control" required>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Yeni Şifre</label>
                                <input type="password" name="new_pass" class="form-control" required minlength="6">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Yeni Şifre (Tekrar)</label>
                                <input type="password" name="confirm_pass" class="form-control" required minlength="6">
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" name="change_password" class="btn btn-danger px-4 fw-semibold">
                                <i class="bi bi-key me-1"></i> Şifreyi Güncelle
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../includes/approver_footer.php'; ?>
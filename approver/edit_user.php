<?php
require_once '../includes/approver_header.php'; // Session ve Config

// 1. GÜVENLİK VE PARAMETRE KONTROLÜ
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<div class='container mt-5'><div class='alert alert-danger'>Geçersiz Kullanıcı ID.</div></div>");
}

$target_user_id = intval($_GET['id']);
$myUnitID = $_SESSION['unit_id'];

// 2. KULLANICIYI ÇEK (GÜVENLİK FİLTRESİ: Sadece benim birimimdeki kullanıcı!)
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND unit_id = ?");
$stmt->execute([$target_user_id, $myUnitID]);
$user = $stmt->fetch();

if (!$user) {
    echo "<div class='container mt-5'>
            <div class='alert alert-danger shadow-sm border-0'>
                <h4 class='alert-heading'><i class='bi bi-shield-lock-fill'></i> Erişim Engellendi</h4>
                <p>Bu kullanıcı sizin biriminize ait değil veya bulunamadı.</p>
                <a href='users.php' class='btn btn-danger btn-sm'>Listeye Dön</a>
            </div>
          </div>";
    require_once 'approver_footer.php';
    exit;
}

$message = "";
$msgType = "";

// 3. GÜNCELLEME İŞLEMİ (POST)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $tc    = trim($_POST['tc_number']);
    $name  = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if (empty($tc) || empty($name) || empty($email)) {
        $message = "Lütfen zorunlu alanları (TC, İsim, Email) doldurun.";
        $msgType = "danger";
    } else {
        try {
            // Güncelleme Sorgusu
            $sql = "UPDATE users SET tc_number=?, name=?, email=?, phone=? WHERE id=? AND unit_id=?";
            $updateStmt = $pdo->prepare($sql);
            $updateStmt->execute([$tc, $name, $email, $phone, $target_user_id, $myUnitID]);

            $message = "Kullanıcı bilgileri başarıyla güncellendi.";
            $msgType = "success";
            
            // Verileri ekranda güncel göstermek için değişkenleri yenile
            $user['tc_number'] = $tc;
            $user['name'] = $name;
            $user['email'] = $email;
            $user['phone'] = $phone;

        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $message = "Hata: Bu TC Kimlik veya E-Posta başkası tarafından kullanılıyor.";
            } else {
                $message = "Veritabanı hatası: " . $e->getMessage();
            }
            $msgType = "danger";
        }
    }
}
?>

<div class="container mt-4 mb-5">

    <?php if ($message): ?>
        <div class="alert alert-<?= $msgType ?> alert-dismissible fade show shadow-sm">
            <i class="bi bi-info-circle-fill me-2"></i> <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Personel Düzenle</h4>
            <small class="text-muted">Birim personelinin iletişim bilgilerini güncelleyin.</small>
        </div>
        <a href="users.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Listeye Dön
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-primary">
                        <i class="bi bi-person-lines-fill me-2"></i>Kullanıcı Bilgileri
                    </h6>
                </div>
                
                <div class="card-body p-4">
                    <form method="POST">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">TC Kimlik No</label>
                                <input type="text" name="tc_number" class="form-control" 
                                       value="<?= htmlspecialchars($user['tc_number']) ?>" required maxlength="11">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Ad Soyad</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">E-Posta Adresi</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                                <div class="form-text">E-posta değişirse kullanıcının giriş adresi de değişir.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Telefon Numarası</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-telephone"></i></span>
                                    <input type="text" name="phone" class="form-control" 
                                           placeholder="05XX XXX XX XX"
                                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-light border mt-4 small text-muted">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            <strong>Not:</strong> Şifre sıfırlama veya birim değiştirme yetkisi sadece sistem yöneticisindedir (Admin).
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary px-4 py-2 fw-bold shadow-sm">
                                <i class="bi bi-save me-2"></i> Değişiklikleri Kaydet
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/approver_footer.php'; ?>
<?php
require_once '../includes/config.php';
// Composer autoload
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
} elseif (file_exists('../../vendor/autoload.php')) {
    require_once '../../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../includes/user_header.php';

// Oturum Kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";
$msgType = "";

// Ayarları Çek
$settings = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$siteUrl = $settings['site_url'] ?? 'http://localhost';

// -----------------------------------------------------------------------
// MEVCUT ARAÇ SAYISINI KONTROL ET
// -----------------------------------------------------------------------
$stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE user_id = ?");
$stmt->execute([$user_id]);
$currentVehicleCount = $stmt->fetchColumn();
$limitReached = ($currentVehicleCount >= 3);

// -----------------------------------------------------------------------
// POST İŞLEMİ
// -----------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // EĞER LİMİT DOLMUŞSA İŞLEMİ DURDUR
    if ($limitReached) {
        $message = "Maksimum araç sınırına (3) ulaştınız. Yeni araç ekleyemezsiniz.";
        $msgType = "warning";
    } 
    else {
        $plate   = strtoupper(trim($_POST['plate']));
        $brand   = trim($_POST['brand']);
        $model   = trim($_POST['model']);
        $year    = intval($_POST['year']);
        $color   = trim($_POST['color']);
        $ownership = trim($_POST['ownership']);

        // 1. Dosya Yükleme
        $license_image = null;
        $uploadOk = true;

        if (!empty($_FILES['license_image']['name'])) {
            $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
            $ext = strtolower(pathinfo($_FILES['license_image']['name'], PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                if (!is_dir("../uploads")) { mkdir("../uploads", 0777, true); }
                
                $newFileName = "ruhsat_" . $user_id . "_" . time() . "." . $ext;
                
                if (move_uploaded_file($_FILES['license_image']['tmp_name'], "../uploads/" . $newFileName)) {
                    $license_image = $newFileName;
                } else {
                    $message = "Dosya yüklenirken hata oluştu."; $msgType = "danger"; $uploadOk = false;
                }
            } else {
                $message = "Geçersiz format. Sadece JPG, PNG, PDF."; $msgType = "danger"; $uploadOk = false;
            }
        }

        if ($uploadOk) {
            // 2. Veritabanı Kayıt
            try {
                $stmt = $pdo->prepare("INSERT INTO vehicles (user_id, plate, brand, model, year, color, ownership, license_image, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
                
                if ($stmt->execute([$user_id, $plate, $brand, $model, $year, $color, $ownership, $license_image])) {
                    $message = "Araç başarıyla kaydedildi! Onay bekleniyor.";
                    $msgType = "success";

                    // 3. Mail Gönderme (Functions dosyası ile)
                    require_once '../includes/functions.php';

                    // Kullanıcı bilgilerini çek (Mail için gerekli)
                    $userStmt = $pdo->prepare("SELECT u.*, un.name as unit_name FROM users u LEFT JOIN units un ON un.id = u.unit_id WHERE u.id = ?");
                    $userStmt->execute([$user_id]);
                    $userInfo = $userStmt->fetch();

                    $mailSubject = "Yeni Araç: $plate";
                    $mailBody = "
                        <h3>Yeni Araç Başvurusu</h3>
                        <p>Kullanıcı <b>{$userInfo['name']}</b> yeni bir araç ekledi ve onay bekliyor.</p>
                        <p><b>Plaka:</b> $plate</p>
                        <p><b>Marka/Model:</b> $brand / $model ($year)</p>
                        <hr>
                        <p><a href='$siteUrl/admin/pending_vehicles.php'>Yönetim Paneline Git</a></p>
                    ";

                    // Adminlere Gönder
                    $admins = $pdo->query("SELECT email FROM users WHERE role = 'admin' AND is_active = 1")->fetchAll();
                    foreach($admins as $admin) { 
                        sendMail($admin['email'], $mailSubject, $mailBody); 
                    }
                    
                    // Birim Sorumlusuna Gönder
                    if ($userInfo['unit_id']) {
                        $approvers = $pdo->prepare("SELECT email FROM users WHERE role = 'approver' AND unit_id = ?");
                        $approvers->execute([$userInfo['unit_id']]);
                        while($app = $approvers->fetch()) { 
                            sendMail($app['email'], $mailSubject, $mailBody); 
                        }
                    }

                    // Yönlendirme (JS ile çünkü headerlar gönderildi)
                    echo "<script>
                        setTimeout(function() {
                            window.location.href = 'my_vehicles.php';
                        }, 2000);
                    </script>";
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $message = "Bu plaka ($plate) zaten sistemde kayıtlı.";
                    $msgType = "warning";
                } else {
                    $message = "Veritabanı hatası oluştu.";
                    $msgType = "danger";
                }
            }
        }
    }
}
?>

<div class="container mt-4 mb-5">

    <?php if ($message): ?>
    <div class="alert alert-<?= $msgType ?> d-flex align-items-center shadow-sm">
        <i class="bi bi-<?= $msgType == 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2 fs-4"></i>
        <div><?= $message ?></div>
    </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Yeni Araç Ekle</h4>
            <small class="text-muted">Mevcut Araç Sayınız: <strong><?= $currentVehicleCount ?> / 3</strong></small>
        </div>
        <a href="my_vehicles.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Listeye Dön
        </a>
    </div>

    <?php if ($limitReached): ?>
        <div class="card border-0 shadow-sm rounded-4 bg-warning bg-opacity-10 text-center py-5">
            <div class="card-body">
                <i class="bi bi-exclamation-octagon text-warning display-1"></i>
                <h3 class="mt-3 fw-bold text-dark">Limit Doldu</h3>
                <p class="text-muted fs-5">Maksimum 3 araç ekleme hakkınız dolmuştur.<br>Yeni araç eklemek için mevcut araçlarınızdan birini silmelisiniz.</p>
                <a href="my_vehicles.php" class="btn btn-dark rounded-pill px-4 mt-2">Araçlarımı Yönet</a>
            </div>
        </div>
    <?php else: ?>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow border-0 rounded-4">
                    <div class="card-header bg-primary text-white py-3 rounded-top-4">
                        <h6 class="mb-0 fw-semibold"><i class="bi bi-car-front-fill me-2"></i>Araç Başvuru Formu</h6>
                    </div>
                    
                    <div class="card-body p-4">
                        <form method="POST" enctype="multipart/form-data">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-medium text-secondary">Plaka</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light"><i class="bi bi-card-heading"></i></span>
                                        <input type="text" name="plate" class="form-control" placeholder="34 ABC 123" required>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-medium text-secondary">Marka</label>
                                    <select name="brand" class="form-select" required>
                                        <option value="">Seçiniz</option>
                                        <?php 
                                        $brands = ["Renault", "Fiat", "Ford", "Volkswagen", "Toyota", "Honda", "Hyundai", "BMW", "Mercedes", "Audi", "Diğer"];
                                        foreach($brands as $b) echo "<option value='$b'>$b</option>";
                                        ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-medium text-secondary">Model</label>
                                    <input type="text" name="model" class="form-control" placeholder="Örn: Megane" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-medium text-secondary">Model Yılı</label>
                                    <input type="number" name="year" class="form-control" placeholder="2023" min="1980" max="<?= date('Y') ?>" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-medium text-secondary">Renk</label>
                                    <input type="text" name="color" class="form-control" placeholder="Örn: Beyaz" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-medium text-secondary">Sahiplik Durumu</label>
                                    <select name="ownership" class="form-select" required>
                                        <option value="sahsi">Şahsi (Kendime Ait)</option>
                                        <option value="aile">Aile (Birinci Derece)</option>
                                        <option value="kurumsal">Kurumsal / Kiralık</option>
                                    </select>
                                </div>

                                <div class="col-12 mt-4">
                                    <div class="p-3 bg-light border border-dashed rounded text-center">
                                        <i class="bi bi-cloud-arrow-up text-primary fs-1"></i>
                                        <h6 class="mt-2 fw-bold">Ruhsat Fotoğrafı</h6>
                                        <small class="text-muted d-block mb-3">Lütfen ruhsatın okunabilir bir fotoğrafını yükleyiniz (Max 5MB).</small>
                                        <input type="file" name="license_image" class="form-control w-75 mx-auto" accept="image/*,.pdf">
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 mt-4">
                                <button type="submit" class="btn btn-primary py-2 fw-bold shadow-sm">
                                    <i class="bi bi-check-lg me-2"></i>Kaydet ve Başvuruyu Tamamla
                                </button>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php require_once '../includes/user_footer.php'; ?>
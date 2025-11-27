<?php
require_once '../includes/config.php';
// Composer autoload kontrolü
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
} elseif (file_exists('../../vendor/autoload.php')) {
    require_once '../../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once '../includes/user_header.php';

// 1. GÜVENLİK VE VERİ KONTROLÜ
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET['id'])) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Geçersiz parametre.</div></div>";
    require_once '../includes/user_footer.php';
    exit;
}

$vehicle_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Ayarları Çek
$settings = [];
$stmt = $pdo->query("SELECT * FROM settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$siteUrl = $settings['site_url'] ?? 'http://localhost';

// Aracı ve sahibinin mail bilgisini çek
$stmt = $pdo->prepare("SELECT v.*, u.name AS owner_name, u.email AS owner_email, u.unit_id, un.name as unit_name
                       FROM vehicles v 
                       JOIN users u ON v.user_id = u.id
                       LEFT JOIN units un ON u.unit_id = un.id
                       WHERE v.id = ? AND v.user_id = ?");
$stmt->execute([$vehicle_id, $user_id]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Araç bulunamadı veya yetkiniz yok.</div></div>";
    require_once '../includes/user_footer.php';
    exit;
}

$message = "";
$msgType = "";

// 2. FORM POST EDİLDİĞİNDE
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    $plate = strtoupper(trim($_POST['plate']));
    $brand = trim($_POST['brand']);
    $model = trim($_POST['model']);
    $year  = intval($_POST['year']);
    $color = trim($_POST['color']);
    $ownership = $_POST['ownership'];
    
    // Resim İşlemleri
    $license_image = $vehicle['license_image']; // Varsayılan: eski resim kalsın
    $uploadOk = true;
    
    // Yeni resim yüklendiyse
    if (isset($_FILES['license_image']) && $_FILES['license_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $ext = strtolower(pathinfo($_FILES['license_image']['name'], PATHINFO_EXTENSION));
        
        if (in_array($ext, $allowed)) {
            // İsteğe bağlı: Eski resmi sil
            if (!empty($vehicle['license_image']) && file_exists("../uploads/" . $vehicle['license_image'])) {
                @unlink("../uploads/" . $vehicle['license_image']);
            }

            $new_name = "ruhsat_" . $user_id . "_" . time() . "." . $ext;
            if (move_uploaded_file($_FILES['license_image']['tmp_name'], "../uploads/" . $new_name)) {
                $license_image = $new_name;
            } else {
                $message = "Resim yüklenirken hata oluştu.";
                $msgType = "danger";
                $uploadOk = false;
            }
        } else {
            $message = "Sadece JPG, PNG veya PDF yüklenebilir.";
            $msgType = "danger";
            $uploadOk = false;
        }
    }

    if ($uploadOk) {
        // 3. GÜNCELLEME SORGUSU (Status -> pending)
        $sql = "UPDATE vehicles SET 
                plate = ?, brand = ?, model = ?, year = ?, 
                color = ?, ownership = ?, license_image = ?, 
                status = 'pending', rejection_reason = NULL, created_at = NOW() 
                WHERE id = ? AND user_id = ?";
        
        $updateStmt = $pdo->prepare($sql);
        $result = $updateStmt->execute([
            $plate, $brand, $model, $year, 
            $color, $ownership, $license_image, 
            $vehicle_id, $user_id
        ]);

        if ($result) {
            $message = "Araç bilgileri güncellendi ve tekrar onaya gönderildi.";
            $msgType = "success";

            // ---------------------------------------------------------
            // 4. MAİL GÖNDERME (Functions dosyası olmadan - INLINE)
            // ---------------------------------------------------------
            // ---------------------------------------------------------
            // 4. MAİL GÖNDERME (Functions dosyası ile)
            // ---------------------------------------------------------
            require_once '../includes/functions.php'; // sendMail fonksiyonunu dahil et

            $mailSubject = "Araç Güncelleme Bildirimi: $plate";
            $mailBody = "
                <h3>Araç Bilgileri Güncellendi</h3>
                <p>Sayın <b>{$vehicle['owner_name']}</b>,</p>
                <p><b>$plate</b> plakalı aracınızda yapılan değişiklikler nedeniyle başvurunuz tekrar <b>Onay Bekliyor</b> durumuna alınmıştır.</p>
                <hr>
                <p><b>Yetkililer için not:</b> Lütfen güncel bilgileri kontrol ederek onaylayınız.</p>
                <p><a href='$siteUrl/admin/pending_vehicles.php'>Yönetim Paneline Git</a></p>
            ";

            // A) Kullanıcıya Gönder
            sendMail($vehicle['owner_email'], $mailSubject, $mailBody);

            // B) Adminlere ve Birim Sorumlusuna Gönder (Opsiyonel: sendMail tek kişiye atıyor, döngü gerekebilir veya sendMail fonksiyonu geliştirilebilir. 
            // Şimdilik sadece kullanıcıya bilgi verelim veya adminlere de tek tek atalım.)
            
            // Adminler
            $admins = $pdo->query("SELECT email FROM users WHERE role = 'admin' AND is_active = 1")->fetchAll();
            foreach($admins as $admin) {
                sendMail($admin['email'], $mailSubject, $mailBody);
            }

            // Birim Sorumlusu
            if ($vehicle['unit_id']) {
                $approvers = $pdo->prepare("SELECT email FROM users WHERE role = 'approver' AND unit_id = ? AND is_active = 1");
                $approvers->execute([$vehicle['unit_id']]);
                while($app = $approvers->fetch()) {
                    sendMail($app['email'], $mailSubject, $mailBody);
                }
            }
            
            // Verileri ekranda güncel göstermek için diziyi güncelle
            $vehicle['plate'] = $plate;
            $vehicle['brand'] = $brand;
            $vehicle['model'] = $model;
            $vehicle['year']  = $year;
            $vehicle['color'] = $color;
            $vehicle['ownership'] = $ownership;
            $vehicle['license_image'] = $license_image;

        } else {
            $message = "Veritabanı hatası.";
            $msgType = "danger";
        }
    }
}
?>

<div class="container mt-4 mb-5">

    <?php if ($message): ?>
        <div class="alert alert-<?= $msgType ?> d-flex align-items-center shadow-sm" role="alert">
            <i class="bi bi-<?= $msgType == 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2 fs-4"></i>
            <div><?= $message ?></div>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-0">Aracı Düzenle</h4>
            <small class="text-danger"><i class="bi bi-info-circle"></i> Değişiklik yapıldığında araç tekrar onaya düşer.</small>
        </div>
        <a href="my_vehicles.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Listeye Dön
        </a>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-pencil-square me-2"></i>Araç Bilgileri</h6>
                </div>
                <div class="card-body p-4">
                    
                    <form method="POST" enctype="multipart/form-data">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-medium text-secondary">Plaka</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-card-heading"></i></span>
                                    <input type="text" name="plate" class="form-control" 
                                           value="<?= htmlspecialchars($vehicle['plate']) ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium text-secondary">Marka</label>
                                <select name="brand" class="form-select" required>
                                    <option value="">Seçiniz</option>
                                    <?php 
                                    $brands = ["Renault", "Fiat", "Ford", "Volkswagen", "Toyota", "Honda", "Hyundai", "BMW", "Mercedes", "Audi", "Diğer"];
                                    foreach ($brands as $b) {
                                        $selected = ($vehicle['brand'] == $b) ? 'selected' : '';
                                        echo "<option value='$b' $selected>$b</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium text-secondary">Model</label>
                                <input type="text" name="model" class="form-control" 
                                       value="<?= htmlspecialchars($vehicle['model']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium text-secondary">Yıl</label>
                                <input type="number" name="year" class="form-control" min="1980" max="<?= date('Y') ?>" 
                                       value="<?= htmlspecialchars($vehicle['year']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium text-secondary">Renk</label>
                                <input type="text" name="color" class="form-control" 
                                       value="<?= htmlspecialchars($vehicle['color']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-medium text-secondary">Sahiplik</label>
                                <select name="ownership" class="form-select" required>
                                    <option value="sahsi" <?= $vehicle['ownership'] == 'sahsi' ? 'selected' : '' ?>>Şahsi</option>
                                    <option value="kurumsal" <?= $vehicle['ownership'] == 'kurumsal' ? 'selected' : '' ?>>Kurumsal / Kiralık</option>
                                    <option value="aile" <?= $vehicle['ownership'] == 'aile' ? 'selected' : '' ?>>Aile Üzerine</option>
                                </select>
                            </div>

                            <div class="col-12 mt-4">
                                <label class="form-label fw-bold">Ruhsat Fotoğrafı (Güncellemek için dosya seçin)</label>
                                <div class="p-3 bg-light border border-dashed rounded text-center">
                                    <input type="file" name="license_image" class="form-control" accept="image/*,.pdf">
                                    <small class="text-muted d-block mt-2">Yeni dosya seçmezseniz mevcudu korunur.</small>
                                </div>
                            </div>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary py-2 fw-semibold shadow-sm">
                                <i class="bi bi-save me-2"></i>Güncelle ve Onaya Gönder
                            </button>
                        </div>

                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white border-bottom py-3 text-center">
                    <h6 class="mb-0 fw-bold text-dark">Mevcut Ruhsat</h6>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center bg-light">
                    <?php if (!empty($vehicle['license_image'])): ?>
                        <div class="text-center">
                            <img src="../uploads/<?= htmlspecialchars($vehicle['license_image']) ?>" 
                                 class="img-fluid rounded shadow-sm border" 
                                 style="max-height: 250px; object-fit: contain;" 
                                 alt="Mevcut Ruhsat">
                            <div class="mt-2 text-muted small"><i class="bi bi-check-circle-fill text-success"></i> Kayıtlı Görsel</div>
                        </div>
                    <?php else: ?>
                        <div class="text-muted opacity-50 text-center">
                            <i class="bi bi-image-alt fs-1"></i>
                            <p>Görsel Yok</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once '../includes/user_footer.php'; ?>
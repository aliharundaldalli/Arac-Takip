<?php
// admin/settings.php
require_once '../includes/config.php';

// Composer Autoload Kontrolü (SMTP için)
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
} elseif (file_exists('../../vendor/autoload.php')) {
    require_once '../../vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// GÜVENLİK: Admin Kontrolü
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: index.php");
    exit;
}

$message = "";
$messageType = "";

//---------------------------------------------------------
// 1. AYARLARI KAYDET
//---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_settings'])) {

    $fields = [
        'site_title', 'site_url', 'theme_color',
        'smtp_host', 'smtp_email', 'smtp_password',
        'smtp_port', 'smtp_secure'
    ];

    try {
        foreach ($fields as $field) {
            $val = trim($_POST[$field] ?? '');
            $stmt = $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$field, $val]);
        }

        //-- Logo Yükleme --
        if (!empty($_FILES['site_logo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
                $newLogo = "logo_" . time() . "." . $ext;
                if(move_uploaded_file($_FILES['site_logo']['tmp_name'], "../assets/img/$newLogo")){
                    $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('site_logo', ?)")->execute([$newLogo]);
                }
            }
        }

        //-- Favicon Yükleme --
        if (!empty($_FILES['site_favicon']['name'])) {
            $ext = strtolower(pathinfo($_FILES['site_favicon']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'ico'])) {
                $newFav = "favicon_" . time() . "." . $ext;
                if(move_uploaded_file($_FILES['site_favicon']['tmp_name'], "../assets/img/$newFav")){
                    $pdo->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('site_favicon', ?)")->execute([$newFav]);
                }
            }
        }

        $message = "Sistem ayarları başarıyla güncellendi.";
        $messageType = "success";

    } catch (PDOException $e) {
        $message = "Veritabanı hatası: " . $e->getMessage();
        $messageType = "danger";
    }
}

//---------------------------------------------------------
// 2. AYARLARI ÇEK
//---------------------------------------------------------
$stmt = $pdo->query("SELECT * FROM settings");
$s = [];
while ($row = $stmt->fetch()) {
    $s[$row['setting_key']] = $row['setting_value'];
}

// Varsayılan Renk (SQL çıktınızdaki renk)
$themeColor = $s['theme_color'] ?? '#6c1913';

//---------------------------------------------------------
// 3. TEST MAİLİ GÖNDER
//---------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_test'])) {

    $mail = new PHPMailer(true);

    try {
        // Formdan gelenleri değil, veritabanındaki kayıtlı ayarları kullan
        // (Çünkü kullanıcı kaydetmeden test etmeye çalışırsa eski ayarlar kullanılır, bu yüzden önce kaydetmesi gerekir)
        $mail->isSMTP();
        $mail->Host       = $s['smtp_host'] ?? '';
        $mail->SMTPAuth   = true;
        $mail->Username   = $s['smtp_email'] ?? '';
        $mail->Password   = $s['smtp_password'] ?? '';
        $mail->CharSet    = "UTF-8";

        if (($s['smtp_secure'] ?? 'tls') === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
        }

        $mail->setFrom($s['smtp_email'], $s['site_title'] ?? 'Araç Sistemi');
        $mail->addAddress($_POST['test_email_address']);

        $mail->isHTML(true);
        $mail->Subject = 'SMTP Test Bağlantısı';
        $mail->Body = "
            <h3>Merhaba,</h3>
            <p>Bu e-posta, araç kayıt sistemi üzerinden <strong>SMTP ayarlarınızı doğrulamak</strong> için gönderilmiştir.</p>
            <p style='color:green;'>✔ Bağlantı Başarılı!</p>
        ";

        $mail->send();
        $message = "Test maili başarıyla gönderildi!";
        $messageType = "success";

    } catch (Exception $e) {
        $message = "Mail Gönderilemedi. Hata: " . $mail->ErrorInfo;
        $messageType = "danger";
    }
}
?>

<?php require_once '../includes/admin_header.php'; ?>

<style>
    .img-preview-box {
        width: 80px;
        height: 80px;
        border: 1px dashed #ccc;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #f9f9f9;
        overflow: hidden;
    }
    .img-preview-box img {
        max-width: 100%;
        max-height: 100%;
    }
   /* Kapsayıcı sekme alanı */
.nav-tabs {
    border-bottom: 2px solid #e5e7eb; /* Hafif gri alt çizgi */
}

/* Pasif sekme */
.nav-tabs .nav-link {
    background: #406890ff;
    border: 1px solid #182031ff;
    color: #4b5563;
    border-radius: 6px 6px 0 0;
    margin-right: 2px;
    padding: 10px 16px;
    transition: all 0.2s ease;
}

/* Hover efekti */
.nav-tabs .nav-link:hover {
    background: #eef2ff; /* Hafif lacivert tonunda hover */
    color: #0c1461ff;
    border-color: #d1d5db;
}

/* Aktif sekme */
.nav-tabs .nav-link.active {
    background-color: #0c1461ff; /* Lacivert */
    color: #fff !important;       /* Beyaz yazı */
    border-color: #0c1461ff;
    border-bottom-color: white !important; /* İçerik alanıyla birleşsin */
    font-weight: 600;
    position: relative;
    z-index: 5;
}

/* Aktif tab’ın içerikle birleşmesi için tablo altına çizgi */
.tab-content {
    border: 1px solid #e5e7eb;
    border-top: none;
    background: #ffffff;
    padding: 20px;
    border-radius: 0 6px 6px 6px;
}

</style>

<div class="container-fluid px-4 py-4">

    <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i> <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-sliders text-primary me-2"></i>Sistem Ayarları
            </h3>
            <p class="text-muted mb-0">Site genel yapılandırması ve e-posta sunucu ayarları.</p>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-light border-bottom-0 pt-3 px-4">
            <ul class="nav nav-tabs card-header-tabs" id="settingsTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button">
                        <i class="bi bi-gear-fill me-2"></i>Genel Ayarlar
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="smtp-tab" data-bs-toggle="tab" data-bs-target="#smtp" type="button">
                        <i class="bi bi-envelope-at-fill me-2"></i>SMTP / E-Posta
                    </button>
                </li>
            </ul>
        </div>

        <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data">
                
                <div class="tab-content" id="settingsTabContent">
                    
                    <div class="tab-pane fade show active" id="general" role="tabpanel">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Site Başlığı</label>
                                <input type="text" name="site_title" class="form-control" value="<?= htmlspecialchars($s['site_title'] ?? '') ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Site URL</label>
                                <input type="url" name="site_url" class="form-control" value="<?= htmlspecialchars($s['site_url'] ?? 'http://localhost') ?>">
                                <div class="form-text">Mail linklerinde kullanılır (Sonunda / olmasın).</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Tema Rengi</label>
                                <div class="d-flex align-items-center">
                                    <input type="color" name="theme_color" class="form-control form-control-color" 
                                           value="<?= htmlspecialchars($themeColor) ?>" title="Renk Seç">
                                    <span class="ms-2 text-muted small"><?= htmlspecialchars($themeColor) ?></span>
                                </div>
                            </div>

                            <div class="col-12"><hr class="text-muted opacity-25"></div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Site Logosu</label>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="img-preview-box">
                                        <?php if (!empty($s['site_logo'])): ?>
                                            <img src="../assets/img/<?= htmlspecialchars($s['site_logo']) ?>" alt="Logo">
                                        <?php else: ?>
                                            <i class="bi bi-image text-muted fs-4"></i>
                                        <?php endif; ?>
                                    </div>
                                    <input type="file" name="site_logo" class="form-control" accept="image/*">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Favicon (Tarayıcı İkonu)</label>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="img-preview-box">
                                        <?php if (!empty($s['site_favicon'])): ?>
                                            <img src="../assets/img/<?= htmlspecialchars($s['site_favicon']) ?>" alt="Favicon">
                                        <?php else: ?>
                                            <i class="bi bi-stars text-muted fs-4"></i>
                                        <?php endif; ?>
                                    </div>
                                    <input type="file" name="site_favicon" class="form-control" accept=".png,.ico,.jpg">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" name="save_settings" class="btn btn-primary px-4 py-2 fw-bold">
                                <i class="bi bi-save me-1"></i> Genel Ayarları Kaydet
                            </button>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="smtp" role="tabpanel">
                        
                        <div class="alert alert-info border-0 shadow-sm d-flex align-items-center mb-4">
                            <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                            <div>
                                <strong>Bilgi:</strong> Onay ve red işlemlerinde otomatik mail gitmesi için bu alanları doğru doldurmalısınız.
                                <br>Gmail kullanıyorsanız "Uygulama Şifresi" almanız gerekebilir.
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">SMTP Sunucu (Host)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-hdd-network"></i></span>
                                    <input type="text" name="smtp_host" class="form-control" placeholder="smtp.gmail.com" value="<?= htmlspecialchars($s['smtp_host'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Port</label>
                                <input type="number" name="smtp_port" class="form-control" placeholder="587" value="<?= htmlspecialchars($s['smtp_port'] ?? '587') ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">E-Posta Adresi</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                                    <input type="email" name="smtp_email" class="form-control" placeholder="mail@site.com" value="<?= htmlspecialchars($s['smtp_email'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">E-Posta Şifresi</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light"><i class="bi bi-key"></i></span>
                                    <input type="password" name="smtp_password" class="form-control" value="<?= htmlspecialchars($s['smtp_password'] ?? '') ?>">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Güvenlik Protokolü</label>
                                <select name="smtp_secure" class="form-select">
                                    <option value="tls" <?= ($s['smtp_secure'] ?? '') == 'tls' ? 'selected' : '' ?>>TLS (Önerilen)</option>
                                    <option value="ssl" <?= ($s['smtp_secure'] ?? '') == 'ssl' ? 'selected' : '' ?>>SSL</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 text-end">
                            <button type="submit" name="save_settings" class="btn btn-primary px-4 py-2 fw-bold">
                                <i class="bi bi-save me-1"></i> SMTP Ayarlarını Kaydet
                            </button>
                        </div>
            </form> <hr class="my-5">
                        
                        <h5 class="fw-bold mb-3 text-secondary"><i class="bi bi-send-check me-2"></i>Bağlantı Testi</h5>
                        <div class="bg-light p-4 rounded-3 border">
                            <form method="POST" class="row g-3 align-items-end">
                                <div class="col-md-8">
                                    <label class="form-label small text-muted">Test E-postası Gönderilecek Adres</label>
                                    <input type="email" name="test_email_address" class="form-control" placeholder="ornek@mail.com" required>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" name="send_test" class="btn btn-dark w-100">
                                        <i class="bi bi-cursor-fill me-1"></i> Test Et
                                    </button>
                                </div>
                            </form>
                            <div class="form-text mt-2">
                                <i class="bi bi-exclamation-circle"></i> Önce yukarıdaki ayarları <strong>kaydetmelisiniz</strong>, sonra test edebilirsiniz.
                            </div>
                        </div>

                    </div> </div> </div> </div> </div>

<?php require_once '../includes/admin_footer.php'; ?>
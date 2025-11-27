<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 1. GÜVENLİK
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'approver'])) {
    header("Location: ../auth/login.php");
    exit;
}

// 2. ID KONTROLÜ
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Geçersiz ID.");
}

$vehicle_id = intval($_GET['id']);
$message = "";
$msgType = "";

// -------------------------------------------------------------------
// İŞLEM 1: BİLGİLERİ GÜNCELLE (ADMIN DÜZELTME)
// -------------------------------------------------------------------
if (isset($_POST['update_vehicle'])) {
    $plate  = strtoupper(trim($_POST['plate']));
    $brand  = trim($_POST['brand']);
    $model  = trim($_POST['model']);
    $year   = intval($_POST['year']);
    $color  = trim($_POST['color']);
    $ownership = $_POST['ownership'];

    try {
        $sql = "UPDATE vehicles SET plate=?, brand=?, model=?, year=?, color=?, ownership=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$plate, $brand, $model, $year, $color, $ownership, $vehicle_id]);
        
        $message = "Araç bilgileri başarıyla güncellendi.";
        $msgType = "success";
    } catch (PDOException $e) {
        $message = "Güncelleme hatası: " . $e->getMessage();
        $msgType = "danger";
    }
}

// -------------------------------------------------------------------
// İŞLEM 2: ONAYLA
// -------------------------------------------------------------------
if (isset($_POST['approve'])) {
    $pdo->prepare("UPDATE vehicles SET status='approved', rejection_reason=NULL WHERE id = ?")
        ->execute([$vehicle_id]);
    
    // Mail Gönderimi
    $stmtEmail = $pdo->prepare("SELECT v.plate, u.name, u.email FROM vehicles v JOIN users u ON v.user_id = u.id WHERE v.id = ?");
    $stmtEmail->execute([$vehicle_id]);
    $vInfo = $stmtEmail->fetch();

    if ($vInfo && !empty($vInfo['email'])) {
        $subject = "Araç Başvurunuz Onaylandı";
        $body = "
            <h3>Merhaba {$vInfo['name']},</h3>
            <p><strong>{$vInfo['plate']}</strong> plakalı araç başvurunuz onaylanmıştır.</p>
            <p>Sisteme giriş yaparak detayları görüntüleyebilirsiniz.</p>
        ";
        sendMail($vInfo['email'], $subject, $body);
    }

    $message = "Araç ONAYLANDI ve bilgilendirme maili gönderildi.";
    $msgType = "success";
}

// -------------------------------------------------------------------
// İŞLEM 3: REDDET
// -------------------------------------------------------------------
if (isset($_POST['reject'])) {
    $reason = trim($_POST['reason']);
    $pdo->prepare("UPDATE vehicles SET status='rejected', rejection_reason=? WHERE id=?")
        ->execute([$reason, $vehicle_id]);
    
    $message = "Araç REDDEDİLDİ.";
    $msgType = "danger";
}

// -------------------------------------------------------------------
// VERİLERİ ÇEK
// -------------------------------------------------------------------
$sql = "SELECT v.*, u.name AS owner_name, u.email AS owner_email, u.phone, u.tc_number,
               un.name AS unit_name
        FROM vehicles v
        JOIN users u ON v.user_id = u.id
        LEFT JOIN units un ON u.unit_id = un.id
        WHERE v.id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$vehicle_id]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    die("Araç kaydı bulunamadı.");
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Araç Detay Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<?php require_once '../includes/admin_header.php'; ?>

<style>
    .plate-display {
        font-family: 'Courier New', monospace;
        font-weight: 800;
        font-size: 1.8rem;
        border: 3px solid #333;
        border-radius: 8px;
        padding: 5px 20px;
        background: #fff;
        display: inline-block;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .detail-label {
        font-size: 0.85rem;
        color: #6c757d;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    .detail-val {
        font-weight: 500;
        color: #212529;
        font-size: 1.05rem;
    }
</style>

<div class="container-fluid px-4 py-4">

    <?php if ($message): ?>
        <div class="alert alert-<?= $msgType ?> alert-dismissible fade show shadow-sm">
            <i class="bi bi-info-circle-fill me-2"></i> <?= $message ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="bi bi-arrow-left"></i> Geri Dön
            </a>
            <h4 class="fw-bold mb-0">Araç Detay Yönetimi</h4>
        </div>
        
        <?php 
            $stColors = ['pending'=>'warning', 'approved'=>'success', 'rejected'=>'danger'];
            $stText   = ['pending'=>'Onay Bekliyor', 'approved'=>'Onaylandı', 'rejected'=>'Reddedildi'];
        ?>
        <span class="badge bg-<?= $stColors[$vehicle['status']] ?> fs-6 px-3 py-2 rounded-pill shadow-sm">
            <?= $stText[$vehicle['status']] ?>
        </span>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-6">
            
            <?php if ($vehicle['status'] == 'rejected'): ?>
                <div class="alert alert-danger shadow-sm border-0 rounded-4 mb-4 d-flex align-items-center">
                    <div class="fs-1 me-3"><i class="bi bi-x-circle-fill"></i></div>
                    <div>
                        <h5 class="fw-bold mb-1">Bu Araç Reddedilmiştir!</h5>
                        <p class="mb-0">
                            <strong>Red Sebebi:</strong> <?= htmlspecialchars($vehicle['rejection_reason']) ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-car-front-fill me-2"></i>Araç Bilgileri</h6>
                    
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editVehicleModal">
                        <i class="bi bi-pencil-square me-1"></i> Düzenle
                    </button>
                </div>
                
                <div class="card-body p-4 text-center">
                    <div class="mb-4">
                        <div class="plate-display"><?= strtoupper($vehicle['plate']) ?></div>
                    </div>
                    
                    <div class="row text-start g-3">
                        <div class="col-6">
                            <div class="detail-label">Marka</div>
                            <div class="detail-val"><?= htmlspecialchars($vehicle['brand']) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="detail-label">Model</div>
                            <div class="detail-val"><?= htmlspecialchars($vehicle['model']) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="detail-label">Yıl</div>
                            <div class="detail-val"><?= htmlspecialchars($vehicle['year']) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="detail-label">Renk</div>
                            <div class="detail-val"><?= htmlspecialchars($vehicle['color']) ?></div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Mülkiyet</div>
                            <div class="detail-val text-capitalize"><?= htmlspecialchars($vehicle['ownership']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3">
                    <h6 class="fw-bold mb-0 text-secondary"><i class="bi bi-person-vcard-fill me-2"></i>Sahip Bilgileri</h6>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-light rounded-circle p-3 me-3">
                            <i class="bi bi-person fs-3 text-secondary"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0"><?= htmlspecialchars($vehicle['owner_name']) ?></h5>
                            <span class="text-muted small"><?= htmlspecialchars($vehicle['unit_name'] ?? 'Birim Yok') ?></span>
                        </div>
                    </div>
                    <hr class="opacity-25">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="detail-label">TC Kimlik</div>
                            <div class="detail-val"><?= htmlspecialchars($vehicle['tc_number']) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="detail-label">Telefon</div>
                            <div class="detail-val"><?= htmlspecialchars($vehicle['phone'] ?? '-') ?></div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">E-Posta</div>
                            <div class="detail-val"><?= htmlspecialchars($vehicle['owner_email']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="col-lg-6">
            
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-file-image me-2"></i>Ruhsat Belgesi</h6>
                    <?php if (!empty($vehicle['license_image'])): ?>
                        <a href="../uploads/<?= htmlspecialchars($vehicle['license_image']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-zoom-in"></i> Büyüt
                        </a>
                    <?php endif; ?>
                </div>

                <div class="card-body bg-light text-center d-flex align-items-center justify-content-center" style="min-height: 300px;">
                    <?php if (!empty($vehicle['license_image'])): ?>
                        <img src="../uploads/<?= htmlspecialchars($vehicle['license_image']) ?>" 
                             class="img-fluid rounded shadow-sm border" 
                             style="max-height: 400px; object-fit: contain;">
                    <?php else: ?>
                        <div class="text-muted">
                            <i class="bi bi-image-alt fs-1 opacity-50"></i>
                            <p>Görsel Yüklenmemiş</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-footer bg-white p-4">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <form method="POST">
                                <?php if ($vehicle['status'] != 'approved'): ?>
                                    <button type="submit" name="approve" class="btn btn-success w-100 py-2 fw-bold" onclick="return confirm('Bu aracı onaylamak istiyor musunuz?')">
                                        <i class="bi bi-check-lg me-2"></i> ONAYLA
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-success w-100 py-2 disabled" disabled>
                                        <i class="bi bi-check-circle-fill me-2"></i> Zaten Onaylı
                                    </button>
                                <?php endif; ?>
                            </form>
                        </div>

                        <div class="col-md-6">
                            <?php if ($vehicle['status'] != 'rejected'): ?>
                                <button type="button" class="btn btn-danger w-100 py-2 fw-bold" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                    <i class="bi bi-x-lg me-2"></i> REDDET
                                </button>
                            <?php else: ?>
                                <button class="btn btn-danger w-100 py-2 disabled" disabled>
                                    <i class="bi bi-x-circle-fill me-2"></i> Reddedildi
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="editVehicleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Araç Bilgilerini Düzenle</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Plaka</label>
                        <input type="text" name="plate" class="form-control" value="<?= htmlspecialchars($vehicle['plate']) ?>" required>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Marka</label>
                            <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($vehicle['brand']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Model</label>
                            <input type="text" name="model" class="form-control" value="<?= htmlspecialchars($vehicle['model']) ?>" required>
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Yıl</label>
                            <input type="number" name="year" class="form-control" value="<?= htmlspecialchars($vehicle['year']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Renk</label>
                            <input type="text" name="color" class="form-control" value="<?= htmlspecialchars($vehicle['color']) ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Mülkiyet</label>
                        <select name="ownership" class="form-select">
                            <option value="sahsi" <?= $vehicle['ownership'] == 'sahsi' ? 'selected' : '' ?>>Şahsi</option>
                            <option value="kurumsal" <?= $vehicle['ownership'] == 'kurumsal' ? 'selected' : '' ?>>Kurumsal</option>
                            <option value="aile" <?= $vehicle['ownership'] == 'aile' ? 'selected' : '' ?>>Aile</option>
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="update_vehicle" class="btn btn-primary">Değişiklikleri Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold">Reddetme Sebebi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <textarea name="reason" class="form-control" rows="4" placeholder="Lütfen red gerekçesini yazınız (Örn: Ruhsat okunamıyor)..." required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="reject" class="btn btn-danger">Aracı Reddet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>
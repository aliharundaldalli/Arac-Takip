<?php
require_once '../includes/approver_header.php'; // Session, Config ve Unit ID

// 1. GÜVENLİK VE PARAMETRE KONTROLÜ
$myUnitID = $_SESSION['unit_id'];
$myUserID = $_SESSION['user_id'];

if (empty($myUnitID)) {
    die("<div class='container mt-5'><div class='alert alert-danger'>Birim yetkisi bulunamadı.</div></div>");
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<div class='container mt-5'><div class='alert alert-danger'>Geçersiz Araç ID.</div></div>");
}

$vehicle_id = intval($_GET['id']);
$message = "";
$msgType = "";

// 2. ONAYLA (POST)
if (isset($_POST['approve'])) {
    // Sadece statüsü değiştir, red sebebini sil
    $stmt = $pdo->prepare("UPDATE vehicles SET status='approved', rejection_reason=NULL WHERE id = ?");
    if($stmt->execute([$vehicle_id])) {
        $message = "Araç onaylandı. Geçiş izni verildi.";
        $msgType = "success";
    }
}

// 3. REDDET (POST)
if (isset($_POST['reject'])) {
    $reason = trim($_POST['rejection_reason']);
    if(empty($reason)) $reason = "Birim sorumlusu tarafından reddedildi.";

    $stmt = $pdo->prepare("UPDATE vehicles SET status='rejected', rejection_reason=? WHERE id = ?");
    if($stmt->execute([$reason, $vehicle_id])) {
        $message = "Araç reddedildi.";
        $msgType = "danger";
    }
}

// 4. VERİLERİ ÇEK (GÜVENLİK FİLTRESİ İLE)
// DİKKAT: "AND u.unit_id = ?" kısmı çok önemli. Başka birimin aracını göremez.
$sql = "SELECT v.*, u.name AS owner_name, u.email AS owner_email, u.phone, u.tc_number, u.unit_id
        FROM vehicles v
        JOIN users u ON v.user_id = u.id
        WHERE v.id = ? AND u.unit_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$vehicle_id, $myUnitID]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    // Araç yoksa veya birim eşleşmiyorsa buraya düşer
    echo "<div class='container mt-5'>
            <div class='alert alert-danger shadow-sm border-0'>
                <h4 class='alert-heading'><i class='bi bi-shield-lock-fill'></i> Erişim Engellendi</h4>
                <p>Bu araç sizin biriminize ait değil veya sistemde bulunamadı.</p>
                <a href='index.php' class='btn btn-danger btn-sm'>Panele Dön</a>
            </div>
          </div>";
    require_once 'approver_footer.php';
    exit;
}

// Kendi aracını onaylamaya çalışırsa uyarı ver (Opsiyonel etik kural)
$isOwnVehicle = ($vehicle['user_id'] == $myUserID);

?>

<style>
    .plate-display {
        font-family: 'Courier New', monospace;
        font-weight: 800;
        font-size: 2rem;
        border: 4px solid #333;
        border-radius: 8px;
        padding: 5px 25px;
        background: #fff;
        display: inline-block;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        color: #000;
    }
    .detail-label {
        font-size: 0.8rem;
        color: #6c757d;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.5px;
        margin-bottom: 2px;
    }
    .detail-val {
        font-weight: 500;
        color: #212529;
        font-size: 1.1rem;
    }
</style>

<div class="container mt-4 mb-5">

    <?php if ($message): ?>
        <div class="alert alert-<?= $msgType ?> alert-dismissible fade show shadow-sm">
            <i class="bi bi-info-circle-fill me-2"></i> <?= $message ?>
            <button class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($isOwnVehicle): ?>
        <div class="alert alert-warning border-0 shadow-sm">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Dikkat:</strong> Bu araç size ait. Kendi aracınızı onaylamanız etik olmayabilir. Yönetici onayı bekleyebilirsiniz.
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="javascript:history.back()" class="btn btn-outline-secondary btn-sm mb-2">
                <i class="bi bi-arrow-left"></i> Geri Dön
            </a>
            <h4 class="fw-bold mb-0">Başvuru İnceleme</h4>
        </div>
        
        <?php 
            $stColors = ['pending'=>'warning', 'approved'=>'success', 'rejected'=>'danger'];
            $stText   = ['pending'=>'Onay Bekliyor', 'approved'=>'Onaylandı', 'rejected'=>'Reddedildi'];
        ?>
        <span class="badge bg-<?= $stColors[$vehicle['status']] ?> fs-6 px-4 py-2 rounded-pill shadow-sm">
            <?= $stText[$vehicle['status']] ?>
        </span>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-6">
            
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-car-front-fill me-2"></i>Araç Bilgileri</h6>
                </div>
                <div class="card-body text-center p-4">
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
                            <div class="detail-label">Model Yılı</div>
                            <div class="detail-val"><?= htmlspecialchars($vehicle['year']) ?></div>
                        </div>
                        <div class="col-6">
                            <div class="detail-label">Renk</div>
                            <div class="detail-val"><?= htmlspecialchars($vehicle['color']) ?></div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Sahiplik Türü</div>
                            <div class="detail-val text-capitalize"><?= htmlspecialchars($vehicle['ownership']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-person-vcard-fill me-2"></i>Personel / Öğrenci Bilgisi</h6>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-light rounded-circle p-3 me-3">
                            <i class="bi bi-person fs-2 text-secondary"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0"><?= htmlspecialchars($vehicle['owner_name']) ?></h5>
                            <span class="text-muted small">Birim Personeli</span>
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

            <?php if ($vehicle['status'] == 'rejected'): ?>
            <div class="alert alert-danger mt-4 border-0 shadow-sm rounded-4">
                <h6 class="fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Red Nedeni:</h6>
                <?= htmlspecialchars($vehicle['rejection_reason']) ?>
            </div>
            <?php endif; ?>

        </div>

        <div class="col-lg-6">
            
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0"><i class="bi bi-file-image me-2"></i>Ruhsat Belgesi</h6>
                    <?php if (!empty($vehicle['license_image'])): ?>
                        <a href="../uploads/<?= htmlspecialchars($vehicle['license_image']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrows-fullscreen"></i> Büyüt
                        </a>
                    <?php endif; ?>
                </div>

                <div class="card-body bg-light text-center d-flex align-items-center justify-content-center" style="min-height: 350px;">
                    <?php if (!empty($vehicle['license_image'])): ?>
                        <img src="../uploads/<?= htmlspecialchars($vehicle['license_image']) ?>" 
                             class="img-fluid rounded shadow-sm border" 
                             style="max-height: 400px; object-fit: contain;">
                    <?php else: ?>
                        <div class="text-muted">
                            <i class="bi bi-image-alt fs-1 opacity-50"></i>
                            <p class="mt-2">Ruhsat görseli bulunamadı.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-footer bg-white p-4">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <form method="POST">
                                <?php if ($vehicle['status'] != 'approved'): ?>
                                    <button type="submit" name="approve" class="btn btn-success w-100 py-2 fw-bold" onclick="return confirm('Bu aracı onaylıyor musunuz?');">
                                        <i class="bi bi-check-lg me-2"></i> ONAYLA
                                    </button>
                                <?php else: ?>
                                    <button type="button" class="btn btn-success w-100 py-2 disabled" disabled>
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
                                <button type="button" class="btn btn-danger w-100 py-2 disabled" disabled>
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

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold">Reddetme İşlemi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <label class="form-label fw-semibold">Lütfen bir red nedeni belirtiniz:</label>
                    <textarea name="rejection_reason" class="form-control" rows="4" placeholder="Örn: Ruhsat görseli okunamıyor, bilgiler eksik..." required></textarea>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="reject" class="btn btn-danger px-4">Aracı Reddet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/approver_footer.php'; ?>
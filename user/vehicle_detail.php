<?php
require_once '../includes/config.php';
require_once '../includes/user_header.php';

// Güvenlik kontrolleri
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

if (!isset($_GET['id'])) {
    die("Geçersiz parametre.");
}

$vehicle_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Veriyi çek
$stmt = $pdo->prepare("SELECT v.*, u.name AS owner_name 
                       FROM vehicles v 
                       JOIN users u ON v.user_id = u.id
                       WHERE v.id = ? AND v.user_id = ?");
$stmt->execute([$vehicle_id, $user_id]);
$vehicle = $stmt->fetch();

if (!$vehicle) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Araç bulunamadı veya yetkiniz yok.</div></div>";
    require_once '../includes/user_footer.php';
    exit;
}

// Durum renklendirmesi ve etiketleri
$statusConfig = [
    "pending"  => ["class" => "warning", "text" => "Onay Bekliyor", "icon" => "hourglass-split"],
    "approved" => ["class" => "success", "text" => "Onaylandı", "icon" => "check-circle-fill"],
    "rejected" => ["class" => "danger",  "text" => "Reddedildi", "icon" => "x-circle-fill"]
];

$currentStatus = $statusConfig[$vehicle['status']] ?? ["class" => "secondary", "text" => $vehicle['status'], "icon" => "question-circle"];
?>

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0 text-secondary"><i class="bi bi-info-circle me-2"></i>Araç Detayı</h4>
        <a href="my_vehicles.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Listeye Dön
        </a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom">
            <div>
                <span class="fs-4 fw-bold text-dark font-monospace"><?= htmlspecialchars($vehicle['plate']) ?></span>
                <span class="text-muted ms-2 small">| <?= htmlspecialchars($vehicle['brand']) ?> - <?= htmlspecialchars($vehicle['model']) ?></span>
            </div>
            
            <span class="badge bg-<?= $currentStatus['class'] ?> bg-opacity-10 text-<?= $currentStatus['class'] ?> border border-<?= $currentStatus['class'] ?> px-3 py-2">
                <i class="bi bi-<?= $currentStatus['icon'] ?> me-1"></i> <?= $currentStatus['text'] ?>
            </span>
        </div>

        <div class="card-body p-4">
            
            <?php if ($vehicle['status'] == 'rejected' && !empty($vehicle['rejection_reason'])): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                    <div>
                        <strong>Red Nedeni:</strong> <?= htmlspecialchars($vehicle['rejection_reason']) ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-7">
                    <h6 class="text-uppercase text-muted mb-3 small fw-bold ls-1">Teknik Bilgiler</h6>
                    
                    <div class="list-group list-group-flush border rounded-3 mb-4">
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted"><i class="bi bi-person me-2"></i>Kullanıcı / Sahip</span>
                            <span class="fw-medium"><?= htmlspecialchars($vehicle['owner_name']) ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted"><i class="bi bi-calendar-event me-2"></i>Model Yılı</span>
                            <span class="fw-medium"><?= htmlspecialchars($vehicle['year']) ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted"><i class="bi bi-palette me-2"></i>Renk</span>
                            <span class="fw-medium"><?= htmlspecialchars($vehicle['color']) ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted"><i class="bi bi-tag me-2"></i>Kullanım Türü</span>
                            <span class="fw-medium text-capitalize"><?= htmlspecialchars($vehicle['ownership']) ?></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted"><i class="bi bi-clock me-2"></i>Kayıt Tarihi</span>
                            <span class="fw-medium"><?= date('d.m.Y H:i', strtotime($vehicle['created_at'])) ?></span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <h6 class="text-uppercase text-muted mb-3 small fw-bold ls-1">Ruhsat Belgesi</h6>
                    
                    <div class="card bg-light border-0 h-100" style="min-height: 250px;">
                        <div class="card-body d-flex align-items-center justify-content-center p-2">
                            <?php if (!empty($vehicle['license_image'])): ?>
                                <a href="../uploads/<?= htmlspecialchars($vehicle['license_image']) ?>" target="_blank">
                                    <img src="../uploads/<?= htmlspecialchars($vehicle['license_image']) ?>" 
                                         class="img-fluid rounded shadow-sm" 
                                         style="max-height: 300px; object-fit: contain;" 
                                         alt="Ruhsat">
                                </a>
                            <?php else: ?>
                                <div class="text-center text-muted">
                                    <i class="bi bi-image-alt fs-1 d-block mb-2 opacity-50"></i>
                                    <span>Görsel Yüklenmemiş</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($vehicle['license_image'])): ?>
                            <div class="card-footer bg-transparent border-0 text-center">
                                <small class="text-muted">Resmi büyütmek için üzerine tıklayın</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div> </div> <div class="card-footer bg-light py-3 text-end">
            <a href="edit_vehicle.php?id=<?= $vehicle['id'] ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-pencil-square"></i> Düzenle
            </a>
        </div>
    </div>

</div>

<?php require_once '../includes/user_footer.php'; ?>
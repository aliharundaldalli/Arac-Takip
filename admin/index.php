<?php
require_once '../includes/config.php'; // Session başlatmak için

// GÜVENLİK KONTROLÜ
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'approver'])) {
    header("Location: ../auth/login.php");
    exit;
}

require_once '../includes/admin_header.php'; // Header'ı çağır

// İSTATİSTİKLERİ ÇEK
// --------------------

// 1. Bekleyen Araçlar (En önemlisi)
$stmt = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'pending'");
$pendingCount = $stmt->fetchColumn();

// 2. Onaylanan Araçlar
$stmt = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE status = 'approved'");
$approvedCount = $stmt->fetchColumn();

// 3. Toplam Kullanıcı
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
$userCount = $stmt->fetchColumn();

// 4. Toplam Birim
$stmt = $pdo->query("SELECT COUNT(*) FROM units");
$unitCount = $stmt->fetchColumn();


// BEKLEYEN SON 5 BAŞVURU (Tablo için)
// ------------------------------------
$sql = "SELECT v.*, u.name as owner_name, un.name as unit_name 
        FROM vehicles v 
        JOIN users u ON v.user_id = u.id 
        LEFT JOIN units un ON u.unit_id = un.id 
        WHERE v.status = 'pending' 
        ORDER BY v.created_at DESC LIMIT 5";
$stmt = $pdo->query($sql);
$pendingVehicles = $stmt->fetchAll();

?>

<div class="container">

    <!-- Welcome Banner -->
    <div class="card bg-primary text-white shadow-lg border-0 rounded-4 mb-4 position-relative overflow-hidden">
        <div class="card-body p-4 position-relative z-1">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-1">Yönetim Paneli</h3>
                    <p class="mb-0 opacity-75">Sistem genel durumu ve bekleyen işlemlerin özeti.</p>
                </div>
                <div class="d-none d-md-block text-end">
                    <span class="badge bg-white text-primary fw-bold px-3 py-2 rounded-pill">
                        <i class="bi bi-calendar3 me-1"></i> <?= date('d.m.Y') ?>
                    </span>
                </div>
            </div>
        </div>
        <!-- Decorative Circles -->
        <div style="position: absolute; top: -20px; right: -20px; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
        <div style="position: absolute; bottom: -40px; left: 10%; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
    </div>

    <!-- Stats Row -->
    <div class="row g-4 mb-5">
        
        <!-- Bekleyen (Acil) -->
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-warning">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-warning bg-opacity-10 text-warning p-2 rounded me-3">
                            <i class="bi bi-hourglass-split fs-4"></i>
                        </div>
                        <span class="text-muted small fw-bold text-uppercase">Bekleyen</span>
                    </div>
                    <h2 class="fw-bold mb-0 text-dark"><?= $pendingCount ?></h2>
                    <a href="pending_vehicles.php" class="stretched-link"></a>
                </div>
            </div>
        </div>

        <!-- Onaylanan -->
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-success">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-success bg-opacity-10 text-success p-2 rounded me-3">
                            <i class="bi bi-check-circle-fill fs-4"></i>
                        </div>
                        <span class="text-muted small fw-bold text-uppercase">Onaylı Araç</span>
                    </div>
                    <h2 class="fw-bold mb-0 text-dark"><?= $approvedCount ?></h2>
                </div>
            </div>
        </div>

        <!-- Kullanıcılar -->
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-info">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-info bg-opacity-10 text-info p-2 rounded me-3">
                            <i class="bi bi-people-fill fs-4"></i>
                        </div>
                        <span class="text-muted small fw-bold text-uppercase">Kullanıcı</span>
                    </div>
                    <h2 class="fw-bold mb-0 text-dark"><?= $userCount ?></h2>
                    <a href="users.php" class="stretched-link"></a>
                </div>
            </div>
        </div>

        <!-- Birimler -->
        <div class="col-md-6 col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-secondary">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="bg-secondary bg-opacity-10 text-secondary p-2 rounded me-3">
                            <i class="bi bi-building fs-4"></i>
                        </div>
                        <span class="text-muted small fw-bold text-uppercase">Birim</span>
                    </div>
                    <h2 class="fw-bold mb-0 text-dark"><?= $unitCount ?></h2>
                    <a href="units.php" class="stretched-link"></a>
                </div>
            </div>
        </div>
    </div>


    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm border-0 rounded-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-list-check me-2 text-warning"></i>Son Bekleyen Başvurular</h6>
                    <a href="pending_vehicles.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Tümünü Gör</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Plaka</th>
                                <th>Araç Sahibi</th>
                                <th>Marka / Model</th>
                                <th>Birim</th>
                                <th>Tarih</th>
                                <th class="text-end pe-4">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pendingVehicles) > 0): ?>
                                <?php foreach ($pendingVehicles as $v): ?>
                                <tr>
                                    <td class="ps-4 fw-bold font-monospace"><?= htmlspecialchars($v['plate']) ?></td>
                                    <td><?= htmlspecialchars($v['owner_name']) ?></td>
                                    <td><?= htmlspecialchars($v['brand'] . " " . $v['model']) ?></td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($v['unit_name'] ?? '-') ?></span></td>
                                    <td class="small text-muted"><?= date('d.m.Y H:i', strtotime($v['created_at'])) ?></td>
                                    <td class="text-end pe-4">
                                        <a href="vehicle_detail.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-primary">
                                            İncele <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-check-circle fs-1 d-block mb-2 text-success opacity-50"></i>
                                        Şu an bekleyen başvuru yok.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once '../includes/admin_footer.php'; ?>
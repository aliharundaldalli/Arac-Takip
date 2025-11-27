<?php
require_once '../includes/approver_header.php'; // Session ve Config buradan gelir

$myUnitID = $_SESSION['unit_id'];

// Eğer birim ID yoksa uyarı ver (Veritabanı hatası veya atama yapılmamış)
if (empty($myUnitID)) {
    echo "<div class='container'><div class='alert alert-danger'>Hesabınıza tanımlı bir Birim bulunamadı. Lütfen yönetici ile iletişime geçin.</div></div>";
    require_once 'approver_footer.php';
    exit;
}

// Birim Adını Çek
$stmt = $pdo->prepare("SELECT name FROM units WHERE id = ?");
$stmt->execute([$myUnitID]);
$unitName = $stmt->fetchColumn();


// 1. BEKLEYEN BAŞVURULAR (Sadece bu birim)
$sqlPending = "SELECT COUNT(*) FROM vehicles v 
               JOIN users u ON v.user_id = u.id 
               WHERE v.status = 'pending' AND u.unit_id = ?";
$stmt = $pdo->prepare($sqlPending);
$stmt->execute([$myUnitID]);
$pendingCount = $stmt->fetchColumn();

// 2. ONAYLANANLAR (Sadece bu birim)
$sqlApproved = "SELECT COUNT(*) FROM vehicles v 
                JOIN users u ON v.user_id = u.id 
                WHERE v.status = 'approved' AND u.unit_id = ?";
$stmt = $pdo->prepare($sqlApproved);
$stmt->execute([$myUnitID]);
$approvedCount = $stmt->fetchColumn();

// 3. TOPLAM KULLANICI (Sadece bu birim)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE unit_id = ?");
$stmt->execute([$myUnitID]);
$userCount = $stmt->fetchColumn();


// SON 5 BEKLEYEN BAŞVURU (Tablo için)
$sqlList = "SELECT v.*, u.name as owner_name, u.tc_number 
            FROM vehicles v 
            JOIN users u ON v.user_id = u.id 
            WHERE v.status = 'pending' AND u.unit_id = ? 
            ORDER BY v.created_at ASC LIMIT 5";
$stmt = $pdo->prepare($sqlList);
$stmt->execute([$myUnitID]);
$pendingVehicles = $stmt->fetchAll();

?>

<div class="container">

    <div class="card bg-dark text-white shadow-lg border-0 rounded-4 mb-4 position-relative overflow-hidden">
        <div class="card-body p-4 position-relative z-1">
            <h3 class="fw-bold mb-1">Hoş geldin, <?= htmlspecialchars($_SESSION['user_name']) ?> 👋</h3>
            <p class="mb-0 opacity-75">
                <i class="bi bi-building me-1"></i> 
                <strong><?= htmlspecialchars($unitName) ?></strong> birimi için işlem yapıyorsunuz.
            </p>
        </div>
        <i class="bi bi-building position-absolute text-white opacity-10" style="font-size: 8rem; right: -20px; bottom: -40px;"></i>
    </div>

    <div class="row g-4 mb-4">
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-warning">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3 me-3">
                        <i class="bi bi-hourglass-split fs-3"></i>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Onay Bekleyen</h6>
                        <h2 class="fw-bold mb-0 text-dark"><?= $pendingCount ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-success">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-success bg-opacity-10 text-success p-3 rounded-3 me-3">
                        <i class="bi bi-check-circle-fill fs-3"></i>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Onaylanmış Araç</h6>
                        <h2 class="fw-bold mb-0 text-dark"><?= $approvedCount ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100 border-start border-4 border-primary">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 me-3">
                        <i class="bi bi-people-fill fs-3"></i>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Kayıtlı Kişi</h6>
                        <h2 class="fw-bold mb-0 text-dark"><?= $userCount ?></h2>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-list-check me-2 text-warning"></i>Son Bekleyen Başvurular</h6>
            <?php if ($pendingCount > 0): ?>
                <a href="pending_vehicles.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">Tümünü Gör</a>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-secondary">
                    <tr>
                        <th class="ps-4">Plaka</th>
                        <th>Araç Sahibi</th>
                        <th>Marka / Model</th>
                        <th>Tarih</th>
                        <th class="text-end pe-4">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($pendingVehicles) > 0): ?>
                        <?php foreach ($pendingVehicles as $v): ?>
                        <tr>
                            <td class="ps-4 fw-bold font-monospace"><?= htmlspecialchars($v['plate']) ?></td>
                            <td>
                                <div><?= htmlspecialchars($v['owner_name']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($v['tc_number']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($v['brand'] . " - " . $v['model']) ?></td>
                            <td class="small text-muted"><?= date('d.m.Y', strtotime($v['created_at'])) ?></td>
                            <td class="text-end pe-4">
                                <a href="vehicle_detail.php?id=<?= $v['id'] ?>" class="btn btn-sm btn-primary rounded-pill">
                                    İncele <i class="bi bi-arrow-right"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-check2-all fs-1 opacity-50"></i>
                                <p class="mt-2">Biriminizde bekleyen araç başvurusu bulunmuyor.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once '../includes/approver_footer.php'; ?>
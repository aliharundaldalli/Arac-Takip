<?php
require_once '../includes/approver_header.php'; // Session, Config ve Unit ID buradan gelir

// Birim ID kontrolü (Header'da set edilmiş olmalı)
$myUnitID = $_SESSION['unit_id'];

if (empty($myUnitID)) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Birim bilgisi bulunamadı. Yönetici ile görüşün.</div></div>";
    require_once 'approver_footer.php';
    exit;
}

// SADECE BU BİRİME AİT BEKLEYENLERİ ÇEK
$sql = "SELECT v.*, u.name AS user_name, u.tc_number
        FROM vehicles v
        JOIN users u ON u.id = v.user_id
        WHERE v.status = 'pending' 
        AND u.unit_id = ? 
        ORDER BY v.created_at ASC"; // Eskiden yeniye sırala

$stmt = $pdo->prepare($sql);
$stmt->execute([$myUnitID]);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    /* Plaka Görünümü */
    .plate-code {
        font-family: 'Courier New', Courier, monospace;
        font-weight: 800;
        font-size: 1.1rem;
        letter-spacing: 1px;
        background-color: #fff3cd; 
        border: 2px solid #ffecb5;
        padding: 2px 8px;
        border-radius: 4px;
        color: #664d03;
        display: inline-block;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(255, 193, 7, 0.08);
    }
</style>

<div class="container mt-4 mb-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-hourglass-split text-warning me-2"></i>Onay Bekleyenler
            </h3>
            <p class="text-muted mb-0">Biriminizdeki personel/öğrencilerin araç başvuruları.</p>
        </div>
        <div>
            <span class="badge bg-warning text-dark fs-6 px-3 py-2 rounded-pill shadow-sm">
                Bekleyen: <?= count($vehicles) ?>
            </span>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            
            <div class="table-responsive">
                <table id="pendingTable" class="table table-hover align-middle w-100">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="py-3 ps-3">Plaka</th>
                            <th class="py-3">Araç Sahibi</th>
                            <th class="py-3">Araç Bilgisi</th>
                            <th class="py-3">Başvuru Tarihi</th>
                            <th class="py-3 text-end pe-3">İşlem</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($vehicles as $v): ?>
                        <tr>
                            <td class="ps-3">
                                <span class="plate-code"><?= htmlspecialchars(strtoupper($v['plate'])) ?></span>
                            </td>

                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars($v['user_name']) ?></span>
                                    <small class="text-muted">TC: <?= htmlspecialchars($v['tc_number']) ?></small>
                                </div>
                            </td>

                            <td>
                                <span class="fw-medium">
                                    <?= htmlspecialchars($v['brand']) ?>
                                </span>
                                <span class="text-muted ms-1"><?= htmlspecialchars($v['model']) ?></span>
                                <div class="small text-muted"><?= htmlspecialchars($v['year']) ?> - <?= ucfirst($v['color']) ?></div>
                            </td>

                            <td class="text-muted small">
                                <i class="bi bi-clock me-1"></i>
                                <?= date('d.m.Y H:i', strtotime($v['created_at'])) ?>
                                <br>
                                <?php
                                    $date1 = new DateTime($v['created_at']);
                                    $date2 = new DateTime();
                                    $diff = $date2->diff($date1);
                                    if($diff->days > 0) echo "<span class='text-danger fw-bold'>(".$diff->days." gün önce)</span>";
                                    else echo "<span class='text-success fw-bold'>(Bugün)</span>";
                                ?>
                            </td>

                            <td class="text-end pe-3">
                                <a href="vehicle_detail.php?id=<?= $v['id'] ?>" 
                                   class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm fw-semibold">
                                    <i class="bi bi-search me-1"></i> İncele
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>

                <?php if(count($vehicles) == 0): ?>
                    <div class="text-center py-5">
                        <div class="mb-3 text-success opacity-75">
                            <i class="bi bi-check-circle-fill" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Her şey güncel!</h5>
                        <p class="text-muted">Biriminizde şu an onay bekleyen araç başvurusu yok.</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/approver_footer.php'; ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#pendingTable').DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json" },
        responsive: true,
        order: [[3, 'asc']], // Tarihe göre eskiden yeniye
        pageLength: 25,
        initComplete: function () {
            $('.dataTables_filter input').addClass('form-control rounded-pill border-1 ps-3');
            $('.dataTables_length select').addClass('form-select rounded-pill border-1');
        }
    });
});
</script>
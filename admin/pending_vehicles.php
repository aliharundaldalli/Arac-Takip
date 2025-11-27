<?php
require_once '../includes/config.php';

// 1. GÜVENLİK KONTROLÜ
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin','approver'])) {
    // header("Location: ../auth/login.php"); exit;
}

// 2. BEKLEYEN ARAÇLARI ÇEK
// status = 'pending' olanları getir.
$sql = "SELECT v.*, u.name AS user_name, u.tc_number, un.name as unit_name
        FROM vehicles v
        LEFT JOIN users u ON u.id = v.user_id
        LEFT JOIN units un ON u.unit_id = un.id
        WHERE v.status = 'pending'
        ORDER BY v.created_at ASC"; // ASC: Eskiden yeniye (Sıradaki ilk iş en üstte olsun)
$vehicles = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once '../includes/admin_header.php'; ?>

<style>
    /* Plaka Görünümü */
    .plate-code {
        font-family: 'Courier New', Courier, monospace;
        font-weight: 800;
        font-size: 1.1rem;
        letter-spacing: 1px;
        background-color: #fff3cd; /* Hafif sarı arka plan */
        border: 2px solid #ffecb5;
        padding: 2px 8px;
        border-radius: 4px;
        color: #664d03;
        display: inline-block;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(255, 193, 7, 0.08); /* Hafif sarı hover */
    }
</style>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-hourglass-split text-warning me-2"></i>Bekleyen Başvurular
            </h3>
            <p class="text-muted mb-0">İncelenmesi ve onaylanması gereken araç listesi.</p>
        </div>
        <div>
            <span class="badge bg-warning text-dark fs-6 px-3 py-2 rounded-pill shadow-sm">
                <i class="bi bi-bell-fill me-1"></i> Bekleyen: <?= count($vehicles) ?>
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
                            <th class="py-3">Kullanıcı Bilgisi</th>
                            <th class="py-3">Araç Bilgisi</th>
                            <th class="py-3">Birim</th>
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
                            </td>
                            
                            <td>
                                <span class="badge bg-light text-dark border fw-normal">
                                    <?= htmlspecialchars($v['unit_name'] ?? '-') ?>
                                </span>
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
                                    else echo "<span class='text-success'>(Bugün)</span>";
                                ?>
                            </td>

                            <td class="text-end pe-3">
                                <a href="vehicle_detail.php?id=<?= $v['id'] ?>" 
                                   class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm fw-semibold">
                                    <i class="bi bi-search me-1"></i> İncele & Onayla
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
                        <h5 class="fw-bold text-dark">Harika!</h5>
                        <p class="text-muted">Şu an onay bekleyen herhangi bir başvuru bulunmuyor.</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/admin_footer.php'; ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Tooltip
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    // DataTable Başlatma
    $('#pendingTable').DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json" },
        responsive: true,
        order: [[4, 'asc']], // Tarihe göre ESKİDEN YENİYE sırala (Bekleyen iş mantığı)
        pageLength: 25,
        initComplete: function () {
            $('.dataTables_filter input').addClass('form-control rounded-pill border-1 ps-3');
            $('.dataTables_length select').addClass('form-select rounded-pill border-1');
        }
    });
});
</script>
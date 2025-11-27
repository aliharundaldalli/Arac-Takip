<?php
require_once '../includes/config.php';

// Yetki kontrolü
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin','approver'])) {
    // header("Location: ../auth/login.php"); exit;
}

// REDDEDİLEN ARAÇLARI ÇEK
$sql = "SELECT v.*, u.name AS user_name, u.tc_number
        FROM vehicles v
        LEFT JOIN users u ON u.id = v.user_id
        WHERE v.status = 'rejected'
        ORDER BY v.id DESC";
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
        background-color: #f8f9fa;
        border: 2px solid #dee2e6;
        padding: 2px 8px;
        border-radius: 4px;
        color: #212529;
        display: inline-block;
    }
    .table-hover tbody tr:hover {
        background-color: rgba(220, 53, 69, 0.05); /* Üzerine gelince hafif kırmızı ton */
    }
</style>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-x-circle-fill text-danger me-2"></i>Reddedilen Araçlar
            </h3>
            <p class="text-muted mb-0">Başvurusu uygun görülmeyen araçların listesi ve red nedenleri.</p>
        </div>
        <div>
            <span class="badge bg-danger fs-6 px-3 py-2 rounded-pill">
                Toplam: <?= count($vehicles) ?> Red
            </span>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            
            <div class="table-responsive">
                <table id="rejectTable" class="table table-hover align-middle w-100">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="py-3 ps-3">Plaka</th>
                            <th class="py-3">Kullanıcı Bilgisi</th>
                            <th class="py-3">Araç Bilgisi</th>
                            <th class="py-3 text-danger">Red Nedeni</th>
                            <th class="py-3">İşlem Tarihi</th>
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
                                    <i class="bi bi-dot text-muted"></i>
                                    <?= htmlspecialchars($v['model']) ?>
                                </span>
                            </td>

                            <td>
                                <div class="d-flex align-items-start text-danger">
                                    <i class="bi bi-exclamation-circle-fill me-2 mt-1"></i>
                                    <span class="fw-medium">
                                        <?= htmlspecialchars($v['rejection_reason'] ?? 'Belirtilmedi') ?>
                                    </span>
                                </div>
                            </td>

                            <td class="text-muted small">
                                <i class="bi bi-calendar-x me-1"></i>
                                <?= date('d.m.Y H:i', strtotime($v['created_at'])) ?>
                            </td>

                            <td class="text-end pe-3">
                                <a href="vehicle_detail.php?id=<?= $v['id'] ?>" 
                                   class="btn btn-sm btn-outline-dark rounded-pill px-3"
                                   data-bs-toggle="tooltip" title="Detayları İncele">
                                    İncele <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
            </div> </div> </div> </div> <?php require_once '../includes/admin_footer.php'; ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Tooltipleri başlat
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    // DataTable Başlat
    $('#rejectTable').DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json" },
        responsive: true,
        order: [[4, 'desc']], // Tarihe göre sırala
        initComplete: function () {
            $('.dataTables_filter input').addClass('form-control rounded-pill border-1 ps-3');
            $('.dataTables_length select').addClass('form-select rounded-pill border-1');
        }
    });
});
</script>

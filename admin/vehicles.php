<?php
require_once '../includes/config.php';

// 1. GÜVENLİK KONTROLÜ
// Sadece Admin ve Onaylayıcılar görebilsin
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin', 'approver'])) {
    header("Location: ../auth/login.php");
    exit;
}

// 2. VERİLERİ ÇEK
// Tüm araçları (Status farketmeksizin) getiriyoruz.
$sql = "SELECT v.*, 
               u.name AS owner_name, 
               u.tc_number,
               un.name AS unit_name
        FROM vehicles v
        JOIN users u ON v.user_id = u.id
        LEFT JOIN units un ON u.unit_id = un.id
        ORDER BY v.created_at DESC";
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
        background-color: rgba(13, 110, 253, 0.05); /* Hafif mavi hover */
    }
</style>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-car-front-fill text-primary me-2"></i>Tüm Araç Listesi
            </h3>
            <p class="text-muted mb-0">Sistemdeki onaylı, reddedilen ve bekleyen tüm araçların arşivi.</p>
        </div>
        <div>
            <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill shadow-sm">
                Toplam: <?= count($vehicles) ?> Kayıt
            </span>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">

            <div class="table-responsive">
                <table id="vehicleTable" class="table table-hover align-middle w-100">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="py-3 ps-3">Plaka</th>
                            <th class="py-3">Araç Sahibi</th>
                            <th class="py-3">Marka / Model</th>
                            <th class="py-3">Birim</th>
                            <th class="py-3">Durum</th>
                            <th class="py-3">Kayıt Tarihi</th>
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
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($v['owner_name']) ?></span>
                                        <small class="text-muted" style="font-size: 0.85rem;">
                                            <i class="bi bi-person-vcard me-1"></i><?= htmlspecialchars($v['tc_number']) ?>
                                        </small>
                                    </div>
                                </td>

                                <td>
                                    <span class="fw-medium"><?= htmlspecialchars($v['brand']) ?></span>
                                    <span class="text-muted small ms-1"><?= htmlspecialchars($v['model']) ?></span>
                                    <div class="small text-muted"><?= htmlspecialchars($v['year']) ?></div>
                                </td>

                                <td>
                                    <?php if(!empty($v['unit_name'])): ?>
                                        <span class="badge bg-light text-dark border fw-normal"><?= htmlspecialchars($v['unit_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?php 
                                        $st = match($v['status']) {
                                            'pending'  => ['color' => 'warning', 'icon' => 'hourglass-split', 'text' => 'Bekliyor'],
                                            'approved' => ['color' => 'success', 'icon' => 'check-circle-fill', 'text' => 'Onaylı'],
                                            'rejected' => ['color' => 'danger',  'icon' => 'x-circle-fill', 'text' => 'Red'],
                                            default    => ['color' => 'secondary', 'icon' => 'question', 'text' => $v['status']]
                                        };
                                    ?>
                                    <span class="badge bg-<?= $st['color'] ?> bg-opacity-10 text-<?= $st['color'] ?> border border-<?= $st['color'] ?> px-3 py-2 rounded-pill">
                                        <i class="bi bi-<?= $st['icon'] ?> me-1"></i> <?= $st['text'] ?>
                                    </span>
                                </td>

                                <td class="text-muted small">
                                    <?= date('d.m.Y', strtotime($v['created_at'])) ?>
                                </td>

                                <td class="text-end pe-3">
                                    <a href="vehicle_detail.php?id=<?= $v['id'] ?>" 
                                       class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm">
                                       <i class="bi bi-search me-1"></i> Detay
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>
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
    $(document).ready(function () {
        $('#vehicleTable').DataTable({
            language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json" },
            responsive: true,
            order: [[5, "desc"]], // Tarihe göre sırala
            pageLength: 25,
            initComplete: function () {
                $('.dataTables_filter input').addClass('form-control rounded-pill border-1 ps-3');
                $('.dataTables_length select').addClass('form-select rounded-pill border-1');
            }
        });
    });
</script>
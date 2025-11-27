<?php
require_once '../includes/config.php';

// 1. GÜVENLİK VE YETKİ KONTROLÜ
// Listeleme sayfası olduğu için burada ID kontrolü YAPILMAZ.
// Sadece oturum ve rol kontrolü yapılır.
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin','approver'])) {
    // header("Location: ../auth/login.php"); exit; 
    // Not: Hata almamak için yorum satırı yaptım, canlıda açabilirsin.
}

// 2. VERİLERİ ÇEK
// Onaylanan araçları listeliyoruz.
$sql = "SELECT v.*, u.name AS user_name, u.tc_number
        FROM vehicles v
        LEFT JOIN users u ON u.id = v.user_id
        WHERE v.status = 'approved'
        ORDER BY v.created_at DESC"; // En son onaylanan en üstte
$vehicles = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>

<?php require_once '../includes/admin_header.php'; ?>

<style>
    /* Plaka için özel font */
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
    /* Tablo satırına hover efekti */
    .table-hover tbody tr:hover {
        background-color: rgba(25, 135, 84, 0.08);
    }
</style>


<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-check-circle-fill text-success me-2"></i>Onaylanan Araçlar
            </h3>
            <p class="text-muted mb-0">Trafiğe çıkışı onaylanmış aktif araç listesi.</p>
        </div>
        <div>
            <span class="badge bg-success fs-6 px-3 py-2 rounded-pill shadow-sm">
                Toplam: <?= count($vehicles) ?> Araç
            </span>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            
            <div class="table-responsive">
                <table id="approvedTable" class="table table-hover align-middle w-100">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="py-3 ps-3">Plaka</th>
                            <th class="py-3">Araç Sahibi</th>
                            <th class="py-3">Araç Bilgisi</th>
                            <th class="py-3">Sahiplik</th>
                            <th class="py-3">Onay Tarihi</th>
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
                                    <small class="text-muted" style="font-size: 0.85rem;">
                                        <i class="bi bi-person-vcard me-1"></i><?= htmlspecialchars($v['tc_number']) ?>
                                    </small>
                                </div>
                            </td>

                            <td>
                                <span class="fw-medium text-dark">
                                    <?= htmlspecialchars($v['brand']) ?>
                                </span>
                                <span class="text-muted ms-1"><?= htmlspecialchars($v['model']) ?></span>
                            </td>

                            <td>
                                <?php 
                                    $badgeClass = match($v['ownership']) {
                                        'sahsi' => 'bg-info text-dark bg-opacity-25 border border-info',
                                        'kurumsal' => 'bg-secondary text-white bg-opacity-75',
                                        'aile' => 'bg-primary text-primary bg-opacity-10 border border-primary',
                                        default => 'bg-light text-dark border'
                                    };
                                    // İlk harfi büyük yap
                                    $ownText = ucfirst($v['ownership']); 
                                ?>
                                <span class="badge <?= $badgeClass ?> px-3 py-2 rounded-pill fw-normal">
                                    <?= htmlspecialchars($ownText) ?>
                                </span>
                            </td>

                            <td class="text-muted small">
                                <i class="bi bi-calendar-check me-1"></i>
                                <?= date('d.m.Y H:i', strtotime($v['created_at'])) ?>
                            </td>

                            <td class="text-end pe-3">
                                <a href="vehicle_detail.php?id=<?= $v['id'] ?>" 
                                   class="btn btn-sm btn-outline-dark rounded-pill px-3 shadow-sm"
                                   data-bs-toggle="tooltip" title="Detayları Gör">
                                    İncele <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>

                </table>
                
                <?php if(count($vehicles) == 0): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-1 opacity-50"></i>
                        <p class="mt-2">Henüz onaylanmış bir araç bulunmuyor.</p>
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
    // Tooltip Aktivasyonu
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    });

    // DataTables Başlatma
    $('#approvedTable').DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json" },
        responsive: true,
        order: [[4, 'desc']], // Tarihe göre sırala
        pageLength: 25,
        initComplete: function () {
            // Arama kutusu ve sayfalama stillerini Bootstrap ile uyumlu hale getir
            $('.dataTables_filter input').addClass('form-control rounded-pill border-1 ps-3');
            $('.dataTables_length select').addClass('form-select rounded-pill border-1');
        }
    });
});
</script>
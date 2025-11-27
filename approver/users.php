<?php
require_once '../includes/approver_header.php'; // Session, Config ve Unit ID

// Birim ID kontrolü
$myUnitID = $_SESSION['unit_id'];

if (empty($myUnitID)) {
    echo "<div class='container mt-4'><div class='alert alert-danger'>Birim bilgisi bulunamadı.</div></div>";
    require_once 'approver_footer.php';
    exit;
}

// SADECE BU BİRİMDEKİ KULLANICILARI ÇEK
// Ayrıca her kullanıcının kaç aracı olduğunu da sayalım (Subquery ile)
$sql = "SELECT u.*, 
               (SELECT COUNT(*) FROM vehicles WHERE user_id = u.id) as vehicle_count
        FROM users u 
        WHERE u.unit_id = ? 
        AND u.role != 'admin' 
        ORDER BY u.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute([$myUnitID]);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container mt-4 mb-5">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-people-fill text-primary me-2"></i>Birim Personeli / Öğrencileri
            </h3>
            <p class="text-muted mb-0">Biriminizde kayıtlı olan kişilerin listesi ve iletişim bilgileri.</p>
        </div>
        <div>
            <span class="badge bg-primary fs-6 px-3 py-2 rounded-pill shadow-sm">
                Toplam: <?= count($users) ?> Kişi
            </span>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            
            <div class="table-responsive">
                <table id="usersTable" class="table table-hover align-middle w-100">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="py-3 ps-3">Ad Soyad</th>
                            <th class="py-3">TC Kimlik</th>
                            <th class="py-3">İletişim</th>
                            <th class="py-3">Rol</th>
                            <th class="py-3 text-center">Araç Sayısı</th>
                            <th class="py-3 text-end pe-3">İşlem</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td class="ps-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                        <span class="fw-bold text-secondary"><?= strtoupper(substr($u['name'], 0, 1)) ?></span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-dark"><?= htmlspecialchars($u['name']) ?></span>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="font-monospace text-muted"><?= htmlspecialchars($u['tc_number']) ?></span>
                            </td>

                            <td>
                                <div class="d-flex flex-column small">
                                    <span><i class="bi bi-envelope me-1 text-muted"></i> <?= htmlspecialchars($u['email']) ?></span>
                                    <?php if(!empty($u['phone'])): ?>
                                        <span class="mt-1"><i class="bi bi-telephone me-1 text-muted"></i> <?= htmlspecialchars($u['phone']) ?></span>
                                    <?php else: ?>
                                        <span class="mt-1 text-muted opacity-50"><i class="bi bi-telephone-x me-1"></i> Yok</span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td>
                                <?php if($u['role'] == 'approver'): ?>
                                    <span class="badge bg-warning text-dark border border-warning bg-opacity-25">Sorumlu</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-secondary border">Kullanıcı</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center">
                                <?php if($u['vehicle_count'] > 0): ?>
                                    <span class="badge bg-primary rounded-pill"><?= $u['vehicle_count'] ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </td>

                          <td class="text-end pe-3">
    <div class="btn-group">
        <a href="mailto:<?= htmlspecialchars($u['email']) ?>" class="btn btn-sm btn-outline-secondary" title="Mail At">
            <i class="bi bi-envelope"></i>
        </a>
        
        <a href="edit_user.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary" title="Bilgileri Düzenle">
            <i class="bi bi-pencil-square"></i>
        </a>
    </div>
</td>
                            
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if(count($users) == 0): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-people fs-1 text-muted opacity-25"></i>
                        <p class="mt-2 text-muted">Biriminizde kayıtlı kullanıcı bulunamadı.</p>
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
    $('#usersTable').DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json" },
        responsive: true,
        pageLength: 25,
        initComplete: function () {
            $('.dataTables_filter input').addClass('form-control rounded-pill border-1 ps-3');
            $('.dataTables_length select').addClass('form-select rounded-pill border-1');
        }
    });
});
</script>
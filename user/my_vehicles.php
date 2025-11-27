<?php
require_once '../includes/config.php';
require_once '../includes/user_header.php';

// Kullanıcı girişi kontrolü (Header'da olsa da garanti olsun)
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='../auth/login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];

// Kullanıcının araçlarını çek
$sql = "SELECT * FROM vehicles WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user_id]);
$vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    /* Özel Plaka Stili */
    .plate-box {
        font-family: 'Courier New', Courier, monospace;
        font-weight: 800;
        font-size: 1.5rem;
        background-color: #fff;
        border: 3px solid #212529;
        padding: 5px 15px;
        border-radius: 8px;
        display: inline-block;
        color: #212529;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .vehicle-card {
        transition: transform 0.2s;
    }
    .vehicle-card:hover {
        transform: translateY(-5px);
    }
</style>

<div class="container mt-4 mb-5">

    <!-- Üst Başlık -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-car-front-fill text-primary me-2"></i>Araçlarım
            </h3>
            <p class="text-muted mb-0">Sisteme kayıtlı araçlarınızın durumu.</p>
        </div>
        <a href="vehicle_add.php" class="btn btn-primary shadow-sm rounded-pill px-4 fw-bold">
            <i class="bi bi-plus-lg me-1"></i> Yeni Araç Ekle
        </a>
    </div>

    <div class="row g-4">
        
        <?php if (count($vehicles) > 0): ?>
            <?php foreach ($vehicles as $v): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 rounded-4 vehicle-card">
                        
                        <!-- Durum Rozeti (Sağ Üst) -->
                        <div class="card-header bg-white border-bottom-0 pt-3 pe-3 text-end">
                            <?php 
                                $st = match($v['status']) {
                                    'pending'  => ['class'=>'warning', 'text'=>'Onay Bekliyor', 'icon'=>'hourglass-split'],
                                    'approved' => ['class'=>'success', 'text'=>'Onaylandı', 'icon'=>'check-circle-fill'],
                                    'rejected' => ['class'=>'danger',  'text'=>'Reddedildi', 'icon'=>'x-circle-fill'],
                                    default    => ['class'=>'secondary', 'text'=>'Bilinmiyor', 'icon'=>'question']
                                };
                            ?>
                            <span class="badge bg-<?= $st['class'] ?> bg-opacity-10 text-<?= $st['class'] ?> border border-<?= $st['class'] ?> px-3 py-2 rounded-pill">
                                <i class="bi bi-<?= $st['icon'] ?> me-1"></i> <?= $st['text'] ?>
                            </span>
                        </div>

                        <!-- Kart İçeriği -->
                        <div class="card-body text-center pt-0">
                            <!-- Plaka -->
                            <div class="mb-3">
                                <span class="plate-box">
                                    <?= htmlspecialchars(strtoupper($v['plate'])) ?>
                                </span>
                            </div>

                            <!-- Araç Bilgisi -->
                            <h5 class="fw-bold text-dark mb-1">
                                <?= htmlspecialchars($v['brand']) ?>
                            </h5>
                            <div class="text-muted small mb-3">
                                <?= htmlspecialchars($v['model']) ?> &bull; <?= htmlspecialchars($v['year']) ?>
                            </div>

                            <!-- Red Nedeni Uyarısı -->
                            <?php if ($v['status'] == 'rejected'): ?>
                                <div class="alert alert-danger py-2 small d-flex align-items-center justify-content-center">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    <div><?= htmlspecialchars($v['rejection_reason'] ?? 'Belirtilmedi') ?></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Alt Butonlar -->
                        <div class="card-footer bg-white border-top-0 pb-4 px-4">
                            <div class="d-grid">
                                <a href="edit_vehicle.php?id=<?= $v['id'] ?>" class="btn btn-outline-primary fw-semibold rounded-pill">
                                    <i class="bi bi-pencil-square me-1"></i> Detayları Yönet
                                </a>
                            </div>
                            
                            <?php if($v['status'] == 'approved'): ?>
                                <div class="text-center mt-3">
                                    <small class="text-success fw-bold">
                                        <i class="bi bi-shield-check"></i> Giriş İzniniz Var
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            <?php endforeach; ?>

        <?php else: ?>
            
            <!-- HİÇ ARAÇ YOKSA -->
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 py-5 text-center">
                    <div class="card-body">
                        <div class="mb-3 text-muted opacity-25">
                            <i class="bi bi-car-front" style="font-size: 4rem;"></i>
                        </div>
                        <h4 class="fw-bold text-dark">Henüz Kayıtlı Aracınız Yok</h4>
                        <p class="text-muted">Kampüse giriş yapabilmek için lütfen araç ekleyiniz.</p>
                        <a href="vehicle_add.php" class="btn btn-success px-4 py-2 rounded-pill fw-bold shadow-sm">
                            <i class="bi bi-plus-lg me-1"></i> İlk Aracını Ekle
                        </a>
                    </div>
                </div>
            </div>

        <?php endif; ?>

    </div>
</div>

<?php require_once '../includes/user_footer.php'; ?>
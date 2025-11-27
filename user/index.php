<?php
require_once '../includes/config.php';
require_once '../includes/user_header.php';

// Güvenlik: Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Kullanıcı';

// ------------------------------------------
// İSTATİSTİKLERİ ÇEK
// ------------------------------------------

// 1. Toplam Araç Sayısı
$stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE user_id = ?");
$stmt->execute([$user_id]);
$totalVehicles = $stmt->fetchColumn();

// 2. Bekleyen Başvurular
$stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicles WHERE user_id = ? AND status = 'pending'");
$stmt->execute([$user_id]);
$pending = $stmt->fetchColumn();

// 3. Son Eklenen Araç
$stmt = $pdo->prepare("SELECT * FROM vehicles WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$user_id]);
$lastVehicle = $stmt->fetch();

// Durum Renk ve Metin Tanımları
$statusMap = [
    'pending'  => ['class' => 'warning', 'text' => 'Onay Bekliyor', 'icon' => 'hourglass-split'],
    'approved' => ['class' => 'success', 'text' => 'Onaylandı', 'icon' => 'check-circle-fill'],
    'rejected' => ['class' => 'danger',  'text' => 'Reddedildi', 'icon' => 'x-circle-fill']
];
?>

<div class="container mt-4 mb-5">

    <div class="card bg-primary text-white shadow-lg border-0 rounded-4 mb-4 overflow-hidden position-relative">
        <div style="position: absolute; top: -20px; right: -20px; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>
        <div style="position: absolute; bottom: -40px; left: 10%; width: 100px; height: 100px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>

        <div class="card-body p-4 position-relative z-1">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold mb-1">Hoş geldin, <?= htmlspecialchars($user_name) ?> 👋</h3>
                    <p class="mb-0 opacity-75">Araç başvurularını ve onay durumlarını buradan takip edebilirsin.</p>
                </div>
                <div class="d-none d-md-block">
                    <a href="vehicle_add.php" class="btn btn-light text-primary fw-bold shadow-sm">
                        <i class="bi bi-plus-lg me-1"></i> Yeni Araç Ekle
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-3 me-3">
                        <i class="bi bi-car-front-fill fs-3"></i>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Kayıtlı Araç</h6>
                        <h2 class="fw-bold mb-0 text-dark"><?= $totalVehicles ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body d-flex align-items-center">
                    <div class="bg-warning bg-opacity-10 text-warning p-3 rounded-3 me-3">
                        <i class="bi bi-hourglass-split fs-3"></i>
                    </div>
                    <div>
                        <h6 class="text-muted text-uppercase small fw-bold mb-1">Onay Bekleyen</h6>
                        <h2 class="fw-bold mb-0 text-dark"><?= $pending ?></h2>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <a href="profile.php" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 hover-scale transition">
                    <div class="card-body d-flex align-items-center">
                        <div class="bg-dark bg-opacity-10 text-dark p-3 rounded-3 me-3">
                            <i class="bi bi-person-gear fs-3"></i>
                        </div>
                        <div>
                            <h6 class="text-muted text-uppercase small fw-bold mb-1">Hesap Ayarları</h6>
                            <span class="fw-semibold text-dark">Profili Düzenle &rarr;</span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-secondary"><i class="bi bi-clock-history me-2"></i>Son Araç Başvurusu</h6>
                    <a href="my_vehicles.php" class="text-decoration-none small fw-bold">Tümünü Gör</a>
                </div>
                
                <div class="card-body p-4">
                    <?php if ($lastVehicle): 
                        // Status configini çek
                        $st = $statusMap[$lastVehicle['status']] ?? ['class' => 'secondary', 'text' => $lastVehicle['status'], 'icon' => 'question'];
                    ?>
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between p-3 border rounded-3 bg-light">
                            <div class="d-flex align-items-center mb-3 mb-md-0">
                                <div class="bg-white p-2 rounded border me-3">
                                    <i class="bi bi-car-front fs-2 text-secondary"></i>
                                </div>
                                <div>
                                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($lastVehicle['plate']) ?></h5>
                                    <div class="text-muted small">
                                        <?= htmlspecialchars($lastVehicle['brand'] . " " . $lastVehicle['model']) ?> &bull; <?= $lastVehicle['year'] ?>
                                    </div>
                                </div>
                            </div>

                            <div class="text-md-end">
                                <span class="badge bg-<?= $st['class'] ?> bg-opacity-10 text-<?= $st['class'] ?> border border-<?= $st['class'] ?> px-3 py-2 rounded-pill">
                                    <i class="bi bi-<?= $st['icon'] ?> me-1"></i> <?= $st['text'] ?>
                                </span>
                                <div class="mt-2">
                                    <a href="vehicle_detail.php?id=<?= $lastVehicle['id'] ?>" class="btn btn-sm btn-outline-dark">
                                        Detaylar
                                    </a>
                                </div>
                            </div>
                        </div>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <div class="mb-3 text-muted opacity-50">
                                <i class="bi bi-cloud-plus fs-1"></i>
                            </div>
                            <h5 class="fw-bold text-dark">Henüz aracın yok</h5>
                            <p class="text-muted">Sisteme kayıtlı bir aracın bulunmuyor. Kampüse giriş için hemen başvuru yap.</p>
                            <a href="vehicle_add.php" class="btn btn-primary px-4 rounded-pill">
                                <i class="bi bi-plus-lg"></i> İlk Aracını Ekle
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="fw-bold mb-0 text-secondary"><i class="bi bi-lightning-charge me-2"></i>Hızlı İşlemler</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush rounded-bottom-4">
                        
                        <a href="vehicle_add.php" class="list-group-item list-group-item-action py-3 d-flex align-items-center">
                            <i class="bi bi-plus-circle text-primary fs-5 me-3"></i>
                            <div>
                                <div class="fw-semibold text-dark">Araç Ekle</div>
                                <div class="small text-muted">Yeni bir araç kaydı oluştur</div>
                            </div>
                            <i class="bi bi-chevron-right ms-auto text-muted small"></i>
                        </a>

                        <a href="my_vehicles.php" class="list-group-item list-group-item-action py-3 d-flex align-items-center">
                            <i class="bi bi-list-check text-success fs-5 me-3"></i>
                            <div>
                                <div class="fw-semibold text-dark">Araçlarım</div>
                                <div class="small text-muted">Kayıtlı araçlarını listele</div>
                            </div>
                            <i class="bi bi-chevron-right ms-auto text-muted small"></i>
                        </a>

                        <a href="profile.php" class="list-group-item list-group-item-action py-3 d-flex align-items-center">
                            <i class="bi bi-person-circle text-info fs-5 me-3"></i>
                            <div>
                                <div class="fw-semibold text-dark">Profilim</div>
                                <div class="small text-muted">Şifre ve telefon güncelle</div>
                            </div>
                            <i class="bi bi-chevron-right ms-auto text-muted small"></i>
                        </a>

                    </div>
                </div>
            </div>
            
            <div class="alert alert-light border mt-4 small text-muted">
                <i class="bi bi-info-circle-fill me-1"></i>
                Başvurular genellikle <strong>24 saat</strong> içinde sonuçlanır. Sorun yaşarsan birim sorumlun ile iletişime geç.
            </div>

        </div>
    </div>

</div>

<style>
    .hover-scale { transition: transform 0.2s; }
    .hover-scale:hover { transform: translateY(-3px); }
</style>

<?php require_once '../includes/user_footer.php'; ?>
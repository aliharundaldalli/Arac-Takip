<?php
require_once '../includes/config.php';

// GÜVENLİK KONTROLÜ
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['admin'])) {
    header("Location: index.php"); // Admin değilse dashboard'a at
    exit;
}

$message = "";
$msgType = "";

// --- 1: Birim Ekle ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_unit'])) {
    $name = trim($_POST['name']);

    if (!empty($name)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO units (name) VALUES (?)");
            $stmt->execute([$name]);
            $message = "Yeni birim başarıyla oluşturuldu.";
            $msgType = "success";
        } catch (PDOException $e) {
            $message = "Bu birim adı zaten mevcut olabilir.";
            $msgType = "danger";
        }
    }
}

// --- 2: Birim Düzenle ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_unit'])) {
    $id = $_POST['unit_id'];
    $name = trim($_POST['name']);

    try {
        $stmt = $pdo->prepare("UPDATE units SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        $message = "Birim bilgileri güncellendi.";
        $msgType = "success";
    } catch (PDOException $e) {
        $message = "Güncelleme sırasında hata oluştu.";
        $msgType = "danger";
    }
}

// --- 3: Birim Sil ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    // İlişki Kontrolü: İçinde kullanıcı var mı?
    $check = $pdo->prepare("SELECT COUNT(*) FROM users WHERE unit_id = ?");
    $check->execute([$id]);
    $count = $check->fetchColumn();

    if ($count > 0) {
        $message = "Bu birime bağlı <b>$count</b> kullanıcı var. Önce kullanıcıları taşıyın veya silin.";
        $msgType = "danger";
    } else {
        $stmt = $pdo->prepare("DELETE FROM units WHERE id = ?");
        $stmt->execute([$id]);
        
        // URL temizleme ve mesaj
        header("Location: units.php?msg=deleted");
        exit;
    }
}

// Silme mesajı URL'den gelirse
if(isset($_GET['msg']) && $_GET['msg'] == 'deleted'){
    $message = "Birim başarıyla silindi.";
    $msgType = "success";
}

// Birimleri Çek
$units = $pdo->query("SELECT * FROM units ORDER BY id DESC")->fetchAll();
?>

<?php require_once '../includes/admin_header.php'; ?>

<div class="container-fluid px-4 py-4">

    <?php if ($message): ?>
        <div class="alert alert-<?= $msgType ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i> <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-building-fill text-primary me-2"></i>Birim Yönetimi
            </h3>
            <p class="text-muted mb-0">Fakülte, daire başkanlığı veya departman tanımları.</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUnitModal">
            <i class="bi bi-plus-lg me-1"></i> Yeni Birim Ekle
        </button>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">

            <div class="table-responsive">
                <table id="unitTable" class="table table-hover align-middle w-100">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="ps-3" style="width: 80px;">ID</th>
                            <th>Birim Adı</th>
                            <th>Oluşturulma Tarihi</th>
                            <th class="text-end pe-3" style="width: 150px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($units as $u): ?>
                        <tr>
                            <td class="ps-3 fw-bold text-muted">#<?= $u['id']; ?></td>
                            
                            <td>
                                <span class="fw-medium text-dark"><?= htmlspecialchars($u['name']); ?></span>
                            </td>
                            
                            <td class="text-muted small">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?= date('d.m.Y H:i', strtotime($u['created_at'] ?? 'now')); ?>
                            </td>
                            
                            <td class="text-end pe-3">
                                <div class="btn-group">
                                    <button 
                                        class="btn btn-sm btn-outline-primary edit-unit-btn"
                                        data-id="<?= $u['id']; ?>"
                                        data-name="<?= htmlspecialchars($u['name']); ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editUnitModal"
                                        title="Düzenle">
                                        <i class="bi bi-pencil"></i>
                                    </button>

                                    <a href="?delete_id=<?= $u['id']; ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Bu birimi silmek istediğinize emin misiniz?')"
                                       title="Sil">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <?php if(count($units) == 0): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-building-dash fs-1 opacity-50"></i>
                        <p class="mt-2">Henüz kayıtlı bir birim yok.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php require_once 'modals_units.php'; ?>

<?php require_once '../includes/admin_footer.php'; ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // Tabloyu Başlat
    $('#unitTable').DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json" },
        responsive: true,
        order: [[0, "desc"]] // ID'ye göre tersten sırala (En yeni en üstte)
    });

    // Düzenleme Modalına Veri Aktarımı
    $('.edit-unit-btn').click(function() {
        var id = $(this).data('id');
        var name = $(this).data('name');

        $('#edit_id').val(id);
        $('#edit_name').val(name);
    });
});
</script>
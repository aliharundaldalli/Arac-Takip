<?php
require_once '../includes/config.php';

// Güvenlik ve Yetki Kontrolü
// admin_header'da session kontrolü olsa da burada role kontrolü yapmak iyidir.
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    // Admin değilse dashboard'a at
    header("Location: index.php");
    exit;
}

$message = "";
$msgType = "";

// --- 1. KULLANICI EKLEME ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $tc = trim($_POST['tc']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $unit_id = !empty($_POST['unit_id']) ? $_POST['unit_id'] : null;

    // Varsayılan şifre
    $default_pass = password_hash("123456", PASSWORD_DEFAULT);

    try {
        // TC veya Email benzersiz olmalı (Veritabanında UNIQUE index varsa hata fırlatır)
        $stmt = $pdo->prepare("INSERT INTO users (tc_number, name, email, password, role, unit_id, must_change_password) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$tc, $name, $email, $default_pass, $role, $unit_id]);
        
        $message = "Kullanıcı başarıyla eklendi! <br><strong>Varsayılan Şifre: 123456</strong>";
        $msgType = "success";
    } catch (PDOException $e) {
        // Hata koduna göre mesaj (23000 genelde Duplicate Entry hatasıdır)
        if ($e->getCode() == 23000) {
            $message = "Bu TC Kimlik veya E-posta adresi zaten kayıtlı.";
        } else {
            $message = "Veritabanı hatası: " . $e->getMessage();
        }
        $msgType = "danger";
    }
}

// --- 2. KULLANICI GÜNCELLEME ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_user'])) {
    $id = $_POST['user_id'];
    $tc = trim($_POST['tc']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $unit_id = !empty($_POST['unit_id']) ? $_POST['unit_id'] : null;
    $new_pass = trim($_POST['password']);

    try {
        if (!empty($new_pass)) {
            // Şifre de güncellenecek
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET tc_number = ?, name = ?, email = ?, role = ?, unit_id = ?, password = ?, must_change_password = 1 WHERE id = ?");
            $stmt->execute([$tc, $name, $email, $role, $unit_id, $hashed, $id]);
            $message = "Kullanıcı bilgileri ve şifresi güncellendi. Kullanıcı bir sonraki girişte şifresini değiştirmek zorunda kalacak.";
        } else {
            // Sadece bilgiler güncellenecek
            $stmt = $pdo->prepare("UPDATE users SET tc_number = ?, name = ?, email = ?, role = ?, unit_id = ? WHERE id = ?");
            $stmt->execute([$tc, $name, $email, $role, $unit_id, $id]);
            $message = "Kullanıcı bilgileri güncellendi.";
        }
        $msgType = "success";
    } catch (PDOException $e) {
        $message = "Güncelleme başarısız: " . $e->getMessage();
        $msgType = "danger";
    }
}

// --- 3. ŞİFRE SIFIRLAMA ---
if (isset($_POST['reset_password_id'])) {
    $id = $_POST['reset_password_id'];
    $default_pass = password_hash("123456", PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("UPDATE users SET password = ?, must_change_password = 1 WHERE id = ?");
    $stmt->execute([$default_pass, $id]);

    $message = "Kullanıcının şifresi '123456' olarak sıfırlandı.";
    $msgType = "warning";
}

// --- 4. KULLANICI SİLME ---
if (isset($_GET['delete_id'])) {
    $del_id = intval($_GET['delete_id']);
    
    if ($del_id == $_SESSION['user_id']) {
        $message = "Güvenlik gereği kendi hesabınızı silemezsiniz!";
        $msgType = "danger";
    } else {
        // İlişkili araçları var mı kontrol et (Opsiyonel ama önerilir)
        // Eğer varsa önce araçları silinmeli veya user_id null yapılmalı.
        // Şimdilik direkt siliyoruz:
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$del_id]);
        
        // Silme işleminden sonra URL'i temizlemek için redirect
        header("Location: users.php?msg=deleted");
        exit;
    }
}

// URL'den gelen silindi mesajı
if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $message = "Kullanıcı sistemden silindi.";
    $msgType = "success";
}

// --- VERİLERİ ÇEK ---
$units = $pdo->query("SELECT * FROM units ORDER BY name ASC")->fetchAll();
$sql = "SELECT users.*, units.name AS unit_name FROM users 
        LEFT JOIN units ON users.unit_id = units.id
        ORDER BY users.id DESC";
$users = $pdo->query($sql)->fetchAll();
?>

<?php require_once '../includes/admin_header.php'; ?>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">

<div class="container-fluid px-4 py-4">

    <?php if ($message): ?>
        <div class="alert alert-<?= $msgType ?> alert-dismissible fade show" role="alert">
            <i class="bi bi-info-circle-fill me-2"></i> <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-people-fill text-primary me-2"></i>Kullanıcı Yönetimi
            </h3>
            <p class="text-muted mb-0">Sistemdeki kullanıcıları, rolleri ve birimleri yönetin.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="users_import.php" class="btn btn-outline-success">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel Yükle
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus-fill me-1"></i> Yeni Kullanıcı
            </button>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="userTable" class="table table-hover align-middle w-100">
                    <thead class="bg-light text-secondary">
                        <tr>
                            <th class="ps-3">TC Kimlik</th>
                            <th>Ad Soyad</th>
                            <th>E-posta</th>
                            <th>Rol</th>
                            <th>Birim</th>
                            <th class="text-end pe-3">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td class="ps-3 fw-bold font-monospace"><?= htmlspecialchars($u['tc_number']) ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                                            <i class="bi bi-person text-secondary"></i>
                                        </div>
                                        <?= htmlspecialchars($u['name']) ?>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <?php
                                        if ($u['role'] == 'admin') echo '<span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 border border-danger">Yönetici</span>';
                                        elseif ($u['role'] == 'approver') echo '<span class="badge bg-warning bg-opacity-10 text-warning px-3 py-2 border border-warning">Birim Sorumlusu</span>';
                                        else echo '<span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-2 border">Personel/Öğrenci</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php if($u['unit_name']): ?>
                                        <span class="badge bg-light text-dark border fw-normal"><?= htmlspecialchars($u['unit_name']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end pe-3">
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-primary edit-btn"
                                            data-bs-toggle="modal" data-bs-target="#editUserModal"
                                            data-id="<?= $u['id'] ?>"
                                            data-tc="<?= htmlspecialchars($u['tc_number']) ?>"
                                            data-name="<?= htmlspecialchars($u['name']) ?>"
                                            data-email="<?= htmlspecialchars($u['email']) ?>"
                                            data-role="<?= $u['role'] ?>"
                                            data-unit="<?= $u['unit_id'] ?>"
                                            title="Düzenle">
                                            <i class="bi bi-pencil"></i>
                                        </button>

                                        <form class="d-inline" method="POST" onsubmit="return confirm('<?= htmlspecialchars($u['name']) ?> kullanıcısının şifresi 123456 yapılacak. Onaylıyor musun?');">
                                            <input type="hidden" name="reset_password_id" value="<?= $u['id'] ?>">
                                            <button class="btn btn-sm btn-outline-warning" title="Şifre Sıfırla (123456)">
                                                <i class="bi bi-key"></i>
                                            </button>
                                        </form>

                                        <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                            <a href="?delete_id=<?= $u['id'] ?>" 
                                               onclick="return confirm('Bu kullanıcıyı silmek istediğine emin misin? Bu işlem geri alınamaz.')" 
                                               class="btn btn-sm btn-outline-danger" title="Sil">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<?php require_once 'modals_users.php'; ?>

<?php require_once '../includes/admin_footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    // DataTable Başlat
    $('#userTable').DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/tr.json" },
        responsive: true,
        order: [[5, 'asc']] // Varsayılan sıralama
    });

    // Düzenle Butonuna Tıklanınca Modala Veri Aktar
    $('.edit-btn').click(function() {
        var id = $(this).data('id');
        var tc = $(this).data('tc');
        var name = $(this).data('name');
        var email = $(this).data('email');
        var role = $(this).data('role');
        var unit = $(this).data('unit');

        // Modal içindeki inputlara değerleri yaz
        $('#edit_id').val(id);
        $('#edit_tc').val(tc);
        $('#edit_name').val(name);
        $('#edit_email').val(email);
        $('#edit_role').val(role);
        $('#edit_unit').val(unit);
    });
});
</script>
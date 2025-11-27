<?php
// admin/users_import.php
require_once '../includes/config.php';

// 1. GÜVENLİK
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../auth/login.php");
    exit;
}

// -----------------------------------------------------------------------
// 2. ÖRNEK CSV İNDİRME
// -----------------------------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] == 'download_sample') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=ornek_kullanici_listesi.csv');
    
    $output = fopen('php://output', 'w');
    fwrite($output, "\xEF\xBB\xBF"); // BOM (Excel Türkçe Desteği)
    
    // Varsayılan Excel formatı (Noktalı Virgül)
    fputcsv($output, array('TC Kimlik', 'Ad Soyad', 'E-Posta', 'Rol', 'Birim ID'), ';');
    fputcsv($output, array('11111111111', 'Ahmet Yılmaz', 'ahmet@mail.com', 'user', '1'), ';');
    
    fclose($output);
    exit;
}

// -----------------------------------------------------------------------
// 3. YÜKLEME VE İŞLEME (En Sağlam Yöntem)
// -----------------------------------------------------------------------
$report = [];
$successCount = 0;
$failCount = 0;
$finalMsg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['csv_file'])) {
    
    $file = $_FILES['csv_file']['tmp_name'];
    $fileSize = $_FILES['csv_file']['size'];
    $delimiterChoice = $_POST['delimiter_type'] ?? 'auto';

    if ($fileSize > 0) {
        
        $handle = fopen($file, "r");
        
        // --- ADIM A: AYIRAÇ BELİRLEME ---
        $delimiter = ';'; // Varsayılan
        
        if ($delimiterChoice == 'auto') {
            // İlk satırı oku ve say
            $firstLine = fgets($handle);
            $comma = substr_count($firstLine, ',');
            $semicolon = substr_count($firstLine, ';');
            $delimiter = ($comma > $semicolon) ? ',' : ';';
            rewind($handle); // Başa dön
        } elseif ($delimiterChoice == 'comma') {
            $delimiter = ',';
        }
        
        // --- ADIM B: BOM TEMİZLİĞİ ---
        $bom = fread($handle, 3);
        if ($bom != "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $rowNumber = 0;
        $default_pass = password_hash("123456", PASSWORD_DEFAULT);

        // --- ADIM C: MANUEL OKUMA (fgets ile) ---
        // fgetcsv yerine fgets kullanıyoruz çünkü satır sonu hatalarını engeller.
        while (($line = fgets($handle)) !== FALSE) {
            $rowNumber++;
            
            // Satırı temizle (Boşluklar ve satır sonu karakterleri)
            $line = trim($line);
            
            // Boş satırsa geç
            if (empty($line)) continue;

            // STR_GETCSV ile parçala (Bu fonksiyon çok daha kararlıdır)
            $data = str_getcsv($line, $delimiter);

            // Başlık satırı kontrolü
            $col0 = trim($data[0] ?? '');
            if ($rowNumber == 1 && !is_numeric($col0)) {
                continue; 
            }

            // SÜTUN KONTROLÜ
            // En az 3 dolu sütun olmalı
            if (count($data) < 3) {
                $failCount++;
                $report[] = "<span class='text-danger fw-bold'>Satır $rowNumber:</span> Format hatası. Sütunlar okunamadı.<br><small class='text-muted'>Veri: ".htmlspecialchars(substr($line, 0, 50))."...</small>";
                continue;
            }

            // 4. Verileri Al
            $tc      = trim($data[0] ?? '');
            $name    = trim($data[1] ?? ''); // Tırnakları otomatik temizler
            $email   = trim($data[2] ?? '');
            $role    = strtolower(trim($data[3] ?? 'user'));
            $unit_id = (int)($data[4] ?? 0);
            
            if ($unit_id == 0) $unit_id = null;

            // --- VALIDASYONLAR ---
            if (empty($tc) || empty($email) || empty($name)) {
                $failCount++;
                $report[] = "<span class='text-danger fw-bold'>Satır $rowNumber:</span> Eksik bilgi (TC, İsim veya Mail).";
                continue;
            }

            // TC 11 hane mi?
            if (strlen($tc) != 11 || !ctype_digit($tc)) {
                $failCount++;
                $report[] = "<span class='text-danger fw-bold'>Satır $rowNumber:</span> Geçersiz TC ($tc).";
                continue;
            }

            // Rol düzeltme
            if (!in_array($role, ['user', 'approver', 'admin'])) {
                $role = 'user';
            }

            // --- VERİTABANI ---
            try {
                $stmt = $pdo->prepare("INSERT INTO users (tc_number, name, email, password, role, unit_id, must_change_password) 
                                       VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute([$tc, $name, $email, $default_pass, $role, $unit_id]);
                $successCount++;
            } catch (PDOException $e) {
                $failCount++;
                if ($e->getCode() == 23000) {
                    $report[] = "<span class='text-warning fw-bold'>Satır $rowNumber:</span> Bu TC ($tc) veya E-posta zaten kayıtlı.";
                } else {
                    $report[] = "<span class='text-danger fw-bold'>Satır $rowNumber:</span> Veritabanı hatası.";
                }
            }
        }
        fclose($handle);
        
        // Sonuç Mesajı
        $alertColor = ($failCount > 0) ? 'warning' : 'success';
        $finalMsg = "<div class='alert alert-$alertColor shadow-sm d-flex align-items-center'>
                        <div class='fs-1 me-3'><i class='bi bi-check-circle-fill'></i></div>
                        <div>
                            <h5 class='alert-heading fw-bold mb-1'>İşlem Tamamlandı</h5>
                            <div>Başarıyla Eklenen: <b class='text-success'>$successCount</b></div>
                            <div>Hatalı / Atlanan: <b class='text-danger'>$failCount</b></div>
                        </div>
                     </div>";
    }
}
?>

<?php include '../includes/admin_header.php'; ?>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="bi bi-file-earmark-spreadsheet-fill text-success me-2"></i>Toplu Kullanıcı Yükleme
            </h3>
            <p class="text-muted mb-0">CSV dosyası ile sisteme hızlıca kullanıcı ekleyin.</p>
        </div>
        <a href="users.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Listeye Dön
        </a>
    </div>

    <?php if (!empty($finalMsg)) echo $finalMsg; ?>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-upload me-2 text-primary"></i>Dosya Yükle</h6>
                </div>
                <div class="card-body p-4">
                    
                    <div class="alert alert-light border border-info text-dark shadow-sm mb-4">
                        <h6 class="fw-bold text-info"><i class="bi bi-info-circle-fill me-2"></i>Format Bilgisi</h6>
                        <ul class="mb-0 small ps-3">
                            <li>Dosya <b>.csv</b> formatında olmalıdır.</li>
                            <li>Excel'de "Farklı Kaydet -> CSV UTF-8 (Virgülle Ayrılmış)" seçeneğini kullanın.</li>
                        </ul>
                    </div>

                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-bold text-secondary">1. CSV Dosyası Seçin</label>
                            <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-secondary">2. Dosya Ayıracı Nedir?</label>
                            <select name="delimiter_type" class="form-select bg-light">
                                <option value="auto">Otomatik Algıla (Önerilen)</option>
                                <option value="semicolon">Noktalı Virgül ( ; ) - Excel Standart</option>
                                <option value="comma">Virgül ( , ) - Google Sheets/Diğer</option>
                            </select>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success py-2 fw-bold shadow-sm">
                                <i class="bi bi-cloud-arrow-up me-2"></i> Yüklemeyi Başlat
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm border-0 rounded-4 h-100">
                <div class="card-header bg-white border-bottom py-3 d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold"><i class="bi bi-table me-2 text-primary"></i>Örnek Format</h6>
                    <a href="?action=download_sample" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                        <i class="bi bi-download me-1"></i> Şablonu İndir
                    </a>
                </div>
                <div class="card-body p-4">
                    
                    <p class="text-muted small">Excel sütun sırası:</p>
                    <table class="table table-bordered table-sm small text-center mb-3">
                        <thead class="table-light">
                            <tr><th>A (TC)</th><th>B (Ad Soyad)</th><th>C (Email)</th><th>D (Rol)</th><th>E (Birim)</th></tr>
                        </thead>
                        <tbody><tr><td>111...</td><td>Ali Yıl</td><td>ali@x.com</td><td>user</td><td>1</td></tr></tbody>
                    </table>

                    <hr>
                    <h6 class="fw-bold small text-secondary">Birim Kodları (ID)</h6>
                    <div class="bg-light p-3 rounded border" style="max-height: 200px; overflow-y: auto;">
                        <?php
                        $units = $pdo->query("SELECT id, name FROM units ORDER BY id ASC")->fetchAll();
                        if ($units) {
                            echo "<ul class='list-unstyled small mb-0'>";
                            foreach ($units as $u) {
                                echo "<li class='mb-1'><span class='badge bg-secondary me-2'>{$u['id']}</span> {$u['name']}</li>";
                            }
                            echo "</ul>";
                        } else { echo "<span class='text-muted small'>Kayıt yok.</span>"; }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($report)): ?>
        <div class="card shadow-sm border-0 rounded-4 border-start border-danger border-5 mt-4">
            <div class="card-header bg-white border-bottom-0 pt-3">
                <h5 class="card-title text-danger fw-bold mb-0">Hata Raporu</h5>
            </div>
            <div class="card-body pt-0">
                <div class="overflow-auto" style="max-height: 300px;">
                    <ul class="list-group list-group-flush small">
                        <?php foreach ($report as $line) echo "<li class='list-group-item text-secondary'>$line</li>"; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php include '../includes/admin_footer.php'; ?>
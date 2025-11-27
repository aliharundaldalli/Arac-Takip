<?php
// includes/config.sample.php
// Bu dosyayı config.php olarak kopyalayın ve kendi veritabanı bilgilerinizi girin.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- VERİTABANI AYARLARI ---
$host = 'localhost';
$dbname = 'arac_yonetim';
$username = 'root';
$password = ''; // Veritabanı şifreniz
$port = 3306;   // MySQL portu (MAMP için 8889 olabilir)
$charset = 'utf8mb4';

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);

} catch (\PDOException $e) {
    die("Veritabanı bağlantı hatası: " . $e->getMessage());
}

// Ayarları veritabanından çek
try {
    $stmt = $pdo->query("SELECT * FROM settings");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (\PDOException $e) {
    $settings = [
        'site_title' => 'Araç Takip Sistemi', 
        'theme_color' => '#0d6efd',
        'site_logo' => ''
    ];
}
?>

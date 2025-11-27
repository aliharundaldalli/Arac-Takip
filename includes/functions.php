<?php
// includes/functions.php

// PHPMailer sınıflarını kullanacağımızı belirtiyoruz
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Composer autoloader'ı bulup dahil edelim
// __DIR__ bu dosyanın olduğu klasördür (includes). Vendor bir üsttedir.
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

/**
 * Standart Mail Gönderme Fonksiyonu
 * * @param string $toEmail Alıcı E-postası
 * @param string $subject Konu
 * @param string $body    HTML İçerik
 * @return bool           Başarılı ise true, değilse false
 */
/**
 * Standart HTML Mail Şablonu Oluşturur
 */
function getMailTemplate($title, $content) {
    global $pdo;
    
    // Ayarları Çek
    $settings = [];
    try {
        $stmt = $pdo->query("SELECT * FROM settings");
        while ($row = $stmt->fetch()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {}

    $siteTitle = $settings['site_title'] ?? 'Araç Takip Sistemi';
    $themeColor = $settings['theme_color'] ?? '#0d6efd';
    $siteUrl = $settings['site_url'] ?? 'http://localhost';
    $logoUrl = $siteUrl . '/assets/img/' . ($settings['site_logo'] ?? '');

    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
            .email-container { max-width: 600px; margin: 40px auto; background: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
            .header { background-color: {$themeColor}; padding: 30px; text-align: center; }
            .header h1 { color: #ffffff; margin: 0; font-size: 24px; font-weight: 600; }
            .content { padding: 40px 30px; color: #333333; line-height: 1.6; }
            .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #888888; border-top: 1px solid #eeeeee; }
            .btn { display: inline-block; padding: 12px 24px; background-color: {$themeColor}; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: 600; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <h1>{$siteTitle}</h1>
            </div>
            <div class='content'>
                <h2 style='color: {$themeColor}; margin-top: 0;'>{$title}</h2>
                {$content}
            </div>
            <div class='footer'>
                &copy; " . date('Y') . " {$siteTitle}. Tüm hakları saklıdır.<br>
                Bu e-posta otomatik olarak gönderilmiştir.
            </div>
        </div>
    </body>
    </html>
    ";
}

/**
 * Standart Mail Gönderme Fonksiyonu
 */
function sendMail($toEmail, $subject, $body, $isRawHtml = false) {
    global $pdo;

    // 1. Ayarları Veritabanından Çek
    try {
        $stmt = $pdo->query("SELECT * FROM settings");
        $s = [];
        while ($row = $stmt->fetch()) {
            $s[$row['setting_key']] = $row['setting_value'];
        }
    } catch (Exception $e) {
        return false;
    }

    // 2. PHPMailer Başlat
    $mail = new PHPMailer(true);

    try {
        // Sunucu Ayarları
        $mail->isSMTP();
        $mail->Host       = $s['smtp_host'] ?? '';
        $mail->SMTPAuth   = true;
        $mail->Username   = $s['smtp_email'] ?? '';
        $mail->Password   = $s['smtp_password'] ?? '';
        $mail->CharSet    = 'UTF-8';

        if (($s['smtp_secure'] ?? 'tls') === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = 465;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = $s['smtp_port'] ?? 587;
        }

        $mail->setFrom($s['smtp_email'], $s['site_title'] ?? 'Araç Takip');
        $mail->addAddress($toEmail);

        // İçerik
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        // Eğer raw HTML değilse şablona giydir
        if (!$isRawHtml) {
            $mail->Body = getMailTemplate($subject, $body);
        } else {
            $mail->Body = $body;
        }

        $mail->send();
        return true;

    } catch (Exception $e) {
        return false;
    }
}
?>
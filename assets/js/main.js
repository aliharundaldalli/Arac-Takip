/**
 * main.js
 * Proje genelindeki ortak JavaScript işlemleri
 */

document.addEventListener('DOMContentLoaded', function() {

    // 1. Bootstrap Tooltip'leri Etkinleştir
    // (data-bs-toggle="tooltip" olan her elementte çalışır)
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // 2. Alert Mesajlarını Otomatik Gizle (5 Saniye sonra)
    // (alert-success veya alert-danger sınıfı olanlar)
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            // Bootstrap'ın alert kapatma fonksiyonunu tetikle
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000); // 5000ms = 5 saniye
    });

    // 3. Silme İşlemleri İçin Ortak Onay (Opsiyonel)
    // data-confirm="Mesajınız" attribute'u olan linklerde çalışır
    const confirmLinks = document.querySelectorAll('a[data-confirm]');
    confirmLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            if (!confirm(this.getAttribute('data-confirm'))) {
                e.preventDefault();
            }
        });
    });

    console.log('Ahd Araç Takip: Ortak JS Yüklendi.');
});
